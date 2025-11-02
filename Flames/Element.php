<?php

namespace Flames;

use Exception;
use Flames\Collection\Arr;
use Flames\Collection\Strings;
use Flames\Element\Shadow;

/**
 * Represents an HTML element.
 *
 * Description for the class
 * @property string|null $tag
 * @property string|null $html
 * @property string|null $value
 * @property string|null $checked
 * @property bool|null $visible
 * @property Element|null $parent
 * @property Element\Event|null $event
 */
class Element
{
    private string|null $uid = null;
    private $element = null;
    private Element\Event|null $event = null;

    /**
     * Class Constructor.
     *
     * @return void
     * @throws Exception If the method is called on the server module.
     *
     */
    public function __construct()
    {
        if (Kernel::MODULE === 'SERVER') {
            throw new Exception('Method only works on client.');
        }
    }


    private function setElementNative($element): void
    {
        $this->element = $element;
    }

    /**
     * Magic getter method.
     *
     * @param string $key The key to retrieve the value for.
     *
     * @return mixed|null The value for the specified key, or null if the key does not exist.
     *
     * @throws Exception If the method is called on the server module.
     */
    public function __get(string $key) : mixed
    {
        $key = strtolower($key);
        if ($key === 'value') {
            return $this->getValue();
        }
        elseif ($key === 'html') {
            return $this->getHtml();
        }
        elseif ($key === 'tag') {
            return Strings::toLower($this->element->tagName);
        }
        elseif ($key === 'checked') {
            return $this->getChecked();
        }
        elseif ($key === 'event') {
            if ($this->event === null && $this->element !== null) {
                $this->event = new Element\Event($this->element);
            }
            return $this->event;
        }
        elseif ($key === 'parent') {
            return $this->getParent();
        }
        elseif ($key === 'element') {
            return $this->element;
        }
        elseif ($key === 'visible') {
            return $this->isVisible();
        }
        elseif ($key === 'shadow') {
            return $this->getShadow();
        }

        return null;
    }

    /**
     * Magic getter method.
     *
     * @param string $key The key to set the value for.
     * @param string $key The value to set.
     *
     * @throws Exception If the method is called on the server module.
     */
    public function __set(string $key, mixed $value) : void
    {
        $key = strtolower($key);
        if ($key === 'value') {
            $this->setValue($value);
        }
        elseif ($key === 'html') {
            $this->setHtml((string)$value);
        }
    }

    /**
     * Checks if the element has a specific class.
     *
     * @param string $class The class to check.
     *
     * @return bool True if the instance has the class, false otherwise.
     *
     */
    public function hasClass(string $class) : bool
    {
        if ($this->element === null) {
            return false;
        }

        $class = strtolower($class);

        $classes = strtolower($this->getAttribute('class'));
        if ($classes === null) {
            return false;
        }

        return in_array($class, explode(' ', $classes));
    }

    /**
     * Adds a class to the element.
     *
     * @param string $class The class to be added.
     *
     * @return void
     *
     */
    public function addClass(string $class) : void
    {
        if ($this->element === null) {
            return;
        }

        $class = strtolower($class);
        if ($this->hasClass($class) === true) {
            return;
        }

        $classes = $this->getAttribute('class');
        if ($classes === null) {
            $classes = '';
        }

        $classes = explode(' ', strtolower($classes));
        $classes[] = $class;
        $classes = implode(' ', $classes);
        $this->setAttribute('class', $classes);
    }

    /**
     * Remove a class from the element.
     *
     * @param string $class The class name to be removed.
     *
     * @return void
     *
     */
    public function removeClass(string $class) : void
    {
        if ($this->element === null) {
            return;
        }

        $class = strtolower($class);
        if ($this->hasClass($class) === false) {
            return;
        }

        $classes = $this->getAttribute('class');
        if ($classes === null) {
            $classes = '';
        }

        $classes = explode(' ', strtolower($classes));

        $_classes = [];
        foreach ($classes as $_class) {
            if ($_class !== $class) {
                $_classes[] = $_class;
            }
        }
        $classes = implode(' ', $_classes);
        $this->setAttribute('class', $classes);
    }

    /**
     * Toggles a class on the element.
     *
     * @param string $class The class to be toggled.
     *
     * @return void
     */
    public function toogleClass(string $class) : void
    {
        if ($this->element === null) {
            return;
        }

        $class = strtolower($class);
        if ($this->hasClass($class) === true) {
            $this->removeClass($class);
            return;
        }

        $this->addClass($class);
    }

    /**
     * Checks if the element has a specific attribute.
     *
     * @param string $attribute The attribute to check for.
     *
     * @return bool Returns true if the element has the specified attribute, otherwise false.
     *
     */
    public function hasAttribute(string $attribute) : bool
    {
        return ($this->getAttribute($attribute) !== null);
    }

