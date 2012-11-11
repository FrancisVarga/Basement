<?php
/**
 * Basement: The simple ODM for Couchbase on PHP
 *
 * @copyright     Copyright 2012, Michael Nitschinger
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use Basement\Client;
use Basement\data\Document;

class ClientTest extends PHPUnit_Framework_TestCase {

	/**
	 * A working instance of the client to test against.
	 */
	protected $_client = null;

	/**
	 * Use this to override the default config settings.
	 */
	protected $_testConfig = array(
		'host' => '127.0.0.1'
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
			'persist' => false
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
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	/**
	 * Tests the save command with the replace operation.
	 */
	public function testSaveWithReplace() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	/**
	 * Tests the find method with the key option.
	 */
	public function testFindWithKey() {
		$key = 'mykey';
		$value = json_encode('foo');

		$result = $this->_client->find('key', compact('key'));
		$this->assertFalse($result);

		$result = $this->_client->connection()->set($key, $value);
		$this->assertNotEmpty($result);

		$result = $this->_client->find('key', compact('key'));
		$this->assertEquals('Basement\data\Document', get_class($result));
		$this->assertEquals($key, $result->key());
		$this->assertEquals(json_decode($value), $result->doc());
		$this->assertNotEmpty($result->cas());

		$this->_deleteKeys(array($key));
	}

	/**
	 * Tests the key find with returning raw data.
	 */
	public function testFindWithKeyRaw() {
		$key = 'mykey';
		$value = json_encode('foo');
		$raw = true;

		$result = $this->_client->find('key', compact('key', 'raw'));
		$this->assertFalse($result);

		$result = $this->_client->connection()->set($key, $value);
		$this->assertNotEmpty($result);

		$result = $this->_client->find('key', compact('key', 'raw'));
		$this->assertTrue(is_string($result));
		$this->assertEquals($value, $result);

		$this->_deleteKeys(array($key));
	}

	/**
	 * Tests the key find with returning serialized data.
	 */
	public function testFindWithKeySerialized() {
		$key = 'mykey';
		$value = serialize('foo');
		$serialize = true;

		$result = $this->_client->find('key', compact('key', 'serialize'));
		$this->assertFalse($result);

		$result = $this->_client->connection()->set($key, $value);
		$this->assertNotEmpty($result);

		$result = $this->_client->find('key', compact('key', 'serialize'));
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
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	/**
	 * Test find with an array of keys returning raw data.
	 */
	public function testFindWithMultipleKeysRaw() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	/**
	 * Test find with an array of keys returning serialized data.
	 */
	public function testFindWithMultipleKeysSerialized() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	/**
	 * Tests the wrapper findByKey() method.
	 */
	public function testFindByKey() {
		$key = 'mykey';
		$value = json_encode('foo');

		$result = $this->_client->findByKey($key);
		$this->assertFalse($result);

		$result = $this->_client->connection()->set($key, $value);
		$this->assertNotEmpty($result);

		$result = $this->_client->findByKey($key);
		$this->assertEquals('Basement\data\Document', get_class($result));
		$this->assertEquals($key, $result->key());
		$this->assertEquals(json_decode($value), $result->doc());
		$this->assertNotEmpty($result->cas());

		$this->_deleteKeys(array($key));
	}

	/**
	 * Tests basic querying of a view.
	 */
	public function testFindWithView() {
		$this->markTestIncomplete('This test has not been implemented yet.');

		$design = 'name';
		$view = 'name';
		$this->_client->find('view', compact('design', 'view'));
	}

	/**
	 * Tests passing in the view query params as an array.
	 */
	public function testFindWithViewQueryArray() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	/**
	 * Tests passing in the view query params as a Query object.
	 */
	public function testFindWithViewQueryObject() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	/**
	 * Tests accessing the view method with empty params.
	 */
	public function testFindWithEmptyView() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}

	/**
	 * Tests accessing a non-existent view.
	 */
	public function testFindWithInvalidView() {
		$this->markTestIncomplete('This test has not been implemented yet.');
	}



}


?>