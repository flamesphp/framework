<?php

namespace Flames\Cli\Command\Build\App;

use Flames\Collection\Arr;
use Flames\Collection\Strings;
use Flames\Environment;
use Flames\Kernel;
use Flames\Kernel\Tools\WinIco;
use Flames\Process;
use Flames\Server\Shell;
use ZipArchive;

class Native
{
    protected const APP_NATIVE_KEY_SALT = 'e196f36370deafd5377d377458185484a18d9cc7';
    protected const ICON_INSTALLER_MAX_SIZE = 256;

    protected bool $debug = false;
    protected string $buildPath;
    protected string $assetsPath;
    protected Arr|null $inputs;

    protected static bool $isRunningBuild = false;

    protected ?string $platform = null;

    protected bool $installer = false;
    protected bool $run = false;

    public function __construct($data)
    {
        if ($data->option->contains('windows') === true) {
            $this->platform = 'windows';
        }
        if ($data->option->contains('linux') === true) {
            $this->platform = 'linux';
        }
        if ($data->option->contains('installer') === true) {
            $this->installer = true;
        }
        if ($data->option->contains('run') === true) {
            $this->run = true;
        }
    }

    public function run(bool $debug = false) : bool
    {
        // Stack overflow protection
        if (self::$isRunningBuild === true) {
            return false;
        }

        self::$isRunningBuild = true;

        $this->debug = $debug;

        $this->buildPath = (ROOT_PATH . '.cache/build-native/');
        $this->assetsPath = (FLAMES_PATH . 'Cli/Command/Build/App/Native/Desktop/');

        $this->checkBuildPath();
        $this->cleanBuild();

        if ($this->verifyDependencies() === false) { return false; }

        if ($this->mountNodeApp() === false) { return false; }
        if ($this->installNodeModules() === false) { return false; }
        if ($this->installElectron() === false) { return false; }
        if ($this->prepareApp() === false) { return false; }
        if ($this->buildIcon() === false) { return false; }
        if ($this->buildApp() === false) { return false; }
        if ($this->packBuild() === false) { return false; }

        self::$isRunningBuild = false;
        return true;
    }

    protected function checkBuildPath(): void
    {
        if (is_dir($this->buildPath) === false) {
            $mask = umask(0);
            mkdir($this->buildPath, 0777, true);
            umask($mask);
        }
    }

    protected function verifyDependencies(): bool
    {
        $process = new Shell('npm -v');
        $npmVersion = $process->getOutput();
        $output = (int)$npmVersion;

        if ($process->getCode() !== Shell\Code::CODE_SUCCESS || $output === 0) {
            $this->log("NPM not found.\n");
            $this->reportNodeMissing();
            return false;
        }

        $process = new Shell('npx -v');
        $npxVersion = $process->getOutput();
        $output = (int)$npxVersion;

        if ($process->getCode() !== Shell\Code::CODE_SUCCESS || $output === 0) {
            $this->log("NPX not found.\n");
            $this->reportNodeMissing();
            return false;
        }

        if (\Flames\Server\Os::isWindows() === false) {
            $process = new Shell('rpmbuild --version');
            $rpmVersion = Strings::split($process->getOutput(), ' ');
            $rpmVersion = $rpmVersion->last;
            $output = (int)$rpmVersion;

            if ($process->getCode() !== Shell\Code::CODE_SUCCESS || $output === 0) {
                $this->log("RPM not found.\n");
                $this->log("Install RPM on Ubuntu using command: 'apt install rpm -y'.\n");
                $this->log("Install RPM on others Unix based OS: 'apt install rpmbuild -y'.\n");
                return false;
            }
        }

        $this->log("Dependencies checks: NPM version " . $npmVersion . " and NPX version " . $npxVersion . ".\n");
        return true;
    }

    protected function reportNodeMissing(): void
    {
        if (\Flames\Server\Os::isWindows() === true) {
            $this->log("Please install Node.JS using command: 'choco install nodejs -y'.\n");
            $this->log("Alternatively, you can download the installer from 'https://nodejs.org/en/download'.\n");
        } else {
            $this->log("Please install NodeJS using command: 'apt install nodejs -y'.\n");
        }
    }

