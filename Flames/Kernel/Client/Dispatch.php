<?php

namespace Flames\Kernel\Client;

use Flames\Client\Browser\DevTools;
use Flames\Connection;
use Flames\Coroutine;
use Flames\Element;
use Flames\Environment;
use Flames\Event\Element\Click;
use Flames\Event\Element\Change;
use Flames\Event\Element\Input;
use Flames\Header;
use Flames\Js;
use Flames\Kernel;
use Flames\Kernel\Client\Dispatch\Native;
use Flames\Kernel\Client\Error;
use Flames\Kernel\Client\Service\Keyboard;
use Flames\Kernel\Route;
use Flames\Router;

/**
 * @internal
 */
final class Dispatch
{
    protected static $instances = null;
    protected static $currentLoadId = 0;

    public static function run() : void
    {
        self::runUriHandler();
        self::runAsync(true);
    }

    public static function runAsync($firstLoad = false): void
    {
        try {
            self::clean();
            self::setup($firstLoad);
        } catch (\Exception|\Error $e) {
            Error::handler($e);
        }
    }

    protected static function clean()
    {
        if (self::$instances !== null) {
            foreach (self::$instances as &$instance) {
                unset($instance);
            }
        }
        self::$instances = [];
    }

    protected static function setup($firstLoad = false) : void
    {
        self::dispatchDevTools();
        self::simulateGlobals();
        self::setDate();
        self::dispatchNativeBuildHooks();
        self::dispatchHooks();
        self::dispatchEvents($firstLoad);
        self::dispatchNativeServices();
    }

    protected static function simulateGlobals() : void
    {
        $location = Js::getWindow()->location;
        $origin = $location->origin;
        $_SERVER['REQUEST_URI'] = explode('#', substr($location->href, strlen($origin)))[0];
    }

    protected static function setDate(): void
    {
        $timezone = Js::getWindow()->Flames->Internal->dateTimeZone;

        if ($timezone !== null && $timezone !== '') {
            \date_default_timezone_set($timezone);
            return;
        }
        \date_default_timezone_set('UTC');
    }

    protected static function dispatchHooks()
    {
        $window = Js::getWindow();
        $elements = Element::queryAll('*');
        foreach ($elements as $element) {
            $clickUid = $element->getAttribute('@click');
            if ($clickUid !== null) {
                foreach ($window->Flames->Internal->eventTriggers as $eventTrigger) {
                    if ($clickUid === $eventTrigger->uid && $eventTrigger->type === 'click') {
                        $element->removeAttribute('@click');
                        $element->setAttribute($window->Flames->Internal->char . 'click', $clickUid);
                        $element->event->click(function($event) use ($eventTrigger) {
                            try {
                                $instance = self::getInstance($eventTrigger->class);
                                $instance->{$eventTrigger->name}($event);
                            } catch (\Exception|\Error $e) {
                                Error::handler($e);
                            }
                        });
                        break;
                    }
                }
            }

            $changeUid = $element->getAttribute('@change');
            if ($changeUid !== null) {
                foreach ($window->Flames->Internal->eventTriggers as $eventTrigger) {
                    if ($changeUid === $eventTrigger->uid && $eventTrigger->type === 'change') {
                        $element->removeAttribute('@change');
                        $element->setAttribute($window->Flames->Internal->char . 'change', $changeUid);
                        $element->event->change(function($event) use ($eventTrigger) {
                            try {
                                $instance = self::getInstance($eventTrigger->class);
                                $instance->{$eventTrigger->name}($event);
                            } catch (\Exception|\Error $e) {
                                Error::handler($e);
                            }
                        });
                        break;
                    }
                }
            }

            $inputUid = $element->getAttribute('@input');
            if ($inputUid !== null) {
                foreach ($window->Flames->Internal->eventTriggers as $eventTrigger) {
                    if ($inputUid === $eventTrigger->uid && $eventTrigger->type === 'input') {
                        $element->removeAttribute('@input');
                        $element->setAttribute($window->Flames->Internal->char . 'input', $inputUid);
                        $element->event->input(function($event) use ($eventTrigger) {
                            try {
                                $instance = self::getInstance($eventTrigger->class);
                                $instance->{$eventTrigger->name}($event);
                            } catch (\Exception|\Error $e) {
                                Error::handler($e);
                            }
                        });
                        break;
                    }
                }
            }

            $destroy = $element->getAttribute('@destroy');
            if ($destroy === 'false') {
                $element->removeAttribute('@destroy');
                $element->setAttribute($window->Flames->Internal->char . 'destroy', 'false');
            }
        }
    }

