<?php
/**
 * Copyright © 2013 t
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 *
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://www.wtfpl.net/ for more details.
 */
namespace Camarera;

/**
 * Camarera is the main static class, grouping some main functionalities:
 *
 *	* logging: you can create and register loggers which are invoked by common Camarera::log() facility. Loggers and
 *		log messages both have a log level, and only loggers with equal or lower log levels will receive log messages.
 * @see Camarera::LOG_ALL
 *
 *	* autoloading: like logging, you can register your autologgers. Each have a namespace for which it is active and
 *		some data telling the autoloader where to look for files. Please read documentation on how Camarera handles
 *		src, autoloding, and how to extend core src.
 * @see Camarera::$_registeredAutoloaders
 *
 *	* stores: I take care of registering and returning store objects.
 * @see Camarera::$_stores
 *
 *  * conf: I take care of loading config files and retrieving config values from it
 * @see Camarera::$_confCache
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera
 * @version 1.1
 */
class Camarera {

	/**
	 * this is a static class
	 */
	protected function __construct() {}
	/**
	 * this method only exists so you can get an instance to be printed.
	 * @return static
	 */
	public static function getDebug() {
		return new static;
	}

	private function __clone() {}

	//////////////////////////////////////////////////////////////////////////
	// LOGGING
	//////////////////////////////////////////////////////////////////////////

	/**
	 * log level constants: each logger must be registered with a log level. It will receive to messages with this
	 * 		level, or lower. Eg. a LOG_WARNING level logger will not print a LOG_NOTICE level message, but will print
	 * 		a LOG_WARNING, LOG_ERROR, LOG_CRITICAL etc. level message.
	 * @var int LOGER_ALL is an alias for LOG_DEBUG but more human readable. If a logger has it, it will receive all messages.
	 */
	const LOGGER_ALL = 7;
	/**
	 * @var int this level shall indicate that some severe error(s) have disrupted normal control flow. Note that an
	 * 		emergency flow can still take place (eg. mysql down, print static 501 page still takes place)
	 */
	const LOG_EMERGENCY = 0;
	/**
	 * @var int this level shall indicate that some components failed the expected behaviour but system still continues
	 * 		normally (eg. using backup server)
	 */
	const LOG_ALERT = 1;
	/**
	 * @var int some environment-dependent resource has been exhausted. Eg. disc quota, request time limit
	 */
	const LOG_CRITICAL = 2;
	/**
	 * @var int this level shall indicate that there is an error in a class definition or input param. Always shall imply
	 * 		a full execution stop, otherwise use LOG_WARNING
	 */
	const LOG_ERROR = 3;
	/**
	 * @var int this level shall indicate message which shall be logged on the production server but is just a notice
	 * 		indeed. Eg. the cache was empty, on production this may get handy to know and may indicate an inoperative cache
	 */
	const LOG_WARNING = 4;
	/**
	 * @var int this level shall indicate message which appear in development env but not in production.
	 */
	const LOG_NOTICE = 5;
	/**
	 * @var int "verbose" mode shall use this loglevel
	 */
	const LOG_INFORMATIONAL = 6;
	/**
	 * @var int this shall be used only by temporary debugging messages
	 */
	const LOG_DEBUG = 7;

	/**
	 * @var array of callable loggers
	 */
	protected static $_loggers = array();

	/**
	 * registers a logger for logging
	 * @param mixed $logger PSR-3 compatible logger
	 * @throws \InvalidArgumentException
	 */
	static function registerLogger($loggerName, $logger, $logLevel) {
		if (is_object($logger)) {
			if (!method_exists($logger, 'log')) {
				throw new \InvalidArgumentException('Camarera::registerLogger() received logger object without log() method');
			}
		}
		else {
			throw new \InvalidArgumentException('Camarera::registerLogger(): not a callable logger in argument');
		}
		if (isset(static::$_loggers[$loggerName])) {
			\Camarera::log(
				\Camarera::LOG_NOTICE,
				""
			);
		}
		static::$_loggers[] = array($loggerName, $logger, $logLevel);

	}
	/**
	 * I return a registered logger instance or callable. You shouldn't use this if you use Camarera logging at all, actually.
	 * @param $loggerName
	 * @return null|callable|object
	 */
	static function getLogger($loggerName) {
		$ret = null;
		foreach (static::$_loggers as $eachLogger) {
			if ($eachLogger[0] == $loggerName) {
				$ret = $eachLogger[1];
				break;
			}
		}
		return $ret;
	}
	/**
	 * calls all registered loggers with param of $var
	 * @param mixed $var log message
	 * @param int logLevel @see LOG_ALL comment at constant descriptions
	 */
	static function log($logLevel, $var, $context=null) {
		foreach (static::$_loggers AS $eachLogger) {
			list($loggerName, $logger, $loggerLevel) = $eachLogger;
			if ($logLevel <= $loggerLevel) {
				$logger->log($logLevel, $var, $context);
			}
		}
	}

