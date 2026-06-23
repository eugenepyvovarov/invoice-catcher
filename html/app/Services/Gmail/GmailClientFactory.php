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
        $client = new GoogleClient;
        $client->setApplicationName(config('app.name', 'Mail Catcher'));
        $client->setClientId(config('gmail.client_id'));
        $client->setClientSecret(config('gmail.client_secret'));
        $client->setRedirectUri(config('gmail.redirect_url'));
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
}