    protected function mountNodeApp(): bool
    {
        $packageData = json_decode(file_get_contents($this->assetsPath . 'package.template.json'));

        $appTitle = Environment::get('APP_TITLE');
        if (!empty($appTitle)) { $packageData->name = $appTitle; }
        else { $this->log("Please set APP_TITLE environment variable in .env. Using default value.\n"); sleep(1); }

        $appVersion = Environment::get('APP_VERSION');
        if (!empty($appVersion)) { $packageData->version = $appVersion; }
        else { $this->log("Please set APP_VERSION environment variable in .env. Using default value.\n"); sleep(1); }

        $appAuthor = Environment::get('APP_AUTHOR');
        if (!empty($appAuthor)) { $packageData->author = $appAuthor; }
        else { $this->log("Please set APP_AUTHOR environment variable in .env. Using default value.\n"); sleep(1); }

        $appDescription = Environment::get('APP_DESCRIPTION');
        if (!empty($appDescription)) { $packageData->description = $appDescription; }
        else { $this->log("Please set APP_DESCRIPTION environment variable in .env. Using default value.\n"); sleep(1); }

        $packageDataMount = json_encode($packageData, JSON_PRETTY_PRINT);
        file_put_contents($this->buildPath . 'package.json', $packageDataMount);

        return true;
    }

    protected function installNodeModules(): bool
    {
        $this->log("Installing node modules. It could take up to several minutes...\n");

        $currentPath = getcwd();
        $this->checkBuildPath();
        chdir($this->buildPath);

        $process = new Shell('npm install --force');
        chdir($currentPath);

        if ($process->getCode() !== Shell\Code::CODE_SUCCESS) {
            $this->log("Error installing node modules.\n");
            return false;
        }

        return true;
    }

    protected function installElectron(): bool
    {
        $this->log("Installing Electron. It could take up to several minutes...\n");

        $currentPath = getcwd();
        chdir($this->buildPath);
        $process = new Shell('npm install --save-dev @electron-forge/cli');

        if ($process->getCode() !== Shell\Code::CODE_SUCCESS) {
            chdir($currentPath);
            $this->log("Error installing Electron.\n");
            return false;
        }

        $process = new Shell('npx electron-forge import');
        chdir($currentPath);

        if ($process->getCode() !== Shell\Code::CODE_SUCCESS) {
            $this->log("Error importing Electron.\n");
            return false;
        }

        return true;
    }

    protected function prepareApp(): bool
    {
        file_put_contents($this->buildPath . 'main.js', file_get_contents($this->assetsPath . 'main.js'));
        file_put_contents($this->buildPath . 'forge.config.js', file_get_contents($this->assetsPath . 'forge.config.js'));

        $assetsAppPath = ($this->buildPath . 'Kernel/');
        if (is_dir($assetsAppPath) === false) {
            $mask = umask(0);
            mkdir($assetsAppPath, 0777, true);
            umask($mask);
        }

        file_put_contents($assetsAppPath . 'BrowserView.js', file_get_contents($this->assetsPath . 'Kernel/BrowserView.js'));
        file_put_contents($assetsAppPath . 'BrowserWindow.js', file_get_contents($this->assetsPath . 'Kernel/BrowserWindow.js'));
        file_put_contents($assetsAppPath . 'Register.js', file_get_contents($this->assetsPath . 'Kernel/Register.js'));
        file_put_contents($assetsAppPath . 'Flames.js', file_get_contents($this->assetsPath . 'Kernel/Flames.js'));
        file_put_contents($assetsAppPath . 'Initialize.js', file_get_contents($this->assetsPath . 'Kernel/Initialize.js'));
        file_put_contents($assetsAppPath . 'Setup.js', file_get_contents($this->assetsPath . 'Kernel/Setup.js'));

        $appDomain = Environment::get('APP_DOMAIN');
        if (empty($appDomain)) {
            $this->log("Please set APP_DOMAIN environment variable in .env. Using default value.\n");
            sleep(1);
            $appDomain = 'flamesphp.com';
        }

        $appProtocol = Environment::get('APP_PROTOCOL');
        if (empty($appProtocol)) {
            $this->log("Please set APP_PROTOCOL environment variable in .env. Using default value.\n");
            sleep(1);
            $appProtocol = 'https';
        }

        $appNativeKey = self::getAppNativeKey();
        $domains = ((string)Environment::get('APP_DOMAIN') . ',');
        $appNativeDomains = Environment::get('APP_NATIVE_DOMAINS');
        if (!empty($appNativeDomains)) { $domains .= ($appNativeDomains . ',');  }

        if (Strings::endsWith($domains, ',') === true) { $domains = substr($domains, 0, -1); }

        $appEnv = file_get_contents($this->assetsPath . 'env.template.js');
        $appEnv = str_replace([
            '{{ url }}',
            '{{ appNativeKey }}',
            '{{ domains }}'
        ], [
            $appProtocol . '://' . $appDomain,
            $appNativeKey,
            $domains
        ], $appEnv);
        file_put_contents($this->buildPath . 'env.js', $appEnv);

        return true;
    }

