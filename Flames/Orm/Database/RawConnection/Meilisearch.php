<?php

namespace Flames\Orm\Database\RawConnection;

use Flames\Http;

/**
 * @internal
 */
class Meilisearch
{
    protected $config = null;
    protected $client = null;

    public function __construct(string $dsn, string $masterKey, $config = null)
    {
        $this->config = $config;

        $this->client = new Http\Client([
            'allow_redirects' => true,
            'base_uri' => $dsn,
            'headers' => [
                'X-MEILI-API-KEY' => $masterKey,
                'Authorization' => ('Bearer ' . $masterKey)
            ]
        ]);
    }

    public function getConfig() { return $this->config; }

    public function getClient()
    {
        return $this->client;
    }
}