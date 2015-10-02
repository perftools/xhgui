<?php

/**
 * Class Xhgui_Sites
 */
class Xhgui_Sites
{
    /** @var MongoDb */
    protected $_db;

    /** @var */
    protected $_list;

    /** @var */
    protected $_current;

    /** @var string */
    protected $_template = 'profiles_';

    /** @var bool */
    protected $_validate = true;

    /**
     * @param MongoDb $db
     */
    public function __construct(MongoDb $db)
    {
        $this->_db = $db;
    }

    /**
     * @return boolean
     */
    public function isValidate()
    {
        return $this->_validate;
    }

    /**
     * @param boolean $validate
     *
     * @return Xhgui_Sites
     */
    public function setValidate($validate)
    {
        $this->_validate = $validate;

        return $this;
    }

    /**
     * @return array
     */
    public function getAvailable()
    {
        if (null === $this->_list) {
            $colls = $this->_db->listCollections();
            $list = array();
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

    /**
     * @return mixed
     */
    public function getFirstAvailable()
    {
        return current($this->getAvailable());
    }

    /**
     * @return bool
     */
    public function hasCurrent()
    {
        return (bool) $this->getCurrent();
    }

    /**
     * @param $current
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setCurrent($current)
    {
        if (true === $this->isValidate() && false === in_array($current, $this->getAvailable())) {
            throw new InvalidArgumentException('No such site');
        }

        $this->_current = $current;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCurrent()
    {
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
