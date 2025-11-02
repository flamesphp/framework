<?php

namespace Flames\Kernel\Client\Dispatch;

use Flames\Element\Shadow;
use Flames\Js;
use Flames\Kernel\Client\Virtual;

/**
 * @internal
 */
final class Tag
{
    protected static $tags = [];

    public static function run(string $tagUid, int $shadowId)
    {
        if (isset(self::$tags[$tagUid]) === false) {
            self::$tags[$tagUid] = [];
        }

        $tagClass = Virtual::getTagClass($tagUid);
        if ($tagClass === null) {
            return;
        }

        Virtual::load($tagClass);

        $window = Js::getWindow();
        $shadowNative = ($window->Flames->Internal->tags->{$tagUid}->shadows->{$shadowId});
        self::$tags[$tagUid][$shadowId] = new $tagClass($shadowNative);
    }

    public static function render(string $tagUid, int $shadowId)
    {
        if (isset(self::$tags[$tagUid]) === false) {
            return;
        }

        if (isset(self::$tags[$tagUid][$shadowId]) === false) {
            return;
        }

        self::$tags[$tagUid][$shadowId]->onRender();
    }
}