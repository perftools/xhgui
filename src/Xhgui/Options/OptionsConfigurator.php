<?php

namespace XHGui\Options;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see https://symfony.com/doc/3.3/components/options_resolver.html
 */
abstract class OptionsConfigurator implements ArrayAccess, IteratorAggregate
{
    /** @var array */
    protected $options;

    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    abstract protected function configureOptions(OptionsResolver $resolver);

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->options);
    }

    public function offsetGet($offset)
    {
        return $this->options[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->options[] = $value;
        } else {
            $this->options[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->options[$offset]);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->options);
    }
}
