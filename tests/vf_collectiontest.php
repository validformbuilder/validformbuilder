<?php

require_once("PHPUnit/Autoload.php");
require_once("../libraries/ValidForm/class.vf_collection.php");

class VF_CollectionTest extends PHPUnit_Framework_TestCase {
	protected $collection;
	protected $dummyData = array("hallo", "daar", "hier");

	protected function setUp() {
		$this->collection = new VF_Collection();
		$this->collection->addObjects($this->dummyData);
	}

	protected function tearDown() {
		unset($this->collection);
	}

	public function testShouldHaveEmptyCollection() {
		$collection = new VF_Collection();
		$this->assertEquals(0, $collection->count());
	}

	public function testAddObject() {
		$strTest = "test_" . mt_rand(0,999999);
		$this->collection->addObject($strTest);
		$this->assertContains($strTest, $this->collection);
	}

	/**
	 * @depends testAddObject
	 */
	public function testAddObjects() {
		$data = array(
			"hallo_" . mt_rand(0,999999),
			"hier_" . mt_rand(0,999999)
		);

		$this->collection->addObject($data);
		$this->assertContains($data, $this->collection);
	}

	/**
	 * @depends testAddObject
	 */
	public function testGetLast() {
		$this->assertEquals(array_pop($this->dummyData), $this->collection->getLast());
	}

	/**
	 * @depends testAddObject
	 */
	public function testGetFirst() {
		$this->assertEquals(array_shift($this->dummyData), $this->collection->getFirst());
	}

	public function testKey() {
		$this->assertEquals(key($this->dummyData), $this->collection->key());
	}

	/**
	 * @depends testKey
	 */
	public function testSeek() {
		$intPosition = 1;
		$this->collection->seek($intPosition); // Should set the pointer to 'daar'

		$this->assertEquals($intPosition, $this->collection->key());
	}

	public function testCurrent() {
		$this->assertEquals(current($this->dummyData), $this->collection->current());
	}

	public function testNext() {
		$this->assertEquals(next($this->dummyData), $this->collection->next());
	}

	public function testPrevious() {
		$this->assertEquals(prev($this->dummyData), $this->collection->previous());
	}

	public function testIsFirst() {
		$this->assertTrue($this->collection->isFirst());
	}

	/**
	 * @depends testSeek
	 */
	public function testIsLast() {
		$this->collection->seek(count($this->dummyData) - 1);
		$this->assertTrue($this->collection->isLast());
	}

    public function testMerge() {
    	$collection = new VF_Collection();
    	$collection->addObjects(array("hallo", "hier", "daar", "en", "morgen"));

    	$this->assertTrue(is_object($collection));
    	$this->assertTrue($collection->count() > 0);
    	$this->assertClassHasAttribute("collection", get_class($collection));

    	// $tmpMerge = array_merge($this->collection, )

		// if (is_object($collection) && $collection->count() > 0) {
  //       	$this->collection = array_merge($this->collection, $collection->collection);
		// }
    }


}