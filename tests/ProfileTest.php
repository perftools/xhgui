<?php

class ProfileTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $contents = file_get_contents('tests/fixtures/results.json');
        $this->_fixture = json_decode($contents, true);
    }

    public function testProcessIncompleteData()
    {
        $data = array(
            'main()' => array(),
            'main()==>do_thing()' => array(
                // empty because of bad extension
            ),
            'other_thing()==>do_thing()' => array(
                'cpu' => 1,
            ),
        );
        $profile = new Xhgui_Profile(array('profile' => $data));
        $this->assertNotEmpty($profile->get('do_thing()'));
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
            'other_func==>isset' => array(
                'ct' => 10,
                'cpu' => 10,
                'wt' => 1,
                'mu' => 5,
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

    public function testGetRelativesWithThreshold()
    {
        $data = array(
            'main()' => array(
                'ct' => 1,
                'wt' => 100,
            ),
            'main()==>other_func' => array(
                'ct' => 1,
                'cpu' => 1,
                'wt' => 50,
                'mu' => 1,
                'pmu' => 1,
            ),
            'main()==>your_func' => array(
                'ct' => 1,
                'cpu' => 1,
                'wt' => 50,
                'mu' => 1,
                'pmu' => 1,
            ),
            'other_func==>func' => array(
                'ct' => 1,
                'cpu' => 1,
                'wt' => 10,
                'mu' => 1,
                'pmu' => 1,
            ),
            'other_func==>isset' => array(
                'ct' => 10,
                'cpu' => 10,
                'wt' => 1,
                'mu' => 5,
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

        $result = $profile->getRelatives('other_func', 'wt', 0.1);
        $this->assertCount(3, $result);

        list($parent, $current, $children) = $result;
        $this->assertCount(1, $parent);
        $this->assertEquals('main()', $parent[0]['function']);

        $this->assertCount(1, $children, 'One method below threshold');
        $this->assertEquals('func', $children[0]['function']);
    }

    public function testGet()
    {
        $fixture = $this->_fixture[0];
        $profile = new Xhgui_Profile($fixture);
        $this->assertEquals($fixture['profile']['main()']['wt'], $profile->get('main()', 'wt'));

        $expected = $fixture['profile']['main()'];
        $result = $profile->get('main()');
        unset($result['parents']);
        $this->assertEquals($expected, $result);

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

    public function testCalculateSelf()
    {
        $profile = new Xhgui_Profile($this->_fixture[1]);
        $result = $profile->calculateSelf()->getProfile();

        $main = $result['main()'];
        $this->assertEquals(800, $main['emu']);
        $this->assertEquals(250, $main['epmu']);
        $this->assertEquals(array(null), $main['parents']);

        $func = $result['eat_burger()'];
        $this->assertEquals(2, $func['ewt']);
        $this->assertEquals(1850, $func['emu']);
        $this->assertEquals(2300, $func['epmu']);
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

    public function testGetWatched()
    {
        $fixture = $this->_fixture[0];
        $profile = new Xhgui_Profile($fixture);
        $data = $profile->getProfile();

        $this->assertEmpty($profile->getWatched('not there'));
        $matches = $profile->getWatched('strpos.*');

        $this->assertCount(1, $matches);
        $this->assertEquals('strpos()', $matches[0]['function']);
        $this->assertEquals($data['strpos()']['wt'], $matches[0]['wt']);

        $matches = $profile->getWatched('str.*');
        $this->assertCount(1, $matches);
        $this->assertEquals('strpos()', $matches[0]['function']);
        $this->assertEquals($data['strpos()']['wt'], $matches[0]['wt']);

        $matches = $profile->getWatched('[ms].*');
        $this->assertCount(2, $matches);
        $this->assertEquals('strpos()', $matches[0]['function']);
        $this->assertEquals($data['strpos()']['wt'], $matches[0]['wt']);

        $this->assertEquals('main()', $matches[1]['function']);
        $this->assertEquals($data['main()']['wt'], $matches[1]['wt']);
    }

    public function testGetFunctionCount()
    {
        $fixture = $this->_fixture[0];
        $profile = new Xhgui_Profile($fixture);

        $this->assertEquals(11, $profile->getFunctionCount());
    }

    public function testCompareAllTheSame()
    {
        $fixture = $this->_fixture[0];
        $base = new Xhgui_Profile($fixture);
        $head = new Xhgui_Profile($fixture);

        $result = $base->compare($head);

        $this->assertArrayHasKey('diffPercent', $result);
        $this->assertArrayHasKey('diff', $result);
        $this->assertArrayHasKey('head', $result);
        $this->assertArrayHasKey('base', $result);

        $this->assertSame($base, $result['base']);
        $this->assertSame($head, $result['head']);

        $this->assertEquals(0, $result['diff']['main()']['ewt']);
        $this->assertEquals(0, $result['diff']['functionCount']);
        $this->assertEquals(0, $result['diff']['strpos()']['ewt']);
    }

    public function testCompareWithDifferences()
    {
        $fixture = $this->_fixture[0];
        $base = new Xhgui_Profile($this->_fixture[3]);
        $head = new Xhgui_Profile($this->_fixture[4]);
        $result = $base->compare($head);

        $this->assertEquals(0, $result['diff']['main()']['ct']);
        $this->assertEquals(9861, $result['diff']['main()']['wt']);

        $this->assertEquals(
            -10,
            $result['diff']['strpos()']['wt'],
            'Missing functions should show as negative'
        );
        $this->assertEquals(
            -10,
            $result['diff']['strpos()']['ewt'],
            'Should include exclusives'
        );
        $this->assertEquals(0.5, $result['diffPercent']['functionCount']);
    }

    public function testGetCallgraph()
    {
        $profile = new Xhgui_Profile($this->_fixture[1]);

        $expected = array(
            'metric' => 'wt',
            'total' => 35,
            'nodes' => array(
                array(
                    'name' => 'main()',
                    'value' => 35,
                    'callCount' => 1,
                ),
                array(
                    'name' => 'eat_burger()',
                    'value' => 25,
                    'callCount' => 1,
                ),
                array(
                    'name' => 'chew_food()',
                    'value' => 22,
                    'callCount' => 10,
                ),
                array(
                    'name' => 'strlen()',
                    'value' => 2,
                    'callCount' => 2,
                ),
                array(
                    'name' => 'drink_beer()',
                    'value' => 14,
                    'callCount' => 1,
                ),
                array(
                    'name' => 'lift_glass()',
                    'value' => 10,
                    'callCount' => 5,
                ),
            ),
            'links' => array(
                array(
                    'source' => 'main()',
                    'target' => 'eat_burger()',
                    'callCount' => 1,
                ),
                array(
                    'source' => 'eat_burger()',
                    'target' => 'chew_food()',
                    'callCount' => 10,
                ),
                array(
                    'source' => 'eat_burger()',
                    'target' => 'strlen()',
                    'callCount' => 2,
                ),
                array(
                    'source' => 'main()',
                    'target' => 'drink_beer()',
                    'callCount' => 1,
                ),
                array(
                    'source' => 'drink_beer()',
                    'target' => 'lift_glass()',
                    'callCount' => 5,
                ),
                array(
                    'source' => 'drink_beer()',
                    'target' => 'strlen()',
                    'callCount' => 2,
                ),
            )
        );
        $result = $profile->getCallgraph();
        $this->assertEquals($expected, $result);
    }

    public function testGetCallgraphNoDuplicates()
    {
        $profile = new Xhgui_Profile($this->_fixture[2]);

        $expected = array(
            'metric' => 'wt',
            'total' => 50139,
            'nodes' => array(
                array(
                    'name' => 'main()',
                    'value' => 50139,
                    'callCount' => 1,
                ),
                array(
                    'name' => 'load_file()',
                    'value' => 10000,
                    'callCount' => 1,
                ),
                array(
                    'name' => 'open()',
                    'value' => 10000,
                    'callCount' => 2,
                ),
                array(
                    'name' => 'strlen()',
                    'value' => 5000,
                    'callCount' => 1,
                ),
                array(
                    'name' => 'parse_string()',
                    'value' => 10000,
                    'callCount' => 1,
                )
            ),
            'links' => array(
                array(
                    'source' => 'main()',
                    'target' => 'load_file()',
                    'callCount' => 1,
                ),
                array(
                    'source' => 'load_file()',
                    'target' => 'open()',
                    'callCount' => 2,
                ),
                array(
                    'source' => 'open()',
                    'target' => 'strlen()',
                    'callCount' => 1,
                ),
                array(
                    'source' => 'main()',
                    'target' => 'parse_string()',
                    'callCount' => 1,
                ),
                array(
                    'source' => 'parse_string()',
                    'target' => 'open()',
                    'callCount' => 2,
                ),
            )
        );
        $result = $profile->getCallgraph();
        $this->assertEquals($expected, $result);
    }

    public function testGetDateFallback()
    {
        $data = array(
            'meta' => array(
                'SERVER' => array()
            )
        );
        $profile = new Xhgui_Profile($data);
        $result = $profile->getDate();
        $this->assertInstanceOf('DateTime', $result);
    }

    public function testGetFlamegraph()
    {
        $fixture = $this->_fixture[0];
        $profile = new Xhgui_Profile($fixture);
        $result = $profile->getFlamegraph();
        $expected = array(
            'data' => array(
                'name' => 'main()',
                'value' => 50139,
                'children' => array(
                    array(
                        'name' => 'strpos()',
                        'value' => 600
                    )
                ),
            ),
            'sort' => array(
                'main()' => 0,
                'strpos()' => 1
            )
        );
        $this->assertEquals($expected, $result);
    }
}