    protected function buildIcon(): bool
    {
        $buildResourcePath = ($this->buildPath . 'Resource/');
        if (is_dir($buildResourcePath) === false) {
            $mask = umask(0);
            mkdir($buildResourcePath, 0777, true);
            umask($mask);
        }

        $iconPath = (APP_PATH . 'Client/Resource/icon.png');
        if (file_exists($iconPath) === false) {
            $iconPath = (FLAMES_PATH . 'Kernel/Client/Engine/Flames.png');
            $this->log("App Icon not found, please put at 'App/Client/Resource/icon.png'. Using default value.\n");
            sleep(1);
        }
        copy($iconPath, ($buildResourcePath . 'icon.png'));

        $winIco = new WinIco($buildResourcePath . 'icon.png');
        $winIco->save($buildResourcePath . 'icon.ico');

        return true;
    }

    protected function buildApp(): bool
    {
        $this->log("Building native app... It could take up to several minutes...\n");

        $currentPath = getcwd();
        chdir($this->buildPath);

        $process = new Shell('npm run make');
        chdir($currentPath);

        if ($process->getCode() !== Shell\Code::CODE_SUCCESS) {
            $this->log("Error building app.\n");
            return false;
        }

        return true;
    }

    protected function packBuild(): bool
    {
        $buildZipPath = (APP_PATH . 'Client/Build/');
        if (is_dir($buildZipPath) === false) {
            $mask = umask(0);
            mkdir($buildZipPath, 0777, true);
            umask($mask);
        }

        $outputPath = ($this->buildPath . 'out/');

        if (\Flames\Server\Os::isWindows() === true) {
            $squirrelPath = ($outputPath . 'make/squirrel.windows/x64/');

            if (is_dir($squirrelPath)) {
                $files = scandir($squirrelPath);
                $outputFile = null;
                foreach ($files as $file) {
                    if (Strings::endsWith($file, '.nupkg') === true) {
                        $outputFile = $file;
                        break;
                    }
                }

                if ($outputFile !== null) {
                    $outputFilePath = ($squirrelPath . $outputFile);
                    $fileName = ('build_' . $this->getBuildFilePrefix() . '.nupkg');
                    copy($outputFilePath, (APP_PATH . 'Client/Build/' . $fileName));
                } else {
                    $this->log("No nupkg build file found in output directory.\n");
                }
            }

            $this->packZip($outputPath);

            if ($this->installer === true) {
                if ($this->buildInstaller($outputPath) === false) {
                    return false;
                }
            }
            elseif ($this->run === true) {
                $this->runBuild($outputPath);
            }
            return true;
        }

        $this->packBuildBundleUnix($outputPath, 'deb');
        $this->packBuildBundleUnix($outputPath, 'rpm');
        if ($this->packZip($outputPath) === false) { return false; }

        if ($this->run === true) {
            $this->runBuild($outputPath);
        }

        return true;
    }

    protected function packBuildBundleUnix(string $outputPath, string $type): void
    {
        $outputPath = ($outputPath . 'make/' . $type . '/x64/');

        if (is_dir($outputPath)) {
            $files = scandir($outputPath);
            $outputFile = null;
            foreach ($files as $file) {
                if (Strings::endsWith($file, '.' . $type) === true) {
                    $outputFile = $file;
                    break;
                }
            }

            if ($outputFile !== null) {
                $outputFilePath = ($outputPath . $outputFile);
                $fileName = ('build_' . $this->getBuildFilePrefix() . '.' . $type);
                copy($outputFilePath, (APP_PATH . 'Client/Build/' . $fileName));
            } else {
                $this->log('No ' . $type . " build file found in output directory.\n");
            }
        }
    }

    protected function packZip(string $outputPath): bool
    {
        $outputDir = $this->getPackPath($outputPath);

        if ($outputDir === null) {
            $this->log("No output directory found.\n");
            return false;
        }

        @unlink($outputDir . '/LICENSES.chromium.html');
        @unlink($outputDir . '/Squirrel.exe');

        $this->buildZip($outputDir);

        return true;
    }

