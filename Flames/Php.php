<?php
declare(strict_types=1);


namespace Flames;

/**
 * PHP class provides a utility method for evaluating arbitrary PHP code.
 */
class Php
{
    /**
     * Evaluates the given code and returns the result.
     *
     * @param string $code The code to be evaluated.
     *
     * @return mixed The result of the evaluated code.
     */
    public static function eval(string $code) : mixed
    {
        return eval($code);
    }
}