<?php

namespace Flames\Cli;

use Flames\Cli\Command\Coroutine;
use Flames\Cli\Command\Install;
use Flames\Cli\Command\Key\Generate as KeyGenerate;
use Flames\Cli\Command\Crypto\Key\Generate as CryptoKeyGenerate;
use Flames\Cli\Command\Build\Assets;
use Flames\Cli\Command\Build\App\StaticEx;
use Flames\Cli\Command\Build\App\Native;
use Flames\Cli\Command\Build\App\Mobile;
use Flames\Cli\Command\Server;
use Flames\Cli\Data;
use Flames\Collection\Arr;

/**
 * Class System
 *
 * Represents the system for executing commands.
 *
 * @internal
 */
final class System
{
    protected static $commands = [
        'install'                   => Install::class,
        'key:generate'              => KeyGenerate::class,
        'crypto:key:generate'       => CryptoKeyGenerate::class,
        'server'                    => Server::class,
        'build:assets'              => Assets::class,
        'build:app:static'          => StaticEx::class,
        'build:app:native'          => Native::class,
        'build:app:mobile'          => Mobile::class,
        'internal:coroutine'        => Coroutine::class
    ];

    protected Arr $data;
    protected bool $debug;

    /**
     * Constructor method for the class.
     *
     * @param Arr|null $data (optional) The data to be assigned to the object. If not provided, data will be fetched from Data::getData().
     * @param bool $debug (optional) Indicates whether debugging is enabled or not. Default is true.
     * @return void
     */
    public function __construct(Arr $data = null, bool $debug = true)
    {
        $this->debug = $debug;

        if ($data === null) {
            $this->data = Data::getData();
            return;
        }

        $this->data = $data;
    }

    /**
     * Run the command.
     *
     * @return bool Indicates if the command was executed successfully.
     */
    public function run() : bool
    {
        if ($this->data->command === null || isset(self::$commands[$this->data->command]) === false) {
            $this->dispatchHelper();
            return false;
        }

        if ($this->data->command === 'internal:coroutine') {
            $this->debug = false;
        }

        $this->dispatchBase();

        if ($this->debug === true) {
            echo ' 
Initializing command ' . $this->data->command . '

';
        }

        $command = new self::$commands[$this->data->command]($this->data);
        $return = $command->run($this->debug);

        if ($this->debug === true) {
            echo "\n";
        }

        return $return;
    }

    /**
     * Dispatch the base command.
     *
     * Displays a logo and information about the command.
     * Only executed if the debug mode is enabled.
     */
    protected function dispatchBase()
    {
        if ($this->debug === true) {
            $buffer = '
    ███████╗██╗      █████╗ ███╗   ███╗███████╗███████╗
    ██╔════╝██║     ██╔══██╗████╗ ████║██╔════╝██╔════╝
    █████╗  ██║     ███████║██╔████╔██║█████╗  ███████╗
    ██╔══╝  ██║     ██╔══██║██║╚██╔╝██║██╔══╝  ╚════██║
    ██║     ███████╗██║  ██║██║ ╚═╝ ██║███████╗███████║
    ╚═╝     ╚══════╝╚═╝  ╚═╝╚═╝     ╚═╝╚══════╝╚══════╝

    𝗖𝗿𝗲𝗮𝘁𝗲𝗱 𝗯𝘆 𝗚𝗮𝗯𝗿𝗶𝗲𝗹 \'𝗞𝗮𝘇𝘇\' 𝗠𝗼𝗿𝗴𝗮𝗱𝗼
    Github: https://github.com/flamesphp/flames
    Docs:   https://flamesphp.com   
    
    _________________________________________________________________ 
';
            echo $buffer;
        }
    }

    /**
     * Dispatch helper method for executing commands when the specific command is not found or is null.
     *
     * @return void
     */
    protected function dispatchHelper() : void
    {
        $this->dispatchBase();

        if ($this->debug === true) {
            $buffer = '
Available commands:
  * install                                | install
  * install --nokey                        | install without generate unique key
  * install --nocryptographykey            | install without generate cryptography unique key
  * install --noexample                    | install without example project
  * key:generate                           | create or update project unique key
  * server                                 | run a development server
  * server {host}:{port}                   | run a development server at specific host (default 0.0.0.0) and port (default 80)
  * server -host={host}                    | run a development server at specific host (default 0.0.0.0)
  * server -port={port}                    | run a development server at specific port (default 80)
  * build:assets                           | build clientside assets, like controllers, events, components and resource
  * build:app:static                       | build complete app as static html pages
  * build:app:static --cloudflare          | build complete app as static html pages for CloudFlare Pages
  * build:app:native                       | build app webview for linux or windows
  * build:app:native --linux               | build app webview for linux
  * build:app:native --windows             | build app webview for windows
  * build:app:native --windows --installer | build app webview installer for windows
';
            echo $buffer;
        }
    }
}