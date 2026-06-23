<?php

namespace App\Services\Gmail;

use Google\Client as GoogleClient;
use Google\Service\Gmail as GmailServiceApi;
use Google\Service\Oauth2 as Oauth2Service;

class GmailClientFactory
{
    public function __construct(
        protected GmailTokenStore $tokenStore
    ) {}

    public function makeClient(?int $userId = null): GoogleClient
    {
        $clientId = trim((string) config('gmail.client_id', ''));
        $clientSecret = trim((string) config('gmail.client_secret', ''));

        if ($clientId === '' || $clientSecret === '') {
            throw new \InvalidArgumentException(
                'Google OAuth is not configured. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in html/.env '.
                '(from Google Cloud Console → APIs & Services → Credentials → OAuth 2.0 Client ID), '.
                'then run: php artisan config:clear'
            );
        }

        $client = new GoogleClient;
        $client->setApplicationName(config('app.name', 'Mail Catcher'));
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($this->redirectUri());
        $client->setAccessType(config('gmail.access_type', 'offline'));
        $client->setPrompt(config('gmail.prompt', 'consent'));
        $client->setIncludeGrantedScopes(true);

        $scopes = array_values(array_unique(array_merge(
            config('gmail.scopes', []),
            config('gmail.additional_scopes', [])
        )));
        $client->setScopes($scopes);

        if ($userId !== null) {
            $token = $this->tokenStore->get($userId);
            if ($token) {
                $client->setAccessToken($token);
                if ($client->isAccessTokenExpired()) {
                    $refreshToken = $client->getRefreshToken();
                    if ($refreshToken) {
                        $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                        if (! isset($newToken['error'])) {
                            $merged = array_merge($token, $newToken);
                            if (! isset($merged['refresh_token']) && $refreshToken) {
                                $merged['refresh_token'] = $refreshToken;
                            }
                            $this->tokenStore->put($userId, $merged);
                            $client->setAccessToken($merged);
                        }
                    }
                }
            }
        }

        return $client;
    }

    public function makeGmailService(int $userId): GmailServiceApi
    {
        return new GmailServiceApi($this->makeClient($userId));
    }

    public function makeOauth2Service(GoogleClient $client): Oauth2Service
    {
        return new Oauth2Service($client);
    }

    /**
     * Google OAuth requires an absolute redirect URI (scheme + host + path).
     */
    public function redirectUri(): string
    {
        $configured = trim((string) config('gmail.redirect_url', ''));

        if ($configured !== '' && preg_match('#^https?://#i', $configured)) {
            return $configured;
        }

        $path = $configured !== '' ? $configured : '/oauth/gmail/callback';
        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        return rtrim((string) config('app.url'), '/').$path;
    }
}
