<?php

class Xhgui_Storage_File extends Xhgui_Storage_Abstract implements
    Xhgui_StorageInterface,
    Xhgui_WatchedFunctionsStorageInterface
{

    /**
     * @var string
     */
    protected $path     = '../data/';

    /**
     * @var string
     */
    protected $prefix   = 'xhgui.data';

    /**
     * @var bool|mixed
     */
    protected $separateMeta = true;

    /**
     * @var mixed
     */
    protected $dataSerializer;

    /**
     * @var mixed
     */
    protected $metaSerializer;

    /**
     * @var string
     */
    protected $watchedFunctionsPathPrefix = '../watched_functions/';

    /**
     * @var int[]
     */
    protected $countCache;

    /**
     * @var Xhgui_Storage_Filter
     */
    private $filter;

    /**
     * Xhgui_Storage_File constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->prefix           = 'xhgui.data';

        $this->path             = $config['save.handler.path'];
        $this->separateMeta     = $config['save.handler.separate_meta'];
        $this->dataSerializer   = $config['save.handler.serializer'];
        $this->metaSerializer   = $config['save.handler.meta_serializer'];
    }

    /**
     * @inheritDoc
     * @param Xhgui_Storage_Filter $filter
     * @param bool $projections
     * @return Xhgui_Storage_ResultSet
     */
    public function find(Xhgui_Storage_Filter $filter, $projections = false)
    {

        if ($filter->getId()) {
            $result = glob($this->path. DIRECTORY_SEPARATOR.$filter->getId());
        } else {
            $result = glob($this->path. $this->prefix . '*');
            sort($result);
        }

        $ret = [];
        foreach ($result as $i => $file) {
            // skip meta files.
            if (strpos($file, '.meta') !== false) {
                continue;
            }

            // try to detect timestamp in filename, to optimize searching.
            // If that fails we need to get it after file import from meta.
            $reqTimeFromFilename = $this->getRequestTimeFromFilename($file);
            if (!empty($reqTimeFromFilename)) {
                if (null !== $filter->getStartDate() &&
                    $this->getDateTimeFromString($filter->getStartDate(), 'start') >= $reqTimeFromFilename) {
                    continue;
                }

                if (null !== $filter->getEndDate() &&
                    $this->getDateTimeFromString($filter->getEndDate(), 'end') <= $reqTimeFromFilename ) {
                    continue;
                }
            }

            $metaFile   = $this->getMetafileNameFromProfileName($file);

            $meta       = $this->importFile($metaFile, true);
            if ($meta === false) {
                continue;
            }

            $profile    = $this->importFile($file, false);
            if ($profile === false) {
                continue;
            }

            if (!empty($profile['meta'])) {
                $meta = array_merge($meta, $profile['meta']);
            }

            if (empty($reqTimeFromFilename) && (null !== $filter->getStartDate() || null !== $filter->getEndDate())){
                if (null !== $filter->getStartDate() &&
                    $this->getDateTimeFromString($filter->getStartDate(), 'start') >= $filter->getStartDate()) {
                    continue;
                }
                if (null !== $filter->getEndDate() &&
                    $this->getDateTimeFromString($filter->getEndDate(), 'end') <= $filter->getEndDate()) {
                    continue;
                }
            }

            if ($filter->getUrl() &&
                strpos($meta['simple_url'], $filter->getUrl()) === false &&
                strpos($meta['SERVER']['SERVER_NAME'].$meta['simple_url'], $filter->getUrl()) === false
            ) {
                continue;
            }

            if (null !== $filter->getCookie() && strpos($meta['SERVER']['HTTP_COOKIE'], $filter->getCookie()) === false) {
                continue;
            }

            if (null !== $filter->getIp() && $meta['SERVER']['REMOTE_ADDR'] !== $filter->getIp()) {
                continue;
            }

            if (!empty($profile['profile'])) {
                $profile = $profile['profile'];
            }

            if (!empty($profile['_id'])) {
                $id = $profile['_id'];
            } else {
                $id = basename($file);
            }
            if (!empty($profile)) {
                $ret[$id] = [
                    'profile'   => $profile,
                    '_id'       => $id,
                    'meta'      => $meta,
                ];
            } else {
                $ret[$id] = $profile;
            }
        }

        try {
            if (!empty($filter->getSort()) && !empty($ret)) {
                $this->filter = $filter;
                usort($ret, array($this, 'sortByColumn'));
                unset($this->filter);
            }
        } catch (InvalidArgumentException $e) {
            // ignore for now.
        }
        
        $cacheId = md5(serialize($filter->toArray()));

        $this->countCache[$cacheId] = count($ret);
        $ret = array_slice($ret, $filter->getPerPage()*($filter->getPage()-1), $filter->getPerPage());
        $ret = array_column($ret, null, '_id');

        return new Xhgui_Storage_ResultSet($ret, $this->countCache[$cacheId]);
    }

    /**
     * @inheritDoc
     * @param Xhgui_Storage_Filter $filter
     * @return int
     */
    public function count(Xhgui_Storage_Filter $filter)
    {
        $cacheId = md5(serialize($filter->toArray()));
        if (empty($this->countCache[$cacheId])) {
            $this->find($filter);
        }
        return $this->countCache[$cacheId];
    }

    /**
     * @inheritDoc
     * @param $id
     * @return mixed
     */
    public function findOne($id)
    {
        $filter = new Xhgui_Storage_Filter();
        $filter->setId($id);
        $resultSet = $this->find($filter);
        return $resultSet->current();
    }

    /**
     * @inheritDoc
     * @param $id
     * @return bool
     */
    public function remove($id)
    {
        if (file_exists($this->path.$id)) {
            $metaFileName = $this->getMetafileNameFromProfileName($id);
            if (file_exists($this->path.$metaFileName)) {
                unlink($this->path.$metaFileName);
            }
            unlink($this->path.$id);
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function drop()
    {
        array_map('unlink', glob($this->path.'*.xhprof'));
        array_map('unlink', glob($this->path.'*.meta'));
    }

    /**
     * @inheritDoc
     * @param $match
     * @param $col
     * @param int $percentile
     * @return array
     */
    public function aggregate(Xhgui_Storage_Filter $filter, $col, $percentile = 1)
    {
        $ret = $this->find($filter);

        $result = [
            'ok'        => 1,
            'result'    => [],
        ];

        foreach ($ret as $row) {
            $date = \DateTime::createFromFormat(
                'U u',
                $row['meta']['request_ts_micro']['sec'].' '.$row['meta']['request_ts_micro']['usec']
            );
            $formattedDate = $date->format('Y-m-d H:i');

            if (empty($result['result'][$formattedDate])) {
                $result['result'][$formattedDate] = [
                    'wall_times'    => [],
                    'cpu_times'     => [],
                    'mu_times'      => [],
                    'pmu_times'     => [],
                    'row_count'     => 0
                ];
            }

            $result['result'][$formattedDate]['wall_times'][]    = $row['profile']['main()']['wt'];
            $result['result'][$formattedDate]['cpu_times'][]     = $row['profile']['main()']['cpu'];
            $result['result'][$formattedDate]['mu_times'][]      = $row['profile']['main()']['mu'];
            $result['result'][$formattedDate]['pmu_times'][]     = $row['profile']['main()']['pmu'];
            $result['result'][$formattedDate]['row_count']++;

            $result['result'][$formattedDate]['raw_index'] =
                $result['result'][$formattedDate]['row_count']*($percentile/100);

            $result['result'][$formattedDate]['_id']= $date->format('Y-m-d H:i:s');
        }

        return $result;
    }


    /**
     * Column sorter
     *
     * @param $a
     * @param $b
     * @return int
     */
    public function sortByColumn($a, $b)
    {
        $sort = $this->filter->getSort();
        switch ($sort) {
            case 'ct':
            case 'wt':
            case 'cpu':
            case 'mu':
            case 'pmu':
                $aValue = $a['profile']['main()'][$sort];
                $bValue = $b['profile']['main()'][$sort];
                break;

            case 'time':
                $aValue = $a['meta']['request_ts']['sec'];
                $bValue = $b['meta']['request_ts']['sec'];
                break;

            case 'controller':
            case 'action':
            case 'application':
            case 'branch':
            case 'version':
                $aValue = $a['meta'][$sort];
                $bValue = $b['meta'][$sort];
                break;

            default:
                throw new InvalidArgumentException('Invalid sort mode');
                break;
        }

        if ($aValue == $bValue) {
            return 0;
        }

        if (is_numeric($aValue) || is_numeric($bValue)) {
            if ($this->filter->getDirection() === 'desc') {
                if ($aValue < $bValue) {
                    return 1;
                }
                return -1;
            }

            if ($aValue > $bValue) {
                return 1;
            }
            return -1;
        }

        if ($this->filter->getDirection() === 'desc') {
            return strnatcmp($aValue, $bValue);
        }
        return strnatcmp($bValue, $aValue);
    }

    /**
     * Generate meta profile name from profile file name.
     *
     * In most cases just add .meta extension
     *
     * @param $file
     * @return mixed
     */
    protected function getMetafileNameFromProfileName($file)
    {
        $metaFile = $file.'.meta';
        return $metaFile;
    }

    /**
     * Load profile file from disk, prepare it and return parsed array
     *
     * @param $path
     * @param bool $meta
     * @return mixed
     */
    protected function importFile($path, $meta = false)
    {
        if ($meta) {
            $serializer = $this->metaSerializer;
        } else {
            $serializer = $this->dataSerializer;
        }

        if (!file_exists($path) || !is_readable($path)) {
            return false;
        }
        
        switch ($serializer) {
            default:
            case 'json':
                return json_decode(file_get_contents($path), true);

            case 'serialize':
                if (PHP_MAJOR_VERSION > 7) {
                    return unserialize(file_get_contents($path), false);
                }
                /** @noinspection UnserializeExploitsInspection */
                return unserialize(file_get_contents($path));

            case 'igbinary_serialize':
            case 'igbinary_unserialize':
            case 'igbinary':
                /** @noinspection PhpComposerExtensionStubsInspection */
                return igbinary_unserialize(file_get_contents($path));

            // this is a path to a file on disk
            case 'php':
            case 'var_export':
                /** @noinspection PhpIncludeInspection */
                return include $path;
        }
    }

    /**
     * @inheritDoc
     * @return array
     */
    public function getWatchedFunctions()
    {
        $ret = [];
        $files = glob($this->watchedFunctionsPathPrefix.'*.json');
        foreach ($files as $file) {
            $ret[] = json_decode(file_get_contents($file), true);
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
        $id = md5($name);
        $i = file_put_contents(
            $this->watchedFunctionsPathPrefix.$id.'.json',
            json_encode(['id'=>$id, 'name'=>$name])
        );
        return $i > 0;
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

        $i = file_put_contents(
            $this->watchedFunctionsPathPrefix.$id.'.json',
            json_encode(['id'=>$id, 'name'=>trim($name)])
        );
        return $i > 0;
    }

    /**
     * @inheritDoc
     * @param $id
     */
    public function removeWatchedFunction($id)
    {
        if (file_exists($this->watchedFunctionsPathPrefix.$id.'.json')) {
            unlink($this->watchedFunctionsPathPrefix.$id.'.json');
        }
    }

    /**
     * Parse filename and try to get request time from filename
     *
     * @param $fileName
     * @return bool|DateTime
     */
    public function getRequestTimeFromFilename($fileName)
    {
        $matches = [];
        // default pattern is: xhgui.data.<timestamp>.<microseconds>_a68888
        //  xhgui.data.15 55 31 04 66 .6606_a68888
        preg_match('/(?<t>[\d]{10})(\.(?<m>[\d]{1,6}))?.+/i', $fileName, $matches);
        try {
            return DateTime::createFromFormat('U u', $matches['t'].' '. $matches['m']);
        } catch (Exception $e) {
            return null;
        }
    }
}
