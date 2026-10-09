<?php
declare(strict_types=1);


namespace Flames\Client;

use Flames\Element;
use Flames\Js;
use Flames\Element\Shadow;

/**
 * Represents an Tag.
 *
 * Description for the class
 * @property Shadow|null $shadow
 */
#[\Attribute(\Attribute::TARGET_CLASS, \Attribute::TARGET_METHOD)]
class Tag
{
    private $__shadow__ =  null;

    public function __construct(private $__shadowNative__, $path = null)
    {
        $this->__shadow__ = new Shadow($this->__shadowNative__);
    }

    protected function getShadow(): ?Shadow
    {
        return $this->__shadow__;
    }

    public function __get($key)
    {
        if ($key === 'shadow') {
            return $this->getShadow();
        }

        return null;
    }

    public function onRender() {}
}