    public function getPackPath(string $outputPath): ?string
    {
        $outDirs = scandir($outputPath);
        $outputDir = null;
        foreach ($outDirs as $outDir) {
            if ($outDir !== '.' && $outDir !== '..' && $outDir !== 'make') {
                $outputDir = ($outputPath . $outDir . '/');
            }
        }

        return $outputDir;
    }

    protected function buildInstaller(string $outputPath): bool
    {
        $this->log("Building windows installer... It could take up to several minutes...\n");

        $outputDir = $this->getPackPath($outputPath);
        $exeFile = $this->getWindowExecutable($outputDir);

        $issrcPath = $this->verifyIssrc();

        $installerPath = ($this->buildPath . 'Installer/');
        if (is_dir($installerPath) === false) {
            $mask = umask(0);
            mkdir($installerPath, 0777, true);
            umask($mask);
        }

        $this->buildIconInstaller();
        $appInstallerUuid = $this->getInstallerUuid();

        $appTitle = Environment::get('APP_TITLE'); if (empty($appTitle)) { $appTitle = 'Flames'; }
        $appVersion = Environment::get('APP_VERSION'); if (empty($appTitle)) { $appVersion = '1.0.0'; }
        $appAuthor = Environment::get('APP_AUTHOR'); if (empty($appTitle)) { $appAuthor = 'Flames'; }
        $appDomain = Environment::get('APP_DOMAIN'); if (empty($appDomain)) { $appDomain = 'localhost'; }
        $appProtocol = Environment::get('APP_PROTOCOL'); if (empty($appDomain)) { $appDomain = 'https'; }

        $issData = file_get_contents($this->assetsPath . 'WinInstaller/setup.template.iss');
        $issData = str_replace([
            '{{ APP_TITLE }}',
            '{{ APP_VERSION }}',
            '{{ APP_AUTHOR }}',
            '{{ APP_URL }}',
            '{{ APP_UUID }}',
            '{{ FILE_EXECUTABLE }}',
            '{{ PATH_INTALLER }}',
            '{{ PATH_BUILD }}',
        ], [
            $appTitle,
            $appVersion,
            $appAuthor,
            ($appProtocol . '://' . $appDomain),
            $appInstallerUuid,
            $exeFile,
            $installerPath,
            $outputDir,
        ], $issData);
        file_put_contents($installerPath . 'setup.iss', $issData);

        $buildCommand = ($issrcPath . 'iscc.exe "' . $installerPath . 'setup.iss"');

        $process = new Shell($buildCommand);
        if ($process->getCode() !== Shell\Code::CODE_SUCCESS) {
            $this->log("Error building installer.\n");
            return false;
        }

        $fileName = ('build_' . $this->getBuildFilePrefix() . '.exe');
        copy($installerPath . 'setup.exe', (APP_PATH . 'Client/Build/' . $fileName));

        return true;
    }

    protected function getInstallerUuid(): string
    {
        $installerUuid = Environment::get('APP_INSTALLER_UUID');
        if (empty($installerUuid)) {
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
            $uuid = Strings::toUpper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));

            $env = Environment::default();
            $env->APP_INSTALLER_UUID = $uuid;
            $env->save();

