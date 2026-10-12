<?php
declare(strict_types=1);


namespace Flames\Framework\Controller;

use Flames\Collection\Arr;
use Flames\Router\RouteMatch;

final class RequestMount
{
    public static function mountRequestData(RouteMatch $match, ?string $ip = null): RequestData
    {
        if (\Flames\Forge\Cli::isCli()) {
            $data   = self::buildData([], [], [], $match->parameters);
            $params = self::parseCliParams($_SERVER['argv'] ?? []);

            if ($params !== []) {
                $data = array_merge($params, $data);
            }

            return new RequestData(
                'CLI',
                null,
                self::arr([]),
                self::arr($match->parameters),
                self::arr([]),
                self::arr([]),
                null,
                self::arr($data),
                self::arr([]),
                null,
                null,
                $ip,
                $match->command,
                self::arr($data),
                self::arr($params),
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

        $multipart = [];
        $urlEncoded = [];
        $json = null;

        if (str_starts_with($contentType, 'multipart/form-data')) {
            $multipart = $_POST;
        } elseif (str_starts_with($contentType, 'application/x-www-form-urlencoded')) {
            if ($method === 'GET') {
                parse_str(file_get_contents('php://input') ?: '', $urlEncoded);
            } else {
                $urlEncoded = $_POST;
            }
        }

        if (str_starts_with($contentType, 'application/json')) {
            $decoded = json_decode(file_get_contents('php://input') ?: 'null', true);
            if (is_array($decoded)) {
                $json = $decoded;
            }
        }

        $getParams = $method === 'GET' ? $urlEncoded : [];

        $postParams = $multipart;
        if ($method !== 'GET') {
            if ($urlEncoded !== []) {
                $postParams = array_merge($postParams, $urlEncoded);
            } elseif ($_POST !== [] && $multipart === [] && $json === null) {
                $postParams = array_merge($postParams, $_POST);
            }
        }

        if ($json !== null) {
            $postParams = array_merge($postParams, $json);
        }

        $data = self::buildData($queries, $getParams, $postParams, $match->parameters);

        return new RequestData(
            $method,
            $path,
            self::arr($queries),
            self::arr($match->parameters),
            self::arr($multipart),
            self::arr($urlEncoded),
            self::arrOrNull($json),
            self::arr($data),
            self::arr(self::headers()),
            $_SERVER['SERVER_NAME'] ?? null,
            isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : null,
            $ip,
            null,
            self::arr($data),
            self::arr([]),
        );
    }

    /**
     * @param list<string> $argv
     *
     * @return array<string, mixed>
     */
    private static function parseCliParams(array $argv): array
    {
        $params = [];

        foreach (array_slice($argv, 1) as $arg) {
            if (!str_starts_with($arg, '-')) {
                continue;
            }

            if (str_starts_with($arg, '--')) {
                $inner = substr($arg, 2);
                if ($inner === '') {
                    continue;
                }

                $eqPos = strpos($inner, '=');
                if ($eqPos === false) {
                    $params[$inner] = true;
                } else {
                    $params[substr($inner, 0, $eqPos)] = substr($inner, $eqPos + 1);
                }

                continue;
            }

            $inner = substr($arg, 1);
            if ($inner === '') {
                continue;
            }

            $eqPos = strpos($inner, '=');
            if ($eqPos === false) {
                $params[$inner] = null;
            } else {
                $params[substr($inner, 0, $eqPos)] = substr($inner, $eqPos + 1);
            }
        }

        return $params;
    }

    /**
     * Merge precedence (low → high): queries, GET params, POST params, route params.
     *
     * @param array<string, mixed> $queries
     * @param array<string, mixed> $getParams
     * @param array<string, mixed> $postParams
     * @param array<string, mixed> $uries
     *
     * @return array<string, mixed>
     */
    private static function buildData(array $queries, array $getParams, array $postParams, array $uries): array
    {
        return array_merge($queries, $getParams, $postParams, $uries);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function arr(array $data): Arr
    {
        return new Arr($data);
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private static function arrOrNull(?array $data): ?Arr
    {
        return $data === null ? null : new Arr($data);
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
