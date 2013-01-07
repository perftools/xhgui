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
     * @param int $limit The number of runs to get.
     * @return MongoCursor
     */
    public function getForUrl($url, $limit)
    {
        return $this->_collection->find(array(
                'meta.simple_url' => $url
            ))
            ->sort(array(
                "meta.SERVER.REQUEST_TIME" => -1
            ))
            ->limit($limit);
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

        $records = $this->_collection->find()
            ->sort($sort)
            ->skip(($page - 1) * $perPage)
            ->limit($perPage);

        $pagination['results'] = $records;
        return $pagination;
    }

    public function pagination($options)
    {
        $totalRows = $this->_collection->find()->count();

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

}
