<?php
/**
 * Basement: The simple ODM for Couchbase on PHP
 *
 * @copyright     Copyright 2012, Michael Nitschinger
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Basement;

use \Couchbase;
use \RuntimeException;
use \InvalidArgumentException;
use \SplFixedArray;

use Basement\data\Document;
use Basement\view\ViewResult;
use Basement\view\InvalidViewException;

/**
 * The `Client`class is your main entry point when working with your Couchbase cluster.
 *
 * It provides the convenience methods around the PHP SDK and also handles connection
 * management. Together with all the supporting classes like the Basement Document and
 * Model it makes it easier to develop applications on Couchbase and PHP than ever before.
 */
class Client {

	/**
	 * Holds the current config.
	 */
	protected $_config;

	/**
	 * Holds the connection resource if connected.
	 */
	protected $_connection = null;

	/**
	 * Contains transcoders which allow the transparent encoding/decoding of values
	 * to store in Couchbase.
	 */
	protected $_transcoders = array();

	/**
	 * Holds all currently initialized connections.
	 */
	protected static $_connections = array();

	/**
	 * Create and connect to the CouchbaseClient.
	 *
	 * If no user is provided (or is null), then it is assumed to be the same
	 * name as the bucket. This is the common behavior and seldom needs to be
	 * changed.
	 */
	public function __construct($options = array()) {
		$defaults = array(
			'name' => 'default',
			'host' => '127.0.0.1',
			'bucket' => 'default',
			'password' => '',
			'user' => null,
			'persist' => false,
			'connect' => true,
			'transcoder' => 'json',
			'environment' => 'development'
		);
		$this->_config = $options + $defaults;
		$this->_config['user'] ?: $this->_config['bucket'];

		if($this->_config['connect'] == true) {
			$this->connect();
		}

		$this->_transcoders += array(
			'json' => array(
				'encode' => function($input) {
					if($input instanceof \Basement\data\Document) {
						return $input->toJson();
					} else { 
						return json_encode($input['doc']);
					}
				},
				'decode' => function($input) {
					return json_decode($input, true);
				}
			),
			'serialize' => array(
				'encode' => function($input) {
					if($input instanceof \Basement\data\Document) {
						return $input->serialize();
					} else {
						return serialize($input['doc']);
					}
				},
				'decode' => function($input) {
					return unserialize($input);
				}
			)
		);
	}

	/**
	 * Returns the current configuration.
	 */
	public function config() {
		return $this->_config;
	}

	/**
	 * Read or set a transcoder.
	 */
	public function transcoder($name = null, $transcoder = array()) {
		if($name == null) {
			return $this->_transcoders;
		} elseif(empty($transcoder) && isset($this->_transcoders[$name])) {
			return $this->_transcoders[$name];
		} elseif(empty($transcoder)) {
			return false;
		}

		if(!isset($transcoder['encode']) || !isset($transcoder['decode']) ||
		   !is_callable($transcoder['encode']) || !is_callable($transcoder['decode'])) {
			$msg = "A transcoder must provide 'encode' and 'decode' callable functions";
			throw new InvalidArgumentException($msg);
		}

		$this->_transcoders[$name] = $transcoder;
	}

	/**
	 * Connects to the CouchbaseClient.
	 */
	public function connect() {
		if($this->connected()) {
			return true;
		}
		extract($this->_config);

		set_error_handler(function($no, $str, $file, $line, array $context) {
			throw new RuntimeException($str);
		});
		$this->_connection = new Couchbase($host, $user, $password, $bucket);
		restore_error_handler();

		static::$_connections[$name] = $this;
		return $this->connected();
	}

	/**
	 * Return all or a specific open connections.
	 */
	public static function connections($name = null) {
		if(!$name) {
			return static::$_connections;
		}

		if(!isset(static::$_connections[$name])) {
			return false;
		}

		return static::$_connections[$name];
	}

	/**
	 * Disconnect the current connection.
	 */
	public function disconnect() {
		$this->_connection = null;
		unset(static::$_connections[$this->_config['name']]);
		return true;
	}

	/**
	 * Returns the sate of the connection.
	 */
	public function connected() {
		return $this->_connection !== null;
	}

