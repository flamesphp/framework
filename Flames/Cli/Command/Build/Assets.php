<?php

namespace Flames\Cli\Command\Build;

use Flames\Cli;
use Flames\Cli\Command\Build\Assets\Automate;
use Flames\Cli\Command\Build\Assets\Data;
use Flames\Client\View;
use Flames\Environment;
use Flames;
use Flames\Kernel;
use http\Env;

/**
 * Class Assets
 *
 * This class is responsible for handling the build assets data for the Flames CLI command.
 *
 * @internal
 */
final class Assets
{
    const BASE_PATH = (APP_PATH . 'Client/Resource/Build/');

    protected static array $defaultFiles = [
        Flames\Kernel\Client\Error::class,
        Flames\Kernel\Client::class,
        Flames\Connection\Client::class,
        Flames\Collection\Strings::class,
        Flames\Collection\Bools::class,
        Flames\Collection\Ints::class,
        Flames\Collection\Floats::class,
        Flames\Collection\Arr::class,
        Flames\Php::class,
        Flames\Js::class,
        Flames\Cli::class,
        Flames\RequestData::class,
        Flames\Kernel\Route::class,
        Flames\Browser\Page::class,
        Flames\Router::class,
        Flames\Json::class,
        Flames\Event\Route::class,
        Flames\Event\Ready::class,
        Flames\Event\Page::class,
        Flames\Event\Native::class,
        Flames\Router\Parser::class,
        Flames\Header\Client::class,
        Flames\Coroutine\Timeout::class,
        Flames\Element::class,
        Flames\Element\Event::class,
        Flames\Money\Client::class,
        Flames\Http\Client\Client::class,
        Flames\Http\Async\Request\Client::class,
        Flames\Http\Async\Response\Client::class,
        Flames\Http\Code::class,
        Flames\Event\Element\Click::class,
        Flames\Event\Element\Change::class,
        Flames\Event\Element\Input::class,
        Flames\Event\Element\KeyDown::class,
        Flames\Event\Element\KeyUp::class,
        Flames\Event\Element\Focus::class,
        Flames\Kernel\Client\Dispatch::class,
        Flames\Js\Module::class,
        Flames\Client\Os::class,
        Flames\Client\Platform::class,
        Flames\Client\Browser::class,
        Flames\Client\UserAgentParser::class,
        Flames\Kernel\Client\Dispatch\Tag::class,
        Flames\Client\Tag::class,
        Flames\Element\Shadow::class,
        Flames\Kernel\Client\Service\Keyboard::class,
        Flames\Kernel\Client\Service\Clipboard::class,
        Flames\Client\Keyboard::class,
        Flames\Client\Keyboard\Event::class,
        Flames\Client\Clipboard::class,
        Flames\Client\Clipboard\Event::class,
        Flames\Event\Clipboard\Paste::class,
        Flames\FunctionEx::class,
        Flames\Cache\Memory\Client::class,
        Flames\Cookie\Client::class,
        Flames\DateTime::class,
        Flames\Date\TimeZone\Client::class,
        Flames\Kernel\Client\Dispatch\Native::class,
        Flames\Client\Native::class,
        Flames\Client\Browser\DevTools::class,
        Flames\Client\Shell::class,
        Flames\Event\Native\Shell::class,
    ];

    protected static array $clientMocks = [
        Flames\Kernel\Client::class,
        Flames\Http\Client\Client::class,
        Flames\Http\Async\Request\Client::class,
        Flames\Http\Async\Response\Client::class,
        Flames\Connection\Client::class,
        Flames\Header\Client::class,
        Flames\Money\Client::class,
        Flames\Cache\Memory\Client::class,
        Flames\Cookie\Client::class,
        Flames\Date\TimeZone\Client::class,
    ];

    protected bool $debug = false;
    protected bool $auto = false;

    protected bool $swfExtension = false;

