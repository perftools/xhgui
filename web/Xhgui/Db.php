<?php

class Xhgui_Db
{
    protected $_mongo;
    protected $_db;
    protected $_collection;
    protected $_mapper;

    public function __construct($host = null, $collection = 'results')
    {
        if (empty($host)) {
            $host = Xhgui_Config::read('db.host');
        }
        $this->_mongo = new Mongo($host);
        $this->_db = $this->_mongo->xhprof;
        $this->_collection = $this->_db->{$collection};
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
        $options = array_merge($options, array(
            'conditions' => array(
                'simple_url' => $url
            )
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
            'results' => $cursor,
            'sort' => $opts['sort'],
            'page' => $page,
            'perPage' => $opts['perPage'],
            'totalPages' => $totalPages
        );
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

}