	/**
	 * Returns the connection resource if connected.
	 */
	public function connection() {
		return $this->connected() ? $this->_connection : false;
	}

	/**
	 * Gathers information on both the client and cluster versions.
	 *
	 * Depending on the given type (either cluster or client, which is the default), the
	 * method returns the appropriate versions. Note that for the cluster type, an 
	 * array is returned with version information for each node. This means that it makes
	 * it easy to count the number of nodes in the cluster as well.
	 *
	 * The version returned for the cluster is the used memcached version, not the 
	 * version of couchbase server itself.
	 */
	public function version($type = 'client') {
		if($type == 'client') {
			return $this->_connection->getClientVersion();
		} elseif($type == 'cluster') {
			return $this->_connection->getVersion();
		}

		throw new InvalidArgumentException('Unsupported version type: ' . $type);
		return false;
	}

	/**
	 * Store a document in Couchbase.
	 *
	 * The document can be any object or array that can be serialized or somehow stored in
	 * Couchbase. By default, the method will try to encode it as JSON and store it
	 * in the cluster. If you pass it an instance of \Basement\Document then you have
	 * much more control on what to do with the object. See the documentation
	 * for more information on that object. If you use a plain array then it will have to
	 * have the following layout:
	 *
	 *		array('key' => $key, 'doc' => $doc)
	 *
	 * This is important because you need a unique key to store documents in couchbase. The
	 * value of doc can be anything that PHP can convert to JSON (or serialize it). The key
	 * has to be a string. You can use the `generateKey()` method to generate a 
	 * (theoretically) uniqe key.
	 *
	 * You can also pass in various options. By default, when override is true and replace
	 * is false, the `set` operation is used which means that the document will just
	 * be overriden. If you set `override` false, the `add` operation will be used instead
	 * and therefore fail if the document did already exist. If `replace`is set to true,
	 * the `replace` operation will be used and it will fail if the document didn't exist
	 * before. If you set `transcoder` to `serialize`, the document will be stored as a
	 * serialized object instead of a JSON one. Keep in mind that this removes the ability
	 * to query the document through a view. Finally, you can pass in an expiration time or
	 * the CAS value.
	 *
	 * If the operation did succeed, the CAS value is returned, otherwise false.
 	 */
	public function save($document, $options = array()) {
		$defaults = array(
			'override' => true,
			'replace' => false,
			'serialize' => false,
			'expiration' => 0,
			'cas' => '0',
			'transcoder' => $this->_config['transcoder']
		);
		$params = $options + $defaults;

		$extractKey = function() use ($document) {
			if($document instanceof \Basement\data\Document) {
				return $document->key();
			} elseif(is_array($document) && !empty($document['key'])) {
				return $document['key'];
			} else {
				throw new InvalidArgumentException("Invald document type given.");
			}
		};

		$key = $extractKey();
		if(is_array($document) && empty($document['key'])) {
			throw new InvalidArgumentException("Invalid or no key given.");
		} elseif(is_array($document) && !isset($document['doc'])) {
			throw new InvalidArgumentException("No 'doc' given.");
		}

		$encoder = $this->_transcoders[$params['transcoder']]['encode'];
		$stringified = $encoder($document);
		

		if($params['override'] == true && $params['replace'] == false) {
			$operation = 'set';
		} elseif($params['override'] == false) {
			$operation = 'add';
		} elseif($params['replace'] == true) {
			$operation = 'replace';
		} else {
			throw new InvalidArgumentException("Unsupported argument combination.");
		}

		$result = $this->_connection->{$operation}(
			$key, $stringified, $params['expiration'], $params['cas']
		);

		return $result ?: false;
	}

	/**
	 * Find documents either through a view or by key.
	 *
	 * The type can either be 'key' or 'view'. Depending on the type, the function
	 * expects different kind of options. If this flexibility is to complex for your
	 * use case, you can use the findByKey() and findByView wrapper methods that
	 * handle this for you.
	 */
	public function find($type = 'key', $options = array()) {
		$defaults = array(
			'json' => true,
			'transcoder' => $this->_config['transcoder'],
			'raw' => false
		);
		$params = $options + $defaults;

		switch($type) {
			case 'key':
				$result = $this->_findKey($params);
				break;
			case 'view':
				$result = $this->_findView($params);
				break;
			default:
				throw new InvalidArgumentException('Unsupported find type');
		}

		return $result;
	}

