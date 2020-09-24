<?php

namespace XHGui\Saver;

use XHGui\Db\PdoRepository;
use XHGui\Util;

class PdoSaver implements SaverInterface
{
    /** @var PdoRepository */
    private $db;

    public function __construct(PdoRepository $db)
    {
        $db->initSchema();
        $this->db = $db;
    }

    public function save(array $data)
    {
        $main = $data['profile']['main()'];

        // build 'request_ts' and 'request_date' from 'request_ts_micro'
        $ts = $data['meta']['request_ts_micro'];
        $sec = $ts['sec'];
        $usec = $ts['usec'];

        $this->db->saveProfile([
            'id'               => $data['_id'] ?? Util::generateId(),
            'profile'          => json_encode($data['profile']),
            'url'              => $data['meta']['url'],
            'SERVER'           => json_encode($data['meta']['SERVER']),
            'GET'              => json_encode($data['meta']['get']),
            'ENV'              => json_encode($data['meta']['env']),
            'simple_url'       => $data['meta']['simple_url'],
            'request_ts'       => $sec,
            'request_ts_micro' => "$sec.$usec",
            'request_date'     => date('Y-m-d', $sec),
            'main_wt'          => $main['wt'],
            'main_ct'          => $main['ct'],
            'main_cpu'         => $main['cpu'],
            'main_mu'          => $main['mu'],
            'main_pmu'         => $main['pmu'],
        ]);
    }
}