    public function __construct($data)
    {
        if ($data->option->contains('auto') === true) {
            $this->auto = true;
        }
    }
    /**
     * Run the application build.
     *
     * @param bool $debug (optional) Determines whether the application should run in debug mode. Defaults to false.
     * @return bool Returns true if the application ran successfully, otherwise returns false.
     */
    public function run(bool $debug = false) : bool
    {
        // Skip build if re-request from client after timeout
        if ($this->auto === true) {
            if (Cli::isCli() === false && isset($_GET['timeout']) === true && $_GET['timeout'] === 'true') {
                $this->verifyAuto();
                return false;
            }
        }

        $this->debug = $debug;

        $this->createFolder();

        try {
            $stream = fopen(self::BASE_PATH . 'Flames.js', 'w');
        } catch (\Exception $e) {
            $mask = umask(0);
            @mkdir(self::BASE_PATH, 0777, true);
            umask($mask);
            $stream = fopen(self::BASE_PATH . 'Flames.js', 'w');
        }

        $this->injectStructure($stream);
        $this->injectExtensions($stream);
        $this->injectDefaultFiles($stream);
        $this->finish($stream);
        $this->verifyAuto();

        return true;
    }

    /**
     * Create the base resource folder if it doesn't exist.
     *
     * @return void
     */
    protected function createFolder() : void
    {
        if ($this->debug === true) {
            echo ('Verifying base resource folder ' . substr(self::BASE_PATH, strlen(ROOT_PATH)) . "\n");
        }

        if (is_dir(self::BASE_PATH) === false) {
            if ($this->debug === true) {
                echo ('Creating base resource folder ' . self::BASE_PATH . "\n");
            }

            $mask = umask(0);
            mkdir(self::BASE_PATH, 0777, true);
            umask($mask);
        }
    }

    /**
     * Injects the structure of the JavaScript system into the given stream.
     *
     * @param resource $stream The stream to inject the structure into.
     * @return void
     */
    protected function injectStructure($stream) : void
    {
        if ($this->debug === true) {
            echo ("Inject structure javascript system\n");
        }

        $this->swfExtension = false;
        $extensions = Environment::get('CLIENT_EXTENSIONS');
        if ($extensions !== null) {
            $extensions = explode(',', strtolower($extensions));
            if (in_array('swf', $extensions) === true) {
                $this->swfExtension = true;
            }
        }

        $dateTimezone = trim((string)Environment::get('DATE_TIMEZONE'));
        if ($dateTimezone === '') {
            $dateTimezone = 'UTC';
        }
        $dateTimezone = rawurlencode($dateTimezone);
        $appNativeKey = (string)Cli\Command\Build\App\Native::getAppNativeKey();

        $unsupported = @file_get_contents(ROOT_PATH . 'App/Client/Resource/Event/Unsupported.js');
        $engine = str_replace([
            '{{ environment }}',
            '{{ dumpLocalPath }}',
            '{{ dateTimeZone }}',
            '\'{{ asyncRedirect }}\'',
            '\'{{ swfExtension }}\'',
            '\'{{ composer }}\'',
            '\'{{ unsupported }}\';',
            '{{ appNativeKey }}',
        ], [
            rawurlencode(Environment::get('ENVIRONMENT')),
            rawurlencode(Environment::get('DUMP_LOCAL_PATH')),
            $dateTimezone,
            ((Environment::get('CLIENT_ASYNC_REDIRECT') === true) ? 'true' : 'false'),
            (($this->swfExtension === true) ? 'true' : 'false'),
            ((FLAMES_COMPOSER === true) ? 'true' : 'false'),
            ('(function(){' . $unsupported . '})();'),
            $appNativeKey
        ], file_get_contents(FLAMES_PATH . 'Kernel/Client/Engine/Flames.js'));

        fwrite($stream, $engine);
        fwrite($stream, 'window.Flames.onReady=function(){');
    }


