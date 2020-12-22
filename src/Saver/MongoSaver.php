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

    public function __construct(MongoCollection $collection)
    {
        $this->_collection = $collection;
    }

    public function save(array $data, string $id = null): string
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
            '_id' => new MongoId($id),
            'meta' => $meta,
            'profile' => $this->encodeProfile($data['profile']),
        ];

        $this->_collection->insert($a, ['w' => 0]);

        return (string)$a['_id'];
    }

    /**
     * MongoDB can't save keys with values containing a dot:
     *
     *   InvalidArgumentException: invalid document for insert: keys cannot contain ".":
     *   "Zend_Controller_Dispatcher_Standard::loadClass==>load::controllers/ArticleController.php"
     *
     * Replace the dots with underscrore in keys.
     *
     * @see https://github.com/perftools/xhgui/issues/209
     */
    private function encodeProfile(array $profile): array
    {
        $results = [];
        foreach ($profile as $k => $v) {
            if (strpos($k, '.') !== false) {
                $k = str_replace('.', '_', $k);
            }
            $results[$k] = $v;
        }

        return $results;
    }
}
