<?php

namespace Flames\Coroutine;

use Exception;
use Flames\Collection\Arr;
use Flames\Coroutine\Timeout\Event;
use Flames\Js;
use Flames\Kernel;

class Timeout
{
    private static $delegatesData = [];
    protected array $uids = [];

    public function add($delegate, array|Arr $args = null, ?int $miliseconds = 1)
    {
        if (Kernel::MODULE === 'SERVER') {
            throw new Exception('Method only works on client.');
        }

        $uid = (count(self::$delegatesData) + 1);

        if ($miliseconds === null) {
            $miliseconds = 1;
        }

        if ($args instanceof Arr) {
            $args = $args->toArray();
        } elseif ($args === null) {
            $args = [];
        }

        self::$delegatesData[$uid] = Arr([
            'delegate' => $delegate,
            'args' => $args,
            'miliseconds' => $miliseconds
        ]);
        $this->uids[] = $uid;
    }

    public function run()
    {
        foreach ($this->uids as $uid) {
            $delegateData = self::$delegatesData[$uid];

            $window = Js::getWindow();
            $window->setTimeout(function() use ($delegateData) {
                $delegate = $delegateData->delegate;
                $args = $delegateData->args;
                while (count($args) < 16) {
                    $args[] = null;
                }

                $delegate($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9], $args[10], $args[11], $args[12], $args[13], $args[14], $args[15]);
            }, $delegateData->miliseconds);
        }
    }
}