<?php

namespace Flames\Cli\Command;

use Flames\Command;
use Flames\Environment;

/**
 * Class Install
 *
 * This class is responsible for running the installation process.
 *
 * @internal
 */
final class Install
{
    protected bool $debug = false;

    protected bool $withKeyGenerate = true;
    protected bool $withCryptoKeyGenerate = true;
    protected bool $withDocker = true;
    protected bool $withApache = true;
    protected bool $withGit = true;

    /**
     * Constructor for the class.
     *
     * @param object $data The data object containing the options.
     * @return void
     */
    public function __construct($data)
    {
        $this->withKeyGenerate = (!$data->option->contains('nokey'));
        $this->withCryptoKeyGenerate = (!$data->option->contains('nocryptokey'));
        $this->withDocker = (!$data->option->contains('nodocker'));
        $this->withApache = (!$data->option->contains('noapache'));
        $this->withGit = (!$data->option->contains('nogit'));
    }

    /**
     * Executes the run method.
     *
     * @param bool $debug (optional) Determines if debugging is enabled. Default is false.
     *
     * @return bool Indicates the success or failure of the run method.
     */
    public function run(bool $debug = false) : bool
    {
        $default = Environment::default();
        if ($default->isValid() === false) {
            $envPath = (ROOT_PATH . '.env');
            $envDistPath = (FLAMES_PATH . '../.env.dist');
            copy($envDistPath, $envPath);
        }

        $binPath = (ROOT_PATH . 'bin');
        if (file_exists($binPath) === false) {
            $binDistPath = (FLAMES_PATH . 'Kernel/Raw/bin');
            copy($binDistPath, $binPath);
        }

        $indexPath = (ROOT_PATH . 'index.php');
        if (file_exists($indexPath) === false) {
            $indexDistPath = (FLAMES_PATH . 'Kernel/Raw/index.php');
            copy($indexDistPath, $indexPath);
        }

        $appPath = (ROOT_PATH . 'App');
        if (is_dir($appPath) === false) {
            $mask = umask(0);
            mkdir(ROOT_PATH . 'App', 0777, true);
            umask($mask);
        }

        if ($this->withKeyGenerate === true) {
            Command::run('key:generate');
        }
        if ($this->withCryptoKeyGenerate === true) {
            Command::run('crypto:key:generate');
        }

        if ($this->withApache === true) {
            $htaccessPath = (ROOT_PATH . '.htaccess');
            if (file_exists($htaccessPath) === false) {
                $htaccessDistPath = (FLAMES_PATH . 'Kernel/Raw/.htaccess');
                copy($htaccessDistPath, $htaccessPath);
            }
        }

        if ($this->withDocker === true) {
            $dockerComposePath = (ROOT_PATH . 'docker-compose.yml');
            if (file_exists($dockerComposePath) === false) {
                $dockerComposeDistPath = (FLAMES_PATH . '../docker-compose.yml');
                copy($dockerComposeDistPath, $dockerComposePath);
            }

            $dockerDataPath = (ROOT_PATH . '.docker');
            if (is_dir($dockerDataPath) === false) {
                $this->recurseCopy(FLAMES_PATH . '../.docker', $dockerDataPath);
            }
        }

        if ($this->withGit === true) {
            $gitIgnorePath = (ROOT_PATH . '.gitignore');
            if (file_exists($gitIgnorePath) === false) {
                $gitIgnoreDistPath = (FLAMES_PATH . 'Kernel/Raw/.gitignore');
                copy($gitIgnoreDistPath, $gitIgnorePath);
            }
        }



        return true;
    }

    private function recurseCopy(string $sourceDirectory, string $destinationDirectory, string $childFolder = ''): void
    {
        $directory = opendir($sourceDirectory);

        if (is_dir($destinationDirectory) === false) {
            mkdir($destinationDirectory);
        }

        if ($childFolder !== '') {
            if (is_dir("$destinationDirectory/$childFolder") === false) {
                mkdir("$destinationDirectory/$childFolder");
            }

            while (($file = readdir($directory)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                if (is_dir("$sourceDirectory/$file") === true) {
                    $this->recurseCopy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
                } else {
                    copy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
                }
            }

            closedir($directory);

            return;
        }

        while (($file = readdir($directory)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (is_dir("$sourceDirectory/$file") === true) {
                $this->recurseCopy("$sourceDirectory/$file", "$destinationDirectory/$file");
            }
            else {
                copy("$sourceDirectory/$file", "$destinationDirectory/$file");
            }
        }

        closedir($directory);
    }
}