	//////////////////////////////////////////////////////////////////////////
	// STORES
	//////////////////////////////////////////////////////////////////////////

	/**
	 * @var array keep Store*** object instances here
	 */
	protected static $_stores = array();

	/**
	 * creates a store object from a config array
	 * @param type $config
	 * @return \classname
	 */
	private static function createStore(\StoreConfig $Config) {
		$classname = $Config->getStoreClassname();
		if (empty($classname)) {
			throw new \InvalidArgumentException('$Config does not contain store class');
		}
		return $classname::serve($Config);
	}
	/**
	 * initializes and registers a store based on config array
	 * 		see StoreXxx src for available config array keys
	 * @param array $config
	 * @throws \InvalidArgumentException
	 */
	static function registerStore(\StoreConfig $Config) {
		if (!strlen($Config->id)) {
			throw new \InvalidArgumentException('$config[\'_id\'] must be set');
		}
		elseif (isset(static::$_stores[$Config->id])) {
			$hint = $Config->id === 'default' ? ' (maybe you want to set the \'_id\' field in config)' : '';
			throw new \RuntimeException('Store id=' . $Config->id . ' is already registered' . $hint);
		}
		static::$_stores[$Config->id] = static::createStore($Config);
		static::log(
			\Camarera::LOG_INFORMATIONAL,
			'Store #' . $Config->id . ' registered'
		);
	}
	/**
	 * returns a registered store
	 * @param unknown_type $storeId
	 * @throws \InvalidArgumentException
	 * @return \Store
	 */
	static function getStore($storeId) {
		if (!isset(static::$_stores[$storeId])) {
			throw new \InvalidArgumentException('Store ' . $storeId . ' not registered');
		}
		else {
			return static::$_stores[$storeId];
		}
	}

	//////////////////////////////////////////////////////////////////////////
	// CONF
	//////////////////////////////////////////////////////////////////////////

	/**
	 * @var array[string]array I load config values in this, keyed by module name
	 */
	protected static $_confCache = array();

	/**
	 * @var string key in config array
	 */
	const CONF_NAMESPACE = 'namespace';
	/**
	 * @var string key in config array
	 */
	const CONF_STORE = '_store';
	/**
	 * @var string key in config array
	 */
	const CONF_LOGGER = '_logger';

	/**
	 * @var string in conf files use this for auto-setting a value, whenever autosetting is possible
	 */
	const CONF_AUTO = '_AUTO';

	/**
	 * I load a given config file, based on its type. XML loading could be implemented here, if ever needed...
	 * @param string $confFname config file name, full path required
	 * @param string $type {php|json}
	 * @return array empty array if not found
	 * @throws \InvalidArgumentException if $type is invalid
	 */
	protected static function _loadConfFile($confFname, $type) {
		switch($type) {
			case 'php':
				if (($conf = @include($confFname)) === false) {
					static::log(
						\Camarera::LOG_WARNING,
						'LOADCONFFILE: ' . $confFname . ' NOT FOUND'
					);
					$conf = array();
				};
				break;
			case 'array':
				if (!is_array($confFname)) {
					static::log(
						\Camarera::LOG_WARNING,
						'LOADCONFFILE param is not array'
					);
					$conf = array();
				}
				else {
					$conf = $confFname;
				}
				break;
			case 'json':
				$confFname = rtrim($path, '/') . '/conf/conf.json';
				if (($conf = @file_get_contents($confFname)) === false) {
					static::log(
						\Camarera::LOG_WARNING,
						'LOADCONFFILE: ' . $confFname . ' NOT FOUND'
					);
					$conf = array();
				}
				else {
					// @todo convert to array format ! ?
					$conf = json_decode($conf, true);
				}
				break;
			default:
				throw new \InvalidArgumentException();
		}
		return $conf;
	}

