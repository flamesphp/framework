<?php

namespace Flames\Event\Native;


class Shell
{
    protected ?string $data = null;
    protected bool $success = true;

    public function __construct(string $data = null, bool $success = true)
    {
        $this->data = $data;
        $this->success = $success;
    }

    public function __get(string $key) : mixed
    {
        $key = strtolower((string)$key);

        if ($key === 'data') {
            return $this->data;
        }
        elseif ($key === 'success') {
            return $this->success;
        }

        return null;
    }
}