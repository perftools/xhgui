<?php

class Xhgui_Saver_Mongo implements Xhgui_Saver_Interface
{
    /**
     * @var MongoCollection
     */
    private $_collection;

    /**
     * @var MongoId lastProfilingId
     */
    private static $lastProfilingId;

    public function __construct(MongoCollection $collection)
    {
        $this->_collection = $collection;
    }

    public function save(array $data)
    {
        // build 'request_ts' and 'request_date' from 'request_ts_micro'
        $ts = $data['meta']['request_ts_micro'];
        $sec = $ts['sec'];
        $usec = $ts['usec'];

        $meta = [
            'url' => $data['meta']['url'],
            'get' => $data['meta']['get'],
            'env' => $data['meta']['env'],
            'SERVER' => $data['meta']['SERVER'],
            'simple_url' => $data['meta']['simple_url'],
            'request_ts' => new MongoDate($sec),
            'request_ts_micro' => new MongoDate($sec, $usec),
            'request_date' => date('Y-m-d', $sec),
        ];

        $a = [
            '_id' => $data['_id'] ?? self::getLastProfilingId(),
            'meta' => $meta,
            'profile' => $data['profile'],
        ];

        return $this->_collection->insert($a, ['w' => 0]);
    }

    /**
     * Return profiling ID
     * @return MongoId lastProfilingId
     */
    public static function getLastProfilingId()
    {
        if (!self::$lastProfilingId) {
            self::$lastProfilingId = new MongoId();
        }

        return self::$lastProfilingId;
    }
}
