<?php
declare(strict_types=1);


namespace Flames\Framework\Controller;

use Flames\Collection\Arr;
use Flames\Framework\Connection;

final class RequestData
{
    public function __construct(
        public string $method,
        public ?string $url,
        public Arr $queries,
        public Arr $uries,
        public Arr $multipart,
        public Arr $urlEncoded,
        public ?Arr $json,
        public Arr $request,
        public Arr $headers,
        public ?string $host,
        public ?int $port,
        public ?string $ip,
        public ?string $command,
        public ?Arr $data = null,
        public Arr $params = new Arr(),
    ) {
    }

    public static function getBase(): self
    {
        return new self(
            'GET',
            '/',
            new Arr(),
            new Arr(),
            new Arr(),
            new Arr(),
            null,
            new Arr(),
            new Arr(),
            $_SERVER['SERVER_NAME'] ?? null,
            isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : null,
            Connection::getIp(),
            null,
        );
    }
}