    protected function injectExtensions($stream): void
    {
        if ($this->debug === true) {
            echo ("Inject default loaded extensions\n");
        }

        $extensions = Environment::get('CLIENT_EXTENSIONS');
        if ($extensions !== null) {
            $extensions = explode(',', strtolower($extensions));

            $eval = '';
            foreach ($extensions as $extension) {
                if ($extension === 'swf') {
                    continue;
                }
                if ($eval !== '') {
                    $eval .= 'usleep(1);';
                }
                $eval .= ('dl(\'' . $extension . '.so\');');
            }

            if ($eval !== '') {
                fwrite($stream, 'Flames.Internal.evalBase64(\'' . base64_encode($eval) . '\');');
            }

            if ($this->swfExtension === true) {
                fwrite($stream, '
                    var xmlhttp = new XMLHttpRequest();
                    xmlhttp.open(\'GET\', \'https://cdn.jsdelivr.net/gh/flamesphp/cdn@' . Kernel::CDN_VERSION . '/swf/swf.js\');
                    xmlhttp.onreadystatechange = function() { if ((xmlhttp.status == 200) && (xmlhttp.readyState == 4)) { eval(xmlhttp.responseText); }};
                    xmlhttp.send();
                ');
            }
        }
    }


    protected function loadPhpFile(string $path): string
    {
        $data = @file_get_contents($path);
        return str_replace(['<?php'], '', $data);
    }

    protected function parseMockFile(string $fullClass, $data)
    {
        $split = explode('\\', $fullClass);
        $oldNamespace = '';
        $countSplitOldNamespace = (count($split) - 1);
        for ($i = 0; $i < $countSplitOldNamespace; $i++) {
            $oldNamespace .= ($split[$i] . '\\');
        }
        $oldNamespace = substr($oldNamespace, 0, -1);

        $newNamespace = '';
        $countSplitNewNamespace = (count($split) - 2);
        for ($i = 0; $i < $countSplitNewNamespace; $i++) {
            $newNamespace .= ($split[$i] . '\\');
        }
        $newNamespace = substr($newNamespace, 0, -1);

        $oldClass = $split[count($split) - 1];
        $newClass = $split[count($split) - 2];

        $data = str_replace([
            'namespace ' . $oldNamespace,
            'class ' .$oldClass
        ], [
            'namespace ' . $newNamespace,
            'class ' . $newClass
        ], $data);

        return $data;
    }

    /**
     * Injects default files into the provided stream.
     *
     * @param resource $stream The stream to inject the files into.
     * @return void
     */
    protected function injectDefaultFiles($stream) : void
    {
        $virtual = (
            $this->loadPhpFile(FLAMES_PATH . 'Kernel/Client/Virtual.php') .
            $this->loadPhpFile(FLAMES_PATH . 'Dump/Client.php')
        );

        $virtualFilesBuffer = '';
        $virtualFilesBuffer = $this->mountVirtualDefaultFiles($virtualFilesBuffer);

        $clientFilesBufferMetadata = $this->mountVirtualClientFilesMetadata($virtualFilesBuffer);
        $virtualFilesBuffer = $clientFilesBufferMetadata['virtualFilesBuffer'];

        fwrite($stream, 'window.Flames.Internal.eventTriggers = Flames.Internal.unserialize(atob(\'' . base64_encode(serialize($clientFilesBufferMetadata['events']->toArray())) . '\'));');

        $virtualConstructsBuffer = 'private static $constructors = [';
        foreach ($clientFilesBufferMetadata['staticConstructors'] as $constructor) {
            $virtualConstructsBuffer .= ('\'' . $constructor . '\',');
        }

        $virtualTagsBuffer = 'private static $tags = [';
        foreach ($clientFilesBufferMetadata['tags'] as $tag) {
            $virtualTagsBuffer .= ('\'' . $tag->uid . '\' => \'' . $tag->class . '\',');
        }

        $virtualViewsBuffer = 'private static $views = [';
        if (Cli\Command\Build\Assets\Template::isTemplateExtension() === true) {
            foreach ($clientFilesBufferMetadata['views'] as $twigNs => $viewData) {
                $virtualViewsBuffer .= ('\'' . $twigNs . '\' => \'' . base64_encode($viewData) . '\',');
            }
        }

        $virtualFilesBuffer = ('private static $buffers = [' . $virtualFilesBuffer);
        $virtual = str_replace(
            [
                'private static $buffers = [',
                'private static $constructors = [',
                'private static $tags = [',
                'private static $views = ['
            ], [
                $virtualFilesBuffer,
                $virtualConstructsBuffer,
                $virtualTagsBuffer,
            $virtualViewsBuffer
            ],
            $virtual);

        $virtual .= $this->parseMockFile(Flames\AutoLoad\Client::class, $this->loadPhpFile(FLAMES_PATH . 'AutoLoad/Client.php'));
        fwrite($stream, 'Flames.Internal.evalBase64(\'' . base64_encode($virtual) . '\');');

        $autorun = '
        \Flames\AutoLoad::run();
        function Arr(mixed $value=null):\Flames\Collection\Arr{if($value instanceof \Flames\Collection\Arr){return $value;}return new \Flames\Collection\Arr($value);}
        \Flames\Kernel\Client\Dispatch::run();
';
        fwrite($stream, "var data=Flames.Internal.evalBase64('" . base64_encode($autorun). "');if (data!==null){dump(data);}");

        foreach ($clientFilesBufferMetadata->tags as $tag) {
            fwrite($stream, "window.eval(atob('" . base64_encode($tag->eval) . "'));");
        }

        fwrite($stream, '};');
    }

    protected function mountVirtualDefaultFiles($virtualFilesBuffer)
    {
        $defaultFiles = self::$defaultFiles;
        $clientMocks = self::$clientMocks;

        if (Cli\Command\Build\Assets\Template::isTemplateExtension() === true) {
            $defaultFiles = Cli\Command\Build\Assets\Template::injectDefaultFiles($defaultFiles);
            $clientMocks = Cli\Command\Build\Assets\Template::injectClientMocks($clientMocks);
        }

        foreach ($defaultFiles as $defaultFile) {
            $phpFile = $this->loadPhpFile(FLAMES_PATH . substr(str_replace('\\', '/', $defaultFile), 6) . '.php');
            if (in_array($defaultFile, $clientMocks) === true) {
                $phpFile = $this->parseMockFile($defaultFile, $phpFile);

                $split = explode('\\', $defaultFile);
                $defaultFile = '';
                for ($i = 0; $i < count($split) - 1; $i++) {
                    $defaultFile .= $split[$i] . '\\';
                }
                $defaultFile = substr($defaultFile, 0, -1);
            }

            if ($this->debug === true) {
                echo ('Compile ' . $defaultFile . ".php\n");
            }

            $virtualFilesBuffer .= '\'' . sha1($defaultFile) . '\'=>\'' . base64_encode($phpFile) . '\',';
        }

        return $virtualFilesBuffer;
    }

    protected function mountVirtualClientFilesMetadata($virtualFilesBuffer)
    {
        $useViews = Cli\Command\Build\Assets\Template::isTemplateExtension();

        $staticConstructors = Arr();
        $events = Arr();
        $tags = Arr();
        $views = Arr();

        $modules = ['Event', 'Component', 'Service', 'Controller', 'Tag', 'View'];
        foreach ($modules as $module) {
            $clientPath = (APP_PATH . 'Client/' . $module);
            if (is_dir($clientPath) === true) {
                $files = $this->getDirContents($clientPath);
                foreach ($files as $file) {
                    if (is_dir($file) === true) {
                        continue;
                    }

                    if ($module === 'View') {
                        if ($useViews === false) {
                            continue;
                        }

                        $fileData = (string)file_get_contents($file);
                        while (str_contains($fileData, '  ') === true) {
                            $fileData = str_replace('  ', ' ', $fileData);
                        }
                        if (str_starts_with($fileData, '{% export true %}') === false) {
                            continue;
                        }

                        $twigNs = substr((str_replace('\\', '/', substr($file, strlen(ROOT_PATH), strlen($file)))), 16);
                        $views[$twigNs] = $fileData;
                        continue;
                    }

                    if ($module === 'Controller' || $module === 'Component' || $module === 'Tag') {
                        $class = (str_replace('/', '\\', substr($file, strlen(ROOT_PATH), -4)));
                        $data = Data::mountData($class);
                        $attributes = $this->verifyAttributes($data, $class);

                        if ($module !== 'Tag') {
                            foreach ($attributes->click as $trigger) {
                                $events[] = $trigger;
                            }
                            foreach ($attributes->change as $trigger) {
                                $events[] = $trigger;
                            }
                            foreach ($attributes->input as $trigger) {
                                $events[] = $trigger;
                            }
                        }

                        if ($data->staticConstruct === true) {
                            $staticConstructors[] = sha1($class);
                        }

                        if ($module === 'Tag') {
                            $tags[] = $this->getTagData($class);
                        }
                    }

                    $class = str_replace('/', '\\', substr($file, strlen(ROOT_PATH), -4));
                    if ($this->debug === true) {
                        echo ('Compile module ' . strtolower($module) . ': ' . $class . "\n");
                    }

                    $phpFile = $this->loadPhpFile($file);
                    $virtualFilesBuffer .= '\'' . sha1($class) . '\'=>\'' . base64_encode($phpFile) . '\',';
                }
            }
        }

        $data = Arr();
        $data->virtualFilesBuffer = $virtualFilesBuffer;
        $data->staticConstructors = $staticConstructors;
        $data->events = $events;
        $data->tags = $tags;
        $data->views = $views;

        return $data;
    }

    protected function getTagData(string $class)
    {
        $tag = Arr([
            'class' => $class,
            'uid' => null,
            'path' => null,
            'content' => null
        ]);

        $reflection = new \ReflectionClass($class);
        $methods = $reflection->getMethods();
        foreach ($methods as $method) {
            if ($method->name === '__constructStatic') {
                continue;
            }

            $attributes = $method->getAttributes();
            foreach($attributes as $attribute) {
                $attributeName = $attribute->getName();

                if ($attributeName === \Flames\Client\Tag::class) {
                    $arguments = $attribute->getArguments();
                    if (isset($arguments['path'])) {
                        $tag->path = $arguments['path'];
                    }
                    if (isset($arguments['uid'])) {
                        $tag->uid = $arguments['uid'];
                    }
                }
            }
        }

        if ($tag->uid === null) {
            throw new \Exception('Missing tag uid.');
        }
        if ($tag->path === null) {
            throw new \Exception('Missing tag path.');
        }

        $fullPath = (APP_PATH . 'Client/View/' . $tag->path);
        if (file_exists($fullPath) === false) {
            throw new \Exception('View path ' . $fullPath . ' does not exists.');
        }


        $loader = new \Flames\Template\Loader\FilesystemLoader(APP_PATH . 'Client/View/');
        $twig = new \Flames\Template\Environment($loader, []);

        $tag->content = $twig->render($tag->path, []);

        $clientClassName = ('__Flames_Tag_' . str_replace('\\', '_', substr($class, 15)));

        $tag->eval = "
            var template = document.createElement('template');
            template.innerHTML = `
            " . $tag->content . "
`;

            Flames.Internal.tags['" . $tag->uid . "'] = {
                template: template,
                shadows: []
            };
            
            class " . $clientClassName . " extends HTMLElement {
                constructor() {
                    super();
                    
                    this.attachShadow({mode: 'open'});
                    this.shadowRoot.appendChild(Flames.Internal.tags['" . $tag->uid . "'].template.content.cloneNode(true));
    
                    var shadowId = Flames.Internal.tags['" . $tag->uid . "'].shadows.length;
                    Flames.Internal.tags['" . $tag->uid . "'].shadows[shadowId] = this.shadowRoot;
                    Flames.Internal.evalBase64(btoa('\\\\Flames\\\\Kernel\\\\Client\\\\Dispatch\\\\Tag::run(\'" . $tag->uid . "\',\'' + shadowId + '\');'));
                }
    
                connectedCallback() {
                    var shadowsCount = Flames.Internal.tags['" . $tag->uid . "'].shadows.length;
                    var shadowId = null;
                    
                    for (var i = 0; i < shadowsCount; i++) {
                        if (this.shadowRoot === Flames.Internal.tags['" . $tag->uid . "'].shadows[i]) {
                            shadowId = i;
                            break;
                        }
                    }

                    Flames.Internal.evalBase64(btoa('\\\\Flames\\\\Kernel\\\\Client\\\\Dispatch\\\\Tag::render(\'" . $tag->uid . "\',\'' + shadowId + '\');'));
                }
            }
            
            window.customElements.define('" . $tag->uid . "', " . $clientClassName . ");
        ";


        return $tag;
    }

    /**
     * Verifies the attributes of a given file.
     *
     * @param array $data The data to verify attributes for.
     * @param string $class The class to verify attributes for.
     * @return Flames\Collection\Arr
     */
    protected function verifyAttributes($data, string $class): Flames\Collection\Arr
    {
        $attributes = Arr([
            'click'  => Arr(),
            'change' => Arr(),
            'input'  => Arr(),
        ]);

        foreach ($data->methods as $method) {
            if ($method->type === 'click') {
                $method->class = $class;
                $attributes->click[] = $method;
            }
            elseif ($method->type === 'change') {
                $method->class = $class;
                $attributes->change[] = $method;
            }
            elseif ($method->type === 'input') {
                $method->class = $class;
                $attributes->input[] = $method;
            }
        }

        return $attributes;
    }

    /**
     * Retrieves the contents of a directory recursively.
     *
     * @param string $dir The directory to retrieve contents from.
     * @param array $results (Optional) An array reference to store the results. Defaults to an empty array.
     *
     * @return void
     */
    protected function getDirContents($dir, &$results = array()) {
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

//    protected function injectEnvironment($stream): void
//    {
//        $localPath = Environment::get('DUMP_LOCAL_PATH');
//        if (str_ends_with($localPath, '\\') || str_ends_with($localPath, '/')) {
//            $localPath = substr($localPath, 0, -1);
//        }
//        if (str_contains($localPath, '\\') === true) {
//            $localPath = str_replace('\\', '\\\\', $localPath);
//        }
//        fwrite($stream, "window.Flames.Internal.dumpLocalPath='" . $localPath . "';");
//        $autoBuildClient = Environment::get('CLIENT_AUTO_BUILD');
//        if ($autoBuildClient === true) {
//            fwrite($stream, "window.Flames.Internal.clientAutoBuildClient=true;");
//        } else {
//            fwrite($stream, "window.Flames.Internal.clientAutoBuildClient=false;");
//        }
//        $asyncRedirectClient = Environment::get('CLIENT_ASYNC_REDIRECT');
//        if ($asyncRedirectClient === true) {
//            fwrite($stream, "window.Flames.Internal.clientAsyncRedirect=true;");
//        } else {
//            fwrite($stream, "window.Flames.Internal.clientAsyncRedirect=false;");
//        }
//        $dateTimezone = trim((string)Environment::get('DATE_TIMEZONE'));
//        if ($dateTimezone === '') {
//            $dateTimezone = 'UTC';
//        }
//        fwrite($stream, "window.Flames.Internal.dateTimezone='" . $dateTimezone . "';");
//    }

    /**
     * Finishes the stream by closing it.
     *
     * @param resource $stream The stream to be finished.
     *
     * @return void
     */
    protected function finish($stream) : void
    {
        fwrite($stream, "\n\n");
        fclose($stream);

        if ($this->debug === true) {
            echo ("\nAssets build successfully\n");
        }
    }

    protected function verifyAuto()
    {
        if ($this->auto === true) {
            $automate = new Automate();
            $automate->run($this->debug);
        }
    }
}