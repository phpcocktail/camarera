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
 *
 * @author t
 * @since 1.0
 * @license DWTFYWT
 * @version 1.01
 */
namespace Camarera;

/**
 * Base config class, convenient to extend and thus form data containers. Configs do:
 *
 *  * ensure easy and clear inline-creation of configurables (by chained setXxx() methods)
 *
 *  * should be used in place of config arrays, for real configuration-like usage, or just as enclosing params to a method
 *
 *  * should be used in usage scope (in the object/method it is passed to), and not directly (eg. don't molest a Model's
 *  	Field->$default unless you have reason,)
 *
 * 	* have real properties defined in PHP, public or protected (tip: use @property docs for easy autocomplete)
 *
 *  * by default, properties with names like $someThing are considered config properties (see $_fieldNameMask)
 *
 *  * protected properties are also exposed for getting, but not for setting (can be set through setProperty(), though)
 *  	protected properties can be accessed by $Object->propertyName instead of declared $_propertyName
 *
 *  * magic getXxx()/setXxx()/addXxx(), latter two can be chained for "fluid" objects
 *
 *  * you can define your getter/setter/magic methods over some properties and thus override default behaviour (eg.
 *		value checking or converting if necessary)
 *
 * @author t
 * @package Camarera
 * @version 1.01
 *
 * <code>
 * // here add real phpdoc syntax
 * // @method ManyParams setParam1(string $value)
 * // @method ManyParams setParam2(string $value)
 * // ...
 * // @method ManyParams setParamX(string $value)
 * class ManyParams extends \Config {
 *		public $param1;
 *		public $param2;
 *		...
 *		public $paramX;
 *
 *		// @return ManyParams (add proper phpdoc in live code to get nice autocomplete)
 *		public static function get($config=null) {
 *			return parent::get($config);
 *		}
 * }
 *
 * // example on how to use as fluid object
 * $Foo->bar(
 *		ManyParams::get()
 *			->setParam1('value1')
 *			->setParam2('value2')
 *			...
 *			->setParamX('value x')
 * );
 *
 * // this oldschool example is the same IDD
 * $ManyParams = ManyParams::get();
 * $ManyParams->param1 = 'value1';
 * $ManyParams->param2 = 'value2';
 * ...
 * $ManyParams->paramX = 'value x';
 * $Foo->bar($ManyParams);
 * </code>
 *
 */
abstract class Config {

	/**
	 * @var array cache field names of this object
	 */
	private static $_cachedFieldNames=array();

	/**
	 * @var string preg pattern used by magic property identification. Override if default pattern doesn not match your
	 *		coding style. By default, eg. using $Object->$foo either matches a public property in $Object, or, calls
	 *		magic __get('foo') which in turn looks for a protected $_foo property in $Object
	 */
	private static $_fieldNameMask = '/^[A-Za-z0-9]*$/';
	/**
	 * @var string preg pattern used by magic getters eg. with $Object->getFoo() getFoo must match this pattern to be used
	 */
	private static $_getNameMask = '/^(get)([A-Z][a-zA-Z0-9]*)$/';
	/**
	 * @var string preg pattern used by magic getters eg. with $Object->setFoo() setFoo must match this pattern to be used
	 */
	private static $_setNameMask = '/^(set)([A-Z][a-zA-Z0-9]*)$/';
	/**
	 * @var string preg pattern used by magic adders eg. with $Object->addFoo() addFoo must match this pattern to be used
	 */
	private static $_addNameMask = '/^(add)([A-Z][A-Za-z0-9]*)$/';

	/**
	 * I return a ConfigXxx object, empty or based on config array. Must be overridden in child classes.
	 * @param array $config raw (!) fieldName=>value pairs to be applied to config, must match existing field names
	 * @param bool $throw true = I throw exception on invalid value
	 * @return static
	 * @throws \InvalidArgumentException
	 */
	public static function get(array $config=null, $throw=true) {
		$Config = new static();
		if (is_null($config));
		elseif (is_array($config)) {
			foreach ($config AS $key=>$val) {
				if ($Config->_propertyExists($key)) {
					$Config->$key = $val;
				}
				elseif ($Config->_propertyExists('_' . $key)) {
					$key = '_' . $key;
					$Config->$key = $val;
				}
				else {
					// look up where the bad get() was called
					$trace = debug_backtrace();
					do {
						$callerInfo = array_shift($trace);
					}
					while (($callerInfo['function'] == 'get') &&
							(($callerInfo['class'] instanceof \Config) || ($callerInfo['class'] == 'Camarera\Config')) &&
							count($trace)
					);
					$msg = 'in Config::get() the field ' . $key . ' does not exists in class ' . $callerInfo['class'] . ', ' .
							' get() called in ' . $callerInfo['file'] . ' line #' . $callerInfo['line'];
					\Camarera::log(\Camarera::LOG_WARNING, $msg);
					if ($throw) {
						throw new \InvalidArgumentException($msg);
					}
				}
			}
		}
		else {
			throw new \InvalidArgumentException('$config must be null or an array');
		}
		return $Config;
	}

