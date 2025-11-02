<?php

namespace Flames\Cli\Command\Build\App;

use Flames\Collection\Arr;
use Flames\Collection\Strings;
use Flames\Environment;
use Flames\Kernel;
use Flames\Kernel\Tools\WinIco;
use Flames\Process;
use Flames\Server\Shell;
use Flames\Kernel\Sync;
use ZipArchive;

class Mobile
{
    protected bool $debug = false;
    protected string $buildPath;
    protected string $assetsPath;
    protected string $toolsPath;
    protected Arr|null $inputs;

    protected static bool $isRunningBuild = false;

    protected ?string $platform = null;

    protected bool $installer = false;
    protected bool $run = false;

    public function __construct($data)
    {

    }

    public function run(bool $debug = false) : bool
    {
        // Stack overflow protection
        if (self::$isRunningBuild === true) {
            return false;
        }

        self::$isRunningBuild = true;

        $this->debug = $debug;

        $this->toolsPath = (ROOT_PATH . '.cache/tools/mobile/android/');
        $this->buildPath = (ROOT_PATH . '.cache/build-mobile/');

        if (is_dir($this->toolsPath) === false) {
            $mask = umask(0);
            mkdir($this->toolsPath, 0777, true);
            umask($mask);
        }
        $this->checkBuildPath();

        $this->syncProject();
        $this->cleanBuild();
        $this->setupProject();

        self::$isRunningBuild = false;
        return true;
    }

    protected function syncProject(): void
    {
        $mobileVersion = (int)Sync::getData('mobile.android');

        $toolsVersionPath = ($this->toolsPath . sha1('version'));
        if (file_exists($toolsVersionPath) === false) {
            $this->downloadProject($mobileVersion, $toolsVersionPath);
        } else {
            $localToolsVersionPath = (int)file_get_contents($toolsVersionPath);
            if ($localToolsVersionPath !== $mobileVersion) {
                $this->downloadProject($mobileVersion, $toolsVersionPath);
            }
        }
    }

    protected function downloadProject(int $mobileVersion, string $toolsVersionPath): void
    {
        file_put_contents($this->toolsPath . 'install.zip',
            file_get_contents('https://cdn.jsdelivr.net/gh/flamesphp/cdn@' . Kernel::CDN_VERSION . '/tools/android.zip.dat')
        );

        file_put_contents($toolsVersionPath, $mobileVersion);
    }

    protected function cleanBuild() : void
    {
        $currentPath = getcwd();

        if (\Flames\Server\Os::isWindows() === true) {
            @exec('del /s /q "' . $this->buildPath . '"');
            sleep(1);
            $this->checkBuildPath();
        } else {
            chdir($this->buildPath);
            @exec('rm -rf *');
            chdir($currentPath);
        }

        if (is_dir($this->buildPath) === false) {
            return;
        }

        $buildFiles = $this->getDirContents($this->buildPath);
        foreach ($buildFiles as $buildFile) {
            if (is_file($buildFile) === true) {
                @unlink($buildFile);
            }
        }
        foreach ($buildFiles as $buildFile) {
            if (is_dir($buildFile) === true) {
                @rmdir($buildFile);
            }
        }

        $this->checkBuildPath();
    }

    protected function getDirContents($dir, &$results = [])
    {
        if (!is_dir($dir)) {
            return [];
        }
        $files = scandir($dir);

        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $results[] = $path;
            } else if ($value !== '.' && $value !== '..') {
                $this->getDirContents($path, $results);
                $results[] = $path;
            }
        }

        return $results;
    }

    protected function checkBuildPath(): void
    {
        if (is_dir($this->buildPath) === false) {
            $mask = umask(0);
            mkdir($this->buildPath, 0777, true);
            umask($mask);
        }
    }

    protected function setupProject(): void
    {
        $zip = new ZipArchive;
        $zip->open($this->toolsPath . 'install.zip');
        $zip->extractTo($this->buildPath);
        $zip->close();
    }
}