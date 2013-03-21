<?php
class Xhgui_WatchFunctions
{
    protected $_collection;

    public function __construct(MongoCollection $collection)
    {
        $this->_collection = $collection;
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
        if (empty($data['_id'])) {
            $this->_collection->insert(
                $data,
                array('w' => 1)
            );
            return true;
        }

        $id = new MongoId($data['_id']);
        unset($data['_id']);
        $this->_collection->update(
            array('_id' => $id),
            $data,
            array('w' => 1)
        );
        return true;
    }

    public function getAll()
    {
        $cursor = $this->_collection->find();
        return iterator_to_array($cursor);
    }

    public function truncate()
    {
        $this->_collection->drop();
    }

}
