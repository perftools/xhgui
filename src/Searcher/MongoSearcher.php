<?php

namespace XHGui\Searcher;

use Exception;
use MongoCursor;
use MongoDate;
use MongoDb;
use MongoId;
use XHGui\Db\Mapper;
use XHGui\Options\SearchOptions;
use XHGui\Profile;

/**
 * A Searcher for a MongoDB backend.
 */
class MongoSearcher implements SearcherInterface
{
    protected $_collection;

    protected $_watches;

    protected $_mapper;

    public function __construct(MongoDb $db)
    {
        $this->_collection = $db->results;
        $this->_watches = $db->watches;
        $this->_mapper = new Mapper();
    }

    /**
     * {@inheritdoc}
     */
    public function latest()
    {
        $cursor = $this->_collection->find()
            ->sort(['meta.request_date' => -1])
            ->limit(1);
        $result = $cursor->getNext();

        return $this->_wrap($result);
    }

    /**
     * {@inheritdoc}
     */
    public function query($conditions, $limit, $fields = [])
    {
        $result = $this->_collection->find($conditions, $fields)
            ->limit($limit);

        return iterator_to_array($result);
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        return $this->_wrap($this->_collection->findOne([
            '_id' => new MongoId($id),
        ]));
    }

    /**
     * {@inheritdoc}
     */
    public function getForUrl($url, $options, $conditions = [])
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

    /**
     * {@inheritdoc}
     */
    public function getPercentileForUrl($percentile, $url, $search = [])
    {
        $result = $this->_mapper->convert([
            'conditions' => $search + ['simple_url' => $url],
        ]);
        $match = $result['conditions'];

        $col = '$meta.request_date';
        if (!empty($search['limit']) && $search['limit'][0] === 'P') {
            $col = '$meta.request_ts';
        }

        $pipeline = [
            ['$match' => $match],
            [
                '$project' => [
                    'date' => $col,
                    'profile.main()' => 1,
                ],
            ],
            [
                '$group' => [
                    '_id' => '$date',
                    'row_count' => ['$sum' => 1],
                    'wall_times' => ['$push' => '$profile.main().wt'],
                    'cpu_times' => ['$push' => '$profile.main().cpu'],
                    'mu_times' => ['$push' => '$profile.main().mu'],
                    'pmu_times' => ['$push' => '$profile.main().pmu'],
                ],
            ],
            [
                '$project' => [
                    'date' => '$date',
                    'row_count' => '$row_count',
                    'raw_index' => [
                        '$multiply' => [
                            '$row_count',
                            $percentile / 100,
                        ],
                    ],
                    'wall_times' => '$wall_times',
                    'cpu_times' => '$cpu_times',
                    'mu_times' => '$mu_times',
                    'pmu_times' => '$pmu_times',
                ],
            ],
            ['$sort' => ['_id' => 1]],
        ];

        $results = $this->_collection->aggregate(
            $pipeline,
            ['cursor' => ['batchSize' => 0]]
        );

        if (empty($results['result'])) {
            return [];
        }
        $keys = [
            'wall_times' => 'wt',
            'cpu_times' => 'cpu',
            'mu_times' => 'mu',
            'pmu_times' => 'pmu',
        ];
        foreach ($results['result'] as &$result) {
            $result['date'] = ($result['_id'] instanceof MongoDate) ? date('Y-m-d H:i:s', $result['_id']->sec) : $result['_id'];
            unset($result['_id']);
            $index = max(round($result['raw_index']) - 1, 0);
            foreach ($keys as $key => $out) {
                sort($result[$key]);
                $result[$out] = $result[$key][$index] ?? null;
                unset($result[$key]);
            }
        }

        return $results['result'];
    }

