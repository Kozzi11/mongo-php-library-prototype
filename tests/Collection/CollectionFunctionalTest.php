<?php

namespace MongoDB\Tests\Collection;

use MongoDB\Collection;
use MongoDB\Driver\BulkWrite;

/**
 * Functional tests for the Collection class.
 */
class CollectionFunctionalTest extends FunctionalTestCase
{
    /**
     * @expectedException MongoDB\Exception\InvalidArgumentException
     * @dataProvider provideInvalidNamespaceValues
     */
    public function testConstructorNamespaceArgument($namespace)
    {
        // TODO: Move to unit test once ManagerInterface can be mocked (PHPC-378)
        new Collection($this->manager, $namespace);
    }

    public function provideInvalidNamespaceValues()
    {
        return array(
            array(null),
            array(''),
            array('db_collection'),
            array('db'),
            array('.collection'),
        );
    }

    public function testToString()
    {
        $this->assertEquals($this->getNamespace(), (string) $this->collection);
    }

    public function getGetCollectionName()
    {
        $this->assertEquals($this->getCollectionName(), $this->collection->getCollectionName());
    }

    public function getGetDatabaseName()
    {
        $this->assertEquals($this->getDatabaseName(), $this->collection->getDatabaseName());
    }

    public function testGetNamespace()
    {
        $this->assertEquals($this->getNamespace(), $this->collection->getNamespace());
    }

    public function testDrop()
    {
        $writeResult = $this->collection->insertOne(array('x' => 1));
        $this->assertEquals(1, $writeResult->getInsertedCount());

        $commandResult = $this->collection->drop();
        $this->assertCommandSucceeded($commandResult);
        $this->assertCollectionCount($this->getNamespace(), 0);
    }

    public function testFindOne()
    {
        $this->createFixtures(5);

        $filter = array('_id' => array('$lt' => 5));
        $options = array(
            'skip' => 1,
            'sort' => array('x' => -1),
        );

        $expected = (object) array('_id' => 3, 'x' => 33);

        $this->assertEquals($expected, $this->collection->findOne($filter, $options));
    }

    /**
     * Create data fixtures.
     *
     * @param integer $n
     */
    private function createFixtures($n)
    {
        $bulkWrite = new BulkWrite(true);

        for ($i = 1; $i <= $n; $i++) {
            $bulkWrite->insert(array(
                '_id' => $i,
                'x' => (integer) ($i . $i),
            ));
        }

        $result = $this->manager->executeBulkWrite($this->getNamespace(), $bulkWrite);

        $this->assertEquals($n, $result->getInsertedCount());
    }
}
