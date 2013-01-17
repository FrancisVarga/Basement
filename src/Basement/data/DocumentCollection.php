<?php
/**
 * Basement: The simple ODM for Couchbase on PHP
 *
 * @copyright     Copyright 2012, Michael Nitschinger
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Basement\data;

class DocumentCollection implements \ArrayAccess, \Iterator, \Countable {

	/**
	 * Contains the data stored in the collection.
	 */
	protected $_data = array();

	/**
	 * Checks wheter or not the given offset exists.
	 */
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->_data);
	}

	/**
	 * Returns the data for the given offset.
	 */
	public function offsetGet($offset) {
		return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
	}

	/**
	 * Set the value at the given offset.
	 */
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			return $this->_data[] = $value;
		}
		return $this->_data[$offset] = $value;
	}

	/**
	 * Unsets the given offset.
	 */
	public function offsetUnset($offset) {
		prev($this->_data);
		if (key($this->_data) === null) {
			$this->rewind();
		}
		unset($this->_data[$offset]);
	}

	/**
	 * Returns the number of elements stored in the collection.
	 */
	public function size() {
		return count($this->_data);
	}

	/**
	 * Returns the number of elements stored in the collection.
	 *
	 * This is an alias method for `size()`, but needed to call count() on it.
	 */
	public function count() {
		return $this->size();
	}

	/**
 	 * Clears the array and resets the key.
 	 */
	public function clear() {
		$this->_data = array();
		reset($this->_data);
	}

	/**
	 * Returns the current document.
	 */
	public function current() {
		return current($this->_data);
	}

	/**
	 * Returns the current key.
	 */
	public function key() {
		return key($this->_data);
	}

	/**
	 * Returns the next document.
	 */
	public function next() {
		next($this->_data);
		return current($this->_data);
	}

	/**
	 * Returns the previous document.
	 */
	public function prev() {
		if (!prev($this->_data)) {
			end($this->_data);
		}
		return current($this->_data);		
	}

	/**
	 * Rewinds to the first document and returns it.
	 */
	public function rewind() {
		reset($this->_data);
		return current($this->_data);		
	}

	/**
	 * Returns if the current key position is valid.
	 */
	public function valid() {
		return key($this->_data) !== null;		
	}

	/**
	 * Returns if the collection is empty or not.
	 */
	public function isEmpty() {
		return $this->size() == 0;
	}

}

?>