            return $env->APP_INSTALLER_UUID;
        }

        return $installerUuid;
    }

    protected function buildIconInstaller(): void
    {
        $iconInstallerPath = ($this->buildPath . 'Installer/icon.png');

        $iconPath = ($this->buildPath . 'Resource/icon.png');
        copy($iconPath, ($iconInstallerPath));
        $iconPath = $iconInstallerPath;

        list($iconWidth, $iconHeight) = getimagesize($iconPath);
        $iconWidth = (int)$iconWidth;
        $iconHeight = (int)$iconHeight;
        $origWidth = $iconWidth;
        $origHeight = $iconHeight;

        if ($iconWidth > self::ICON_INSTALLER_MAX_SIZE) {
            $iconHeight = ((self::ICON_INSTALLER_MAX_SIZE / $iconWidth) * $iconHeight);
            $iconWidth = self::ICON_INSTALLER_MAX_SIZE;
        }
        if ($iconHeight > self::ICON_INSTALLER_MAX_SIZE) {
            $iconWidth = ((self::ICON_INSTALLER_MAX_SIZE / $iconHeight) * $iconWidth);
            $iconHeight = self::ICON_INSTALLER_MAX_SIZE;
        }

        if ($iconWidth !== $origWidth || $iconHeight !== $origHeight) {
            $iconImageResized = imagecreate($iconWidth, $iconHeight);
            $iconImage = imagecreatefrompng($iconPath);
            imagecopyresampled($iconImageResized, $iconImage, 0, 0, 0, 0,
                $iconWidth, $iconHeight, $origWidth, $origHeight);
            imagepng($iconImageResized, $iconPath);
        }

        $winIco = new WinIco($iconPath);
        $winIco->save($this->buildPath . 'Installer/icon.ico');
    }

    protected function verifyIssrc(): string
    {
        $issrcPath = (ROOT_PATH . '.cache/tools/issrc/');
        if (is_dir($issrcPath) === false) {
            $mask = umask(0);
            mkdir($issrcPath, 0777, true);
            umask($mask);
        }

        if (file_exists($issrcPath . 'ok') === false) {
            $installPath = ($issrcPath . 'install.zip');
            file_put_contents($installPath, file_get_contents('https://cdn.jsdelivr.net/gh/flamesphp/cdn@' . Kernel::CDN_VERSION . '/tools/issrc.zip.dat'));

            $zip = new ZipArchive;
            $zip->open($installPath);
            $zip->extractTo($issrcPath);
            $zip->close();

            @unlink($installPath);
            file_put_contents($issrcPath . 'ok', '');
        }

        return $issrcPath;
    }

    protected function runBuild(string $outputPath): void
    {
        $outDirs = scandir($outputPath);
        $outputDir = null;
        foreach ($outDirs as $outDir) {
            if ($outDir !== '.' && $outDir !== '..' && $outDir !== 'make') {
                $outputDir = ($outputPath . $outDir . '/');
            }
        }

        if ($outputDir === null) {
            $this->log("No output directory found. Can't run build.\n");
            return;
        }

        $exeFile = $this->getWindowExecutable($outputDir);
        if ($exeFile === null) {
            $this->log("No executable file found. Can't run build.\n");
            return;
        }

        $exePath = ($outputDir . $exeFile);
        proc_open("start /b " . $exePath, [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
        ], $pipes);
    }

    protected function getWindowExecutable(string $outputDir): ?string
    {
        $files = scandir($outputDir);
        $exeFile = null;
        foreach ($files as $file) {
            if (Strings::endsWith($file, '.exe') === true) {
                $exeFile = $file;
                break;
            }
        }

        return $exeFile;
    }

    protected function getBuildFilePrefix(): string
    {
        $pathName = '';
        $appName = Environment::get('APP_NAME');
        if (!empty($appName)) {
            $pathName = (strtolower($appName) . '_');
        }

        $pathName .= (new \DateTime())->format('Y_m_d_His');
        return $pathName;
    }

    protected function buildZip(string $buildPath): void
    {
        $this->log("Building zip file... It could take up to several minutes...\n");

        $buildZipPath = (APP_PATH . 'Client/Build/');
        if (is_dir($buildZipPath) === false) {
            $mask = umask(0);
            mkdir($buildZipPath, 0777, true);
            umask($mask);
        }

        $zipName = 'build_';
        $appName = Environment::get('APP_NAME');
        if (!empty($appName)) {
            $zipName .= (strtolower($appName) . '_');
        }

        $zipName .= (new \DateTime())->format('Y_m_d_His');
        $zipPath = ($buildZipPath . $zipName . '.zip');


        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $buildPathLen = strlen($buildPath);
        $buildFiles = $this->getDirContents($buildPath);
        foreach ($buildFiles as $buildFile) {
            if (is_dir($buildFile) === true) {
                continue;
            }

            $zipFilePath = substr($buildFile, $buildPathLen);
            $zip->addFile($buildFile, $zipFilePath);
        }
        $zip->close();
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
    
    protected function log(string $message)
    {
        echo $message;
        @flush();
        @ob_flush();
    }

    public static function getAppNativeKey(): string
    {
        $appNativeKey = Environment::get('APP_NATIVE_KEY');
        if (empty($appNativeKey)) {
            $appNativeKey = sha1(
                self::APP_NATIVE_KEY_SALT . '.' .
                Environment::get('APP_KEY') . '.' .
                Environment::get('CRYPTO_KEY')
            );

            $env = Environment::default();
            $env->APP_NATIVE_KEY = $appNativeKey;
            $env->save();
        }

        return $appNativeKey;
    }
}