<?php

namespace XHGui\Saver;

use PDO;
use PDOStatement;
use XHGui\Util;

class PdoSaver implements SaverInterface
{
    const TABLE_DDL = <<<SQL

CREATE TABLE IF NOT EXISTS "%s" (
  id               CHAR(24) PRIMARY KEY,
  profile          TEXT           NOT NULL,
  url              TEXT           NULL,
  SERVER           TEXT           NULL,
  GET              TEXT           NULL,
  ENV              TEXT           NULL,
  simple_url       TEXT           NULL,
  request_ts       INTEGER        NOT NULL,
  request_ts_micro NUMERIC(15, 4) NOT NULL,
  request_date     DATE           NOT NULL,
  main_wt          INTEGER        NOT NULL,
  main_ct          INTEGER        NOT NULL,
  main_cpu         INTEGER        NOT NULL,
  main_mu          INTEGER        NOT NULL,
  main_pmu         INTEGER        NOT NULL
);

SQL;

    const INSERT_DML = <<<SQL

INSERT INTO "%s" (
  id,
  profile,
  url,
  SERVER,
  GET,
  ENV,
  simple_url,
  request_ts,
  request_ts_micro,
  request_date,
  main_wt,
  main_ct,
  main_cpu,
  main_mu,
  main_pmu
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
);

SQL;

    /**
     * @var PDOStatement
     */
    private $stmt;

    /**
     * @param PDO $pdo
     * @param string $table
     */
    public function __construct(PDO $pdo, $table)
    {
        $pdo->exec(sprintf(self::TABLE_DDL, $table));

        $this->stmt = $pdo->prepare(sprintf(self::INSERT_DML, $table));
    }

    public function save(array $data)
    {
        $main = $data['profile']['main()'];

        // build 'request_ts' and 'request_date' from 'request_ts_micro'
        $ts = $data['meta']['request_ts_micro'];
        $sec = $ts['sec'];
        $usec = $ts['usec'];

        $this->stmt->execute([
            'id'               => $data['_id'] ?? Util::generateId(),
            'profile'          => json_encode($data['profile']),
            'url'              => $data['meta']['url'],
            'SERVER'           => json_encode($data['meta']['SERVER']),
            'GET'              => json_encode($data['meta']['get']),
            'ENV'              => json_encode($data['meta']['env']),
            'simple_url'       => $data['meta']['simple_url'],
            'request_ts'       => $sec,
            'request_ts_micro' => "$sec.$usec",
            'request_date'     => date('Y-m-d', $sec),
            'main_wt'          => $main['wt'],
            'main_ct'          => $main['ct'],
            'main_cpu'         => $main['cpu'],
            'main_mu'          => $main['mu'],
            'main_pmu'         => $main['pmu'],
        ]);
    }

    public function __destruct()
    {
        $this->stmt->closeCursor();
    }
}
