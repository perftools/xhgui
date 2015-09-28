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
            sort($list);

            $this->_list = $list;
        }

        return $this->_list;
    }

    public function getFirstAvailable()
    {
        return current($this->getAvailable());
    }

    public function hasCurrent()
    {
        return ($this->getCurrent());
    }

    /**
     * @param $current
     *
     * @return $this
     * @throws \Slim\Exception\Pass
     */
    public function setCurrent($current)
    {
        if (false === in_array($current, $this->getAvailable())) {
            throw new \Slim\Exception\Pass('No such site');
        }

        $this->_current = $current;

        Xhgui_Config::write('db.collection', $this->getCurrentCollection());

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCurrent()
    {
        if (null === $this->_current) {
            $this->_current = current($this->getAvailable());
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