    /**
     * Get the value of a specific attribute.
     *
     * @param string $attribute The name of the attribute.
     *
     * @return string|null The value of the attribute, or null if it doesn't exist.
     *
     */
    public function getAttribute(string $attribute) : string|null
    {
        if ($this->element === null) {
            return null;
        }

        $attribute = strtolower($attribute);
        return $this->element->getAttribute($attribute);
    }

    /**
     * Sets the value of an attribute.
     *
     * @param string $attribute The name of the attribute.
     * @param string $value The value to set for the attribute.
     *
     * @return void
     */
    public function setAttribute(string $attribute, string $value) : void
    {
        if ($this->element === null) {
            return;
        }

        $attribute = strtolower($attribute);
        $this->element->setAttribute($attribute, $value);
    }

    /**
     * Get the value of a specific style.
     *
     * @param string $style The name of the style.
     *
     * @return string|null The value of the style, or null if it doesn't exist.
     *
     */
    public function getStyle(string $style) : string|null
    {
        if ($this->element === null) {
            return null;
        }

        $value = $this->element->style->{$style};

        if ($value === null) {
            $style = $this->translateStyleCssToCammel($style);
            $value = $this->element->style->{$style};
        }

        return $value;
    }

    private function translateStyleCssToCammel(string $style)
    {
        $style = strtolower($style);

        while (Strings::startsWith($style, '-')) {
            $style = Strings::sub($style, 1);
        }
        $split = explode('-', $style);
        $splitCount = count($split);
        if ($splitCount === 1) {
            return $split[0];
        }

        $style = '';
        for ($i = 0; $i < $splitCount; $i++) {
            if ($i === 0) {
                $style .= $split[$i];
                continue;
            }

            $_style = $split[$i];
            $_style[0] = strtoUpper($_style[0]);
            $style .= $_style;
        }

        return $style;
    }

    /**
     * Sets the value of an style.
     *
     * @param string $style The name of the style.
     * @param string $value The value to set for the style.
     *
     * @return void
     */
    public function setStyle(string $style, string $value, bool $important = false) : void
    {
        if ($this->element === null) {
            return;
        }

        if ($important === true) {
            $cssText = $this->element->style->cssText;

            $newCsss = [];

            $csss = explode(';', $cssText);
            foreach ($csss as $css) {
                if ($css === '') {
                    continue;
                }
                $_css = explode(':', $css);
                $newCsss[strtolower(trim($_css[0]))] = trim($_css[1]);
            }

            if (str_ends_with($value, '!important') === false) {
                $value .= ' !important';
            }
            $newCsss[strtolower($style)] = $value;

            $mountCss = '';
            foreach ($newCsss as $style => $value) {
                $mountCss .= ($style . ': ' . $value . '; ');
            }
            if ($mountCss !== '') {
                $mountCss = substr($mountCss, 0, -1);
            }

            $this->element->style->cssText = $mountCss;
            return;
        }


        if (Strings::contains($style, '-') === true) {
            $style = $this->translateStyleCssToCammel($style);
        }

        $this->element->style->{$style} = $value;
    }

    /**
     * Removes an attribute from the element.
     *
     * @param string $attribute The name of the attribute to remove.
     *
     * @return void
     *
     */
    public function removeAttribute(string $attribute) : void
    {
        $attribute = strtolower($attribute);
        if ($this->element === null) {
            return;
        }

        $this->element->removeAttribute($attribute);
    }

    /**
     * Destroys the element.
     *
     * @return void
     */
    public function destroy()
    {
        if ($this->element !== null) {
            $this->element->remove();
        }

        $this->uid = null;
        $this->element = null;
    }

    public function htmlInsertEnd(string $html)
    {
        if ($this->element === null) {
            return;
        }

        $this->element->insertAdjacentHTML('beforeend', $html);
    }

    public function getHtml(): null|string
    {
        if ($this->element === null) {
            return null;
        }

        return $this->element->innerHTML;
    }

    public function setHtml(string $html): void
    {
        if ($this->element === null) {
            return;
        }

        $this->element->innerHTML = $html;
    }

    public function appendHtml(string $html): void
    {
        if ($this->element === null) {
            return;
        }

        $this->element->insertAdjacentHTML('beforeend', $html);
    }

    public function getValue(): null|string|bool
    {
        if ($this->element === null) {
            return null;
        }

        $tag = $this->tag;
        if ($tag === 'textarea' || $tag === 'select') {
            return $this->element->value;
        }
        elseif ($tag === 'input') {
            $type = $this->getAttribute('type');
            if ($type !== null && strtolower($type) === 'checkbox') {
                return $this->element->checked;
            }

            return $this->element->value;
        }

        return null;
    }

