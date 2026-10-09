<?php
declare(strict_types=1);


namespace Flames\Framework\Controller;

use Flames\Framework\Connection;

final class RequestData
{
    /**
     * @param array<string, mixed> $queries
     * @param array<string, mixed> $uries
     * @param array<string, mixed> $multipart
     * @param array<string, mixed> $urlEncoded
     * @param array<string, mixed>|null $json
     * @param array<string, mixed> $request
     * @param array<string, mixed> $headers
     * @param array<string, mixed>|null $data
     */
    public function __construct(public string $method, public ?string $url, public array $queries, public array $uries, public array $multipart, public array $urlEncoded, public ?array $json, public array $request, public array $headers, public ?string $host, public ?int $port, public ?string $ip, public ?string $command, public ?array $data = null)
    {
    }

    public static function getBase(): self
    {
        return new self(
            'GET',
            '/',
            [],
            [],
            [],
            [],
            null,
            [],
            [],
            $_SERVER['SERVER_NAME'] ?? null,
            isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : null,
            Connection::getIp(),
            null,
        );
    }
}
