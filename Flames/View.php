<?php

namespace Flames;

use Flames\Cli\Command\Build\Assets\Automate;
use Flames\Client\Builder;
use Flames\Collection\Arr;
use Flames\Collection\Strings;
use http\Env;

/**
 * Class View
 *
 * The View class is responsible for rendering HTML templates or raw HTML content. It uses the Twig Based template engine.
 */
class View
{
    protected string|null $path = null;
    protected string|null $html = null;

    /**
     * Renders the data into a string or returns null if no rendering is necessary.
     *
     * @param Arr|array|null $data The data to be rendered. It can be an instance of Arr or an array.
     *                            If null is provided, an empty array will be used.
     *
     * @return string|null The rendered data as a string. If no rendering is necessary, null is returned.
     */
    public function render(Arr|array $data = null) : string|null
    {
        if ($data instanceof Arr) {
            $data = (array)$data;
        } elseif ($data === null) {
            $data = [];
        }

        if ($this->path !== null) {
            return $this->renderFile($data);
        }
        elseif ($this->html !== null) {
            return $this->renderHtml($data);
        }

        return null;
    }

    /**
     * Render the HTML content using a Template Engine and return the post-rendered result.
     *
     * @param Arr|array|null $data The data to be passed to the template for rendering. This can be an associative array or an instance of the Arr class.
     * @return string The post-rendered HTML content.
     */
    protected function renderHtml(Arr|array $data = null)
    {
        $loader = new Template\Loader\ArrayLoader([
            'index' => $this->html,
        ]);
        $twig = new Template\Environment($loader);
        $htmlRendered = $this->postRender($twig->render('index', $data), $data);
    }

    /**
     * Render the content of a template file using a Template Engine and return the post-rendered result.
     *
     * @param Arr|array|null $data The data to be passed to the template for rendering. This can be an associative array or an instance of the Arr class.
     * @return string The post-rendered content of the template file.
     */
    protected function renderFile(Arr|array $data = null)
    {
        $loader = new Template\Loader\FilesystemLoader(APP_PATH . 'Client/View/');
        $twig = new Template\Environment($loader, [
//            'cache' => (ROOT_PATH . '.cache/view-twig'),
        ]);

        return $this->postRender($twig->render($this->path, $data), $data);
    }

    /**
     * Add a view path to the object's path property.
     *
     * @param string $path The path of the view file to be added.
     * @return void
     * @throws \Exception Throws an exception if the view path does not exist.
     */
    public function addView(string $path) : void
    {
        $fullPath = (APP_PATH . 'Client/View/' . $path);
        if (file_exists($fullPath) === false) {
            throw new \Exception('View path ' . $fullPath . ' does not exists.');
        }
        $this->path = $path;
    }

    /**
     * Adds HTML to the existing HTML content.
     *
     * @param string $html The HTML content to add.
     *
     * @return void
     */
    public function addHtml(string $html) : void
    {
        $this->html = $html;
    }

    /**
     * Performs post-render operations on the provided HTML content.
     *
     * @param string $html The HTML content to perform post-render operations on.
     * @param array $data The data to be serialized and injected into the HTML content.
     *
     * @return string The modified HTML content after performing post-render operations.
     *
     * @throws \Error If the provided HTML content is missing the closing body HTML tag.
     */
    protected function postRender(string $html, array $data) : string
    {
        if (Environment::get('CLIENT_ENGINE_ENABLED') === false) {
            return $html;
        }

        $bodyCloseTag = '</body>';
        if (str_contains($html, $bodyCloseTag) === false) {
            throw new \Error('Missing body html tag.');
        }

        $hash = Kernel::VERSION;
        $clientResource = (APP_PATH . 'Client/Resource/Build/Flames.js');
        if (file_exists($clientResource) === true) {
            $hash .= ('.' . filemtime($clientResource));
        }

        $token = base64_encode (
            rawurlencode(Kernel::CDN_VERSION) . '|' .
            rawurlencode((string)Environment::get('CLIENT_CDN')) . '|' .
            sha1(Environment::get('APP_KEY') . $hash) . '|' .
            serialize($data)
        );
        $html = str_replace($bodyCloseTag, "\t<flames hidden>" .
            $token .
            "</flames>\n\t" . $bodyCloseTag, $html);
        if (Environment::get('AUTO_BUILD_CLIENT') === true) {
            $automate = new Automate();
            $html = str_replace($bodyCloseTag, "\t<flames-autobuild>" . $automate->getCurrentHash() . "</flames-autobuild>\n\t" . $bodyCloseTag, $html);
        }
        $flamesEngine = '<script async src="//cdn.jsdelivr.net/gh/flamesphp/cdn@' . Kernel::CDN_VERSION . '/flames.js"></script>';
        $html = str_replace($bodyCloseTag, "\t" . $flamesEngine . "\n\t" . $bodyCloseTag, $html);


        $sanizateOutput = Environment::get('CLIENT_SANIZATE_OUTPUT');
        if ($sanizateOutput === true) {
            $html = Html::sanitize($html);
        }

        return $html;
    }
}