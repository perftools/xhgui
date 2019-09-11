<?php

/**
 * Class Xhgui_Storage_Mongo
 */
class Xhgui_Storage_Mongo extends Xhgui_Storage_Abstract implements
    \Xhgui_StorageInterface,
    \Xhgui_WatchedFunctionsStorageInterface
{

    protected $config;

    /**
     * @var \MongoDB
     */
    protected $connection;

    /**
     * @var int
     */
    protected $defaultPerPage = 25;

    /**
     * @var string
     */
    protected $collectionName;

    /**
     * @var \MongoClient
     */
    protected $mongoClient;

    /**
     * Mongo constructor.
     * @param $config
     * @throws \MongoConnectionException
     * @throws \MongoException
     */
    public function __construct($config, $collection = 'results')
    {
        $this->config = $config;
        // set default number of rows for all results. This can be changed
        // for each query
        $this->defaultPerPage = $config['page.limit'];

        $this->collectionName = $collection;

        // make sure options is an array
        if (empty($config['db.options'])) {
            $config['db.options'] = array();
        }

        $config['db.options']['connect'] = true;
    }

    /**
     * @inheritDoc
     * @param $options
     * @param bool $projections
     * @return Xhgui_Storage_ResultSet
     * @throws \MongoCursorException
     */
    public function find(\Xhgui_Storage_Filter $filter, $projections = false)
    {
        $sort = array();
        switch ($filter->getSort()) {
            case 'ct':
            case 'wt':
            case 'cpu':
            case 'mu':
            case 'pmu':
                $sort['profile.main().' . $filter->getSort()] = $filter->getDirection() === 'asc' ? 1 : -1;
                break;
            case 'time':
                $sort['meta.request_ts'] = $filter->getDirection() === 'asc' ? 1 : -1;
                break;
        }

        $conditions = $this->getConditions($filter);

        $ret = $this->getCollection()
                    ->find($conditions)
                    ->sort($sort)
                    ->skip((int)($filter->getPage() - 1) * $filter->getPerPage())
                    ->limit($filter->getPerPage());


        $result = new \Xhgui_Storage_ResultSet(iterator_to_array($ret));
        return $result;
    }

    /**
     * @inheritDoc
     * @param $options
     * @return int
     * @throws \MongoCursorTimeoutException
     * @throws \MongoException
     */
    public function count(\Xhgui_Storage_Filter $filter)
    {
        $conditions = $this->getConditions($filter);

        $ret = $this->getCollection()->find($conditions, array('_id' => 1))->count();
        return $ret;
    }

    /**
     * @inheritDoc
     * @param $id
     * @return array|null
     * @throws \MongoException
     */
    public function findOne($id)
    {
        $ret = $this->getCollection()
                    ->findOne(array('_id' => new \MongoId($id)));
        return $ret;
    }

    /**
     * @inheritDoc
     * @param $id
     * @return array|bool
     * @throws MongoCursorException
     * @throws MongoCursorTimeoutException
     * @throws MongoException
     */
    public function remove($id)
    {
        return $this->getCollection()->remove(
            array('_id' => new MongoId($id)),
            array('w' => 1)
        );
    }

    /**
     * @inheritDoc
     */
    public function drop()
    {
        // TODO: Implement drop() method.
    }

    /**
     * @inheritDoc
     * @param $match
     * @param $col
     * @param int $percentile
     * @codeCoverageIgnore despite appearances this is very simple function and there is nothing to test here.
     * @return array
     * @throws \MongoException
     */
    public function aggregate(\Xhgui_Storage_Filter $filter, $col, $percentile = 1)
    {

        $conditions = $this->getConditions($filter);
        $param = array(
            array('$match' => $conditions),
            array(
                '$project' => array(
                    'date'           => '$meta.request_ts',
                    'profile.main()' => 1
                )
            ),
            array(
                '$group' => array(
                    '_id'        => '$date',
                    'row_count'  => array('$sum' => 1),
                    'wall_times' => array('$push' => '$profile.main().wt'),
                    'cpu_times'  => array('$push' => '$profile.main().cpu'),
                    'mu_times'   => array('$push' => '$profile.main().mu'),
                    'pmu_times'  => array('$push' => '$profile.main().pmu'),
                )
            ),
            array(
                '$project' => array(
                    'date'       => '$date',
                    'row_count'  => '$row_count',
                    'raw_index'  => array(
                        '$multiply' => array(
                            '$row_count',
                            $percentile / 100
                        )
                    ),
                    'wall_times' => '$wall_times',
                    'cpu_times'  => '$cpu_times',
                    'mu_times'   => '$mu_times',
                    'pmu_times'  => '$pmu_times',
                )
            ),
            array(
                '$sort' => array('_id' => 1)
            ),
        );
        $ret = $this->getCollection()->aggregate(
            $param,
            array(
                'cursor' => array('batchSize' => 0)
            )
        );

        return $ret;
    }

    /**
     * @inheritDoc
     * @return array
     */
    public function getWatchedFunctions()
    {
        $ret = array();
        try {
            $cursor = $this->getConnection()->watches->find()->sort(array('name' => 1));
            $ret = array();
            foreach ($cursor as $row) {
                $ret[] = array('id' => $row['_id']->__toString(), 'name' => $row['name']);
            }
        } catch (\Exception $e) {
            // if something goes wrong just return empty array
            // @todo add exception
        }
        return $ret;
    }

    /**
     * @inheritDoc
     * @param $name
     * @return bool
     */
    public function addWatchedFunction($name)
    {

        $name = trim($name);
        if (empty($name)) {
            return false;
        }

        try {
            $id = new \MongoId();

            $data = array(
                '_id'  => $id,
                'name' => $name
            );
            $this->getConnection()->watches->insert($data);

            return true;
        } catch (\Exception $e) {
            // if something goes wrong just ignore for now
            // @todo add exception
        }
        return false;
    }

    /**
     * @inheritDoc
     * @param $id
     * @param $name
     * @return bool
     */
    public function updateWatchedFunction($id, $name)
    {
        $name = trim($name);
        if (empty($name)) {
            return false;
        }

        try {
            $id = new \MongoId($id);
            $data = array(
                '_id'  => $id,
                'name' => $name
            );
            $this->getConnection()->watches->save($data);

            return true;
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * @inheritDoc
     * @param $id
     * @return bool
     */
    public function removeWatchedFunction($id)
    {

        try {
            $id = new \MongoId($id);

            $this->getConnection()->watches->remove(array('_id' => $id));

            return true;
        } catch (\Exception $e) {
        }
        return false;
    }

    /**
     * Convert filter into mongo condition
     *
     * @param \Xhgui_Storage_Filter $filter
     * @return array
     */
    protected function getConditions(\Xhgui_Storage_Filter $filter)
    {
        $conditions = array();
        if (null !== $filter->getStartDate()) {
            $conditions['meta.request_ts']['$gte'] = new \MongoDate(
                $this->getDateTimeFromString($filter->getStartDate(), 'start')->format('U')
            );
        }

        if (null !== $filter->getEndDate()) {
            $conditions['meta.request_ts']['$lte'] = new \MongoDate(
                $this->getDateTimeFromString($filter->getEndDate(), 'end')->format('U')
            );
        }

        if (null !== $filter->getUrl()) {
            $conditions['meta.simple_url'] = new \MongoRegex('/'.preg_quote($filter->getUrl(), '/').'/');
        }

        if (null !== $filter->getIp()) {
            $conditions['meta.SERVER.REMOTE_ADDR'] = $filter->getIp();
        }

        if (null !== $filter->getCookie()) {
            $conditions['meta.SERVER.HTTP_COOKIE'] = new \MongoRegex('/'.preg_quote($filter->getCookie(), '/').'/');
        }

        foreach (array(
                     'method'      => 'method',
                     'application' => 'application',
                     'version'     => 'version',
                     'branch'      => 'branch',
                     'controller'  => 'controller',
                     'action'      => 'action',
                 ) as $dbField => $field) {
            $method = 'get' . ucfirst($field);
            if ($filter->{$method}()) {
                $conditions['meta.' . $dbField] = $filter->{$method}();
            }
        }

        return $conditions;
    }

    /**
     * Get mongo client from config
     *
     * @return MongoClient
     * @throws MongoConnectionException
     */
    public function getMongoClient()
    {
        if (empty($this->mongoClient)) {
            $this->mongoClient = new \MongoClient($this->config['db.host'], $this->config['db.options']);
        }
        return $this->mongoClient;
    }

    /**
     * Set prepared mongo client.
     *
     * @param MongoClient $mongoClient
     */
    public function setMongoClient($mongoClient)
    {
        $this->mongoClient = $mongoClient;
    }

    /**
     * Get connection.
     *
     * @return MongoDB
     * @throws MongoConnectionException
     */
    public function getConnection()
    {
        if (empty($this->connection)) {
            $this->connection = $this->getMongoClient()->{$this->config['db.db']};
        }

        return $this->connection;
    }

    /**
     * Set existing connection
     *
     * @param MongoDB $connection
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Select specific connection
     *
     * @return MongoCollection
     * @throws Exception
     */
    public function getCollection()
    {
        return $this->getConnection()->selectCollection($this->collectionName);
    }
}
