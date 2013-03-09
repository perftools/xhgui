<?php
class DbTest extends PHPUnit_Framework_TestCase
{
    public function testConnect()
    {
        Xhgui_Config::load(XHGUI_ROOT_DIR . '/config/config.php');
        $db = Xhgui_Db::connect();
        $this->assertInstanceOf('MongoDb', $db);

        $db2 = Xhgui_Db::connect();
        $this->assertSame($db, $db2, 'Not the same object');
    }

    public function testGetConnection()
    {
        Xhgui_Config::load(XHGUI_ROOT_DIR . '/config/config.php');
        Xhgui_Db::connect();
        $mongo = Xhgui_Db::getConnection();
        $this->assertInstanceOf('MongoClient', $mongo);
    }

}
