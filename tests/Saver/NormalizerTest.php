<?php

namespace XHGui\Test\Saver;

use XHGui\Saver\NormalizingSaver;
use XHGui\Saver\SaverInterface;
use XHGui\Test\TestCase;

class NormalizerTest extends TestCase
{
    /** @var NormalizingSaver */
    private $normalizer;

    public function setUp(): void
    {
        $this->saver = new class() implements SaverInterface {
            private $store = [];

            public function save(array $data, string $id = null): string
            {
                $this->store[] = $data;

                return $id;
            }

            public function first(): array
            {
                return $this->store[0];
            }
        };

        $this->normalizer = new NormalizingSaver($this->saver);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testSave(array $profile, array $normalized): void
    {
        $this->normalizer->save($profile, $profile['_id'] ?? null);
        $this->assertEquals($normalized, $this->saver->first());
    }

    public function dataProvider(): iterable
    {
        $results = $this->loadFixture('results.json');
        $normalized = $this->loadFixture('normalized.json');

        foreach ($results as $index => $result) {
            yield $index => [$result, $normalized[$index]];
        }
    }
}
