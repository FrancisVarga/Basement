<?php
/**
 * Basement: The simple ODM for Couchbase on PHP
 *
 * @copyright     Copyright 2012, Michael Nitschinger
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use Basement\data\Document;

class DocumentTest extends PHPUnit_Framework_TestCase {

	/**
	 * Tests the creation of an empty document and checks for the random key that is
	 * generated when not defined.
	 */
	public function testEmptyCreation() {
		$document = new Document();
		$this->assertEmpty($document->doc());

		$key = $document->key();
		$this->assertRegexp('/\w+/', $key);
	}

	/**
	 * Verifies the correct document creation with a key and with an empty document.
	 */
	public function testCreationWithKey() {
		$key = 'mykey';
		$document = new Document(compact('key'));

		$this->assertEmpty($document->doc());
		$this->assertEquals($key, $document->key());
	}

	/**
	 * Verifies that the document instance is created correctly with both key and document.
	 */
	public function testCreationWithKeyAndDoc() {
		$key = 'mykey';
		$doc = array('foo' => 'bar');
		$document = new Document(compact('key', 'doc'));
		
		$this->assertEquals($doc, $document->doc());
		$this->assertEquals($key, $document->key());
	}

	public function testCreationWithKeyDocAndValue() {
		$key = 'mykey';
		$doc = array('foo' => 'bar');
		$value = 'something';
		$document = new Document(compact('key', 'doc', 'value'));
		
		$this->assertEquals($doc, $document->doc());
		$this->assertEquals($key, $document->key());
		$this->assertEquals($value, $document->value());
	}

	/**
	 * Tests the modification of attributes.
	 */
	public function testModificationOfAttributes() {
		$document = new Document();
		$oldkey = $document->key();
		$olddoc = $document->doc();
		$oldcas = $document->cas();
		$oldval = $document->value();

		$document->key('newkey');
		$document->doc('newdoc');
		$document->cas('newcas');
		$document->value('newval');

		$this->assertEquals('newkey', $document->key());
		$this->assertEquals('newdoc', $document->doc());
		$this->assertEquals('newcas', $document->cas());
		$this->assertEquals('newval', $document->value());
	}

	/**
	 * Tests if the serialization of the payload works properly with both an array and an
	 * object.
	 */
	public function testSerialization() {
		$doc = array('foo' => 'bar');
		$document = new Document(compact('doc'));
		$this->assertEquals($doc, unserialize($document->serialize()));

		$doc = new DateTime();
		$document->doc($doc);
		$this->assertEquals($doc, unserialize($document->serialize()));
	}

	/**
	 * Verifies proper JSON encoding of the payload.
	 */
	public function testJsonEncode() {
		$doc = array('foo' => 'bar');
		$document = new Document(compact('doc'));
		$this->assertEquals($doc, json_decode($document->toJson(), true));

		$doc = new DateTime();
		$document->doc($doc);
		$decoded = json_decode($document->toJson(), true);
		$this->assertEquals($doc->date, $decoded['date']);
	}

	/**
	 * Tests getting and setting of document attributes.
	 */
	public function testGetAndSet() {
		$document = new Document();
		$document->set('beer', 'is_cool');
		$document->set('wine', array('not_so' => 'much'));

		$this->assertEquals('is_cool', $document->get('beer'));
		$this->assertEquals(array('not_so' => 'much'), $document->get('wine'));
		$this->assertNull($document->get('notSet'));
		$expected = array(
			'beer' => 'is_cool',
			'wine' => array('not_so' => 'much')
		);
		$this->assertEquals($expected, $document->doc());
	}

	/**
	 * Tests the __get and __set magic methods for syntactic sugar.
	 */
	public function testMagicGettersAndSetters() {
		$document = new Document();
		$document->firstname = 'Michael';
		$document->gender = 'male';

		$expected = array(
			'firstname' => 'Michael',
			'gender' => 'male'
		);
		$this->assertEquals($expected, $document->doc());
	}
}

?>