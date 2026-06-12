<?php
declare(strict_types=1);


namespace Flames\Framework\Controller;

use Flames\Collection\Arr;

final class Response
{
    private const int JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    public readonly mixed $data;
    public readonly string $output;
    public readonly int $statusCode;
    public readonly string $contentType;

    public function __construct(
        mixed $data = null,
        int $statusCode = 200,
        ?string $contentType = null,
    ) {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->contentType = $contentType ?? self::resolveContentType($data);
        $this->output = self::encode($data);
    }

    public static function from(mixed $result): self
    {
        return $result instanceof self ? $result : new self($result);
    }

    public function getViewData(): array|Arr|null
    {
        if ($this->data instanceof Arr) {
            return $this->data;
        }

        if (is_array($this->data)) {
            return $this->data;
        }

        return null;
    }

    private static function resolveContentType(mixed $data): string
    {
        return match (true) {
            is_array($data), $data instanceof Arr, is_object($data) => 'application/json',
            default => 'text/html',
        };
    }

    private static function encode(mixed $data): string
    {
        return match (true) {
            $data === null => '',
            is_string($data) => $data,
            $data instanceof Arr => (string) json_encode($data->toArray(), self::JSON_FLAGS),
            is_array($data), is_object($data) => (string) json_encode($data, self::JSON_FLAGS),
            default => (string) $data,
        };
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function __get(string $key): mixed
    {
        return match (strtolower($key)) {
            'output' => $this->output,
            'code', 'statuscode' => $this->statusCode,
            'contenttype', 'content-type' => $this->contentType,
            default => null,
        };
    }
}
