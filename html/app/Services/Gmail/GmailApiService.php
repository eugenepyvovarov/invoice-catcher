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
        if (! $token || (empty($token['access_token']) && empty($token['refresh_token']))) {
            return false;
        }

        try {
            $client = $this->clientFactory->makeClient($userId);
            $accessToken = $client->getAccessToken();

            if (empty($accessToken)) {
                return false;
            }

            // Token may have been refreshed in makeClient(); treat stored/usable token as authenticated.
            if (! $client->isAccessTokenExpired()) {
                return true;
            }

            return ! empty($client->getRefreshToken()) || ! empty($token['refresh_token']);
        } catch (\Throwable $e) {
            Log::warning('Gmail auth check failed', ['user_id' => $userId, 'error' => $e->getMessage()]);

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

        // Preserve refresh_token if Google only returns it on first consent.
        $client->setAccessToken($token);
        $email = $this->resolveAccountEmail($client, $token);
        if ($email) {
            $token['email'] = $email;
        }

        return ['token' => $token, 'email' => $email];
    }

    /**
     * Resolve the signed-in account email via OAuth2 userinfo or Gmail profile.
     */
    protected function resolveAccountEmail(\Google\Client $client, array $token): ?string
    {
        if (! empty($token['email']) && filter_var($token['email'], FILTER_VALIDATE_EMAIL)) {
            return $token['email'];
        }

        // id_token (openid scope) often includes email without an extra API call
        if (! empty($token['id_token'])) {
            try {
                $parts = explode('.', $token['id_token']);
                if (count($parts) >= 2) {
                    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')) ?: '', true);
                    if (is_array($payload) && ! empty($payload['email'])) {
                        return $payload['email'];
                    }
                }
            } catch (\Throwable $e) {
                Log::debug('Could not parse id_token email', ['error' => $e->getMessage()]);
            }
        }

        try {
            $oauth2 = $this->clientFactory->makeOauth2Service($client);
            $email = $oauth2->userinfo->get()->getEmail();
            if ($email) {
                return $email;
            }
        } catch (\Throwable $e) {
            Log::warning('Could not fetch Google userinfo email', ['error' => $e->getMessage()]);
        }

        // Works with gmail.readonly without userinfo scopes
        try {
            $gmail = new GmailServiceApi($client);
            $profile = $gmail->users->getProfile('me');
            $email = $profile->getEmailAddress();
            if ($email) {
                return $email;
            }
        } catch (\Throwable $e) {
            Log::warning('Could not fetch Gmail profile email', ['error' => $e->getMessage()]);
        }

        return null;
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
