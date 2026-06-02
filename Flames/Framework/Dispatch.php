<?php

namespace Flames\Framework;

use Flames\Env\Env;
use Flames\Forge\Cli;
use Flames\Errors;
use Flames\Required;

/**
 * @internal
 */
class Dispatch
{

    public static function run()
    {
        if (Cli::isCli()) {
            return self::cli();
        }

        return self::dispatch();
    }

    protected static function cli()
    {
        $system = new \Flames\Forge\Cli\System();
        return $system->run();
    }

    public static function dispatch()
    {
        // LOGICA DO REQUEST AQUI
        echo @$_SERVER['REQUEST_URI'];
        echo 'teste 00017';
        return;
    }

}
