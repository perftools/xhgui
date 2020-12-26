<?php

namespace XHGui\Test\Searcher;

use ArrayIterator;
use MongoDB;
use MultipleIterator;

class MongoHelper
{
    /** @var MongoDB */
    private $mongodb;
    /** @var array */
    private $indexes = [];

    public function __construct(MongoDB $mongodb)
    {
        $this->mongodb = $mongodb;
    }

    public function dropCollection(string $collectionName): void
    {
        $collection = $this->mongodb->selectCollection($collectionName);
        $collection->drop();
    }

    public function createCollection(string $collectionName, array $indexes): void
    {
        $collection = $this->mongodb->createCollection($collectionName);

        foreach ($indexes as [$keys, $options]) {
            $collection->createIndex($keys, $options);
        }
        $this->indexes[$collectionName] = $indexes;
    }

    public function getIndexes(string $collectionName): MultipleIterator
    {
        $collection = $this->mongodb->selectCollection($collectionName);
        $expectedIndexes = $this->indexes[$collectionName];

        $resultIndexInfo = $collection->getIndexInfo();
        $resultIndexes = array_column($resultIndexInfo, 'key');
        $resultIndexNames = array_column($resultIndexInfo, 'name');

        $iterator = new MultipleIterator();
        $iterator->attachIterator(new ArrayIterator($resultIndexes));
        $iterator->attachIterator(new ArrayIterator($resultIndexNames));
        $iterator->attachIterator(new ArrayIterator($expectedIndexes));

        return $iterator;
    }
}
