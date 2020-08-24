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
    public function getForUrl($url, $options, $conditions = [])
    {
        // TODO: Implement getForUrl() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getPercentileForUrl($percentile, $url, $search = [])
    {
        // TODO: Implement getPercentileForUrl() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getAvgsForUrl($url, $search = [])
    {
        // TODO: Implement getAvgsForUrl() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($options = [])
    {
        $sort = $options['sort'];
        $direction = $options['direction'];
        $page = (int)$options['page'];
        if ($page < 1) {
            $page = 1;
        }
        $perPage = (int)$options['perPage'];
        $url = $options['conditions']['url'] ?? "";

        $stmt = $this->pdo->prepare("
          SELECT COUNT(*) AS count
          FROM {$this->table}
          WHERE simple_url LIKE :url
        ");
        $stmt->execute(['url' => '%'.$url.'%']);
        $totalRows = (int)$stmt->fetchColumn();

        $totalPages = max(ceil($totalRows/$perPage), 1);
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $skip = ($page-1) * $perPage;

        $stmt = $this->pdo->prepare("
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
          WHERE simple_url LIKE :url
          ORDER BY request_ts DESC
          LIMIT $skip, $perPage
        ");
        $stmt->execute(['url' => '%'.$url.'%']);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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

        return [
            'results' => $results,
            'sort' => 'meta.request_ts',
            'direction' => 'desc',
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
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

    /**
     * {@inheritdoc}
     */
    public function stats()
    {
        $stmt = $this->pdo->query("
          SELECT
            COUNT(*) AS profiles,
            MAX(request_ts) AS latest,
            SUM(LENGTH(profile)) AS bytes
          FROM {$this->table}
        ", PDO::FETCH_ASSOC);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (false === $row) {
            $row = [
                'profiles' => 0,
                'latest'   => 0,
                'bytes'    => 0,
            ];
        }

        return $row;
    }
}
