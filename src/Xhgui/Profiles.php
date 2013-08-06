<?php
/**
 * Contains logic for getting/creating/removing profile records.
 */
class Xhgui_Profiles
{
    protected $_collection;
    protected $_mapper;

    public function __construct(MongoCollection $collection)
    {
        $this->_collection = $collection;
        $this->_mapper = new Xhgui_Db_Mapper();
    }

    /**
     * Get a single profile run by id.
     *
     * @param string $id The id of the profile to get.
     * @return MongoCursor
     */
    public function get($id)
    {
        return $this->_wrap($this->_collection->findOne(array(
            '_id' => new MongoId($id)
        )));
    }

    /**
     * Get the list of profiles for a simplified url.
     *
     * @param string $url The url to load profiles for.
     * @param array $options Pagination options to use.
     * @param array $conditions The search options.
     * @return MongoCursor
     */
    public function getForUrl($url, $options, $conditions = array())
    {
        $conditions = array_merge(
            (array)$conditions,
            array('simple_url' => $url)
        );
        $options = array_merge($options, array(
            'conditions' => $conditions,
        ));
        return $this->paginate($options);
    }

    public function paginate($options)
    {
        $opts = $this->_mapper->convert($options);

        $totalRows = $this->_collection->find($opts['conditions'])
            ->count();

        $totalPages = max(ceil($totalRows / $opts['perPage']), 1);
        $page = 1;
        if (isset($options['page'])) {
            $page = min(max($options['page'], 1), $totalPages);
        }

        $cursor = $this->_collection->find($opts['conditions'])
            ->sort($opts['sort'])
            ->skip(($page - 1) * $opts['perPage'])
            ->limit($opts['perPage']);

        return array(
            'results' => $this->_wrap($cursor),
            'sort' => $opts['sort'],
            'direction' => $opts['direction'],
            'page' => $page,
            'perPage' => $opts['perPage'],
            'totalPages' => $totalPages
        );
    }

    /**
     * Get the Percentile metrics for a URL
     *
     * This will group data by date and returns only the
     * percentile + date, making the data ideal for time series graphs
     *
     * @param integer $percentile The percentile you want. e.g. 90.
     * @param string $url
     * @param array $search Search options containing date_start and or date_end
     * @return array Array of metrics grouped by date
     */
    public function getPercentileForUrl($percentile, $url, $search = array())
    {
        $match = array('meta.simple_url' => $url);
        if (isset($search['date_start'])) {
            $match['meta.request_date']['$gte'] = (string)$search['date_start'];
        }
        if (isset($search['date_end'])) {
            $match['meta.request_date']['$lte'] = (string)$search['date_end'];
        }
        $results = $this->_collection->aggregate(array(
            array('$match' => $match),
            array(
                '$project' => array(
                    'date' => '$meta.request_date',
                    'profile.main()' => 1
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$date',
                    'row_count' => array('$sum' => 1),
                    'wall_times' => array('$push' => '$profile.main().wt'),
                    'cpu_times' => array('$push' => '$profile.main().cpu'),
                    'mu_times' => array('$push' => '$profile.main().mu'),
                    'pmu_times' => array('$push' => '$profile.main().pmu'),
                )
            ),
            array(
                '$project' => array(
                    'date' => '$date',
                    'row_count' => '$row_count',
                    'raw_index' => array(
                        '$multiply' => array(
                            '$row_count',
                            $percentile / 100
                        )
                    ),
                    'wall_times' => '$wall_times',
                    'cpu_times' => '$cpu_times',
                    'mu_times' => '$mu_times',
                    'pmu_times' => '$pmu_times',
                )
            ),
            array('$sort' => array('_id' => 1)),
        ));

        if (empty($results['result'])) {
            return array();
        }
        $keys = array(
            'wall_times' => 'wt',
            'cpu_times' => 'cpu',
            'mu_times' => 'mu',
            'pmu_times' => 'pmu'
        );
        foreach ($results['result'] as &$result) {
            $result['date'] = $result['_id'];
            unset($result['_id']);
            $index = max(round($result['raw_index']) - 1, 0);
            foreach ($keys as $key => $out) {
                sort($result[$key]);
                $result[$out] = $result[$key][$index];
                unset($result[$key]);
            }
        }
        return $results['result'];
    }

    /**
     * Get the Average metrics for a URL
     *
     * This will group data by date and returns only the
     * avg + date, making the data ideal for time series graphs
     *
     * @param string $url
     * @param array $search Search options containing date_start and or date_end
     * @return array Array of metrics grouped by date
     */
    public function getAvgsForUrl($url, $search = array())
    {
        $match = array('meta.simple_url' => $url);
        if (isset($search['date_start'])) {
            $match['meta.request_date']['$gte'] = (string)$search['date_start'];
        }
        if (isset($search['date_end'])) {
            $match['meta.request_date']['$lte'] = (string)$search['date_end'];
        }
        $results = $this->_collection->aggregate(array(
            array('$match' => $match),
            array(
                '$project' => array(
                    'date' => '$meta.request_date',
                    'profile.main()' => 1,
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$date',
                    'avg_wt' => array('$avg' => '$profile.main().wt'),
                    'avg_cpu' => array('$avg' => '$profile.main().cpu'),
                    'avg_mu' => array('$avg' => '$profile.main().mu'),
                    'avg_pmu' => array('$avg' => '$profile.main().pmu'),
                )
            ),
            array('$sort' => array('_id' => 1))
        ));
        if (empty($results['result'])) {
            return array();
        }
        foreach ($results['result'] as $i => $result) {
            $results['result'][$i]['date'] = $result['_id'];
            unset($results['result'][$i]['_id']);
        }
        return $results['result'];
    }

    /**
     * Get a paginated set of results.
     *
     * @param array $options The find options to use.
     * @return array An array of result data.
     */
    public function getAll($options = array())
    {
        return $this->paginate($options);
    }

    /**
     * Insert a profile run.
     *
     * Does unchecked inserts.
     *
     * @param array $profile The profile data to save.
     */
    public function insert($profile)
    {
        return $this->_collection->insert($profile, array('w' => 0));
    }

    /**
     * Used to truncate a collection.
     *
     * Primarly used in test cases to reset the test db.
     *
     * @return boolean
     */
    public function truncate()
    {
        return $this->_collection->drop();
    }

    /**
     * Converts arrays + MongoCursors into Xhgui_Profile instances.
     *
     * @param array|MongoCursor $data The data to transform.
     * @return Xhgui_Profile|array The transformed/wrapped results.
     */
    protected function _wrap($data)
    {
        if (is_array($data)) {
            return new Xhgui_Profile($data);
        }
        $results = array();
        foreach ($data as $row) {
            $results[] = new Xhgui_Profile($row);
        }
        return $results;
    }
}
