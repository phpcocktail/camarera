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
 * Class TraitMagicGetterSetterMask is a child of TraitMagicGetterSetterXxx family which adds magic methods to your
 * 		class, exposing getting and setting as well (plus the addXxx method).
 * TraitMagicGetterSetterMask will validate the method names against defined preg patterns, thus eg. you can restrict
 * 		the addProperty() method to properties with prefix '_foo'
 * note: requires $this->_propertyExists($propertyName) defined (can be static, eg. as in TraitPropertyExists). Include
 * 		use \Camarera\TraitMagicGetterSetterMask, \Camarera\TraitPropertyExists;
 * 		to resolve the dependency
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera\Trait
 * @since 1.1
 * @version 1.1
 */
trait TraitMagicGetterSetterMask {

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
	 * 		note I kept this separate so you can have a common prefix for properties which can be added to, eg. with
	 * 		a mask pf '/^(add)(sum[A-Z][A-Za-z0-9]*)ß/ you could add to 'sumFoo' by addSumFoo($add) but no to 'someFoo'
	 */
	private static $_addNameMask = '/^(add)([A-Z][A-Za-z0-9]*)$/';

	/**
	 * I am a magic caller to catch setProperty() etc calls. I will match mathod (and thus) property name and set/add to
	 *		it as params tell, on any error I'll throw MagicCallException
	 * @param string $name
	 * @param array $arguments
	 * @throws \MagicCallException
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
				// @todo why count here?
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

}
