<?php

namespace Flames\Client;

use Flames\Js;
use Flames\Kernel\Client\Dispatch\Native as DispatchNative;

class Native
{
    protected static object|null $info = null;

    public static function close(): void
    { DispatchNative::add('exit'); }

    public static function eval(string $code): void
    {
        $window = Js::getWindow();
        $appNativeKey = $window->Flames->Internal->appNativeKey;

        DispatchNative::add('eval', ['appNativeKey' => $appNativeKey, 'code' => base64_encode($code)], function ($data) {
            if (isset($data->error) === true) {
                $window  = Js::getWindow();
                $window->console->error($data->message);
            }
        });
    }
}