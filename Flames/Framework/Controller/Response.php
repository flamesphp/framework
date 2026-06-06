<?php
declare(strict_types=1);


namespace Flames\Framework\Controller;

use Flames\Collection\Arr;

class Response
{
    protected mixed $data;
    protected int $statusCode;
    protected string $contentType;
    protected ?string $output = null;

    public function __construct(
        mixed $data = null,
        int $statusCode = 200,
        ?string $contentType = null
    ) {
        $this->data        = $data;
        $this->statusCode  = $statusCode;
        $this->contentType = $contentType ?? self::resolveContentType($data);
    }

    public static function from(mixed $result): self
    {
        if ($result instanceof self) {
            return $result;
        }

        if (is_string($result) || $result instanceof Arr || is_array($result) || is_object($result) || $result === null) {
            return new self($result);
        }

        return new self((string) $result);
    }

    protected static function resolveContentType(mixed $data): string
    {
        if ($data instanceof Arr || is_array($data) || is_object($data)) {
            return 'application/json';
        }

        return 'text/html';
    }

    public function getOutput(): string
    {
        if ($this->output !== null) {
            return $this->output;
        }

        if ($this->data === null) {
            $this->output = '';
            return $this->output;
        }

        if (is_string($this->data)) {
            $this->output = $this->data;
            return $this->output;
        }

        if ($this->data instanceof Arr) {
            $this->output = json_encode($this->data->toArray(), JSON_UNESCAPED_UNICODE);
            return $this->output;
        }

        if (is_array($this->data)) {
            $this->output = json_encode($this->data, JSON_UNESCAPED_UNICODE);
            return $this->output;
        }

        $this->output = json_encode($this->data, JSON_UNESCAPED_UNICODE);

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
            'output'      => $this->getOutput(),
            'code'        => $this->statusCode,
            'statuscode'  => $this->statusCode,
            'contenttype' => $this->contentType,
            'data'        => $this->data,
            default       => null,
        };
    }
}