    public function setValue(mixed $value): void
    {
        if ($this->element === null) {
            return;
        }

        $tag = $this->tag;
        if ($tag === 'textarea') {
            $this->element->value = $value;
        }
        elseif ($tag === 'input') {
            $type = $this->getAttribute('type');
            if ($type !== null && strtolower($type) === 'checkbox') {
                $this->element->checked = ($value === true);
                return;
            }

            $this->element->value = $value;
        }
    }

    public function getChecked(): null|string|bool
    {
        if ($this->element === null) {
            return null;
        }

        return $this->element->checked;
    }

    public function setChecked(bool $value): void
    {
        if ($this->element === null) {
            return;
        }

        $this->element->checked = ($value === true);
    }

    public function getParent(): Element|null
    {
        if ($this->element === null) {
            return null;
        }

        $element = $this->element->parentNode;
        if ($element === null) {
            return null;
        }

        $_element = new Element();
        $_element->setElementNative($element);
        return $_element;
    }

    protected function isVisible(): bool
    {
        if ($this->element === null) {
            return false;
        }

        $tolerance = 0.5;
        $rect = $this->element->getBoundingClientRect();

        $window = Js::getWindow();
        $windowHeight = 0;
        $windowWidth = 0;

        if ($window->innerHeight !== null && $window->innerHeight > 0) {
            $windowHeight = $window->innerHeight;
        } elseif ($window->document->documentElement->clientHeight !== null && $window->document->documentElement->clientHeight !== null > 0) {
            $windowHeight = $window->document->documentElement->clientHeight;
        }

        if ($window->innerWidth !== null && $window->innerWidth > 0) {
            $windowWidth = $window->innerWidth;
        } elseif ($window->document->documentElement->clientWidth !== null && $window->document->documentElement->clientWidth !== null > 0) {
            $windowWidth = $window->document->documentElement->clientWidth;
        }

        $inViewVertically = ($rect->top <= ($windowHeight + $tolerance) && $rect->bottom >= -$tolerance);
        $inViewHorizontally = ($rect->left <= ($windowWidth + $tolerance) && $rect->right >= -$tolerance);
        $inViewport = ($inViewVertically && $inViewHorizontally);

        $style = $window->getComputedStyle($this->element);

        $notHiddenByCSS = $style->display !== 'none' && $style->visibility !== 'hidden' && (float)$style->opacity > 0;
        $notHiddenAttribute = !$this->element->hidden;
        $hasDimensions = ($this->element->offsetWidth > 0 || $this->element->offsetHeight > 0 || $this->element->getClientRects()->length > 0);

        return $inViewport && $notHiddenByCSS && $notHiddenAttribute && $hasDimensions;
    }

    public function getShadow(): Shadow|null
    {
        if ($this->element === null) {
            return null;
        }

        $shadow = $this->element->shadowRoot;
        if ($shadow === null) {
            return null;
        }

        return new Shadow($shadow);
    }

    /**
     * Queries the DOM for an element matching the given query string.
     *
     * @param string $query The query string used to search for the element.
     * @return Element|null Returns an instance of Element if an element is found, otherwise returns null.
     * @throws Exception Throws an exception if the method is called on the server.
     */
    public static function query(string $query) : Element|null
    {
        if (Kernel::MODULE === 'SERVER') {
            throw new Exception('Method only works on client.');
        }

        $window = Js::getWindow();

        $element = $window->document->querySelector($query);
        if ($element === null) {
            return null;
        }

        $_element = new Element();
        $_element->setElementNative($element);
        return $_element;
    }

    /**
     * Queries the DOM for an element matching the given query string.
     *
     * @param string $query The query string used to search for the element.
     * @return Arr<Element>|null
     * @throws Exception Throws an exception if the method is called on the server.
     */
    public static function queryAll(string $query) : Arr|null
    {
        if (Kernel::MODULE === 'SERVER') {
            throw new Exception('Method only works on client.');
        }

        $window = Js::getWindow();
        $elements = Arr();
        $elementsUids = (array)unserialize($window->document->querySelectorAll($query)->toPhpSerializeUids());
        $countElements = count($elementsUids);
        if ($countElements === 0) {
            return $elements;
        }

        foreach ($elementsUids as $elementUid) {
            $element = self::fromUid($elementUid);
            $element->removeAttribute($window->Flames->Internal->char . 'uid');
            $elements[] = $element;
        }

        return $elements;
    }

    public static function fromNative($element)
    {
        $_element = new Element();
        $_element->setElementNative($element);

        return $_element;
    }

    public static function fromUid(string $uid)
    {
        $window = Js::getWindow();
        return Element::query('[' . $window->Flames->Internal->char . 'uid="' . $uid . '"]');
    }

    public static function getBody()
    {
        return self::fromNative(Js::getWindow()->document->body);
    }
}