    protected static function dispatchEvents($firstLoad) : bool
    {
        // Memory Overflow Bugfix
        if ($firstLoad === false) {
            $currentLoadId = self::$currentLoadId;
            Js::getWindow()->setTimeout(function() use($currentLoadId) {
                if (self::$currentLoadId <= $currentLoadId) {
                    $location = Js::getWindow()->location;
                    $origin = $location->origin;
                    $uri = explode('#', substr($location->href, strlen($origin)))[0];
                    \Flames\Browser\Page::load($uri, null, true);
                }
            }, 150);
        }

        \Flames\Kernel::__injector();

        try {
            self::dispatchNativeBuild();

            if (class_exists('\\App\\Client\\Event\\Ready') === true) {
                $ready = new \App\Client\Event\Ready();
                $ready->onReady();
            }

            if (class_exists('\\App\\Client\\Event\\Route') === true) {
                $route = new \App\Client\Event\Route();
                $router = $route->onRoute(new Router());

                if ($router !== null) {
                    $match = $router->getMatch();
                    if ($match === null) {
                        self::$currentLoadId++;
                        return false;
                    }

                    $dispatchRoute = self::dispatchRoute($match, $route);
                    self::$currentLoadId++;
                    return $dispatchRoute;
                }
            }

            self::$currentLoadId++;
            return false;
        }
        catch (\Exception|\Error $e) {
            Error::handler($e);
        }

        return false;
    }

    protected static function dispatchRoute($routeData, $route) : bool
    {
        $requestData = Route::mountRequestData($routeData, Connection::getIp());

        $requestDataAllow = $route->onMatch($requestData);
        if ($requestDataAllow === false) {
            return false;
        }

        $controller = new $routeData->controller();
        self::$instances[$routeData->controller] = $controller;
        $controller->{$routeData->delegate}($requestData);
        return true;
    }

    public static function getInstance(string $class) : mixed
    {
        if (isset(self::$instances[$class]) === true) {
            return self::$instances[$class];
        } else {
            self::$instances[$class] = new $class();
            return self::$instances[$class];
        }
    }

    protected static $currentUri = null;
    protected static function runUriHandler()
    {
        try {
            if (Js::getWindow()->Flames->Internal->asyncRedirect !== true) {
                return;
            }

            $window = Js::getWindow();
            $location = $window->location;
            $origin = $location->origin;
            self::$currentUri = explode('#', substr($location->href, strlen($origin)))[0];

            $window->setInterval(function() use($location) {
                try {
                    $origin = $location->origin;
                    $currentUri = explode('#', substr($location->href, strlen($origin)))[0];
                    if ($currentUri !== self::$currentUri) {
                        self::$currentUri = $currentUri;
                        \Flames\Browser\Page::load($currentUri, null, true);
                    }
                } catch (\Exception|\Error $_) {}
            }, 100);
        } catch (\Exception|\Error $e) {
            Error::handler($e);
        }
    }

    public static function injectUri(string $uri)
    {
        self::$currentUri = $uri;
    }

    protected static function dispatchNativeBuild()
    {
        $window = Js::getWindow();
        $window->Flames->__nativeInfoDelegate__ = function() {
            self::dispatchNativeBuildEvents();
        };

        $nativeInfo = (string)$window->Flames->__nativeInfo__;
        if (empty($nativeInfo)) {
            return;
        }

        self::dispatchNativeBuildEvents();
    }

    protected static function dispatchNativeBuildEvents()
    {
        Kernel::__setNativeBuild(true);

        if (class_exists('\\App\\Client\\Event\\Native') === true) {
            $ready = new \App\Client\Event\Native();
            $ready->onNative();
        }
    }

    protected static function dispatchNativeServices()
    { Keyboard::register(); }

    protected static function dispatchNativeBuildHooks()
    { Native::register(); }

    protected static function dispatchDevTools()
    {
        $window = Js::getWindow();
        if ($window->localStorage === null) {
            return;
        }

        $devToolsOpen = (int)$window->localStorage->getItem('flames-internal-devtools-open');
        if ($devToolsOpen === 1) {
            DevTools::open();
        }
    }
}