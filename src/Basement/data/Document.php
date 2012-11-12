<?php
/**
 * Basement: The simple ODM for Couchbase on PHP
 *
 * @copyright     Copyright 2012, Michael Nitschinger
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Basement\data;

use Basement\Client;

/**
 * The object representation of a JSON document stored inside Couchbase.
 */
class Document {

	/**
	 * The unique key of the document.
	 */
	protected $_key = null;

	/**
	 * The "payload" to store in the cluster.
	 */
	protected $_doc = null;

	/**
	 * Contains the CAS value for the document if there is one.
	 */
	protected $_cas = null;

	/**
	 * Create a new document.
	 */
	public function __construct($options = array()) {
		if(!empty($options['key'])) {
			$this->_key = $options['key'];
		}

		if(isset($options['doc'])) {
			$this->_doc = $options['doc'];
		}
	}

	/**
	 * Gets or sets the key of the document.
	 *
	 * Note that if no key was set and it is accessed for the first time, 
	 * a random key is generated.
	 */
	public function key($key = null) {
		if($key) {
			$this->_key = $key;
			return $this;
		}

		if($this->_key == null) {
			$this->_key = Client::generateKey();
		}

		return $this->_key;
	}

	/**
	 * Gets or sets the payload to store.
	 */
	public function doc($doc = null) {
		if($doc) {
			$this->_doc = $doc;
			return $this;
		}

		return $this->_doc;
	}

	/**
	 * Gets or sets the CAS value.
	 */
	public function cas($cas = null) {
		if($cas) {
			$this->_cas = $cas;
			return $this;
		}

		return $this->_cas;
	}

	/**
	 * Serializes the payload to a storable string.
	 */
	public function serialize() {
		return serialize($this->_doc);
	}

	/**
	 * Convert the payload into its JSON representation.
	 */
	public function toJson() {
		return json_encode($this->_doc);
	}
}

?>