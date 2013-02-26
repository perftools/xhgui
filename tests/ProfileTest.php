<?php

class ProfileTest extends PHPUnit_Framework_TestCase
{
    public function testGetRelatives()
    {
        $data = array(
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
        $result = Xhgui_Profile::getRelatives($data, 'func');
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
}
