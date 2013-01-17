<?php
/**
 * Basement: The simple ODM for Couchbase on PHP
 *
 * @copyright     Copyright 2012, Michael Nitschinger
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use Basement\Client;

abstract class BaseTest extends PHPUnit_Framework_TestCase {

	/**
	 * Use this to override the default config settings.
	 */
	protected $_testConfig = array(
		'host' => '127.0.0.1',
		'environment' => 'test'
	);

	/**
	 * Generate a random key.
	 */
	protected function _randomKey($prefix = "basement_") {
		return Client::generateKey($prefix);
	}
}

?>