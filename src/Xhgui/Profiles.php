<?php
/**
 * Contains logic for getting/creating/removing profile records.
 */
class Xhgui_Profiles
{
    /**
     * @var Xhgui_StorageInterface
     */
    protected $storage;

    /**
     * Xhgui_Profiles constructor.
     * @param Xhgui_StorageInterface $storage
     */
    public function __construct(\Xhgui_StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param $conditions
     * @param null $fields
     * @return mixed
     */
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
        return $this->wrap($this->storage->findOne($id));
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

    /**
     * @param Xhgui_Storage_Filter $filter
     * @return array
     * @throws Exception
     */
    public function paginate(Xhgui_Storage_Filter $filter)
    {
        $projection = false;

        if ($projection === false) {
            $result = $this->storage->find($filter, null);
        } else {
            $result = $this->storage->find($filter, $projection);
        }

        $totalRows = $this->storage->count($filter);
        $totalPages = max(ceil($totalRows / $filter->getPerPage()), 1);

        return array(
            'results'       => $this->wrap($result),
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
            if ($result['_id'] instanceof MongoDate) {
                $result['date'] = date('Y-m-d H:i:s', $result['_id']->sec);
            } else {
                $result['date'] = $result['_id'];
            }
            
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
    protected function wrap($data)
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

    /**
     * @return Xhgui_StorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @param Xhgui_StorageInterface $storage
     */
    public function setStorage($storage)
    {
        $this->storage = $storage;
    }
}
