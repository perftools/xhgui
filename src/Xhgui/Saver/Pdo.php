<?php

class Xhgui_Saver_Pdo implements Xhgui_Saver_Interface
{
    const TABLE_DDL = <<<SQL

CREATE TABLE IF NOT EXISTS "%s" (
  id               TEXT PRIMARY KEY,
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
     * @param PDO    $pdo
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

        $this->stmt->execute(array(
            'id'               => Xhgui_Util::generateId(),
            'profile'          => json_encode($data['profile']),
            'url'              => $data['meta']['url'],
            'SERVER'           => json_encode($data['meta']['SERVER']),
            'GET'              => json_encode($data['meta']['get']),
            'ENV'              => json_encode($data['meta']['env']),
            'simple_url'       => $data['meta']['simple_url'],
            'request_ts'       => $data['meta']['request_ts']['sec'],
            'request_ts_micro' => "{$data['meta']['request_ts_micro']['sec']}.{$data['meta']['request_ts_micro']['usec']}",
            'request_date'     => $data['meta']['request_date'],
            'main_wt'          => $main['wt'],
            'main_ct'          => $main['ct'],
            'main_cpu'         => $main['cpu'],
            'main_mu'          => $main['mu'],
            'main_pmu'         => $main['pmu'],
        ));

        $this->stmt->closeCursor();
    }
}
