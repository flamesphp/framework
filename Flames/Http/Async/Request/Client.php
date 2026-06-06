<?php
declare(strict_types=1);


namespace Flames\Http\Async\Request;

use Flames\Collection\Arr;

/**
 * @internal
 */
class Client
{
    private string $method;
    private string $url;
    private array $header;

    public function __construct(string $method = 'GET', string $url = '/', Arr|array|null $headers = [])
    {
        $this->method = strtoupper($method);
        $this->url    = $url;
        if ($headers === null) {
            $headers = [];
        }
        $this->header = (array)$headers;
        $this->hookClientHeader();
    }

    protected function hookClientHeader()
    {
        if (isset($this->header['X-Flames-Request']) === true) {
            return;
        }
        if (isset($this->header['x-flames-request']) === true) {
            return;
        }
        $this->header['X-Flames-Request'] = 'client';;
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function getUri() : string
    {
        return $this->url;
    }

    public function getHeaders() : array
    {
        return $this->header;
    }
}