    /**
     * {@inheritdoc}
     */
    public function getAvgsForUrl($url, $search = [])
    {
        $match = ['meta.simple_url' => $url];
        if (isset($search['date_start'])) {
            $match['meta.request_date']['$gte'] = (string)$search['date_start'];
        }
        if (isset($search['date_end'])) {
            $match['meta.request_date']['$lte'] = (string)$search['date_end'];
        }
        $results = $this->_collection->aggregate(
            [
            ['$match' => $match],
            [
                '$project' => [
                    'date' => '$meta.request_date',
                    'profile.main()' => 1,
                ],
            ],
            [
                '$group' => [
                    '_id' => '$date',
                    'avg_wt' => ['$avg' => '$profile.main().wt'],
                    'avg_cpu' => ['$avg' => '$profile.main().cpu'],
                    'avg_mu' => ['$avg' => '$profile.main().mu'],
                    'avg_pmu' => ['$avg' => '$profile.main().pmu'],
                ],
            ],
            ['$sort' => ['_id' => 1]],
        ],
            ['cursor' => ['batchSize' => 0]]
        );
        if (empty($results['result'])) {
            return [];
        }
        foreach ($results['result'] as $i => $result) {
            $results['result'][$i]['date'] = $result['_id'];
            unset($results['result'][$i]['_id']);
        }

        return $results['result'];
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
        $this->_collection->remove(['_id' => new MongoId($id)], []);
    }

    public function truncate()
    {
        $this->_collection->remove();

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
            $this->_watches->remove(
                ['_id' => new MongoId($data['_id'])],
                ['w' => 1]
            );

            return true;
        }

        if (empty($data['_id'])) {
            $this->_watches->insert(
                $data,
                ['w' => 1]
            );

            return true;
        }

        $data['_id'] = new MongoId($data['_id']);
        $this->_watches->update(
            ['_id' => $data['_id']],
            $data,
            ['w' => 1]
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWatches(): array
    {
        $cursor = $this->_watches->find();

        return array_values(iterator_to_array($cursor));
    }

    public function truncateWatches()
    {
        $this->_watches->remove();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    private function paginate(array $options): array
    {
        $opts = $this->_mapper->convert($options);

        $totalRows = $this->_collection->find(
            $opts['conditions'],
            ['_id' => 1]
        )->count();

        $totalPages = max(ceil($totalRows / $opts['perPage']), 1);
        $page = 1;
        if (isset($options['page'])) {
            $page = min(max($options['page'], 1), $totalPages);
        }

        $projection = false;
        if (isset($options['projection'])) {
            if ($options['projection'] === true) {
                $projection = ['meta' => 1, 'profile.main()' => 1];
            } else {
                $projection = $options['projection'];
            }
        }

        if ($projection === false) {
            $cursor = $this->_collection->find($opts['conditions'])
                ->sort($opts['sort'])
                ->skip((int)($page - 1) * $opts['perPage'])
                ->limit($opts['perPage']);
        } else {
            $cursor = $this->_collection->find($opts['conditions'], $projection)
                ->sort($opts['sort'])
                ->skip((int)($page - 1) * $opts['perPage'])
                ->limit($opts['perPage']);
        }

        return [
            'results' => $this->_wrap($cursor),
            'sort' => $opts['sort'],
            'direction' => $opts['direction'],
            'page' => $page,
            'perPage' => $opts['perPage'],
            'totalPages' => $totalPages,
        ];
    }

    /**
     * Converts arrays + MongoCursors into Profile instances.
     *
     * @param array|MongoCursor $data the data to transform
     * @return Profile|Profile[] the transformed/wrapped results
     */
    private function _wrap($data)
    {
        if ($data === null) {
            throw new Exception('No profile data found.');
        }

        if (is_array($data)) {
            return new Profile($data);
        }
        $results = [];
        foreach ($data as $row) {
            $results[] = new Profile($row);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function stats(): array
    {
        return [
            'profiles' => 0,
            'latest' => 0,
            'bytes' => 0,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAllServerNames(): ?array
    {
        return $this->_collection->distinct('meta.SERVER.SERVER_NAME');
    }
}
