<?php

namespace Flames;

use Flames\Collection\Arr;
use Flames\Environment\Helpers;

/**
 * Class Environment
 *
 * @property bool $DUMP_ENABLED
 * @property string $DUMP_THEME
 */
final class Environment
{
    public function __construct()
    {
        $this->load();
    }

    public function getAll(): Arr
    {
        return Arr(\Flames\Ready\ResetData::$data['.env']) ?? Arr();
    }

    public function toArray(): array
    {
        return (array)(\Flames\Ready\ResetData::$data['.env'] ?? Arr());
    }

    public static function get($key): mixed
    {
        $key = (string)$key;
        if (empty($key)) {
            return null;
        }

        $store = \Flames\Ready\ResetData::$data['.env'] ?? null;
        if ($store !== null && $store->containsKey($key)) {
            return $store[$key];
        }

        return null;
    }

    public static function set(mixed $key, mixed $value): void
    {
        $key = (string)$key;
        if (empty($key) || !isset(\Flames\Ready\ResetData::$data['.env'])) {
            return;
        }

        \Flames\Ready\ResetData::$data['.env'][$key] = $value;
    }

    public function inject(): void
    {
        $this->load();
    }

    public function save(): void
    {
        $path = ROOT_PATH . '.env';
        $raw  = str_replace(["\r\n", "\r"], "\n", file_get_contents($path));

        $store       = Arr(\Flames\Ready\ResetData::$data['.env']) ?? Arr();
        $keys        = $store->getKeys()->toArray();
        $rewriteKeys = [];
        $mount       = '';

        foreach (explode("\n", $raw) as $line) {
            if (str_starts_with($line, "\n") || str_starts_with($line, '#') || str_contains($line, '=') === false) {
                $mount .= ($line . "\n");
                continue;
            }

            $var = trim(explode('=', $line)[0]);

            if (in_array($var, $keys, true)) {
                $rewriteKeys[] = $var;
            }

            $mount .= ($var . '=' . $this->formatValue($store[$var] ?? null) . "\n");
        }

        foreach (array_diff($keys, $rewriteKeys) as $key) {
            $mount .= ($key . '=' . $this->formatValue($store[$key]) . "\n");
        }

        @file_put_contents($path, $mount);
    }

    protected function load(): void
    {
        $path = ROOT_PATH . '.env';

        if (!file_exists($path)) {
            \Flames\Ready\ResetData::$data['.env'] = Arr();
            return;
        }

        \Flames\Ready\ResetData::$data['.env'] = Arr(Helpers::fromFile($path));
    }

    private function formatValue(mixed $value): string
    {
        if ($value === true)  return 'true';
        if ($value === false) return 'false';
        $value = (string)$value;
        return str_contains($value, ' ') ? '"' . $value . '"' : $value;
    }
}
