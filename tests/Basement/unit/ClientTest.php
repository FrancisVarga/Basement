<?php
/**
 * Basement: The simple ODM for Couchbase on PHP
 *
 * @copyright     Copyright 2012, Michael Nitschinger
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use Basement\Client;
use Basement\data\Document;
use Basement\view\Query;
use Basement\view\ViewResult;

class ClientTest extends PHPUnit_Framework_TestCase {

	/**
	 * A working instance of the client to test against.
	 */
	protected $_client = null;

	/**
	 * Use this to override the default config settings.
	 */
	protected $_testConfig = array(
		'host' => '127.0.0.1',
		'environment' => 'test'
	);

	/**
	 * Instantiate a Client ready to use for the tests.
	 */
	public function setUp() {
		$this->_client = new Client($this->_testConfig);
	}

	/**
	 * Delete the given keys to leave the bucket in a clean state.
	 */
	protected function _deleteKeys($keys = array()) {
		foreach($keys as $key) {
			$this->_client->connection()->delete($key);
		}
	}

	/**
	 * Tests the default configuration settings.
	 */
	public function testDefaultSettings() {
		$client = new Client(array('connect' => false));

		$expected = array(
			'host' => '127.0.0.1',
			'bucket' => 'default',
			'password' => '',
			'user' => null,
			'persist' => false,
			'transcoder' => 'json',
			'environment' => 'development'
		);

		$config = $client->config();
		unset($config['connect']);

		$this->assertEquals($expected, $config);
	}

	/**
	 * Tests that when connect is false it does not connect.
	 */
	public function testConnectParam() {
		$client = new Client(array('connect' => false));
		$config = $client->config();

		$this->assertFalse($config['connect']);
		$this->assertFalse($client->connected());
		$this->assertFalse($client->connection());
	}

	/**
	 * Tests the proper connection to the Couchbase cluster.
	 */
	public function testSuccessfulConnect() {
		$client = new Client($this->_testConfig);

		$this->assertTrue($client->connected());
		$this->assertInstanceOf('Couchbase', $client->connection());
		$this->assertTrue($client->connect());
	}

	/**
	 * Tests the proper exception raising on connection failure.
	 *
	 * @expectedException RuntimeException
	 */
	public function testInvalidConnectHost() {
		$client = new Client(array('host' => '1.2.3.4'));
	}

	/**
	 * Tests the proper connection to the Couchbase cluster.
	 *
	 * There is something not working so this is skipped for now.
	 */
	public function testSuccessfulConnectWithTwoHosts() {
		$this->markTestSkipped('Skipped because of a bug in the SDK.');

		$config = array('host' => array('1.2.3.4', $this->_testConfig['host']));
		$client = new Client($config);

		$this->assertTrue($client->connected());
		$this->assertInstanceOf('Couchbase', $client->connection());
	}

	/**
	 * Verifies the proper usage of the different version variations.
	 */
	public function testVersion() {
		$versionRegex = '/(\d)+\.(\d)+.(\d)+(-(\w)+)*/';

		$this->assertRegexp($versionRegex, $this->_client->version());
		$this->assertRegexp($versionRegex, $this->_client->version('client'));

		$result = $this->_client->version('cluster');
		$this->assertGreaterThanOrEqual(1, count($result));
		foreach($result as $addr => $version) {
			$this->assertTrue((is_string($addr) && !empty($addr)));
			$this->assertTrue((is_string($addr) && !empty($version)));
		}
	}

	/**
	 * Test the exception when an invalid version type is given.
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidVersion() {
		$this->_client->version('invalid');
	}

	/**
	 * Tests the generation of key names with optional prefixes.
	 */
	public function testGenerateKey() {
		$result1 = Client::generateKey();
		$this->assertTrue(is_string($result1));
		$this->assertRegexp('/\w+/', $result1);

		$result2 = Client::generateKey();
		$this->assertNotEquals($result1, $result2);
	}

	/**
	 * Tests the save operation with default settings and an array
	 * as the document to store.
	 */
	public function testSaveWithDefaultSettingsAndArray() {
		$key = 'testdocument-1';
		$doc = array('foobar');

		$result = $this->_client->save(compact('key', 'doc'));
		$this->assertTrue(is_string($result));
		$this->assertNotEmpty($result);

		$check = $this->_client->connection()->get($key);
		$this->assertEquals($doc, json_decode($check));

		$this->_deleteKeys(array($key));
	}

	/**
	 * Test with an array document that is not well formatted.
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testSaveWithInvalidArray() {
		$document = array('just' => 'some', 'data');
		$result = $this->_client->save($document);	
	}

	/**
	 * Test with a string as document.
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testSaveWithInvalidString() {
		$result = $this->_client->save("storeme");	
	}

	/**
	 * Test the correct saving of a Basement Document.
	 */
	public function testSaveWithDefaultSettingsAndDocument() {
		$key = 'testdocument-1';
		$doc = array('foobar');

		$document = new Document(compact('key', 'doc'));
		$result = $this->_client->save($document);
		$this->assertTrue(is_string($result));
		$this->assertNotEmpty($result);

		$check = $this->_client->connection()->get($key);
		$this->assertEquals($doc, json_decode($check));

		$this->_deleteKeys(array($key));
	}

	/**
	 * Tests the save command with the add operation.
	 */
	public function testSaveWithNoOverride() {
		$key = 'testdocument-1';
		$doc = array('foobar');

		$stored = 'something';
		$this->_client->connection()->set($key, $stored);

		$document = new Document(compact('key', 'doc'));
		$result = $this->_client->save($document, array('override' => false));

		$this->assertEquals($stored, $this->_client->connection()->get($key));
		$this->assertFalse($result);

		$this->_deleteKeys(array($key));
	}

	/**
	 * Tests the save command with the replace operation.
	 */
	public function testSaveWithReplace() {
		$key = 'testdocument-1';
		$doc = array('foobar');

		$this->_client->connection()->delete($key);

		$document = new Document(compact('key', 'doc'));
		$result = $this->_client->save($document, array('replace' => true));
		$this->assertNull($this->_client->connection()->get($key));
		$this->assertFalse($result);

		$this->_deleteKeys(array($key));
	}

	/**
	 * Tests the find method with the key option.
	 */
	public function testFindWithKeyWithFirst() {
		$key = 'mykey';
		$value = json_encode('foo');
		$first = true;

		$result = $this->_client->find('key', compact('key', 'first'));
		$this->assertFalse($result);

		$result = $this->_client->connection()->set($key, $value);
		$this->assertNotEmpty($result);

		$result = $this->_client->find('key', compact('key', 'first'));
		$this->assertEquals('Basement\data\Document', get_class($result));
		$this->assertEquals($key, $result->key());
		$this->assertEquals(json_decode($value), $result->doc());
		$this->assertNotEmpty($result->cas());

		$this->_deleteKeys(array($key));
	}

	/**
	 * Tests the key find with returning raw data.
	 */
	public function testFindWithKeyRawWithFirst() {
		$key = 'mykey';
		$value = json_encode('foo');
		$raw = true;
		$first = true;

		$result = $this->_client->find('key', compact('key', 'raw', 'first'));
		$this->assertFalse($result);

		$result = $this->_client->connection()->set($key, $value);
		$this->assertNotEmpty($result);

		$result = $this->_client->find('key', compact('key', 'raw', 'first'));
		$this->assertTrue(is_string($result));
		$this->assertEquals($value, $result);

		$this->_deleteKeys(array($key));
	}

	/**
	 * Tests the key find with returning serialized data.
	 */
	public function testFindWithKeySerializedWithFirst() {
		$key = 'mykey';
		$value = serialize('foo');
		$transcoder = 'serialize';
		$first = true;

		$result = $this->_client->find('key', compact('key', 'transcoder', 'first'));
		$this->assertFalse($result);

		$result = $this->_client->connection()->set($key, $value);
		$this->assertNotEmpty($result);

		$result = $this->_client->find('key', compact('key', 'transcoder', 'first'));
		$this->assertEquals('Basement\data\Document', get_class($result));
		$this->assertEquals($key, $result->key());
		$this->assertEquals(unserialize($value), $result->doc());
		$this->assertNotEmpty($result->cas());

		$this->_deleteKeys(array($key));
	}

	/** 
	 * Test find with an array of keys.
	 */
	public function testFindWithMultipleKeys() {
		$data = array(
			'multikey_1' => array('foo'),
			'multikey_2' => array('bar'),
			'multikey_3' => array('aaa')
		);

		foreach($data as $key => $value) {
			$this->_client->connection()->set($key, json_encode($value));
		}

		$documents = $this->_client->findByKey(array_keys($data));
		$this->assertEquals(count($data), count($documents));
		foreach($documents as $num => $document) {
			$this->assertEquals('Basement\data\Document', get_class($document));
			$this->assertEquals($data[$document->key()], $document->doc());
			$this->assertTrue(array_key_exists($document->key(), $data));
			$this->assertNotEmpty($document->cas());
		}

		$this->_deleteKeys(array_keys($data));
	}

	/**
	 * Tests the regular collection behavior.
	 */
	public function testFindWithKeyWithoutFirst() {
		$key = 'mykey';
		$value = json_encode('foo');

		$result = $this->_client->find('key', compact('key'));
		$this->assertFalse($result);

		$result = $this->_client->connection()->set($key, $value);
		$this->assertNotEmpty($result);

		$result = $this->_client->find('key', compact('key'));
		$this->assertFalse(empty($result));
		$this->assertEquals(1, count($result));
		foreach($result as $doc) {
			$this->assertEquals($key, $doc->key());
			$this->assertEquals(json_decode($value), $doc->doc());
			$this->assertNotEmpty($doc->cas());
		}

		$this->_deleteKeys(array($key));
	}

	/**
	 * Tests the wrapper findByKey() method.
	 */
	public function testFindByKeyWithFirst() {
		$key = 'mykey';
		$value = json_encode('foo');
		$first = true;

		$result = $this->_client->findByKey($key, compact('first'));
		$this->assertFalse($result);

		$result = $this->_client->connection()->set($key, $value);
		$this->assertNotEmpty($result);

		$result = $this->_client->findByKey($key, compact('first'));
		$this->assertEquals('Basement\data\Document', get_class($result));
		$this->assertEquals($key, $result->key());
		$this->assertEquals(json_decode($value), $result->doc());
		$this->assertNotEmpty($result->cas());

		$this->_deleteKeys(array($key));
	}

	/**
	 * Tests basic querying of a view.
	 *
	 * Design: "posts", View: "all"
	 *
	 * function (doc, meta) {
	 *   if(doc.type == "post") {
	 *     emit(meta.id, null);
	 *   }
	 * }
	 *
	 */
	public function testFindWithView() {
		$docAmount = 5;
		$keysToDelete = $this->_populateSamplePostDocuments($docAmount);

		sleep(2);

		$design = 'posts';
		$view = 'all';
		$query = array('stale' => 'false', 'reduce' => 'false');
		$result = $this->_client->find('view', compact('design', 'view', 'query'));
		$this->assertEquals($docAmount, count($result->get()));
		$this->assertTrue($result instanceof \Basement\view\ViewResult);
		$this->assertFalse($result->isReduced());

		foreach($result->get() as $document) {
			$this->assertRegexp('/post:\d/', $document->key());
			$this->assertNull($document->doc());
			$this->assertNull($document->cas());
		}

		$query = new Query();
		$query->stale(false)->reduce(false);
		$result = $this->_client->find('view', compact('design', 'view', 'query'));
		$this->assertEquals($docAmount, count($result->get()));
		$this->assertTrue($result instanceof \Basement\view\ViewResult);
		$this->assertFalse($result->isReduced());

		foreach($result->get() as $document) {
			$this->assertRegexp('/post:\d/', $document->key());
			$this->assertNull($document->doc());
			$this->assertNull($document->cas());
		}

		$this->_deleteKeys($keysToDelete);
	}

	/**
	 * Helper method to create documents for the testFindWithView.
	 */
	protected function _populateSamplePostDocuments($docAmount) {
		$keysToDelete = array();
		for($i = 1; $i <= $docAmount; $i++) {
			$key = "post:$i";
			$encoded = json_encode(array(
				'type' => 'post',
				'title' => 'This is post number: ' . $i
			));
			$this->_client->connection()->set($key, $encoded);
			$keysToDelete[] = $key;
		}
		return $keysToDelete;		
	}

	/**
	 * Tests passing in the view query and include docs.
	 */
	public function testFindWithViewAndIncludeDocs() {
		$docAmount = 5;
		$keysToDelete = $this->_populateSamplePostDocuments($docAmount);

		sleep(2);

		$design = 'posts';
		$view = 'all';
		$query = array('stale' => 'false', 'reduce' => 'false', 'include_docs' => 'true');
		$result = $this->_client->find('view', compact('design', 'view', 'query'));
		$this->assertEquals($docAmount, count($result->get()));
		$this->assertTrue($result instanceof \Basement\view\ViewResult);
		$this->assertFalse($result->isReduced());

		foreach($result->get() as $document) {
			$this->assertRegexp('/post:\d/', $document->key());
			$this->assertNotEmpty($document->doc());
			$this->assertTrue(is_array($document->doc()));
			$this->assertNull($document->cas());
		}

		$query = new Query();
		$query->stale(false)->reduce(false)->includeDocs(true);
		$result = $this->_client->find('view', compact('design', 'view', 'query'));
		$this->assertEquals($docAmount, count($result->get()));
		$this->assertTrue($result instanceof \Basement\view\ViewResult);
		$this->assertFalse($result->isReduced());

		foreach($result->get() as $document) {
			$this->assertRegexp('/post:\d/', $document->key());
			$this->assertNotEmpty($document->doc());
			$this->assertTrue(is_array($document->doc()));
			$this->assertNull($document->cas());
		}

		$this->_deleteKeys($keysToDelete);
	}

	/**
	 * Tests the behavior with a view reduce function.
	 */
	public function testFindWithViewAndReduce() {
		$docAmount = 5;
		$keysToDelete = $this->_populateSamplePostDocuments($docAmount);

		sleep(2);

		$design = 'posts';
		$view = 'all';
		$query = array('stale' => 'false', 'reduce' => 'true');
		$result = $this->_client->find('view', compact('design', 'view', 'query'));
		$this->assertTrue($result->isReduced());
		$documents = $result->get();
		$this->assertEquals($docAmount, $documents[0]->value());

		$this->_deleteKeys($keysToDelete);
	}

	/**
	 * Tests accessing the view method with empty params.
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testFindWithEmptyView() {
		$this->_client->find('view', array(
			'design' => '',
			'view' => ''
		));
	}

	/**
	 * Tests accessing a non-existent design doc.
	 *
	 * @expectedException \Basement\view\InvalidViewException
	 */
	public function testFindWithInvalidDesignDoc() {
		$this->_client->find('view', array(
			'design' => 'post',
			'view' => 'foobar'
		));
	}

	/**
	 * Tests accessing a non-existent view.
	 *
	 * @expectedException \Basement\view\InvalidViewException
	 */
	public function testFindWithInvalidView() {
		$this->_client->find('view', array(
			'design' => 'posts',
			'view' => 'foobar'
		));
	}

	/**
	 * Test of adding a transcoder.
	 *
	 * This transcoder does nothing with the input, just passes it through.
	 */
	public function testAddingTranscoder() {
		$defaults = $this->_client->transcoder();
		$this->assertFalse($this->_client->transcoder('custom'));
		$this->assertFalse(isset($defaults['custom']));
		$this->assertTrue(isset($defaults['json']));
		$this->assertTrue(isset($defaults['serialize']));

		$custom = array(
			'encode' => function($input) {
				return $input;
			},
			'decode' => function($input) {
				return $input;
			}
		);
		$this->_client->transcoder('custom', $custom);
		$this->assertTrue(is_array($this->_client->transcoder('custom')));
	}

	/**
	 * Verifies that an invalid transcoder throws an exception
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidTranscoder() {
		$this->_client->transcoder('invalid', array('encoder' => array()));
	}

	/**
	 * Verifies the correct usage of the view environment.
	 *
	 * * @expectedException \Basement\view\InvalidViewException
	 */
	public function testViewEnvironment() {
		$config = array('environment' => 'development') + $this->_testConfig;
		$client = new Client($config);

		$query = new Query();
		$design = 'posts';
		$view = 'all';
		$result = $client->find('view', compact('design', 'view', 'query'));
	}

}

?>