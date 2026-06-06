<?php
declare(strict_types=1);


namespace Flames\Framework\Controller;

use Flames\Router\RouteMatch;

final class RequestMount
{
    public static function mountRequestData(RouteMatch $match, ?string $ip = null): RequestData
    {
        if (\Flames\Forge\Cli::isCli()) {
            return new RequestData(
                'CLI',
                null,
                [],
                $match->parameters,
                [],
                [],
                null,
                [],
                [],
                null,
                null,
                $ip,
                $match->command,
            );
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = ($pos = strpos($uri, '?')) !== false ? substr($uri, 0, $pos) : $uri;
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

        $queries = [];
        if ($pos !== false) {
            parse_str(substr($uri, $pos + 1), $queries);
        }

        $request = $queries;
        $multipart = [];
        $urlEncoded = [];
        $json = null;

        if (str_starts_with($contentType, 'multipart/form-data')) {
            $multipart = $_POST;
            $request = array_merge($request, $_POST);
        } elseif (str_starts_with($contentType, 'application/x-www-form-urlencoded')) {
            if ($method === 'GET') {
                parse_str(file_get_contents('php://input') ?: '', $urlEncoded);
            } else {
                $urlEncoded = $_POST;
            }
            $request = array_merge($request, $urlEncoded);
        } elseif ($method !== 'GET' && $_POST !== []) {
            $request = array_merge($request, $_POST);
        }

        if (str_starts_with($contentType, 'application/json')) {
            $decoded = json_decode(file_get_contents('php://input') ?: 'null', true);
            if (is_array($decoded)) {
                $json = $decoded;
                $request = array_merge($request, $decoded);
            }
        }

        if ($match->parameters !== []) {
            $request = array_merge($request, $match->parameters);
        }

        return new RequestData(
            $method,
            $path,
            $queries,
            $match->parameters,
            $multipart,
            $urlEncoded,
            $json,
            $request,
            self::headers(),
            $_SERVER['SERVER_NAME'] ?? null,
            isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : null,
            $ip,
            null,
        );
    }

    /**
     * @return array<string, string>
     */
    private static function headers(): array
    {
        if (\function_exists('getallheaders')) {
            $headers = getallheaders();

            return is_array($headers) ? $headers : [];
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!is_string($key) || !str_starts_with($key, 'HTTP_')) {
                continue;
            }

            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
            $headers[$name] = (string) $value;
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        return $headers;
    }
}
