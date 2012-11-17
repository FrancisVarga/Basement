Basement: The simple ODM for Couchbase on PHP
=============================================

Basement is a fast, efficient and helpful wrapper around the [Couchbase PHP SDK](http://www.couchbase.com/develop/php/next). While the SDK itself is very flexible, it lacks lots of convenience features that you as a developer may want to have. Instead of working with the lowlevel operations directly, you get the ability to query your data with models and documents. It also provides sensible defaults that allow you to write less verbose code. Of course, all original methods and options can be accessed at any time when needed.

Currently, this library is in a very early stage, but should be usable pretty soon. As more features become available, I'll make sure to improve the documentation at the same time. Feel free to raise any issues, improvements or feature requests directly on GitHub.

Requirements
------------
Basement depends on the [Couchbase PHP SDK](http://www.couchbase.com/develop/php/next), which depends on [libcouchbase](http://www.couchbase.com/develop/c/next). Also, you need to have a [Couchbase Server 2.0](http://www.couchbase.com/couchbase-server/beta) cluster running (you can use 1.8, but then you won't have support for views). Please refer to their documentation on how to install them (it mainly depends on the operating system that you are using).

Here is a quick example on how to do it on Mac OS X. First, install libcouchbase through [homebrew](http://mxcl.github.com/homebrew/).

```
shell> brew install https://github.com/couchbase/homebrew/raw/preview/Library/Formula/libcouchbase.rb
```

Now, go to the [SDK download site](http://www.couchbase.com/develop/php/next), download the archive for Mac and extract it. Then copy the `.so` file to your extension directory (if you are unsure where, try with `php -i | grep extension_dir`) and add it to your `php.ini` with `extension=couchbase.so`.

Finally, you can check with `php -m | grep couchbase` if it is installed correctly.

As a side note, Basement needs at least PHP 5.3 to work properly. If you are now thinking "I can't use it in my environment, because its older", then you should stop reading this and go upgrading. PHP 5.3 has been around for years and 5.4 is the current stable release. Basement is fully tested on 5.4 and is recommended for production usage. 

Installation
------------
Basement is available either standalone or through [Composer](http://getcomposer.org/). If you want to use it standalone, make sure you have an appropriate PSR-0 autoloader around (most modern frameworks provide one). If you use Composer, you can use its autoloader as well.

Add this to your `composer.json`:

```json
{
	"require": {
		"daschl/Basement": "0.1.0"
	}
}
```

You can now run `composer.phar` to install the dependency:

```
shell> composer.phar update
```

If you want to use the Composer autoloader in your code, add this line somewhere in your bootstrap code:

```php
require 'vendor/autoload.php';
```

For more information regarding Composer, please consult their [documentation](http://getcomposer.org/doc/).

Connecting to your Cluster
--------------------------
The Connection and the lower level abstractions are handled through the `Basement\Client` class. The `Client` object therefore is the main entry point when talking to your Couchbase cluster.

The easiest way to open a connection is to use the default settings:

```php
use Basement\Client;
$client = new Client();
```

If you don't provide any further parameters, it will try to connect to `127.0.0.1` and will use the `default` bucket. You can use this settings if you're developing locally or just starting out with Couchbase. You can also pass in an array of options, which can override the default settings. Here is the array of default settings that you can override as needed:

```php
$defaults = array(
	'host' => '127.0.0.1',
	'bucket' => 'default',
	'password' => '',
	'user' => null,
	'persist' => false,
	'connect' => true,
	'transcoder' => 'json'
);
```

Note that for host, you can also pass in an array of hosts to connect to or a `;`-delimited string. For example, if you want to connect to the `beer-sample` bucket on `192.168.1.100` you can use the following code:

```php
use Basement\Client;
$client = new Client(array('host' => '192.168.1.100', 'bucket' => 'beer-sample'));
```

You can then check with `$client->connected()` if the connection was successful. If the client could not connect to the cluster, it will raise a `RuntimeException` with the corresponding error message from the SDK.

You can also get access to the underlying SDK client object through the `connection()` method after the connection has been established. You can use it to issue every command you like against the SDK.

```php
use Basement\Client;
$client = new Client();
$client->connection()->increment("mycounter", 1);
```

The `transcoder` setting defines in which format the data will be encoded/decoded when written/read to/from the cluster. The default setting is `json`, but you can either provide `serialize` or your own ones (see the advanced topics). You can also override this setting on a per-query-basis.

Storing Documents
-----------------
Basement will do its best to transform the data you want to store into JSON. To store whole documents, you can use the `save()` method on the `Basement\Client` object. Currently, there are two ways of passing a document to the `save()` method: either through an array in the format of `array('key' => $stringKey, 'doc' => $anyDoc)` or by using instances of the `Basement\model\Document` object. The latter is recommended since it provides much more flexibility on the document handling and more features will be added to it in the future.

Here is a quick example on how to store the same array as JSON in two different ways:

```php
use Basement\Client;
use Basement\model\Document;

$client = new Client();

$key = 'sampledocument';
$doc = array('store' => 'me', 'please');

// Through an array
$arrayDoc = array(compact('key', 'doc'));
$client->save($arrayDoc);

// Through the Document object
$objectDoc = new Document(compact('key', 'doc'));
$client->save($objectDoc);
```

In this simple example its not obvious why the `Basement\model\Document` is preferred, but (as shown later) it provides much more convenience features like automatic key generation for free.

The `save()` method has a bunch of options that you can set, with sensible defaults already set:

```php
$defaults = array(
	'override' => true,
	'replace' => false,
	'transcoder' => 'json',
	'expiration' => 0,
	'cas' => '0'
);
```

By overriding them, you can control the behavior how the document is stored and which underlying operations are used. For example, if you set `override` to `false`, it will use the `add` operation instead of the `set` operation and will return false if the document already exists. You can also set `transcoder` to `serialize` if you want PHP object serialization instead of JSON documents, but keep in mind that you are not able to use the full power of views on these documents then. Also, you have to take extra care when fetching those documents out of Couchbase because a `json_decode` would fail of course.

If either the `Document` or the `array()` are somehow invalid or not well-formatted, a `InvalidArgumentException`is raised with a proper error message.

Retreiving Documents
--------------------
Documents can either be retreived by key or through a view. Handling is nearly the same from a client perspective, so working with views is discussed later on. This part focuses on the `find()` method and all its variants.

The `findByKey()` and `findByView()`methods are the easiest way to read a document (or a collection of documents) out of your Couchbase cluster. The default behavior will convert them from JSON to instances of `Basement\model\Document`, but you can also use serialization or raw data if you prefer to.

Here is an example which stores a document and then fetches it back out again:

```php
use Basement\Client;
use Basement\model\Document;

$client = new Client();

$document = new Document(array(
	'key' => 'my_blogpost',
	'doc' => array(
		'title' => 'This is my first posting',
		'content' => 'Every blog has a first post, so this is mine...'
	)
));

$client->save($document);

$documents = $client->findByKey($document->key());

foreach($documents as $doc) {
	// Prints "my_blogpost"
	echo $doc->key();

	// Contains the stored array
	echo $doc->doc();

	// Holds the associated CAS value
	echo $doc->cas();	
}
```

If you prefer to work with the raw result instead of having it shuffled into an instance of `Basement\model\Document`, then you can use the `'raw' => true` option. Also, if you've previously stored serialized documents instead of JSON, you can use `'transcoder' => 'serialize'` so that it will use `unserialize()` instead of `json_decode()`.

If you pass in an array of keys, the collection returned contains more than one document. Behind the scenes, the more efficient `getMulti()` method is used instead of the normal `get()` method. On the other hand, if you are sure that you only want one document back you can add the `"first" => true` option. This will return the first document of the collection:

```php
// Longer version:
$documents = $client->findByKey("mykey");
$document = $documents[0];

// Shorter version:
$document = $client->findByKey("mykey", array('first' => true));
```

Passing in more than one key is easy:

```php
$documents = $client->findByKey(array("key1", "key2"));
```

Both the `findByKey()` and `findByView()` methods are convenience wrappers around the `find()` method, which you can call directly as well (for example if you want to provide your own abstractions on top of it).

```php
// Those two statemens are equal:
$client->find('key', array('key' => $key));
$client->findByKey($key);

// You can still pass options like this:
$client->find('key', array('key' => $key, 'transcoder' => 'serialize'));
$client->findByKey($key, array('transcoder' => 'serialize'));
```

Working with Views
------------------
Working with views is naturally a little bit different than querying by a unique key. While the process "behind the scenes" is completely different, both the SDK and Basement are trying to keep the interface as uniform as possible. Please refer to the official Couchbase Server 2.0 documentation on how to create design documents and views.

Here is a full example on how to query views:

```php
use Basement\Client;

$client = new Client();
$viewResult = $client->findByView("designName", "viewName");

foreach($viewResult->get() as $document) {
	echo $document->key();
}
```

The `findByView()` method returns an instance of `Basement\view\ViewResult`. The `get()` method provides instances of `\Basement\data\Document` objects (similar to key-based get operations). If none are found, an empty collection is returned. This makes it easy to iterate over it and not run into `null` errors.

Couchbase views can be queried with a large variety of parameters to customize the output. These arguments can either be passed in as an array or by using the `\Basement\view\Query` object. The latter is preferred because the object only allows you to set the correct params and checks for programming and logic errors. If you use a plain array, you are on your own. Here is a short example on how to use the query parameters:

```php
use Basement\Client;
use Basement\view\Query;

$client = new Client();

$arrayQuery = array('reduce' => 'false', 'include_docs' => 'true');
$viewResult = $client->findByView("design", "view", $arrayQuery);

$objectQuery = new Query();
$objectQuery->reduce(false)->includeDocs(true);
$viewResult = $client->findByView("design", "view", $objectQuery);
```

You can see that the `Query` object allows you to chain params and also handles the conversion from booleans to strings for you. See the API documentation for the `Query` class and the Couchbase Server 2.0 Manual on Views for more information on what is supported.

If you don't use a reduce function and you set `includeDocs` to `true`, the appropriate payload will be automatically populated into the `Document`objects:


Of course, there is also the more verbose `find()` method available:

```php
// These two method calls are the same:
$client->findByView('myDesign', 'myView', $arrayQuery);
$client->find('view', array('design' => 'myDesign', 'view' => 'myView', 'query' => $arrayQuery));
```

The `ViewResult` object allows you to check with `isReduced()` if it is reduced or not. If it is a reduced result, then the collection of documents don't contain documents with only values (because reduced results can't be mapped to documents one by one). You can still iterate over it and read the results:

```php
$query = new Query();
$query->setReduce(true);
$viewResult = $client->findByView('design', 'view', $query);

foreach($viewResult->get() as $reducedDoc) {
	echo $reducedDoc->value();
}
```

Advanced Usage
--------------
The following topics are not needed for everyday use but can come in handy when you need advanced functionality.

### Providing custom transcoders
By default, Basement provides transcoders for `json` and `serialize`. If you want, you can easily add your own (or override default ones). A transcoder needs to provide both a `encode` and a `decode` callable, which is called on every document. Note that on `encode`, you may want to check if the given data is an array or an instance of \Basement\data\Document, since this would change the way you interact with the object. See the implementation for `json` or `serialize` for an example. Here is how to provide a custom transcoder (this one just passes the data through, but you can do whatever you like with it in the callbacks):

```php
$custom = array(
	// called on save() for example
	'encode' => function($input) {
		// your code here.
		return $input;
	},
	// called on find() for example
	'decode' => function($input) {
		// your code here
		return $input;
	}
);
$this->_client->transcoder('custom', $custom);
```

Here is the default JSON transcoder for reference:

```php
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
)
```

Roadmap
-------
The roadmap is still in flux and certainly subject to change. TL;DR: it will support all needed features to work with Couchbase Server 2.0 when it hits a stable release.

Todo for 0.1 (Initial Release):

	- Add test-coverage for all written code.
	- Extensive PHPDoc for all provided classes.
	- Available on packagist.
	- Moved from github.com/daschl to github.com/couchbaselabs
	- README.md-Documentation for all of these features.

Planned for 0.2:
	
	- A full-stack ODM model handling (working with your objects through models).
	- Automated testing through travis-ci.
	- Adding and deleting of design documents through a friendly API.
	- Bucket management through a friendly API.
	- Support for easy dumping/loading of design docs (speak "migrations")
	- Best-Effort test coverage for the new features.
	- README.md-Documentation for all of these features.

Once all those major features are implemented, we'll hit "1.0". If we plan to add more features or refactor big stuff, there will be a 0.3 release as well. Be aware that until "1.0", the API is subject to change and there will be no release notes available to show the API breakages.

Contributing & Support
----------------------
This project is not officially supported by Couchbase, but I do my best to keep it up-to-date with the SDK itself. Any help is greatly appreciated!

If you want to hack on Basement, make sure you run Composer with `--dev` to have the development dependencies (like [PHPUnit](http://www.phpunit.de/)) installed. You can then invoke the test suite like this:

```
shell> vendor/bin/phpunit --colors tests/
```

If you have your cluster running somewhere else, make sure to override the `$_testConfig` variable in the `ClientTest.php`. I know this is not the best way to do it, so I'll provide a more flexible way (through a config file) soon.