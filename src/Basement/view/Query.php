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
	protected $_params = array();

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
	 * Use the reduction function or return the stored value.
	 */
	public function reduce($reduce = null) {
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
		$this->_params['include_docs'] = $docs == true;
		return $this;		
	}

	/**
	 * Set the key query param.
	 *
	 * Key must be specified as a JSON value.
	 */
	public function key($key) {
		$this->_params['key'] = json_encode($key);
		return $this;
	}

	/**
	 * Set the keys query param.
	 *
	 * Key must be specified as a JSON value.
	 */
	public function keys($keys) {
		$this->_params['keys'] = json_encode($keys);
		return $this;
	}

	/**
	 * Return records with a value equal to or greater than the specified key.
	 *
	 * Key must be specified as a JSON value.
	 */
	public function startKey($startKey) {
		$this->_params['startkey'] = json_encode($startKey);
		return $this;
	}

	/**
	 * Stop returning records when the specified key is reached.
	 *
	 * Key must be specified as a JSON value.
	 */
	public function endKey($endKey) {
		$this->_params['endkey'] = json_encode($endKey);
		return $this;
	}

	/**
	 * Return records starting with the specified document ID.
	 */
	public function startKeyDocId($docId) {
		if(!is_string($docId)) {
			throw new InvalidArgumentException("startKeyDocID must be a string");
		}

		$this->_params['startkey_docid'] = $docId;
		return $this;
	}

	/**
	 *
	 */
	public function endKeyDocId($docId) {
		if(!is_string($docId)) {
			throw new InvalidArgumentException("endKeyDocID must be a string");
		}

		$this->_params['endkey_docid'] = $docId;
		return $this;
	}

	/**
	 * Group the results using the reduce function to a group or single row.
	 */
	public function group($group) {
		if($group == true || $group == 'true') {
			$this->_params['group'] = true;
		} else {
			$this->_params['group'] = false;
		}

		return $this;
	}

	/**
	 * Specify the group level to be used.
	 *
	 * Must be a positive integer (or 0).
	 */
	public function groupLevel($groupLevel) {
		if(!is_integer($groupLevel) || $groupLevel < 0) {
			throw new InvalidArgumentException("groupLevel must be a positive integer");
		}
		$this->_params['group_level'] = $groupLevel;
		return $this;
	}

	/**
	 * Specifies whether the specified end key should be included in the result.
	 */
	public function inclusiveEnd($inclusiveEnd) {
		if($inclusiveEnd == true || $inclusiveEnd == 'true') {
			$this->_params['inclusiveEnd'] = true;
		} else {
			$this->_params['inclusiveEnd'] = false;
		}

		return $this;
	}

	/**
	 * Sets the response in the event of an error.
	 *
	 * Must either be "continue" or "stop".
	 */
	public function onError($onError) {
		if($onError == 'continue') {
			$this->_param['on_error'] = 'continue';
		} elseif($onError == 'stop') {
			$this->_param['on_error'] = 'stop';
		} else {
			throw new InvalidArgumentException("onError is either continue or stop.");
		}

		return $this;
	}

}

?>