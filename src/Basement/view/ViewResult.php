<?php
/**
 * Basement: The simple ODM for Couchbase on PHP
 *
 * @copyright     Copyright 2012, Michael Nitschinger
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Basement\view;

/**
 * Wraps a set of returned view documents.
 */
class ViewResult {


	/**
	 * If the view result is reduced or not.
	 */
	protected $_reduce;

	/** 
	 * Holds the actual documents to iterate over.
	 */
	protected $_docs;

	/**
	 * Create a new ViewResult document.
	 */
	public function __construct($reduced, $documents) {
		$this->_reduce = $reduced;
		$this->_docs = $documents;
	}

	/**
	 * Returns wheter the view result is reduced or not.
	 */
	public function isReduced() {
		return $this->_reduce;
	}


	/**
	 * Returns the associated documents.
	 */
	public function get() {
		return $this->_docs;
	}

}

?>