	/**
	 * I load a config file into cache
	 * @param string $container
	 * @param string $path I load $path . '/conf/conf.php'
	 * @param string $type {php|array|json} supported file formats to load
	 * @param int $position to which position the config should be loaded, possible values:
	 * 		null-append at end of array
	 * 		0 - append at beginning of array (default)
	 * 		-1 - append at 2nd position (so eg. Shake config gets between Cocktail and App, App being the first
	 * @todo make config type configurable
	 */
	public static function loadConf($container, $path, $type='php', $position=0) {

		static::log(
			\Camarera::LOG_INFORMATIONAL,
			'LOADCONF: ' . $container . ' IN ' . $path
		);
		$path = rtrim($path, '/');

		$conf = $confLoaded = static::_loadConfFile($path, $type);

		unset($conf[static::CONF_LOGGER]);
		unset($conf[static::CONF_STORE]);

		switch(true) {
			case is_null($position):
				break;
			case $position === 0:
				// prepend new configs to array, so foreach() will find last loaded conf first
				static::$_confCache = array_merge(array($container=>$conf), static::$_confCache);
				break;
			case $position === -1:
				static::$_confCache = array_merge(
					array_slice(static::$_confCache, 0, 1),
					array($container=>$conf),
					array_slice(static::$_confCache, 1)
				);
				break;
			default:
				throw new \InvalidArgumentException('invalid position ' . $position);
		}

		$sysConf = reset($conf);

		if (!empty($sysConf[static::CONF_NAMESPACE])) {
			\Camarera\AutoloaderNamespaceAliaser::registerAlias('', $sysConf[static::CONF_NAMESPACE]);
		}

		if (!empty($confLoaded[static::CONF_STORE])) {
			foreach ($confLoaded[static::CONF_STORE] as $eachKey=>$shit) {
				$StoreConfig = static::_confGet($confLoaded[static::CONF_STORE], $eachKey);
				static::registerStore($StoreConfig);
			}
		}
	}
	/**
	 * I look up an element in the config, and execute it if it's a callback
	 * @param array $data
	 * @param string[]|string $keys
	 * @return mixed
	 */
	protected static function _confGet($data, $keys) {
		// to support simple key calling
		if (!is_array($keys)) {
			$keys = array($keys);
		}

		// get current key
		$key = array_shift($keys);

		// if it's empty in data, nothing to return
		if (!isset($data[$key])) {
			return null;
		}

		// if it's a callback, execute it (lazy init)
		if (is_callable($data[$key]) && !is_array($data[$key])) {
			$data[$key] = $data[$key]();
		}

		// if there are more keys left, call myself recursively
		if (count($keys)) {
			return static::_confGet($data[$key], $keys);
		}

		// just return (it's not empty for sure here)
		$ret = $data[$key];

		return $ret;
	}
	/**
	 * @return string[] I return the keys of the main config array. These are the containers used to load them indeed.
	 * 		Looping through these you can loop through the whole config
	 */
	public static function confContainers() {
		return array_keys(static::$_confCache);
	}
	/**
	 * I return a value from config cache. I follow the key path, inflate config if a callback is found by executing it.
	 * The key must consist of dot separated indexes in the multi dimensional config arrays. All loaded containers will
	 * 		be checked for the config value, in reverse load order, and value returned on first match. Note that the
	 * 		returned value's content is not cascading, eg. 1st loaded container contains x=>array(a=1,b=2) and 2nd contains
	 * 		x=>array(a=10,c=20) then conf('x') will return array(a=10,c=20)
	 * If the first item is empty (key begins with dot) then each config container's first item will be referenced,
	 *		and cascaded, in reverse load order. Thus, latest loaded overwrites previous. Eg. '.env' will return
	 * 		config item Camarera.Camarera.env if no other config overwrites it, but Cocktail.Cocktail.env if defined etc.
	 * @param string $key dot delimeted lookup key, first element is module name
	 * @param string $container if sent, config value will be searched only in that container (which was also
	 * 		specified at conf load)
	 * @return mixed config value if found, null if not
	 */
	public static function conf($key, $container=null) {

		$keys = explode('.', $key);
		$val = null;

		// conf('.localRoot')
		if (empty($keys[0])) {
			array_shift($keys);
			foreach (static::$_confCache as $eachContainer=>$eachConf) {
				$val = static::_confGet(reset($eachConf), $keys);
				if (!is_null($val)) {
					break;
				}
			}
			return $val;
		}

		// conf('Cocktail.localRoot')
		foreach (static::$_confCache as $eachContainer=>$eachConf) {
			if (!is_null($container) && ($container != $eachContainer)) {
				continue;
			}
			$val = static::_confGet($eachConf, $keys);
			if (!is_null($val)) {
				break;
			}
		}

		return $val;
	}
	/**
	 * I recursively loop through data items and get all non-array ones from config. I have to do this to inflate
	 * 		closure values. It uses static::conf so it won't actually return data's values but any value which was
	 * 		defined on that key.
	 * Quite inefficient.
	 * Furthermore, quite inefficient.
	 * @param $data
	 * @param $usedKeys
	 * @return array
	 */
	protected static function _rConfGet($data, $usedKeys) {
		if (!is_array($data)) {
			return $data;
		}
		$ret = array();
		foreach ($data AS $eachKey=>$eachVal) {
			if (is_callable($eachVal)) {
				$eachVal = $eachVal();
			}
			if (is_array($eachVal)) {
				$newUsedKeys = array_merge($usedKeys, array($eachKey));
				$ret[$eachKey] = static::_rConfGet($eachVal, $newUsedKeys);
			}
			else {
				$key = implode('.', $usedKeys) . '.' . $eachKey;
				$ret[$eachKey] = static::conf($key);
			}
		}
		return $ret;
	}
	/**
	 * I return a value from the config cache, recursively cascaded.
	 * Eg. if 1st loaded conf contains x=>array(a=>1, b=>2) and 2nd loaded contains x=>array(a=>10, c=>20) then I return
	 * 		array(a=>10, b=>2, c=>20)
	 * @param $key
	 * @return array
	 *
	 */
	public static function rConf($key) {
		$keys = explode('.', $key);
		$configs = array();
		foreach (static::$_confCache as $eachContainer=>$eachConfig) {
			$configs[$eachContainer] = static::_confGet($eachConfig, $keys);
		}
		$configs = array_reverse($configs);

		$config = array();
		foreach ($configs as $eachContainer=>$eachConfig) {
			if (is_null($eachConfig));
			elseif (is_array($eachConfig)) {
				$usedKeys = $keys;
				$moreConfig = static::_rConfGet($eachConfig, $usedKeys);
				$config = \Util::arrayMergeRecursive($config, $moreConfig);
			}
			else {
				$config = $eachConfig;
			}
		}
		return $config;
	}

}

