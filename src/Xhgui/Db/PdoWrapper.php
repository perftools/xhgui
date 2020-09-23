<?php

namespace Xhgui\Db;

use PDO;
use PDOException;
use PDOStatement;

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

    /**
     * Replace placeholders surrounded by {} with values from $params.
     */
    public function prepareTemplate(string $template, array $params): ?PDOStatement
    {
        $keys = array_map(static function ($value) {
            return sprintf("{%s}", $value);
        }, array_keys($params));
        $query = str_replace($keys, array_values($params), $template);

        return $this->prepare($query) ?: null;
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
