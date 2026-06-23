<?php

namespace App\Services\Gmail;

use Google\Service\Gmail as GmailServiceApi;
use Illuminate\Support\Facades\Log;

class GmailApiService
{
    public function __construct(
        protected GmailClientFactory $clientFactory,
        protected GmailTokenStore $tokenStore
    ) {}

    public function isAuthenticated(int $userId): bool
    {
        if (! $this->tokenStore->exists($userId)) {
            return false;
        }

        $token = $this->tokenStore->get($userId);
        if (! $token) {
            return false;
        }

        try {
            $client = $this->clientFactory->makeClient($userId);

            return ! empty($client->getAccessToken()) && ! $client->isAccessTokenExpired();
        } catch (\Throwable $e) {
            Log::warning('Gmail auth check failed', ['user_id' => $userId, 'error' => $e->getMessage()]);

            // Allow offline token presence even if refresh probe fails transiently
            return isset($token['refresh_token']) || isset($token['access_token']);
        }
    }

    public function getAuthUrl(): string
    {
        return $this->clientFactory->makeClient()->createAuthUrl();
    }

    /**
     * Exchange authorization code for tokens. Returns token array including email when possible.
     *
     * @return array{token: array, email: ?string}
     */
    public function exchangeCode(string $code): array
    {
        $client = $this->clientFactory->makeClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new \RuntimeException('Google OAuth error: '.($token['error_description'] ?? $token['error']));
        }

        $client->setAccessToken($token);
        $email = null;

        try {
            $oauth2 = $this->clientFactory->makeOauth2Service($client);
            $email = $oauth2->userinfo->get()->getEmail();
            $token['email'] = $email;
        } catch (\Throwable $e) {
            Log::warning('Could not fetch Google userinfo email', ['error' => $e->getMessage()]);
        }

        return ['token' => $token, 'email' => $email];
    }

    public function storeToken(int|string $userId, array $token): void
    {
        $this->tokenStore->put($userId, $token);
    }

    public function moveToken(int|string $fromUserId, int|string $toUserId): void
    {
        $this->tokenStore->move($fromUserId, $toUserId);
    }

    public function revoke(int $userId): void
    {
        try {
            $client = $this->clientFactory->makeClient($userId);
            $accessToken = $client->getAccessToken();
            if (is_array($accessToken) && isset($accessToken['access_token'])) {
                $client->revokeToken($accessToken);
            }
        } catch (\Throwable $e) {
            Log::info('Gmail token revoke failed (continuing with local delete)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        $this->tokenStore->delete($userId);
    }

    /**
     * List message IDs matching a Gmail search query.
     *
     * @return array{messages: array<int, array{id: string}>, nextPageToken: ?string}
     */
    public function listMessages(int $userId, string $query = '', int $maxResults = 100, ?string $pageToken = null): array
    {
        $service = $this->clientFactory->makeGmailService($userId);
        $params = [
            'maxResults' => $maxResults,
            'q' => $query,
        ];
        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        $response = $service->users_messages->listUsersMessages('me', $params);
        $messages = [];
        foreach ($response->getMessages() ?? [] as $message) {
            $messages[] = ['id' => $message->getId()];
        }

        return [
            'messages' => $messages,
            'nextPageToken' => $response->getNextPageToken(),
        ];
    }

    public function getMessage(int $userId, string $messageId): GmailMessage
    {
        $service = $this->clientFactory->makeGmailService($userId);
        $message = $service->users_messages->get('me', $messageId, ['format' => 'full']);

        $wrapped = new GmailMessage($message);

        // Fetch attachment bytes that were only referenced by attachmentId
        foreach ($wrapped->getAttachments() as $index => $attachment) {
            if (($attachment['data'] ?? '') === '' && ! empty($attachment['attachmentId'])) {
                $part = $service->users_messages_attachments->get('me', $messageId, $attachment['attachmentId']);
                $data = $part->getData() ? $this->decodeBody($part->getData()) : '';
                // mutate via reflection is messy; re-build by storing externally in GmailService
            }
        }

        return $this->hydrateAttachments($service, $messageId, $wrapped);
    }

    protected function hydrateAttachments(GmailServiceApi $service, string $messageId, GmailMessage $wrapped): GmailMessage
    {
        $attachments = $wrapped->getAttachments();
        $needsHydration = false;
        foreach ($attachments as $attachment) {
            if (($attachment['data'] ?? '') === '' && ! empty($attachment['attachmentId'])) {
                $needsHydration = true;
                break;
            }
        }

        if (! $needsHydration) {
            return $wrapped;
        }

        // Re-parse from API message is simpler: fetch and decorate via new instance is enough
        // if data already present. For missing data, load bytes into a decorated subclass-less array
        // consumed only by GmailService::downloadAttachments through getAttachments().
        $message = $service->users_messages->get('me', $messageId, ['format' => 'full']);
        $hydrated = new GmailMessage($message);
        $fixed = [];
        foreach ($hydrated->getAttachments() as $attachment) {
            if (($attachment['data'] ?? '') === '' && ! empty($attachment['attachmentId'])) {
                $part = $service->users_messages_attachments->get('me', $messageId, $attachment['attachmentId']);
                $attachment['data'] = $part->getData() ? $this->decodeBody($part->getData()) : '';
                $attachment['size'] = (int) ($part->getSize() ?? strlen($attachment['data']));
            }
            $fixed[] = $attachment;
        }

        return new class($message, $fixed) extends GmailMessage
        {
            public function __construct($message, protected array $fixedAttachments)
            {
                parent::__construct($message);
            }

            public function getAttachments(): array
            {
                return $this->fixedAttachments;
            }

            public function hasAttachments(): bool
            {
                return count($this->fixedAttachments) > 0;
            }
        };
    }

    protected function decodeBody(string $data): string
    {
        $raw = strtr($data, '-_', '+/');

        return base64_decode($raw) ?: '';
    }

    public function buildSearchQuery(?string $filter, $beforeDate = null, $afterDate = null): string
    {
        $parts = [];
        if ($filter) {
            $parts[] = $filter;
        }
        if ($beforeDate) {
            $parts[] = 'before:'.$beforeDate;
        }
        if ($afterDate) {
            $parts[] = 'after:'.$afterDate;
        }

        return trim(implode(' ', $parts));
    }
}
