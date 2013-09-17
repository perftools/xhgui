<?php

class Xhgui_Saver_Mongo implements Xhgui_Saver_Interface
{
    protected $_profiles;
    
    public function __construct(Xhgui_Profiles $profiles) 
    {
        $this->_profiles = $profiles;
    }
    
    public function save($data)
    {
        return $this->_profiles->insert($data);
    }
}
