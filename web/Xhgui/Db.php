<?php

class Xhgui_Db
{
    protected $_mongo;
    protected $_db;
    protected $_collection;

    public function __construct($host = null, $collection = 'results')
    {
        if (empty($host)) {
            $host = Xhgui_Config::read('db.host');
        }
        $this->_mongo = new Mongo($host);
        $this->_db = $this->_mongo->xhprof;
        $this->_collection = $this->_db->{$collection};
    }

    /**
     * Get a single profile run by id.
     *
     * @param string $id The id of the profile to get.
     * @return MongoCursor
     */
    public function get($id)
    {
        return $this->_collection->findOne(array(
            '_id' => new MongoId($id)
        ));
    }

    /**
     * Get the list of profiles for a simplified url.
     *
     * @param string $url The url to load profiles for.
     * @param array $options Pagination options to use.
     * @return MongoCursor
     */
    public function getForUrl($url, $options)
    {
        $options['conditions'] = array(
            'meta.simple_url' => $url
        );
        $pagination = $this->pagination($options);
        $perPage = $pagination['perPage'];
        $page = $pagination['page'];
        $sort = $pagination['sort'];

        $cursor = $this->_collection->find($options['conditions'])
            ->sort($sort)
            ->skip(($page - 1) * $perPage)
            ->limit($perPage);
        $pagination['results'] = $cursor;
        return $pagination;
    }

    /**
     * Get the Average metrics for a URL
     *
     * This will group data by date and returns only the
     * avg + date, making the data ideal for time series graphs
     *
     * @param string $url
     * @return array Array of metrics grouped by date
     */
    public function getAvgsForUrl($url)
    {
        $results = $this->_collection->aggregate(array(
            array('$match' => array('meta.simple_url' => $url)),
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
        $pagination = $this->pagination($options);

        $perPage = $pagination['perPage'];
        $page = $pagination['page'];
        $sort = $pagination['sort'];

        $cursor = $this->_collection->find()
            ->sort($sort)
            ->skip(($page - 1) * $perPage)
            ->limit($perPage);

        $pagination['results'] = $cursor;
        return $pagination;
    }

    public function pagination($options)
    {
        $conditions = isset($options['conditions']) ? $options['conditions'] : array();
        $totalRows = $this->_collection->find($conditions)->count();

        $perPage = isset($options['perPage']) ? $options['perPage'] : 25;

        $totalPages = max(ceil($totalRows / $perPage), 1);
        $page = 1;
        if (isset($options['page'])) {
            $page = min(max($options['page'], 1), $totalPages);
        }

        $sort = $this->_getSort($options);
        return array(
            'sort' => $sort,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        );
    }

    /**
     * Get sort options for a paginated set.
     *
     * Whitelists to valid known keys.
     *
     * @param array $options Pagination options including the sort key.
     * @return array Sort field & direction.
     */
    protected function _getSort($options)
    {
        $valid = array('wt', 'mu', 'cpu');
        if (
            empty($options['sort']) ||
            (isset($options['sort']) && !in_array($options['sort'], $valid))
        ) {
            return array('meta.SERVER.REQUEST_TIME' => -1);
        }
        if ($options['sort'] == 'wt') {
            return array('profile.main().wt' => -1);
        } elseif ($options['sort'] == 'mu') {
            return array('profile.main().mu' => -1);
        } elseif ($options['sort'] == 'cpu') {
           return array('profile.main().cpu' => -1);
        }
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

}
