<?php

namespace Flames\Framework;

/**
 * @internal
 */
class PersistentData
{
    public static ?\WeakMap $weakMap = null;
}

PersistentData::$weakMap = new \WeakMap();