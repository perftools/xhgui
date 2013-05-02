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
     * Get the 95th percentil metrics for a URL
     *
     * This will group data by date and returns only the
     * 95th percentil + date, making the data ideal for time series graphs
     *
     * @param string $url
     * @return array Array of metrics grouped by date
     */
    public function getPercentileForUrl($url)
    {
        //search for 95th position for each day
        $ranks = $this->_collection->aggregate(array(
            array('$match' => array('meta.simple_url' => $url)),
            array(
                '$project' => array(
                    'date' => '$meta.request_date',
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$date',
                    'count' => array('$sum' => 1),
                )
            ),
            array('$sort' => array('_id' => 1)),
            array(
                '$project' => array(
                    'count' => '$count',
                    'r' => array(
                        '$multiply'=>array(
                            array('$subtract'=> array('$count',1)),
                            0.95    
                        )
                    ),
                )
            )
        ));
        
        //for each metrics per day, search the value at $rank position
        $results = array();
        foreach ($ranks['result'] as $rank) {
            $result = array('date'=>$rank['_id']);
            $decimals = $rank['r']-floor($rank['r']);
            foreach (array('wt', 'cpu', 'mu', 'pmu') as $metric) {
                $options = array('sort' => $metric, 'direction' => 'asc',);
                $opts = $this->_mapper->convert($options);
                
                $cursor = $this->_collection->find(array('meta.simple_url' => $url, 'meta.request_date'=>$rank['_id']), array('profile.main().'.$metric))
                    ->sort($opts['sort'])
                    ->skip(floor($rank['r']))
                    ->limit(2);
                $first = $cursor->getNext()['profile']['main()'][$metric];
                $second = $cursor->getNext()['profile']['main()'][$metric];
                
                $result['avg_'.$metric] = $first+($decimals*($second-$first));
            }
            
            $results[] = $result;
        }
        
        return $results;
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
