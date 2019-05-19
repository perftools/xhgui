<?php
class Xhgui_WatchFunctions
{
    protected $storage;

    public function __construct(\Xhgui_StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Save a value to the collection.
     *
     * Will do an insert or update depending
     * on the id field being present.
     *
     * @param array $data The data to save.
     * @return boolean
     */
    public function save($data)
    {
        if (empty($data['name'])) {
            return false;
        }

        if (!empty($data['removed']) && isset($data['_id'])) {
            $this->storage->remove($data['_id']);
            return true;
        }

        if (empty($data['_id'])) {
            $this->storage->insert($data);
            return true;
        }
        $this->storage->update($data['_id'], $data);
        return true;
    }

    /**
     * Get all the known watch functions.
     *
     * @return array Array of watch functions.
     */
    public function getAll()
    {
        $cursor = $this->storage->find();
        if ($this->storage instanceof \Xhgui_Storage_Mongo) {
            $ret = $cursor->toArray();
        } else {
            $ret = array_column($cursor->toArray(), 'profile', '_id');
        }
        return $ret;
    }

    /**
     * Truncate the watch collection.
     *
     * @return void
     */
    public function truncate()
    {
        $this->storage->drop();
    }

}
