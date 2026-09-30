<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Connections;

use McoreServices\TeamleaderSDK\Exceptions\ConfigurationException;

/**
 * One Teamleader connection's credentials.
 *
 * Every Teamleader account needs its own integration — an integration's
 * client ID and secret only work in the account it was created in — so
 * `client_id` and `client_secret` are required on every connection. Only
 * `redirect_uri` may be shared: register the same callback URL in each
 * integration and one route serves them all.
 */
final readonly class ConnectionConfig
{
    public function __construct(
        public string $name,
        public string $clientId,
        public string $clientSecret,
        public string $redirectUri,
        public ?string $expectedAccountId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $config  client_id, client_secret, redirect_uri, expected_account_id
     * @param  string|null  $fallbackRedirectUri  Used when the connection sets none
     *
     * @throws ConfigurationException When a required value is missing, naming the connection
     */
    public static function fromArray(string $name, array $config, ?string $fallbackRedirectUri = null): self
    {
        $redirectUri = $config['redirect_uri'] ?? null;
        $redirectUri = is_string($redirectUri) && $redirectUri !== '' ? $redirectUri : $fallbackRedirectUri;

        $missing = [];

        foreach (['client_id' => $config['client_id'] ?? null, 'client_secret' => $config['client_secret'] ?? null, 'redirect_uri' => $redirectUri] as $key => $value) {
            if (! is_string($value) || $value === '') {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new ConfigurationException(
                "Teamleader connection '{$name}' is missing: ".implode(', ', $missing).'. '
                .($name === 'default'
                    ? 'Set TEAMLEADER_CLIENT_ID, TEAMLEADER_CLIENT_SECRET and TEAMLEADER_REDIRECT_URI.'
                    : "Every connection needs its own integration's client_id and client_secret; "
                        .'redirect_uri may be left out to use the default connection\'s.')
            );
        }

        $expected = $config['expected_account_id'] ?? null;

        return new self(
            name: $name,
            clientId: (string) $config['client_id'],
            clientSecret: (string) $config['client_secret'],
            redirectUri: (string) $redirectUri,
            expectedAccountId: is_string($expected) && $expected !== '' ? $expected : null,
        );
    }
}
