<?php
declare(strict_types=1);


namespace Flames\Controller;

use Flames\Collection\Arr;

/**
 * @internal
 * Description for the class
 * @property string $plain
 * @property Arr|null $data
 * @property int $code
 * @property Arr $headers
 */
class Response
{
    protected string|null $plain;
    protected Arr|null $data;
    protected int $code;
    protected Arr|null $headers;
    private string|null $output = null;

    public function __construct(string|null $plain, Arr|null $data = null, int $code = 200, Arr|null $headers = null)
    {
        $this->plain   = $plain;
        $this->data    = $data;
        $this->code    = $code;
        $this->headers = $headers;
    }

    public function __get(string $key) : mixed
    {
        $key = strtolower((string)$key);

        if ($key === 'plain') {
            return $this->plain;
        } elseif ($key === 'data') {
            return $this->data;
        } elseif ($key === 'code') {
            return $this->code;
        } elseif ($key === 'headers') {
            return $this->headers;
        } elseif ($key === 'output') {
            return $this->output();
        }

        return null;
    }

    protected function output()
    {
        if ($this->output !== null) {
            return $this->output;
        }

        if ($this->plain !== null) {
            $this->output = $this->plain;
        }
        elseif ($this->data !== null) {
            $data = (array)$this->data;

            // TODO: support yaml/xml
            $this->output = json_encode($data);
            if ($this->headers === null) {
                $this->headers = Arr();
            }
            $this->headers['Content-Type'] = 'application/json';
            return $this->output;
        }

        return $this->output;
    }
}