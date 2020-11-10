<?php

namespace XHGui\Db;

use Generator;
use PDO;
use RuntimeException;

class PdoRepository
{
    /** @var PDO */
    private $pdo;

    /** @var string */
    private $table;

    /**
     * @param PDO $pdo An open database connection
     * @param string $table Table name where Xhgui profiles are stored
     */
    public function __construct(PDO $pdo, string $table)
    {
        $this->pdo = $pdo;
        $this->table = sprintf('"%s"', $table);
        $this->initSchema();
    }

    public function getLatest(): array
    {
        $query = sprintf('
          SELECT
            "id",
            "profile",
            "url",
            "SERVER",
            "GET",
            "ENV",
            "simple_url",
            "request_ts",
            "request_ts_micro,"
            "request_date"
          FROM %s
          ORDER BY "request_date" ASC
          LIMIT 1',
            $this->table
        );
        $stmt = $this->pdo->query($query);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw new RuntimeException('No profile available yet.');
        }

        return $row;
    }

    public function getById(string $id): array
    {
        $query = sprintf('
          SELECT
            "profile",
            "url",
            "SERVER",
            "GET",
            "ENV",
            "simple_url",
            "request_ts",
            "request_ts_micro",
            "request_date"
          FROM %s
          WHERE id = :id
        ', $this->table);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw new RuntimeException('No profile data found.');
        }

        return $row;
    }

    public function countByUrl(string $url): int
    {
        $query = sprintf('
          SELECT COUNT(*) AS count
          FROM %s
          WHERE "simple_url" LIKE :url
        ', $this->table);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['url' => '%' . $url . '%']);

        return (int)$stmt->fetchColumn();
    }

    public function findByUrl(string $url, string $direction, int $skip, int $perPage): Generator
    {
        $query = sprintf('
          SELECT
            "id",
            "url",
            "SERVER",
            "GET",
            "ENV",
            "simple_url",
            "request_ts",
            "request_ts_micro",
            "request_date",
            "main_wt",
            "main_ct",
            "main_cpu",
            "main_mu",
            "main_pmu"
          FROM %s
          WHERE "simple_url" LIKE :url
          ORDER BY "request_ts" %s
          LIMIT %d OFFSET %d',
            $this->table,
            $direction,
            $perPage,
            $skip
        );
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['url' => '%' . $url . '%']);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function deleteById(string $id)
    {
        $stmt = $this->pdo->prepare(sprintf('
          DELETE FROM %s
          WHERE id = :id
        ', $this->table));

        $stmt->execute(['id' => $id]);
    }

    public function deleteAll()
    {
        return is_int(
            $this->pdo->exec(sprintf('DELETE FROM %s', $this->table))
        );
    }

    public function getStatistics()
    {
        $stmt = $this->pdo->query(
            sprintf(
                '
          SELECT
            COUNT(*) AS profiles,
            MAX("request_ts") AS latest,
            SUM(LENGTH("profile")) AS bytes
          FROM %s',
                $this->table
            ),
            PDO::FETCH_ASSOC
        );

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function initSchema()
    {
        $this->pdo->exec(sprintf('
            CREATE TABLE IF NOT EXISTS %s (
              "id"               CHAR(24) PRIMARY KEY,
              "profile"          TEXT           NOT NULL,
              "url"              TEXT           NULL,
              "SERVER"           TEXT           NULL,
              "GET"              TEXT           NULL,
              "ENV"              TEXT           NULL,
              "simple_url"       TEXT           NULL,
              "request_ts"       INTEGER        NOT NULL,
              "request_ts_micro" NUMERIC(15, 4) NOT NULL,
              "request_date"     DATE           NOT NULL,
              "main_wt"          INTEGER        NOT NULL,
              "main_ct"          INTEGER        NOT NULL,
              "main_cpu"         INTEGER        NOT NULL,
              "main_mu"          INTEGER        NOT NULL,
              "main_pmu"         INTEGER        NOT NULL
            )
        ', $this->table));
    }

    public function saveProfile(array $data)
    {
        $stmt = $this->pdo->prepare(sprintf('
            INSERT INTO %s (
              "id",
              "profile",
              "url",
              "SERVER",
              "GET",
              "ENV",
              "simple_url",
              "request_ts",
              "request_ts_micro",
              "request_date",
              "main_wt",
              "main_ct",
              "main_cpu",
              "main_mu",
              "main_pmu"
            ) VALUES (
              :id,
              :profile,
              :url,
              :SERVER,
              :GET,
              :ENV,
              :simple_url,
              :request_ts,
              :request_ts_micro,
              :request_date,
              :main_wt,
              :main_ct,
              :main_cpu,
              :main_mu,
              :main_pmu
            )
        ', $this->table));
        $stmt->execute($data);
    }
}
