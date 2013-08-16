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
 * Class TraitServe it is a standard serve() function to avoid confusion. There shall be another one, for models/collections
 *        serve() by default accepts only null to get an empty object OR ConfigXxx object to set it in $_Config (given
 *        that property exists!!!)
 * note: requires $this->_propertyExists($propertyName) defined (can be static, eg. as in TraitPropertyExists). Include
 * 		use \Camarera\TraitServe, \Camarera\TraitPropertyExists;
 * 		to resolve the dependency
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera\Trait
 * @since 1.1
 * @version 1.1
 */
trait TraitServe {

	/**
	 * I create and return an instance
	 *
	 * @todo maybe array param should be accepted?
	 * @param null|Config
	 * @return static
	 */
	public static function serve($data = null) {
		if (is_null($data)) {
			$Object = new static;
		}
		elseif (is_object($data) && ($data instanceof static)) {
			$Object = $data;
		}
		elseif (is_array($data)) {
			$Object = new static($data);
		}
		else {
			throw new \InvalidArgumentException('$data must be null or an array to serve');
		}
		return $Object;
	}

	/**
	 * I am protected, use serve() instead. I take an array and set each $this->{$key}=$val if $this->{$key} exists,
	 * 		otherwise throw exception
	 * mote you have to supply full var names in key, eg. '_Foo' => $Foo
	 * @throws \InvalidArgumentException
	 */
	protected function __construct(array $data = null) {
		if (is_null($data));
		else {
			foreach ($data AS $key=>$val) {
				if ($this->_propertyExists($key)) {
					$this->$key = $val;
				}
				elseif ($this->_propertyExists('_' . $key)) {
					$key = '_' . $key;
					$this->$key = $val;
				}
				else {
					// @todo revise this code as it is moved to a trait it should have changed
					// look up where the bad get() was called
					$trace = debug_backtrace();
					do {
						$callerInfo = array_shift($trace);
					}
					while (($callerInfo['function'] == 'get') &&
						(($callerInfo['class'] instanceof \Config) || ($callerInfo['class'] == 'Camarera\Config')) &&
						count($trace)
					);
					$msg = 'in Config::serve() the field ' . $key . ' does not exists in class ' . $callerInfo['class'] . ', ' .
						' get() called in ' . $callerInfo['file'] . ' line #' . $callerInfo['line'];
					\Camarera::log(\Camarera::LOG_WARNING, $msg);
					throw new \InvalidArgumentException($msg);
				}
			}
		}
	}

}
