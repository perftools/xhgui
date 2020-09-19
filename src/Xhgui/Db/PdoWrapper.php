<?php

namespace Xhgui\Db;

use PDO;
use PDOException;

class PdoWrapper extends PDO
{
    /** @var string */
    private $quoteIdentifier;

    public static function create(array $options): self
    {
        $pdoOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        $pdo = new self(
            $options['dsn'],
            $options['user'],
            $options['pass'],
            $pdoOptions
        );

        $pdo->quoteIdentifier = self::getQuoteIdentifier($options['dsn']);

        return $pdo;
    }

    public function quoteIdentifier(string $identifier)
    {
        return sprintf('%c%s%c', $this->quoteIdentifier, $identifier, $this->quoteIdentifier);
    }

    private static function getQuoteIdentifier(string $dsn): string
    {
        $adapter = explode(':', $dsn, 2)[0];
        $identifierMap = [
            'mysql' => '`',
            'pgsql' => '"',
            'sqlite' => '"',
        ];
        $quoteIdentifier = $identifierMap[$adapter] ?? null;

        if ($quoteIdentifier === null) {
            throw new PdoException("Unsupported adapter '$adapter' to detect quote identifier");
        }

        return $quoteIdentifier;
    }
}
