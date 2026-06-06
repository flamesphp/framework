<?php
declare(strict_types=1);


namespace Flames\Framework\Controller;

use Flames\Collection\Arr;
use Flames\Forge\Cli;

/**
 * @internal
 */
final class RequestMount
{
    public static function mountRequestData(Arr $routeData, string|null $ip = null): RequestData
    {
        $isCLI       = (Cli::isCli() === true);
        $method      = null;
        $headers     = null;
        $contentType = null;
        $multipart   = [];
        $request     = [];
        $urlEncoded  = [];
        $json        = null;
        $queryString = null;

//        if (\Flames\Kernel::MODULE === 'SERVER') {
            if ($isCLI === false) {
                $method = $_SERVER['REQUEST_METHOD'];
                $headers = (function_exists('getallheaders') ? getallheaders() : []);
                $contentType = null;
                if (isset($headers['Content-Type']) === true) {
                    $contentType = $headers['Content-Type'];
                } elseif (isset($headers['content-type']) === true) {
                    $contentType = $headers['content-type'];
                }

                $splitUri = explode('?', $_SERVER['REQUEST_URI']);
                if (count($splitUri) >= 2) {
                    $queryString = [];
                    parse_str($splitUri[1], $queryString);
                    foreach ($queryString as $key => $value) {
                        $request[$key] = $value;
                    }
                }

                if ($contentType !== null && str_starts_with($contentType, 'multipart/form-data')) {
                    if ($method === 'GET') {
                        parse_raw_http_request($multipart);
                    } else {
                        foreach ($_POST as $key => $value) {
                            $multipart[$key] = $value;
                        }
                    }
                    foreach ($multipart as $key => $value) {
                        $request[$key] = $value;
                    }
                }

                if ($contentType !== null && str_starts_with($contentType, 'application/x-www-form-urlencoded')) {
                    if ($method === 'GET') {
                        $input = file_get_contents('php://input');
                        parse_str($input, $urlEncoded);
                    } else {
                        foreach ($_POST as $key => $value) {
                            $urlEncoded[$key] = $value;
                        }
                    }

                    foreach ($_POST as $key => $value) {
                        $request[$key] = $value;
                    }
                }

                if ($method !== 'GET') {
                    foreach ($_POST as $key => $value) {
                        $request[$key] = $value;
                    }
                }

                if ($contentType !== null && str_starts_with($contentType, 'application/json')) {
                    $json = @json_decode(file_get_contents('php://input'));
                    if ($json !== false) {
                        $json = (array) $json;
                        foreach ($json as $key => $value) {
                            $request[$key] = $value;
                        }
                        $json = Arr($json);
                    } else {
                        $json = null;
                    }
                }

                foreach ($routeData->parameters as $key => $value) {
                    $request[$key] = $value;
                }

                return new RequestData(
                    $method,
                    explode('?', $_SERVER['REQUEST_URI'])[0],
                    Arr($queryString),
                    $routeData->parameters,
                    Arr($multipart),
                    Arr($urlEncoded),
                    $json,
                    Arr($request),
                    Arr($headers),
                    $_SERVER['SERVER_NAME'],
                    $_SERVER['SERVER_PORT'],
                    $ip,
                    null,
                    null
                );
            }

            return new RequestData(
                'CLI',
                null,
                Arr($queryString),
                $routeData->parameters,
                Arr($multipart),
                Arr($urlEncoded),
                $json,
                Arr($request),
                Arr($headers),
                null,
                null,
                null,
                $routeData->command,
                null
            );
//        }

//        $splitUri = explode('?', $_SERVER['REQUEST_URI']);
//        if (count($splitUri) >= 2) {
//            $queryString = [];
//            parse_str($splitUri[1], $queryString);
//            foreach ($queryString as $key => $value) {
//                $request[$key] = $value;
//            }
//        }
//
//        foreach ($routeData->parameters as $key => $value) {
//            $request[$key] = $value;
//        }
//
//        return new RequestData(
//            'GET',
//            explode('?', $_SERVER['REQUEST_URI'])[0],
//            Arr($queryString),
//            $routeData->parameters,
//            Arr(),
//            Arr(),
//            $json,
//            Arr($request),
//            Arr(),
//            null,
//            null,
//            null,
//            null,
//            Arr(\Flames\Kernel::__getData())
//        );
    }
}
