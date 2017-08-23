<?php
/**
 * Contains logic for getting/creating/removing profile records.
 */
class Xhgui_Profiles
{
    protected $_collection;

    protected $_mapper;

    /**
     * @var MongoId lastProfilingId
     */
    private static $lastProfilingId;

    public function __construct(MongoDb $db)
    {
        $this->_collection = $db->results;
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
        $profile['_id'] = self::getLastProfilingId();
        return $this->_collection->insert($profile, array('w' => 0));
    }

    /**
     * Return profiling ID
     * @return MongoId lastProfilingId
     */
    public static function getLastProfilingId() {
        if (!self::$lastProfilingId) {
            self::$lastProfilingId = new MongoId();
        }
        return self::$lastProfilingId;
    }
}
