<?php

class ProfileTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $contents = file_get_contents('tests/fixtures/results.json');
        $this->_fixture = json_decode($contents, true);
    }

    public function testGetRelatives()
    {
        $data = array(
            'main()' => array(),
            'main()==>other_func' => array(
                'ct' => 1,
                'cpu' => 1,
                'wt' => 1,
                'mu' => 1,
                'pmu' => 1,
            ),
            'main()==>your_func' => array(
                'ct' => 1,
                'cpu' => 1,
                'wt' => 1,
                'mu' => 1,
                'pmu' => 1,
            ),
            'other_func==>func' => array(
                'ct' => 1,
                'cpu' => 1,
                'wt' => 1,
                'mu' => 1,
                'pmu' => 1,
            ),
            'your_func==>func' => array(
                'ct' => 1,
                'cpu' => 1,
                'wt' => 1,
                'mu' => 1,
                'pmu' => 1,
            ),
            'func==>strlen' => array(
                'ct' => 1,
                'cpu' => 1,
                'wt' => 1,
                'mu' => 1,
                'pmu' => 1,
            ),
            'func==>isset' => array(
                'ct' => 1,
                'cpu' => 1,
                'wt' => 1,
                'mu' => 1,
                'pmu' => 1,
            ),
        );
        $profile = new Xhgui_Profile(array('profile' => $data));

        $result = $profile->getRelatives('not there at all');
        $this->assertCount(3, $result);
        $this->assertEquals(array(), $result[0]);
        $this->assertEquals(array(), $result[1]);
        $this->assertEquals(array(), $result[2]);

        $result = $profile->getRelatives('func');
        $this->assertCount(3, $result);

        list($parent, $current, $children) = $result;
        $this->assertCount(2, $parent);
        $this->assertEquals('other_func', $parent[0]['function']);
        $this->assertEquals('your_func', $parent[1]['function']);

        $this->assertCount(2, $children);
        $this->assertEquals('strlen', $children[0]['function']);
        $this->assertEquals('isset', $children[1]['function']);

        $this->assertEquals('func', $current['function']);
        $this->assertEquals(2, $current['ct']);
        $this->assertEquals(2, $current['wt']);
        $this->assertEquals(2, $current['mu']);
        $this->assertEquals(2, $current['pmu']);
    }

    public function testGet()
    {
        $fixture = $this->_fixture[0];
        $profile = new Xhgui_Profile($fixture);
        $this->assertEquals($fixture['profile']['main()']['wt'], $profile->get('main()', 'wt'));

        $this->assertNull($profile->get('main()', 'derp'));
        $this->assertNull($profile->get('derp', 'wt'));
    }

    public function testGetMeta()
    {
        $fixture = $this->_fixture[0];
        $profile = new Xhgui_Profile($fixture);

        $this->assertEquals($fixture['meta'], $profile->getMeta());

        $this->assertEquals($fixture['meta']['simple_url'], $profile->getMeta('simple_url'));
        $this->assertEquals($fixture['meta']['SERVER']['REQUEST_TIME'], $profile->getMeta('SERVER.REQUEST_TIME'));

        $this->assertNull($profile->getMeta('not there'));
        $this->assertNull($profile->getMeta('SERVER.NOT_THERE'));
    }

    public function testExtractDimension()
    {
        $profile = new Xhgui_Profile($this->_fixture[0]);
        $result = $profile->extractDimension('mu', 1);

        $this->assertCount(1, $result);
        $expected = array(
            'name' => 'main()',
            'value' => 3449360
        );
        $this->assertEquals($expected, $result[0]);
    }

    public function testCalculateExclusive()
    {
        $profile = new Xhgui_Profile($this->_fixture[1]);
        $result = $profile->calculateExclusive()->getProfile();

        $main = $result['main()'];
        $this->assertEquals(800, $main['emu']);
        $this->assertEquals(250, $main['epmu']);
        $this->assertEquals(array(null), $main['parents']);

        $func = $result['eat_burger()'];
        $this->assertEquals(3, $func['ewt']);
        $this->assertEquals(1900, $func['emu']);
        $this->assertEquals(2350, $func['epmu']);
        $this->assertEquals(array('main()'), $func['parents']);
    }

    public function testSort()
    {
        $data = array(
            'main()' => array(
                'mu' => 12345
            ),
            'main()==>class_exists()' => array(
                'mu' => 34567
            ),
        );
        $profile = new Xhgui_Profile(array());
        $result = $profile->sort('mu', $data);

        $expected = array(
            'main()==>class_exists()' => array(
                'mu' => 34567
            ),
            'main()' => array(
                'mu' => 12345
            ),
        );
        $this->assertSame($expected, $result);
    }

}
