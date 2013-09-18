<?php

class Saver_FileTest extends PHPUnit_Framework_TestCase
{
    public function testSave()
    {
        $data = array(
            'meta' => array(
                'some_meta_data'
            ),
            'profile' => array(
                array('symbols'=>array())
            )
        );
        
        $file = tempnam("/tmp", "xhgui");
        
        $saver = new Xhgui_Saver_File($file);
        $saver->save($data);
        
        $this->assertEquals(json_encode($data).PHP_EOL, file_get_contents($file));
    }

}
