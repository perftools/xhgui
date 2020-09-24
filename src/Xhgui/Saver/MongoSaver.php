<?php

namespace XHGui\Saver;

use MongoCollection;
use MongoDate;
use MongoId;

class MongoSaver implements SaverInterface
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
            '_id' => $data['_id'] ?? new MongoId(),
            'meta' => $meta,
            'profile' => $data['profile'],
        ];

        return $this->_collection->insert($a, ['w' => 0]);
    }
}
