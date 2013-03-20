<?php
class Xhgui_WatchFunctions
{
    protected $_collection;

    public function __construct(MongoCollection $collection)
    {
        $this->_collection = $collection;
    }

    public function save($data)
    {
    }

    public function delete($id)
    {
    }

    public function getAll()
    {
        return array(
            array(
                'id' => '1',
                'name' => 'strlen',
            ),
            array(
                'id' => '1',
                'name' => 'empty',
            ),
        );
    }

}
