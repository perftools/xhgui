<?php

class Saver_FileTest extends PHPUnit_Framework_TestCase
{
    public function testSave()
    {
        $data = file_get_contents('tests/fixtures/results.json');
        
        $file = tempnam(sys_get_temp_dir(), "xhgui");
        
        $saver = new Xhgui_Saver_File($file);
        $saver->save($data);
        
        $this->assertEquals(json_encode($data).PHP_EOL, file_get_contents($file));
    }

}
