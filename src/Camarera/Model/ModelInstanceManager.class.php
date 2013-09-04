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
 * Model and Model data manager. It acts like a multiton pool, but can also store raw data for lazy instantiation.
 * 		- the multiton concept assures that you have shared named objects through your application, eg. if you load
 * 			user #1 at multiple code points independently, you still get the same object (without reloading)
 * 		- the data store just registers data for objects, and they are instanciated when they are asked for by get()
 * 		- I decided to make it static and not singleton, since I can't think of any case in which the manager would be
 * 			passed as a parameter
 * @author t
 * @package Camarera\Model
 * @version 1.01
 */
class ModelInstanceManager {

	/**
	 * @var array[string](array|object) the main data registry, holds either data of the given object only (for lazy
	 * 		init) or object instances themselves
	 */
	protected static $_registry = array();

	/**
	 * I need no setup
	 */
	private final function __construct() {}

	/**
	 * I get a model by ID or data
	 * @param string $modelClass with namespace
	 * @param int|string| $idOrData
	 * @param boolean $asObject if false, I return only array (and do not instanciate object if not has been done yet)
	 * @throws \BadMethodCallException
	 */
	public static function get($modelClass, $idOrData, $asObject=true) {

		$ret = null;

		if (is_string($idOrData) || is_integer($idOrData)) {
			$ret = $asObject ? static::getObject($modelClass, $idOrData) : static::getData($modelClass, $idOrData);
		}
		elseif (is_array($idOrData) && !empty($idOrData)) {
			if (!empty(self::$_registry[$modelClass])) {
				foreach (self::$_registry[$modelClass] as &$dataOrInstance) {
					if (is_array($dataOrInstance)) {
						// if $dataOrInstance is array and contains $idOrData
						if (array_intersect($idOrData, $dataOrInstance) === $idOrData) {
							if (!$asObject) {
								$ret = $dataOrInstance;
							}
							else {
								// @todo set $ret here???
								$Config = \ModelLoadConfig::serve(array(
										'allowLoad' => false,
								));
								$ret = $modelClass::serve()
									->setValue($idOrData, true);
								break;
							}
						}
					}
					elseif ($dataOrInstance->valuesContain($idOrData)) {
						$ret = $asObject ? $dataOrInstance : $dataOrInstance->getValue(null);
						break;
					}
				}
			}
		}
		else {
			throw new \BadMethodCallException();
		}

		return $ret;

	}

	/**
	 * I find all matching objects (which contain data)
	 * @todo this should return a collection, disabled for now
	 * @param string $modelClass with namespace
	 * @param array $data
	 * @throws \InvalidArgumentException
	 * @return multitype:unknown
	 */
/*
	public function find($modelClass, array $data) {

		$ret = array();

		if (!is_array($data)) {
			throw new \InvalidArgumentException();
		}

		if (!empty($this->_registry[$modelClass])) {
			foreach ($this->_registry[$modelClass] as $eachClassArray) {
				foreach ($eachClassArray as $dataOrInstance) {
					if (is_array($dataOrInstance)) {
						// if $dataOrInstance contains $idOrData
						if (array_intersect($idOrData, $dataOrInstance) === $data) {
							die ('@todo instanciate and break');
						}
					}
					else {
						$ret[] = $data;
					}
				}
			}
		}

		return $ret;

	}
*/

	/**
	 * I get an object from the registry by ID
	 * @param string $modelClass with namepsace
	 * @param int|string $id
	 * @return NULL|\Model
	 */
	public static function getObject($modelClass, $id) {

		$ret = null;
		if (isset(self::$_registry[$modelClass][$id])) {
			if (is_object(self::$_registry[$modelClass][$id])) {
				$ret = self::$_registry[$modelClass][$id];
			}
			elseif (is_array(self::$_registry[$modelClass][$id])) {
				// @todo I'll have to implement some default features here like disallow loading from store or registry
				$Config = \ModelLoadConfig::serve(array(
					'allowLoad' => false,
				));
				$ret = self::$_registry[$modelClass][$id] = $modelClass::serve(self::$_registry[$modelClass][$id], $Config);
			}
		}
		return $ret;
	}

	/**
	 * I get an object's data from the registry. If data is not yet inflated, I won't neither.
	 * @param string $modelClass with namepsace
	 * @param int|string $id
	 * @return NULL|array
	 */
	public static function getData($modelClass, $id) {

		$ret = null;
		if (isset(self::$_registry[$modelClass][$id])) {
			if (is_object(self::$_registry[$modelClass][$id])) {
				$ret = self::$_registry[$modelClass][$id]->getValue(null);
			}
			elseif (is_array(self::$_registry[$modelClass][$id])) {
				$ret = self::$_registry[$modelClass][$id];
			}
		}

		return $ret;

	}

	/**
	 * I set a model's data or instance in the registry
	 * @param string $modelClass with namespace
	 * @param array|\Model $dataOrObject
	 * @param string|null $precalculatedId I will send this if it is already pre-calculated (otherwised guessed)
	 * @throws \BadMethodCallException
	 */
	public static function set($modelClass, $dataOrObject=null, $precalculatedId=null) {

		if (is_array($dataOrObject) && !empty($dataOrObject));
		elseif (is_object($dataOrObject) && ($dataOrObject instanceof $modelClass)) {
			// only models can be registered
			if (!$dataOrObject instanceof \Model) {
				throw new \InvalidArgumentException();
			}
		}
		else {
			throw new \InvalidArgumentException();
		}

		if (!isset(self::$_registry[$modelClass])) {
			self::$_registry[$modelClass] = array();
		}

		$id = is_null($precalculatedId)
			? (is_object($dataOrObject)
				? $dataOrObject->getID()
				: $modelClass::calculateIdByArray($dataOrObject))
			: $precalculatedId;

		if (is_null($id)) {
			throw new \InvalidArgumentException('ID shouldn\'t be null here');
		}

		self::$_registry[$modelClass][$id] = $dataOrObject;

	}

}
