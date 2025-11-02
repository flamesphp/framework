<?php

namespace Flames\View;

use Flames\Collection\Arr;
use Flames\Kernel\Client\Virtual;
use Flames\Template;

/**
 * Class View
 *
 * The View class is responsible for rendering HTML templates or raw HTML content. It uses the Twig Based template engine.
 */
class Client
{
    protected static $views = null;

    protected string|null $virtualPath = null;
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

        if ($this->virtualPath !== null) {
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
        return $twig->render('index', $data);
    }

    /**
     * Render the content of a template file using a Template Engine and return the post-rendered result.
     *
     * @param Arr|array|null $data The data to be passed to the template for rendering. This can be an associative array or an instance of the Arr class.
     * @return string The post-rendered content of the template file.
     */
    protected function renderFile(Arr|array $data = null)
    {
        $loader = new Template\Loader\ArrayLoader(self::$views);
        $twig = new Template\Environment($loader);
        return $twig->render($this->virtualPath, $data);
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
        self::setupViews();

        $fullPath = ('Client/View/' . $path);
        if (isset(self::$views[$path]) === false) {
            throw new \Exception('View path ' . $fullPath . ' does not exists.');
        }

        $this->virtualPath = $path;
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

    protected static function setupViews()
    {
        if (self::$views !== null) {
            return;
        }

        self::$views = [];
        $views = Virtual::getViews();
        foreach ($views as $twigNs => $viewData) {
            self::$views[$twigNs] = base64_decode($viewData);
        }
    }
}