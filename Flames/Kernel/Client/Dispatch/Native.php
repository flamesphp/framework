<?php

namespace Flames\Kernel\Client\Dispatch;

use Flames\Js;
use Flames\Kernel\Client\Error;

/**
 * @internal
 */
class Native
{
    protected static int $messageId = -1;
    protected static int $currentMessageId = 0;

    protected static array $messages = [];

    public static function register(): void
    {
        $window = Js::getWindow();
        $window->Flames->Internal->nativeBridgeRequest = function() {
            return self::onBridgeRequest();
        };
        $window->Flames->Internal->nativeBridgeMessage = function(int $id, string $data = null) {
            self::onBridgeMessage($id, $data);
        };
    }

    public static function add(string $action, array $params = null, \Closure $delegate = null): void
    {
        self::$messageId++;
        self::$messages[self::$messageId] = ['id' => self::$messageId, 'action' => $action, 'params' => $params, 'delegate' => $delegate];
    }

    protected static function onBridgeRequest(): mixed
    {
        if (isset(self::$messages[self::$currentMessageId]) === false) {
            return null;
        }

        $message = self::$messages[self::$currentMessageId];
        $messagePack = [
            'id' => $message['id'],
            'action' => $message['action'],
            'params' => $message['params']
        ];

        self::$currentMessageId++;

        $messagePack = base64_encode(json_encode($messagePack));
        return $messagePack;
    }

    protected static function onBridgeMessage(int $id, string $data = null): void
    {
        $data = json_decode(base64_decode($data));
        if (isset(self::$messages[$id]) === false) {
            return;
        }

        $message = self::$messages[$id];
        if ($message['delegate'] !== null) {
            try {
                $message['delegate']($data);
            } catch (\Exception|\Error $e) {
                Error::handler($e);
            }
        }

        unset(self::$messages[$id]);
    }
}