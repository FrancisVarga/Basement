<?php
/**
 * Basement: The simple ODM for Couchbase on PHP
 *
 * @copyright     Copyright 2012, Michael Nitschinger
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Basement\view;

/**
 * The Query class can be used to more easily manage view query params.
 *
 * The query properties can only be set through their appropriate setter methods,
 * because checks are needed to make sure no invalid params are used. This also
 * makes it possible to reuse Query objects while reduce programming errors at
 * the same time.
 */
class Query {

	/** 
	 * Contains the query params.
	 */
	protected $_params = array(
		'stale' => 'false',
		'reduce' => 'false',
		'include_docs' => 'false'
	);

	/**
	 * Returns the params as an array.
	 */
	public function params() {
		return $this->_params;
	}

	/**
	 * Allow the results from a stale view to be used.
	 *
	 * The current default setting on Couchbase Server is "update_after".
	 */
	public function stale($stale) {
		if($stale == false || $stale == 'false') {
			$this->_params['stale'] = 'false';
		} elseif($stale == true || $stale == 'true' || $stale == 'ok') {
			$this->_params['stale'] = 'ok';
		} elseif($stale == 'update_after' || $stale == 'after') {
			$this->_params['stale'] = 'update_after';
		} else {
			throw new InvalidArgumentException("Unknown stale mode: " . $stale);
		}
		return $this;
	}

	/**
	 * Set the desceinding param.
	 */
	public function descending($descending) {
		$this->_params['descending'] = $descending == true ? 'true' : 'false';
		return $this;
	}

	/**
	 * Use the reduction function.
	 */
	public function reduce($reduce) {
		$this->_params['reduce'] = $reduce == true ? 'true' : 'false';
		return $this;
	}

	/**
	 * Skip this number of records before starting to return the results.
	 */
	public function skip($skip) {
		if(is_integer($skip) && $skip >= 0) {
			$this->_params['skip'] = $skip;
		} else {
			throw new InvalidArgumentException("Given skip value is invalid.");
		}
		return $this;
	}

	/**
	 * Set the limit of the amount docs returned.
	 */
	public function limit($limit) {
		if(is_integer($limit) && $limit >= 0) {
			$this->_params['limit'] = $limit;
		} else {
			throw new InvalidArgumentException("Given limit value is invalid.");
		}
		return $this;
	}

	/**
	 * Whether to include the full docs or not.
	 */
	public function includeDocs($docs) {
		$this->_params['include_docs'] = $docs == true ? 'true' : 'false';
		return $this;		
	}

	/**
	 * Set the key query param.
	 */
	public function key($key) {

	}

	/**
	 * Set the keys query param.
	 */
	public function keys($keys) {

	}

	/**
	 *
	 */
	public function startKey($startKey) {

	}

	/**
	 * Stop returning records when the specified key is reached.
	 */
	public function endKey($endKey) {

	}

	/**
	 * Return records starting with the specified document ID.
	 */
	public function startKeyDocId($docId) {

	}

	/**
	 *
	 */
	public function endKeyDocId($docId) {

	}

	/**
	 *
	 */
	public function group($group) {

	}

	/**
	 *
	 */
	public function groupLevel($groupLevel) {

	}

	/**
	 *
	 */
	public function inclusiveEnd($inclusiveEnd) {

	}

	/**
	 * Sets the response in the event of an error.
	 */
	public function onError($onError) {

	}

}

?>