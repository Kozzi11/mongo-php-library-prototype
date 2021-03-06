<?php

namespace MongoDB\Tests\Collection\CrudSpec;

use MongoDB\Collection;
use MongoDB\Driver\ReadPreference;

/**
 * CRUD spec functional tests for aggregate().
 *
 * @see https://github.com/mongodb/specifications/tree/master/source/crud/tests
 */
class AggregateFunctionalTest extends FunctionalTestCase
{
    private static $wireVersionForOutOperator = 2;

    public function setUp()
    {
        parent::setUp();

        $this->createFixtures(3);
    }

    public function testAggregateWithMultipleStages()
    {
        $cursor = $this->collection->aggregate(
            array(
                array('$sort' => array('x' => 1)),
                array('$match' => array('_id' => array('$gt' => 1))),
            ),
            array('batchSize' => 2)
        );

        $expected = array(
            array('_id' => 2, 'x' => 22),
            array('_id' => 3, 'x' => 33),
        );

        $this->assertSameDocuments($expected, $cursor);
    }

    public function testAggregateWithOut()
    {
        $server = $this->manager->selectServer(new ReadPreference(ReadPreference::RP_PRIMARY));

        if ( ! \MongoDB\server_supports_feature($server, self::$wireVersionForOutOperator)) {
            $this->markTestSkipped('$out aggregation pipeline operator is not supported');
        }

        $outputCollection = new Collection($this->manager, $this->getNamespace() . '_output');
        $this->dropCollectionIfItExists($outputCollection);

        $this->collection->aggregate(
            array(
                array('$sort' => array('x' => 1)),
                array('$match' => array('_id' => array('$gt' => 1))),
                array('$out' => $outputCollection->getCollectionName()),
            )
        );

        $expected = array(
            array('_id' => 2, 'x' => 22),
            array('_id' => 3, 'x' => 33),
        );

        $this->assertSameDocuments($expected, $outputCollection->find());

        // Manually clean up our output collection
        $this->dropCollectionIfItExists($outputCollection);
    }
}
