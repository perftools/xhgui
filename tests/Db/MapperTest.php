<?php

namespace XHGui\Test\Db;

use DateInterval;
use DateTime;
use MongoDate;
use XHGui\Db\Mapper;
use XHGui\Test\TestCase;

class MapperTest extends TestCase
{
    /** @var Mapper */
    private $mapper;

    public function setUp(): void
    {
        parent::setUp();
        $this->mapper = new Mapper();
    }

    public function testConvertConditions(): void
    {
        $opts = [
            'conditions' => [
                'simple_url' => '/tasks',
                'url' => 'tasks',
                'date_start' => '2013-01-20',
                'date_end' => '2013-01-21',
                'request_start' => '2013-02-13 12:22:00',
                'request_end' => '2013-02-13 14:22:00',
                'remote_addr' => '127.0.0.1',
            ],
        ];
        $result = $this->mapper->convert($opts);
        $expected = [
            'meta.simple_url' => '/tasks',
            'meta.url' => [
                '$regex' => 'tasks',
                '$options' => 'i',
            ],
            'meta.request_date' => [
                '$gte' => '2013-01-20',
                '$lte' => '2013-01-21',
            ],
            'meta.SERVER.REQUEST_TIME' => [
                '$gte' => strtotime($opts['conditions']['request_start']),
                '$lte' => strtotime($opts['conditions']['request_end']),
            ],
            'meta.SERVER.REMOTE_ADDR' => '127.0.0.1',
        ];
        $this->assertEquals($expected, $result['conditions']);
    }

    public function testConvertConditionsLimit(): void
    {
        $opts = [
            'conditions' => [
                'simple_url' => '/tasks',
                'limit' => 'P1D',
            ],
        ];
        $date = new DateTime();
        $date->sub(new DateInterval('P1D'));

        $result = $this->mapper->convert($opts);
        $expected = [
            'meta.request_ts' => [
                '$gte' => new MongoDate($date->getTimestamp()),
            ],
            'meta.simple_url' => '/tasks',
        ];
        $this->assertEquals($expected, $result['conditions']);
    }

    public function testConvertConditionsLimitIgnoreDateStart(): void
    {
        $opts = [
            'conditions' => [
                'simple_url' => '/tasks',
                'limit' => 'P1D',
                'date_start' => '2013-10-16',
            ],
        ];
        $date = new DateTime();
        $date->sub(new DateInterval('P1D'));

        $result = $this->mapper->convert($opts);
        $expected = [
            'meta.request_ts' => [
                '$gte' => new MongoDate($date->getTimestamp()),
            ],
            'meta.simple_url' => '/tasks',
        ];
        $this->assertEquals($expected, $result['conditions']);
    }

    public function testConditionsPartial(): void
    {
        $result = $this->mapper->convert([
            'conditions' => [
                'date_start' => '2013-01-15',
            ],
        ]);
        $expected = [
            'meta.request_date' => [
                '$gte' => '2013-01-15',
            ],
        ];
        $this->assertEquals($expected, $result['conditions']);

        $result = $this->mapper->convert([
            'conditions' => [
                'date_end' => '2013-01-20',
            ],
        ]);
        $expected = [
            'meta.request_date' => [
                '$lte' => '2013-01-20',
            ],
        ];
        $this->assertEquals($expected, $result['conditions']);

        $result = $this->mapper->convert([
            'conditions' => [
                'date_start' => '2013-01-15',
                'date_end' => '2013-01-20',
                'url' => 'tasks',
            ],
        ]);
        $expected = [
            'meta.url' => [
                '$regex' => 'tasks',
                '$options' => 'i',
            ],
            'meta.request_date' => [
                '$gte' => '2013-01-15',
                '$lte' => '2013-01-20',
            ],
        ];
        $this->assertEquals($expected, $result['conditions']);
    }

    public function testConvertSort(): void
    {
        $options = [
            'sort' => 'time',
        ];
        $result = $this->mapper->convert($options);
        $this->assertEquals(
            ['meta.SERVER.REQUEST_TIME' => -1],
            $result['sort']
        );

        $options = [
            'sort' => 'wt',
        ];
        $result = $this->mapper->convert($options);
        $this->assertEquals(
            ['profile.main().wt' => -1],
            $result['sort']
        );

        $options = [
            'sort' => 'wt',
            'direction' => 'asc',
        ];
        $result = $this->mapper->convert($options);
        $this->assertEquals(
            ['profile.main().wt' => 1],
            $result['sort']
        );
        $this->assertEquals('asc', $result['direction']);

        $options = [
            'sort' => 'wt',
            'direction' => 'desc',
        ];
        $result = $this->mapper->convert($options);
        $this->assertEquals(
            ['profile.main().wt' => -1],
            $result['sort']
        );
        $this->assertEquals('desc', $result['direction']);

        $options = [
            'sort' => 'wt',
            'direction' => 'farts',
        ];
        $result = $this->mapper->convert($options);
        $this->assertEquals(
            ['profile.main().wt' => -1],
            $result['sort']
        );
        $this->assertEquals('desc', $result['direction']);

        $options = [
            'sort' => 'barf',
        ];
        $result = $this->mapper->convert($options);
        $this->assertEquals(
            ['meta.SERVER.REQUEST_TIME' => -1],
            $result['sort']
        );
    }

    public function testConvertPerPage(): void
    {
        $options = [];
        $result = $this->mapper->convert($options);
        $this->assertEquals(25, $result['perPage']);

        $options = [
            'perPage' => 1,
        ];
        $result = $this->mapper->convert($options);
        $this->assertEquals(1, $result['perPage']);
    }
}
