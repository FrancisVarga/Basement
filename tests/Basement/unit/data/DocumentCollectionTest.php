<?php
/**
 * Basement: The simple ODM for Couchbase on PHP
 *
 * @copyright     Copyright 2012, Michael Nitschinger
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use Basement\data\Document;
use Basement\data\DocumentCollection;

class DocumentCollectionTest extends PHPUnit_Framework_TestCase {

	/**
	 * Verifies a simple and empty collection.
	 */
	public function testEmptyCollection() {
		$collection = new DocumentCollection();
		$this->assertEquals(0, count($collection));
		$this->assertTrue($collection instanceof \Basement\data\DocumentCollection);
	}

	/**
	 * Verifies that the Collection can be used like a plain array.
	 */
	public function testArrayAccess() {
		$collection = new DocumentCollection();
		$this->assertEquals(0, count($collection));
		
		$emptyDoc = new Document();
		$collection[0] = $emptyDoc;
		$this->assertEquals(1, count($collection));
		$this->assertEquals($emptyDoc, $collection[0]);
		$this->assertFalse(isset($collection[1]));

		$collection[] = new Document();
		$this->assertEquals(2, count($collection));
		$this->assertTrue(isset($collection[1]));
		unset($collection[1]);
		$this->assertFalse(isset($collection[1]));
		$this->assertEquals(1, count($collection));

		unset($collection);
		$collection = new DocumentCollection();
		$docAmount = 10;
		for($i = 0; $i < $docAmount; $i++) {
			$collection[] = new Document();
		}
		$this->assertEquals($docAmount, count($collection));
		foreach($collection as $key => $val) {
			$this->assertTrue(is_int($key)); 
			$this->assertTrue($val instanceof \Basement\data\Document);
		}

	}

	/**
	 * Verifies that the Collection can be used with iterators.
	 */
	public function testIteratorAccess() {
		$collection = new DocumentCollection();
		$docAmount = 10;
		for($i = 0; $i < $docAmount; $i++) {
			$collection[] = new Document(array('key' => $i));
		}

		$this->assertEquals(0, $collection->current()->key());
		$this->assertEquals(0, $collection->key());

		$this->assertEquals(1, $collection->next()->key());
		$this->assertEquals(2, $collection->next()->key());
		$this->assertEquals(2, $collection->current()->key());

		$this->assertEquals(1, $collection->prev()->key());
		$this->assertEquals(1, $collection->current()->key());

		$this->assertEquals(0, $collection->rewind()->key());
	}

	/**
	 * Tests the size method.
	 */
	public function testSize() {
		$collection = new DocumentCollection();
		$this->assertEquals(0, $collection->size());
		$collection[] = new Document();
		$this->assertEquals(1, $collection->size());
	}

	/**
	 * Tests the clear method.
	 */
	public function testClear() {
		$collection = new DocumentCollection();
		$collection[] = new Document();
		$collection[] = new Document();
		$this->assertEquals(2, $collection->size());
		$collection->clear();
		$this->assertEquals(0, $collection->size());
	}

}

?>