<?php
declare(strict_types=1);


namespace Flames\Framework;

use Flames\Collection\Arr;
use Flames\Mesh\Environment;
use Flames\Mesh\Loader\FilesystemLoader;
use Flames\Microservice\Microservice;
use Flames\Surface\Kernel;

class View
{
    protected ?string $path = null;

    public function addView(string $path): void
    {
        $fullPath = (Microservice::getPath() . 'Server/View/' . $path);
        if (file_exists($fullPath) === false) {
            throw new \RuntimeException('View path ' . $fullPath . ' does not exist.');
        }

        $this->path = $path;
    }

    public function render(Arr|array|null $data = null): string
    {
        if ($data instanceof Arr) {
            $data = $data->toArray();
        } elseif ($data === null) {
            $data = [];
        }

        $loader = new FilesystemLoader(Microservice::getPath() . 'Server/View/');
        $twig = new Environment($loader, [
            'cache' => rtrim(Cache::getPath(), '/') . '/flames/mesh/',
            'auto_reload' => true,
        ]);

        return Kernel::inject($twig->render($this->path, $data));
    }
}
