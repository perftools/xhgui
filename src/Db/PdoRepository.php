<?php

namespace XHGui\Db;

use DateInterval;
use DateTime;
use Generator;
use PDO;
use RuntimeException;
use XHGui\Searcher\SearcherInterface;

class PdoRepository
{
    /** @var PDO */
    private $pdo;

    /** @var string */
    private $driverName;

    /** @var string */
    private $table;

    /** @var string */
    private $tableWatches;

    /**
     * @param PDO $pdo An open database connection
     * @param string $table Table name where Xhgui profiles are stored
     * @param string $tableWatch Table name where Xhgui watch functions are stored
     */
    public function __construct(PDO $pdo, string $driverName, string $table, string $tableWatch)
    {
        $this->pdo = $pdo;
        $this->driverName = $driverName;
        $this->table = sprintf('"%s"', $table);
        $this->tableWatches = sprintf('"%s"', $tableWatch);
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

    public function countByUrl(array $options): int
    {
        $query = sprintf('
          SELECT COUNT(*) AS count
          FROM %s
          WHERE %s',
            $this->table,
            $options['where']['conditions']
        );
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($options['where']['params']);

        return (int)$stmt->fetchColumn();
    }

    public function findByUrl(array $options = []): Generator
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
          WHERE %s
          ORDER BY %s %s
          LIMIT %d OFFSET %d',
            $this->table,
            $options['where']['conditions'],
            $options['sort'],
            $options['direction'],
            $options['perPage'],
            $options['skip']
        );
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($options['where']['params']);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function deleteById(string $id): void
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

    public function initSchema(): void
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
        $this->pdo->exec(sprintf('
            CREATE TABLE IF NOT EXISTS %s (
              "id"               CHAR(24) PRIMARY KEY,
              "removed"          TEXT           NULL,
              "name"             TEXT           NOT NULL
            )
        ', $this->tableWatches));
    }

    public function saveProfile(array $data): void
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

    public function saveWatch(array $data): bool
    {
        $stmt = $this->pdo->prepare(sprintf('
            INSERT INTO %s (
              "id",
              "removed",
              "name"
            ) VALUES (
              :_id,
              :removed,
              :name
            )
        ', $this->tableWatches));

        return $stmt->execute($data);
    }

    public function removeWatch(string $id): bool
    {
        $stmt = $this->pdo->prepare(sprintf('
          DELETE FROM %s
          WHERE id = :id
        ', $this->tableWatches));

        return $stmt->execute(['id' => $id]);
    }

    public function updateWatch(array $data): bool
    {
        $stmt = $this->pdo->prepare(sprintf('
            UPDATE %s SET
              "removed" = :removed,
              "name" = :name
            WHERE
              "id" = :_id
        ', $this->tableWatches));

        return $stmt->execute($data);
    }

    public function getAllWatches(): Generator
    {
        $query = sprintf('
          SELECT
            "id",
            "removed",
            "name"
          FROM %s
          ', $this->tableWatches);
        $stmt = $this->pdo->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function truncateWatches()
    {
        return is_int(
            $this->pdo->exec(sprintf('DELETE FROM %s', $this->tableWatches))
        );
    }

    public function aggregate(array $options)
    {
        $query = sprintf(
            'SELECT
            "id",
            "request_ts",
            "main_wt",
            "main_ct",
            "main_cpu",
            "main_mu",
            "main_pmu"
          FROM %s
          WHERE %s
          ORDER BY %s %s',
            $this->table,
            $options['where']['conditions'],
            $options['sort'],
            $options['direction']
        );
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($options['where']['params']);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    /**
     * Convert request data keys into pdo query.
     */
    public function buildQuery(array $options): array
    {
        return [
            'where' => $this->buildWhere($options['conditions']),
            'sort' => $this->buildSort($options),
            'direction' => $this->buildDirection($options),
            'perPage' => $options['perPage'] ?? SearcherInterface::DEFAULT_PER_PAGE,
        ];
    }

    /**
     * build pdo where
     */
    public function buildWhere(array $search): array
    {
        $where = ['conditions' => '1=1', 'params' => []];

        if (empty($search)) {
            return $where;
        }

        if (isset($search['limit_custom']) && $search['limit_custom'][0] === 'P') {
            $search['limit'] = $search['limit_custom'];
        }
        $hasLimit = (isset($search['limit']) && $search['limit'] !== -1);

        // simple_url equals match
        if (isset($search['simple_url'])) {
            $where['conditions'] .= ' and simple_url = :simple_url';
            $where['params']['simple_url'] = $search['simple_url'];
        }

        if (isset($search['date_start']) && !$hasLimit) {
            $where['conditions'] .= ' and request_date >= :date_start';
            $where['params']['date_start'] = $search['date_start'];
        }

        if (isset($search['date_end']) && !$hasLimit) {
            $where['conditions'] .= ' and request_date <= :date_end';
            $where['params']['date_end'] = $search['date_end'];
        }

        if (isset($search['request_start'])) {
            $where['conditions'] .= ' and request_ts >= :request_start';
            $where['params']['request_start'] = strtotime($search['request_start']);
        }

        if (isset($search['request_end'])) {
            $where['conditions'] .= ' and request_ts <= :request_end';
            $where['params']['request_end'] = strtotime($search['request_end']);
        }

        // TODO need JSON support
        if (isset($search['remote_addr'])) {
            $where['conditions'] .= ' and SERVER like :remote_addr';
            $where['params']['remote_addr'] = '%' . $search['remote_addr'] . '%';
        }

        if (isset($search['cookie'])) {
            $where['conditions'] .= ' and SERVER like :cookie';
            $where['params']['cookie'] = '%' . $search['cookie'] . '%';
        }

        if (isset($search['server_name'])) {
            $where['conditions'] .= ' and SERVER like :server_name';
            $where['params']['server_name'] = '%' . $search['server_name'] . '%';
        }

        if ($hasLimit && $search['limit'][0] === 'P') {
            $date = new DateTime();
            try {
                $date->sub(new DateInterval($search['limit']));
                $where['conditions'] .= ' and request_ts >= :limit_start';
                $where['params']['limit_start'] = $date->getTimestamp();
            } catch (\Exception $e) {
                $where['conditions'] .= ' and request_ts >= :limit_start';
                $where['params']['limit_start'] = time() + 86400;
            }
        }

        // fuzzy match
        if (isset($search['url'])) {
            $where['conditions'] .= ' and url like :url';
            $where['params']['url'] = '%' . $search['url'] . '%';
        }

        return $where;
    }

    /**
     * build pdo order sort
     */
    private function buildSort(array $options): string
    {
        if (!isset($options['sort'])) {
            return 'request_ts';
        }

        $valid = ['time', 'wt', 'mu', 'cpu', 'pmu'];
        if (isset($options['sort'])) {
            if ($options['sort'] === 'time') {
                return 'request_ts';
            }

            if (in_array($options['sort'], $valid, true)) {
                return 'main_' . $options['sort'];
            }
        }

        return $options['sort'];
    }

    /**
     * build pdo order direction
     */
    private function buildDirection(array $options): string
    {
        if (!isset($options['direction'])) {
            return SearcherInterface::DEFAULT_DIRECTION;
        }

        $valid = ['desc', 'asc'];
        if (in_array($options['direction'], $valid, true)) {
            return $options['direction'];
        }

        return 'desc';
    }
}
