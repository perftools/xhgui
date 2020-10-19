<?php

namespace XHGui\Searcher;

use XHGui\Db\PdoRepository;
use XHGui\Profile;

class PdoSearcher implements SearcherInterface
{
    /** @var PdoRepository */
    private $db;

    public function __construct(PdoRepository $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function latest(): Profile
    {
        $row = $this->db->getLatest();

        return new Profile([
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
            'profile' => json_decode($row['profile'], true),
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
    public function get($id): Profile
    {
        $row = $this->db->getById($id);

        return new Profile([
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
            'profile' => json_decode($row['profile'], true),
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
        $page = (int)$options['page'];
        $direction = $options['direction'];
        if ($page < 1) {
            $page = 1;
        }
        $perPage = (int)$options['perPage'];
        $url = $options['conditions']['url'] ?? '';

        $totalRows = $this->db->countByUrl($url);
        $totalPages = max(ceil($totalRows / $perPage), 1);
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $skip = ($page - 1) * $perPage;

        $results = [];
        foreach ($this->db->findByUrl($url, $direction, $skip, $perPage) as $row) {
            $results[] = new Profile([
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
                    ],
                ],
            ]);
        }

        return [
            'results' => $results,
            'sort' => 'meta.request_ts',
            'direction' => $direction,
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
        $this->db->deleteById($id);
    }

    /**
     * {@inheritdoc}
     */
    public function truncate()
    {
        return $this->db->deleteAll();
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
        $row = $this->db->getStatistics();

        if (!$row) {
            $row = [
                'profiles' => 0,
                'latest' => 0,
                'bytes' => 0,
            ];
        }

        return $row;
    }
}