	/**
	 * Helper method for the `find` method to find by key.
	 */
	protected function _findKey($options = array()) {
		$defaults = array(
			'first' => false
		);
		$params = $options + $defaults;

		if(!isset($params['key'])) {
			throw new InvalidArgumentException("No key given");
		} elseif(!is_string($params['key']) && !is_array($params['key'])) {
			$message = "The given key must be a string or an array.";
			throw new InvalidArgumentException($message);
		}

		extract($params);
		$cas = null;

		$keys = (array) $key;
		$docs = $this->_connection->getMulti($keys, $cas);

		if(empty($docs)) {
			return false;
		} elseif($params['raw'] == true) {
			return count($docs) == 1 ? array_shift($docs) : $docs;
		}

		$decoder = $this->_transcoders[$options['transcoder']]['decode'];
		$decoded = new SplFixedArray(count($docs));

		$num = 0;
		foreach($docs as $key => $doc) {
			$decoded[$num] = new Document(array(
				'key' => $key,
				'doc' => $decoder($doc)
			));
			$decoded[$num]->cas($cas[$key]);
			$num++;
		}

		return $params['first'] == true ? $decoded[0] : $decoded;
	}

	/**
	 * Helper method for the `find` method to find by view.
	 */
	protected function _findView($options = array()) {
		$defaults = array(
			'query' => array(),
			'environment' => $this->_config['environment']
		);
		$params = $options + $defaults;

		if(empty($params['design']) || empty($params['view'])) {
			$message = "Please provide both a design document and a view name";
			throw new InvalidArgumentException($message);
		}

		extract($params);
		if($query instanceof \Basement\view\Query) {
			$query = $query->params();
		} elseif(!is_array($query)) {
			throw new InvalidArgumentException("Unknown query value given");
		}

		$includeDocs = false;
		if(isset($query['include_docs']) && $query['include_docs'] == true) {
			$includeDocs = true;
			unset($query['include_docs']);
		}

		if($environment == 'development' && !preg_match('/^dev_/', $design)) {
			$design = 'dev_' . $design;
		}

		set_error_handler(function($no, $str, $file, $line, array $context) {
			if(preg_match('/Error opening view ([^,]+)/', $str, $match)) {
				throw new InvalidViewException("View " . $match[1] . " not found");
			} elseif(preg_match('/Design document _design\/([^\s]+) not found/', $str, $match)) {
				throw new InvalidViewException("Design \"" . $match[1] . "\" not found");
			} elseif(preg_match('/\{not_found,deleted\}/', $str)) {
				throw new InvalidViewException("View not found (deleted)");
			} else {
				throw new RuntimeException($str);
			}
		});

		$result = $this->_connection->view($design, $view, $query);
		if(isset($result['error'])) {
			throw new InvalidViewException($result['reason']);
		}

		restore_error_handler();

		$reduce = isset($query['reduce']) && $query['reduce'] == 'true';

		$documents = new SplFixedArray(count($result['rows']));
		if($documents->getSize() == 0) {
			return $documents;
		}

		foreach($result['rows'] as $num => $row) {
			$doc = array('value' => $row['value']);
			if(!$reduce) {
				$doc['key'] = $row['id'];
			}

			if($includeDocs) {
				$foundDoc = $this->_connection->get($doc['key']);
				$doc['doc'] = $foundDoc ? json_decode($foundDoc, true) : null;
			}

			$documents[$num] = new Document($doc);
		}

		return new ViewResult($reduce, $documents);
	}

	/**
	 * This method allows for easier quering by the key.
	 */
	public function findByKey($key, $options = array()) {
		$options = $options + array('key' => $key);
		return $this->find('key', $options);
	}

	/**
	 * This method allows for easier quering through a view.
	 */
	public function findByView($design, $view, $query = null) {
		$options = compact('design', 'view', 'query');
		return $this->find('view', $options);
	}


	/**
	 * Generate a unique key with an optional prefix.
	 */
	public static function generateKey($prefix = '') {
		return uniqid($prefix, false);
	}

}

?>