/**
 * thrown when unimplemented features are triggered
 */
class UnImplementedException extends \Exception {};
/**
 * thrown when a user defined class has definition errors (eg. doesn't contain required static fields for scoping)
 */
class ClassDefinitionException extends \Exception {};
/**
 * thrown when a __get fails to return a value
 */
class MagicGetException extends \Exception {
	function __construct($fieldName, $classname, $code=null, $previous=null) {
		$callerInfo = array_shift($this->getTrace());
		$message = 'property ' . $fieldName . ' does not exist in ' . $classname . '	Called in ' . $callerInfo['file'] . ' line ' . $callerInfo['line'];
		parent::__construct($message, $code, $previous);
	}
}
/**
 * thrown when a __set fails to set a value
 */
class MagicSetException extends MagicGetException {}
/**
 * thrown when a __call fails to return a value
 */
class MagicCallException extends \Exception {
	function __construct($methodName, $classname, $code=null, $previous=null) {
		$callerInfo = array_shift($this->getTrace());
		$message = 'call to undefined methodx ' . $classname . '->' . $methodName . '()	Called in ' . @$callerInfo['file'] . ' line ' . @$callerInfo['line'];
		parent::__construct($message, $code, $previous);
	}
}
/**
 * thrown when a __callStatic fails to return a value
 */
class MagicCallStaticException extends \Exception {
	function __construct($methodName, $classname, $code=null, $previous=null) {
		$callerInfo = array_shift($this->getTrace());
		$message = 'call to undefined static method ' . $classname . '::' . $methodName . '()		Called in ' . $callerInfo['file'] . ' line ' . $callerInfo['line'];
		parent::__construct($message, $code, $previous);
	}
}
