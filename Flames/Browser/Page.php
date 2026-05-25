<?php

namespace Flames\Browser;

use App\Client\Component\Loading;
use Flames\Coroutine;
use Flames\Header;
use Flames\Js;
use Flames\Kernel;
use Flames\Http;

/**
 * @internal
 */
class Page
{
    public static function load(string $uri, $delegate = null, $fromHandler = false): mixed
    {
        if (Kernel::MODULE === 'SERVER') {
            return Header::redirect($uri);
        }

        if (class_exists('\\App\\Client\\Event\\Page') === true) {
            $page = new \App\Client\Event\Page();
            try {
                $uri = $page->onPreLoad($uri);
            } catch (\Exception|\Error $_) {}
        }

        $client = new Http\Client();
        $request = new Http\Async\Request('GET', $uri, ['Content-Type' => 'application/json', 'X-Flames-Request' => 'async']);

        $client->sendAsync($request)->then(function(Http\Async\Response $response) use ($uri, $delegate, $fromHandler) {
            $data = $response->getBody();

            if ($response->getStatusCode() !== 200) {
                Js::getWindow()->location = $uri;
                return null;
            }

            self::processPage($uri, $data, $fromHandler, $delegate);
            return null;
        });

        return null;
    }

    protected static function processPage($uri, $html, $fromHandler = false, $delegate = null)
    {
        if (class_exists('\\App\\Client\\Event\\Page') === true) {
            $page = new \App\Client\Event\Page();
            try {
                $html = $page->onLoad($html);
            } catch (\Exception|\Error $_) {}
        }

        if ($fromHandler === false) {
            $window = Js::getWindow();
            $currentUri = ($window->location->pathname . $window->location->search);
            if ($currentUri !== $uri) {
                $window->history->pushState('change', 'Title', $uri);
            }
            \Flames\Kernel\Client\Dispatch::injectUri($uri);
        }

        $head = null;
        $headPos = strpos($html, '<head');
        if ($headPos !== false) {
            $_head = substr($html, $headPos);
            $headClosePos = strpos($_head, '</head>');
            if ($headClosePos !== false) {
                $head = substr($_head, 0, $headClosePos + 7);
            }
        }

        $body = null;
        $bodyClasses = null;
        $bodyPos = strpos($html, '<body');
        if ($bodyPos !== false) {
            $_body = substr($html, $bodyPos);
            $bodyClosePos = strpos($_body, '</body>');
            if ($bodyClosePos !== false) {
                $_body = substr($_body, 0, $bodyClosePos);

                $bodyHeadClosePos = strpos($_body, '>');
                if ($bodyHeadClosePos !== null) {
                    $_bodyHead = substr($_body, 0, $bodyHeadClosePos + 1);
                    $body = substr($_body, $bodyHeadClosePos + 1);

                    $classPos = strpos($_bodyHead, 'class="');
                    if ($classPos !== null) {
                        $_class = substr($_bodyHead, $classPos + 7);
                        $classClosePos = strpos($_class, '"');
                        if ($classClosePos !== null) {
                            $bodyClasses = substr($_class, 0, $classClosePos);
                        }
                    }
                }
            }
        }

//        $currentBody = Js::getWindow()->document->body->innerHTML;
        preg_match_all('#<script(.*?)<\/script>#is', $body, $matches);
        $scripts = $matches[0];
        foreach ($scripts as $script) {
//            if (str_contains($currentBody, $script) === true) {
                $body = str_replace($script, '', $body);
//            }
        }

        $flamesData = null;
        $flamesPos = strpos($body, '<flames hidden>');
        if ($flamesPos !== false) {
            $_flamesData = substr($body, $flamesPos + 15);
            $body = substr($body, 0, $flamesPos);
            $flamesClosePos = strpos($_flamesData, '<');
            if ($flamesClosePos !== false) {
                $flamesData = substr($_flamesData, 0, $flamesClosePos);
            }
        }

        self::swapHtml($head, $body);
        self::dispatchEvents($uri, $flamesData, $delegate);
    }

    protected static function swapHtml($head, $body)
    {
        // TODO: change header styles
//        Js::eval("
//            var head = document.querySelector('head');
//            if (head !== null) {
//                var removeCount = 0;
//                do {
//                    removeCount = 0;
//                    for (var child of head.children) {
//                        if (!(child.tagName === 'STYLE' || child.tagName === 'LINK')) {
//                            child.remove();
//                            removeCount++;
//                        }
//                    }
//                } while (removeCount > 0);
//            }
//        ");

        Js::eval("
            var body = document.querySelector('body');
            if (body !== null) {
                var removeCount = 0;
                do {
                    removeCount = 0;
                    for (var child of body.children) {
                        if (!(child.tagName === 'FLAMES' || child.tagName === 'SCRIPT')) {
                            if (child.getAttribute(Flames.Internal.char + 'destroy') !== 'false') {
                                child.remove();
                                removeCount++;
                            }
                        }
                    }
                } while (removeCount > 0);
            }
        ");
//        Js::eval("
//            var html = document.querySelector('html');
//            html.insertAdjacentHTML('afterbegin', decodeURIComponent('" . rawurlencode($head) . "'))
//        ");
//
        Js::eval("
            var body = document.querySelector('body');
            body.insertAdjacentHTML('afterbegin', decodeURIComponent('" . rawurlencode($body) . "'))
        ");
    }

    protected static function dispatchEvents($uri, $flamesData, $delegate = null)
    {
        if ($flamesData !== null) {
            Kernel::__injectData($flamesData);
        }

        if (class_exists('\\App\\Client\\Event\\Page') === true) {
            $page = new \App\Client\Event\Page();
            try {
                $page->onPostLoad();
            } catch (\Exception|\Error $_) {}
        }

        if (self::$currentAsyncId === null) {
            self::$currentAsyncId = 0;
        }
        self::$currentAsyncId++;
        self::$currentLoadedId = self::$currentAsyncId;

        // Secure load by two ways
        Js::eval('window.setTimeout(function() { Flames.Internal.evalBase64(btoa(\'\\\\Flames\\\\Browser\\\\Page::runAsync();\')); }, 5);');
        Js::getWindow()->setTimeout(function() {
            self::runAsync();
        }, 5);
    }

    protected static $currentAsyncId = null;
    protected static $currentLoadedId = null;

    // Grant not loading two pages at same time
    public static function runAsync()
    {
        if (self::$currentLoadedId !== self::$currentAsyncId) {
            return;
        }

        self::$currentLoadedId = null;
        \Flames\Kernel\Client\Dispatch::runAsync();
    }
}