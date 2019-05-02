<?php

class Xhgui_Searcher_Pdo implements Xhgui_Searcher_Interface
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var string
     */
    private $table;

    /**
     * @param PDO    $pdo   An open database connection
     * @param string $table Table name where Xhgui profiles are stored
     */
    public function __construct(PDO $pdo, $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * {@inheritdoc}
     */
    public function latest()
    {
        $stmt = $this->pdo->query("
          SELECT
            id,
            profile,
            url,
            SERVER,
            GET,
            ENV,
            simple_url,
            request_ts,
            request_ts_micro,
            request_date
          FROM {$this->table}
          ORDER BY request_date ASC
          LIMIT 1
        ");

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (false === $row) {
            throw new Exception('No profile available yet.');
        }

        return new Xhgui_Profile([
            '_id' => $row['id'],
            'meta' => [
                'url' => $row['url'],
                'SERVER' => json_decode($row['SERVER'], true),
                'get' => json_decode($row['GET'], true),
                'env' => json_decode($row['ENV'], true),
                'simple_url' => $row['simple_url'],
                'request_ts' => (int) $row['request_ts'],
                'request_ts_micro' => $row['request_ts_micro'],
                'request_date' => $row['request_date'],
            ],
            'profile' => json_decode($row['profile'], true)
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function query($conditions, $limit, $fields = [])
    {
        // TODO: Implement query() method.
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        $stmt = $this->pdo->prepare("
          SELECT
            profile,
            url,
            SERVER,
            GET,
            ENV,
            simple_url,
            request_ts,
            request_ts_micro,
            request_date
          FROM {$this->table}
          WHERE id = :id
        ");

        $stmt->execute(['id' => $id]);

        if (false === $row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('No profile data found.');
        }

        return new Xhgui_Profile([
            '_id' => $id,
            'meta' => [
                'url' => $row['url'],
                'SERVER' => json_decode($row['SERVER'], true),
                'get' => json_decode($row['GET'], true),
                'env' => json_decode($row['ENV'], true),
                'simple_url' => $row['simple_url'],
                'request_ts' => (int) $row['request_ts'],
                'request_ts_micro' => $row['request_ts_micro'],
                'request_date' => $row['request_date'],
            ],
            'profile' => json_decode($row['profile'], true)
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getForUrl($url, $options, $conditions = array())
    {
        // TODO: Implement getForUrl() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getPercentileForUrl($percentile, $url, $search = array())
    {
        // TODO: Implement getPercentileForUrl() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getAvgsForUrl($url, $search = array())
    {
        // TODO: Implement getAvgsForUrl() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($options = array())
    {
        $sort = $options['sort'];
        $direction = $options['direction'];
        $page = $options['page'];
        $perPage = $options['perPage'];

        $stmt = $this->pdo->query("
          SELECT
            id,
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
          FROM {$this->table}
          ORDER BY request_ts DESC
        ", PDO::FETCH_ASSOC);

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = new Xhgui_Profile([
                '_id' => $row['id'],
                'meta' => [
                    'url' => $row['url'],
                    'SERVER' => json_decode($row['SERVER'], true),
                    'get' => json_decode($row['GET'], true),
                    'env' => json_decode($row['ENV'], true),
                    'simple_url' => $row['simple_url'],
                    'request_ts' => $row['request_ts'],
                    'request_ts_micro' => $row['request_ts_micro'],
                    'request_date' => $row['request_date'],
                ],
                'profile' => [
                    'main()' => [
                        'wt' => (int) $row['main_wt'],
                        'ct' => (int) $row['main_ct'],
                        'cpu' => (int) $row['main_cpu'],
                        'mu' => (int) $row['main_mu'],
                        'pmu' => (int) $row['main_pmu'],
                    ]
                ]
            ]);
        }

        return array(
            'results' => $results,
            'sort' => 'meta.request_ts',
            'direction' => 'desc',
            'page' => 1,
            'perPage' => count($results),
            'totalPages' => 1
        );
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $stmt = $this->pdo->prepare("
          DELETE FROM {$this->table}
          WHERE id = :id
        ");

        $stmt->execute(['id' => $id]);
    }

    /**
     * {@inheritdoc}
     */
    public function truncate()
    {
        return is_int(
            $this->pdo->exec("DELETE FROM {$this->table}")
        );
    }

    /**
     * {@inheritdoc}
     */
    public function saveWatch(array $data)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWatches()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function truncateWatches()
    {
    }
}
