<?php
declare(strict_types=1);


namespace Flames;

use Exception;
use App\App;
use Flames\Js\Window;
use Flames\Surface\Surface\Js\Bridge;

/**
 * Class Js
 *
 * The JS class provides utility methods for executing JavaScript code
 * on the client-side.
 */
class Js
{
    protected static $window = null;

    /**
     * Evaluate JavaScript code on the client-side.
     *
     * @param string $code The JavaScript code to be evaluated.
     *
     * @return mixed The result of the evaluation.
     *
     * @throws Exception If the method is called on the server-side.
     */
    public static function eval(string $code) : mixed
    {
        if (App::MODULE === 'SERVER') {
            throw new Exception('Method only works on client.');
        } else {
            return self::getWindow()->eval($code);
        }
    }

    /**
     * Returns the window object.
     *
     * @return Window|null The window object if it exists, otherwise null.
     *
     * @throws Exception If the method is called on the server module.
     */
    public static function getWindow() : Window|Bridge|null
    {
        if (self::$window !== null) {
            return self::$window;
        }

        if (App::MODULE === 'SERVER') {
            throw new Exception('Method only works on client.');
        } else {
            self::$window ??= new Bridge();
            return self::$window;
        }
    }
}
