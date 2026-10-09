<?php
declare(strict_types=1);


namespace Flames\Event\Native;


class Shell
{
    public function __construct(protected ?string $data = null, protected bool $success = true)
    {
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