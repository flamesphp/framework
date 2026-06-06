<?php
declare(strict_types=1);


namespace Flames\Crypto\Hashid;

class Gmp
{
    public function add($a, $b)
    {
        return gmp_add($a, $b);
    }

    public function multiply($a, $b)
    {
        return gmp_mul($a, $b);
    }

    public function divide($a, $b)
    {
        return gmp_div_q($a, $b);
    }

    public function mod($n, $d)
    {
        return gmp_mod($n, $d);
    }

    public function greaterThan($a, $b)
    {
        return gmp_cmp($a, $b) > 0;
    }

    public function intval($a)
    {
        return gmp_intval($a);
    }

    public function strval($a)
    {
        return gmp_strval($a);
    }

    public function get($a)
    {
        return gmp_init($a);
    }
}