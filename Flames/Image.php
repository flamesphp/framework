<?php
declare(strict_types=1);


/**
 * Class Image
 *
 * The Image class represents an image and provides various methods for image manipulation.
 *
 * @package Flames
 */

namespace Flames;

use Flames\Crypto\Hash;
use Flames\Framework\Header;
use Flames\Exception\Unserialize;
use Flames\Http\Client;
use GdImage;

/**
 * Class Image
 *
 * This class represents an image object. It provides various methods for working with image files.
 */
class Image
{
    protected const VERSION = 1;

    const FORMAT_PNG = 'png';
    const FORMAT_JPG = 'jpg';

    protected string|null $path;
    protected GdImage|null $image;

    /**
     * Constructor method for the class.
     *
     * @param string|null $path Path to the image file. (Optional)
     *
     * @return void
     */
    public function __construct(string|null $path = null)
    {
        $this->path = $path;

        if ($this->path !== null) {
            $imageData = file_get_contents($this->path);
            $imageGd = imagecreatefromstring($imageData);
            if ($imageGd !== false) {
                $this->image = $imageGd;
            }
        }
    }

    /**
     * Display the image in the specified format.
     *
     * @param string $format The format in which the image should be displayed. Defaults to "png".
     *
     * @return void
     */
    public function show(string $format = self::FORMAT_PNG) : void
    {
        if ($this->image === null) {
            return;
        }

        if ($format === self::FORMAT_PNG) {
            Header::set('Content-Type', 'image/png');
            Header::send();
            imagepng($this->image);
        }
        elseif ($format === self::FORMAT_JPG) {
            Header::set('Content-Type', 'image/jpg');
            Header::send();
            imagejpeg($this->image);
        }
        else {
            imagegd($this->image);
        }
    }

    /**
     * Returns the image data as a string.
     *
     * @return string|null The image data as a string. Returns null if the image is not set.
     */
    public function getString() : string|null
    {
        if ($this->image === null) {
            return null;
        }

        ob_start();
        imagepng($this->image);
        $buffer = ob_get_contents();
        ob_end_clean();

        return $buffer;
    }

    /**
     * Crops the image to the specified dimensions.
     *
     * @param int $x The x-coordinate of the starting point of the crop.
     * @param int $y The y-coordinate of the starting point of the crop.
     * @param int $width The width of the cropped image.
     * @param int $height The height of the cropped image.
     */
    public function crop(int $x, int $y, int $width, int $height)
    {
        if ($this->image === null) {
            return;
        }

        $this->image = imagecrop($this->image, ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height]);
    }

    /**
     * Returns the OCR (Optical Character Recognition) output as text.
     *
     * @return string|null The OCR output as text. Returns null if the OCR process fails or no text is detected.
     */
    public function getText() : string|null
    {
        $tmpDir = (ROOT_PATH . '.cache/ocr-image/');
        if (is_dir($tmpDir) === false) {
            $mask = umask(0);
            mkdir($tmpDir, 0777, true);
            umask($mask);
        }

        $imageHash = Hash::getRandom();
        $tmpPathPng = ($tmpDir . $imageHash . '.png');
        $tmpPathTxt = ($tmpDir . $imageHash . '.txt');
        $this->save($tmpPathPng, 'png');

        chdir($tmpDir);
        exec('tesseract ' . $imageHash . '.png ' . $imageHash . ' --psm 7');
        chdir(ROOT_PATH);
        $ocrData = @file_get_contents($tmpPathTxt);
        unlink($tmpPathPng);
        unlink($tmpPathTxt);
        if ($ocrData === false) {
            return null;
        }
        return trim($ocrData);
    }

    /**
     * Saves the image to the specified path.
     *
     * @param string $path The path where the image will be saved.
     * @param string $format The format in which the image will be saved. Defaults to "png".
     * @return bool Returns true if the image is successfully saved, false otherwise.
     */
    public function save(string $path, string $format = self::FORMAT_PNG)
    {
        ob_start();

        if ($format === self::FORMAT_PNG) {
            imagepng($this->image);
        }
        elseif ($format === self::FORMAT_JPG) {
            imagejpeg($this->image);
        }
        else {
            imagegd($this->image);
        }

        $buffer = ob_get_contents();
        ob_end_clean();

        try {
            file_put_contents($path, $buffer);
            return true;
        } catch (\ErrorException $e) {}

        $dir = dirname($path);
        if (is_dir($dir) === false) {
            $mask = umask(0);
            mkdir($dir, 0777, true);
            umask($mask);
        }

        try {
            file_put_contents($path, $buffer);
            return true;
        } catch (\ErrorException $e) {}

        return false;
    }

    /**
     * Creates an Image object from a given URL.
     *
     * @param string $url The URL of the image to be fetched.
     *
     * @return Image|null The Image object created from the URL. Returns null if the image cannot be fetched or if the response status code is not 200.
     */
    public static function fromUrl(string $url) : Image|null
    {
        $client = new Client(['defaults' => [
            'verify' => false
        ]]);
        $response = $client->request('GET', $url);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $gdImage = (@imagecreatefromstring($response->getBody()));
        if ($gdImage === false) {
            return null;
        }

        $image = new Image();
        $image->image = $gdImage;
        return $image;
    }

    /**
     * Creates a new Image object from a string representation of an image.
     *
     * @param string $string The string representation of an image.
     * @return Image|null The newly created Image object. Returns null if the string is not a valid image representation.
     */
    public static function fromString(string $string) : Image|null
    {
        $gdImage = (@imagecreatefromstring($string));
        if ($gdImage === false) {
            return null;
        }

        $image = new Image();
        $image->image = $gdImage;
        return $image;
    }

    /**
     * Serializes the object into an array.
     *
     * @return array The serialized object data.
     */
    public function __serialize(): array
    {
        return [
            'version' => self::VERSION,
            'path'    => $this->path,
            'image'   => $this->getString()
        ];
    }

    /**
     * Unserializes the object from an array.
     *
     * @param array $data The array containing the serialized data.
     *
     * @throws Unserialize If the serialized data version is outdated.
     */
    public function __unserialize(array $data): void
    {
        if ($data['version'] !== self::VERSION) {
            throw new Unserialize('Outdated serialize data version.');
        }

        $this->path = $data['path'];

        $imageGd = imagecreatefromstring($data['image']);
        if ($imageGd !== false) {
            $this->image = $imageGd;
        }
    }
}