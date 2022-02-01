<?php

namespace XHGui\Searcher;

use XHGui\Db\PdoRepository;
use XHGui\Exception\NotImplementedException;
use XHGui\Options\SearchOptions;
use XHGui\Profile;
use XHGui\Util;

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

    public function query($conditions, $limit, $fields = []): void
    {
        throw NotImplementedException::notImplementedPdo(__METHOD__);
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

    public function getForUrl($url, $options, $conditions = []): array
    {
        $conditions = array_merge(
            (array)$conditions,
            ['simple_url' => $url]
        );

        $options = array_merge($options, [
            'conditions' => $conditions,
        ]);

        return $this->paginate($options);
    }

    public function getPercentileForUrl($percentile, $url, $search = []): array
    {
        $search = array_merge((array)$search, ['simple_url' => $url]);
        $option = $this->db->buildQuery(['conditions' => $search]);

        $results = [];
        foreach ($this->db->aggregate($option) as $row) {
            $timestamp = $row['request_ts'];
            $rowCount = $results[$timestamp]['row_count'] ?? 0;

            $results[$timestamp]['_id'] = $timestamp;
            $results[$timestamp]['row_count'] = $rowCount + 1;
            $results[$timestamp]['raw_index'] = $percentile / 100;
            $results[$timestamp]['wall_times'][] = intval($row['main_wt']);
            $results[$timestamp]['cpu_times'][] = intval($row['main_cpu']);
            $results[$timestamp]['mu_times'][] = intval($row['main_mu']);
            $results[$timestamp]['pmu_times'][] = intval($row['main_pmu']);
        }

        $keys = [
            'wall_times' => 'wt',
            'cpu_times' => 'cpu',
            'mu_times' => 'mu',
            'pmu_times' => 'pmu',
        ];
        foreach ($results as &$result) {
            $result['date'] = date('Y-m-d H:i:s', $result['_id']);
            unset($result['_id']);
            $index = max(round($result['raw_index']) - 1, 0);
            foreach ($keys as $key => $out) {
                sort($result[$key]);
                $result[$out] = $result[$key][$index] ?? null;
                unset($result[$key]);
            }
        }

        return array_values($results);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvgsForUrl($url, $search = []): void
    {
        throw NotImplementedException::notImplementedPdo(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(SearchOptions $options): array
    {
        return $this->paginate($options->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id): void
    {
        $this->db->deleteById($id);
    }

    public function truncate()
    {
        $this->db->deleteAll();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function saveWatch(array $data): bool
    {
        if (empty($data['name'])) {
            return false;
        }

        if (!empty($data['removed']) && isset($data['_id'])) {
            $this->db->removeWatch($data['_id']);

            return true;
        }

        if (empty($data['_id'])) {
            $data['_id'] = Util::generateId();
            $data['removed'] = 0;
            $this->db->saveWatch($data);

            return true;
        }

        $this->db->updateWatch($data);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWatches(): array
    {
        $results = [];
        foreach ($this->db->getAllWatches() as $row) {
            $results[] = [
                '_id' => $row['id'],
                'removed' => $row['removed'],
                'name' => $row['name'],
            ];
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function truncateWatches()
    {
        $this->db->truncateWatches();

        return $this;
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

    /**
     * {@inheritdoc}
     */
    public function getAllServerNames(): ?array
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    private function paginate(array $options): array
    {
        $opt = $this->db->buildQuery($options);

        $totalRows = $this->db->countByUrl($opt);
        $totalPages = max(ceil($totalRows / $opt['perPage']), 1);

        $page = min(max($options['page'] ?? 1, 1), $totalPages);
        $opt['skip'] = ($page - 1) * $opt['perPage'];

        $results = [];
        foreach ($this->db->findByUrl($opt) as $row) {
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
            'direction' => $opt['direction'],
            'page' => $page,
            'perPage' => $opt['perPage'],
            'totalPages' => $totalPages,
        ];
    }
}
