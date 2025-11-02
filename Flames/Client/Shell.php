<?php

namespace Flames\Client;

use Flames\Js;
use Flames\Kernel;
use Flames\Kernel\Client\Dispatch\Native as DispatchNative;

class Shell
{
    protected ?\Closure $delegate;
    protected ?string $output;

    protected bool $done = false;
    protected bool $error = false;

    /**
     * Constructor method for the class.
     *
     * @param string $command The command to be executed.
     */
    public function __construct(?string $command = null)
    {
        if (self::isNativeBuild() === false || $command === null) {
            return;
        }

        $myThis = $this;
        $window = Js::getWindow();
        $appNativeKey = $window->Flames->Internal->appNativeKey;

        DispatchNative::add('shell', ['appNativeKey' => $appNativeKey, 'command' => base64_encode($command)], function ($data) use ($myThis) {
            if (isset($data->error) === true) {
                $myThis->error = true;
                $window  = Js::getWindow();
                $window->console->error($data->message);
            }

            $myThis->output = $data->message;
            $this->callDelegate();
        });
    }

    public function then(\Closure $delegate): void
    { $this->delegate = $delegate; }

    protected function callDelegate(): void
    {
        if ($this->delegate === null) {
            return;
        }

        try {
            $delegate = $this->delegate;
            $delegate($this->getEvent());
        } catch (\Exception|\Error $e) {
            Kernel\Client\Error::handler($e);
        }
        dump($this->output);
    }

    public function isDone(): bool
    { return $this->done; }

    public function getEvent(): \Flames\Event\Native\Shell
    { return new \Flames\Event\Native\Shell($this->output, !$this->error); }

    protected static function isNativeBuild(): bool
    { return Kernel::isNativeBuild(); }
}