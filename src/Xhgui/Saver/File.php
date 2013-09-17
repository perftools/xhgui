<?php

class Xhgui_Saver_File implements Xhgui_Saver_Interface
{
    protected $_file;

    public function __construct($file) 
    {
        $this->_file = $file;
    }
    
    public function save($data)
    {
        $json = json_encode($data);
        return file_put_contents($this->_file, $json.PHP_EOL, FILE_APPEND);
    }
}
