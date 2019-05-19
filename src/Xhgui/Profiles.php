<?php
/**
 * Contains logic for getting/creating/removing profile records.
 */
class Xhgui_Profiles
{
    protected $storage;

    public function __construct(\Xhgui_StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Get the latest profile data.
     *
     * @return Xhgui_Profile
     * @throws Exception
     */
    public function latest()
    {
        $cursor = $this->storage->find()
                                ->sort(array('meta.request_date' => -1))
                                ->limit(1);
        $result = $cursor->getNext();
        return $this->_wrap($result);
    }

    public function query($conditions, $fields = null)
    {
        return $this->storage->find($conditions, $fields);
    }

    /**
     * Get a single profile run by id.
     *
     * @param string $id The id of the profile to get.
     * @return Xhgui_Profile
     * @throws Exception
     */
    public function get($id)
    {
        return $this->_wrap($this->storage->findOne($id));
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

    public function paginate(Xhgui_Storage_Filter $filter)
    {
        $projection = false;
        if (isset($options['projection'])) {
            if ($options['projection'] === true) {
                $projection = array('meta' => 1, 'profile.main()' => 1);
            } else {
                $projection = $options['projection'];
            }
        }

        if ($projection === false) {
            $result = $this->storage->find($filter, null);
        } else {
            $result = $this->storage->find($filter, $projection);
        }

        $totalRows = $this->storage->count($filter);
        $totalPages = max(ceil($totalRows / $filter->getPerPage()), 1);

        return array(
            'results'       => $this->_wrap($result),
            'sort'          => $filter->getSort(),
            'direction'     => $filter->getDirection(),
            'page'          => $filter->getPage(),
            'perPage'       => $filter->getPerPage(),
            'totalPages'    => $totalPages
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
     * @param array $search Search options containing startDate and or endDate
     * @return array Array of metrics grouped by date
     */
    public function getPercentileForUrl($percentile, $url, $filter)
    {
        $col = '$meta.request_date';
//        if (!empty($search['limit']) && $search['limit'][0] == "P") {
//            $col = '$meta.request_ts';
//        }

        $results = $this->storage->aggregate($filter, $col, $percentile);
        if (empty($results['result'])) {
            return array();
        }
        $keys = array(
            'wall_times'    => 'wt',
            'cpu_times'     => 'cpu',
            'mu_times'      => 'mu',
            'pmu_times'     => 'pmu'
        );
        foreach ($results['result'] as &$result) {
            $result['date'] = ($result['_id'] instanceof MongoDate) ? date('Y-m-d H:i:s', $result['_id']->sec) : $result['_id'];
            unset($result['_id']);
            $index = max(round($result['raw_index']) - 1, 0);
            foreach ($keys as $key => $out) {
                sort($result[$key]);
                $result[$out] = isset($result[$key][$index]) ? $result[$key][$index] : null;
                unset($result[$key]);
            }
        }
        return array_values($results['result']);
    }

    /**
     * Get a paginated set of results.
     *
     * @param array $options The find options to use.
     * @return array An array of result data.
     */
    public function getAll($filter)
    {
        return $this->paginate($filter);
    }

    /**
     * Insert a profile run.
     *
     * Does unchecked inserts.
     *
     * @param array $profile The profile data to save.
     * @return
     */
    public function insert($profile)
    {
        return $this->storage->insert($profile, array('w' => 0));
    }

    /**
     * Delete a profile run.
     *
     * @param string $id The profile id to delete.
     * @return array|bool
     */
    public function delete($id)
    {
        return $this->storage->remove($id);
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
        return $this->storage->drop();
    }

    /**
     * Converts arrays + MongoCursors into Xhgui_Profile instances.
     *
     * @param array|MongoCursor $data The data to transform.
     * @return Xhgui_Profile|array The transformed/wrapped results.
     * @throws Exception
     */
    protected function _wrap($data)
    {
        if ($data === null) {
            throw new Exception('No profile data found.');
        }

        if (!($data instanceof \Xhgui_Storage_ResultSet)) {
            return new Xhgui_Profile($data, true);
        }

        $results = array();
        foreach ($data as $row) {
            $results[] = new Xhgui_Profile($row, true);
        }
        return $results;
    }
}
