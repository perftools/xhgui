<?php

namespace XHGui\Exception;

use RuntimeException;

class NotImplementedException extends RuntimeException
{
    public static function notImplementedPdo(string $method): self
    {
        throw new self(sprintf('%s not implemented for PDO', $method));
    }
}
