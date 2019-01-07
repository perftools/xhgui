<?php

class Saver_FileTest extends PHPUnit\Framework\TestCase
{
    public function testSave()
    {
        $data = json_decode(file_get_contents(XHGUI_ROOT_DIR . '/tests/fixtures/results.json'), true);

        $file = tempnam(sys_get_temp_dir(), "xhgui");

        $saver = new Xhgui_Saver_File($file);
        $saver->save($data);

        $this->assertEquals(json_encode($data).PHP_EOL, file_get_contents($file));
    }

}
