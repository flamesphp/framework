<?php
declare(strict_types=1);


namespace Flames\Framework\Controller;

use Flames\Framework\Connection;

final class RequestData
{
    public string $method;
    public ?string $url;
    public ?string $command;
    public array $queries;
    public array $uries;
    public array $multipart;
    public array $urlEncoded;
    public ?array $json;
    public array $request;
    public array $headers;
    public ?string $host;
    public ?int $port;
    public ?string $ip;
    public ?array $data;

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
    public function __construct(
        string $method,
        ?string $url,
        array $queries,
        array $uries,
        array $multipart,
        array $urlEncoded,
        ?array $json,
        array $request,
        array $headers,
        ?string $host,
        ?int $port,
        ?string $ip,
        ?string $command,
        ?array $data = null,
    ) {
        $this->method = $method;
        $this->url = $url;
        $this->queries = $queries;
        $this->uries = $uries;
        $this->multipart = $multipart;
        $this->urlEncoded = $urlEncoded;
        $this->json = $json;
        $this->request = $request;
        $this->headers = $headers;
        $this->host = $host;
        $this->port = $port;
        $this->ip = $ip;
        $this->command = $command;
        $this->data = $data;
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
