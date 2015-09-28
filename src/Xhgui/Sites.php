<?php

/**
 * Class Xhgui_Sites
 */
class Xhgui_Sites
{
    /**
     * @var
     */
    protected $_list;
    /**
     * @var
     */
    protected $_current;
    /**
     * @var string
     */
    protected $_template = 'profiles_';

    /**
     * @return array
     */
    public function getAvailable()
    {
        if (null === $this->_list) {
            $client = new MongoClient(Xhgui_Config::read('db.host'));
            $db = $client->selectDB(Xhgui_Config::read('db.db'));
            $colls = $db->listCollections();
            $list = [];
            /** @var MongoCollection $coll */
            foreach ($colls as $coll) {
                $name = $coll->getName();
                if (0 === strpos($name, $this->_template)) {
                    $list[] = str_replace($this->_template, '', $name);
                }
            }
            $this->_list = $list;
        }

        return $this->_list;
    }

    public function hasCurrent()
    {
        return ($this->getCurrent());
    }

    /**
     * @param $current
     *
     * @return $this
     */
    public function setCurrent($current)
    {
        $this->_current = $current;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCurrent()
    {
        if (null === $this->_current) {
            $available = $this->getAvailable();
            if (isset($_COOKIE['currsite']) && in_array($_COOKIE['currsite'], $available, false)) {
                $this->_current = $_COOKIE['currsite'];
            } else {
                $this->_current = current($available);
            }
        }

        return $this->_current;
    }

    /**
     * @return string
     */
    public function getCurrentCollection()
    {
        return $this->_getCollectionName($this->getCurrent());
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _getCollectionName($name)
    {
        return $this->_template.$name;
    }
}
