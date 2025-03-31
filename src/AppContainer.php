<?php

namespace XHGui;

use Pimple\Container as PimpleContainer;
use Psr\Container\ContainerInterface;

/**
 * Container implementing Pimple arrayAccess and PSR ContainerInterface
 */
class AppContainer extends PimpleContainer implements ContainerInterface
{
    public function get(string $id)
    {
        return $this[$id];
    }

    public function has(string $id): bool
    {
        return isset($this[$id]);
    }
}
