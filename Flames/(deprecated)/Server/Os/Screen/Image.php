<?php
declare(strict_types=1);


namespace Flames\Os\Server\Screen;

use Exception;
use Flames\Crypto\Hash;
use Flames\Server\Os;
use Flames\Image as ImageEx;

class Image
{
    public static function take()
    {
        if (Os::isUnix() === true) {
            throw new Exception('Unix not supported yet.');
        }

        $tmpDir = (ROOT_PATH . '.cache\\os-image\\');
        if (is_dir($tmpDir) === false) {
            $mask = umask(0);
            mkdir($tmpDir, 0777, true);
            umask($mask);
        }

        chdir($tmpDir);
        $batPath = str_replace('/', '\\', (FLAMES_PATH . 'Server\OS\Screen\Image\Take.bat'));
        $imageHash = Hash::getRandom();
        exec($batPath . ' ' . $imageHash . '.png');
        chdir(ROOT_PATH);

        $imagePath = ($tmpDir . $imageHash . '.png');
        if (file_exists($imagePath) === false) {
            return null;
        }

        $imageData = file_get_contents($imagePath);
        $image = ImageEx::fromString($imageData);
        unlink($imagePath);
        return $image;
    }
}