<?php

namespace XHGui\Test;

use LazyProperty\LazyPropertiesTrait;
use XHGui\ServiceContainer;

trait LazyContainerProperties
{
    use LazyPropertiesTrait;

    /** @var ServiceContainer */
    protected $di;

    protected function setupProperties()
    {
        $this->initLazyProperties([
            'di',
        ]);
    }

    protected function getDi()
    {
        return ServiceContainer::instance();
    }
}