	/**
	 * I return a list of the names of defined fields. The list is cached for efficiency
	 * @return array
	 */
	protected static function _getDefinedProperties() {
		$classname = get_called_class();
		if (empty(self::$_cachedFieldNames[$classname])) {
			$Reflection = new \ReflectionClass($classname);
			$definedProperties = $Reflection->getProperties();
			$fieldNames = array();
			foreach($definedProperties AS $property) {
				if (preg_match(self::$_fieldNameMask, $property->name) ||
						preg_match(str_replace('/^', '/^_', self::$_fieldNameMask), $property->name)) {
					$fieldNames[] = $property->name;
				}
			}
			self::$_cachedFieldNames[$classname] = $fieldNames;
		}
		return self::$_cachedFieldNames[$classname];
	}

	/**
	 * I tell if $this->$var exists or not. For efficiency, I use a pre-generated lookup list
	 * @param string $fieldName fieldname to check
	 * @return type
	 */
	protected static function _propertyExists($fieldName) {
		return in_array($fieldName, static::_getDefinedProperties());
	}

	/**
	 * I am the magic getter to access protected properties
	 * @param $fieldName
	 * @return mixed
	 * @throws MagicGetException
	 */
	public function __get($fieldName) {
		if (preg_match(str_replace('/^_', '/^', self::$_fieldNameMask), $fieldName) && $this->_propertyExists('_' . $fieldName)) {
			$fieldName = '_' . $fieldName;
			return $this->$fieldName;
		}
		throw new MagicGetException($fieldName, get_class($this));
	}

	/**
	 * magic caller to catch setProperty() calls
	 * @param string $name
	 * @param array $arguments
	 */
	public function __call($method, $arguments) {
		// getFieldName($value)
		// setFieldName($value)
		// addFieldName($value)
		// $addFieldName($value, $index)
		if (preg_match(self::$_getNameMask, $method, $matches) ||
				preg_match(self::$_setNameMask, $method, $matches) ||
				preg_match(self::$_addNameMask, $method, $matches)) {
			$propertyName = lcfirst(substr($method, 3));
			$methodName = $matches[1];
			// prepend underscore if protected variable exists
			if (!$this->_propertyExists($propertyName) && $this->_propertyExists('_' . $propertyName)) {
				$propertyName = '_' . $propertyName;
			}
			if (!$this->_propertyExists($propertyName)) {
				throw new \MagicCallException($method, get_class($this));
			}
			if ($methodName == 'get') {
				$default = count($arguments) ? reset($arguments) : null;
				return is_null($this->$propertyName) ? $default : $this->$propertyName;
			}
			elseif ($methodName == 'set') {
				if (count($arguments) != 1) {
					throw new \BadMethodCall('setXxx() must be called with exactly one parameter');
				}
				$this->$propertyName = reset($arguments);
				return $this;
			}
			elseif ($methodName == 'add') {

				if (is_null($this->$propertyName)) {
					$this->$propertyName = array();
				}
				elseif (!is_array($this->$propertyName)) {
					throw new \InvalidArgumentException('add' . ucfirst($propertyName) . '() property ' . $propertyName . ' is not array or null, cannot add');
				}

				if (count($arguments) == 2) {
					$var = &$this->$propertyName;
					$var[$arguments[1]] = $arguments[0];
				}
				elseif (count($arguments) == 1) {
					array_push($this->$propertyName, $arguments[0]);
				}
				else {
					throw new \InvalidArgumentException('addXxx() must be called with 1 or 2 parameter(s)');
				}

				return $this;

			}
		}
		else {
			throw new \MagicCallException($method, get_class($this));
		}
	}

	/**
	 * use get() instead
	 */
	protected function __construct() {}

}

class ConfigException extends \LogicException{};
