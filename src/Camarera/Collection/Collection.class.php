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
 */
namespace Camarera;

/**
 *
 * @author t
 * @package Camarera\Collection
 * @version 1.01
 *
 * @property-read \CollectionGetConfig $GetConfig @see $_GetConfig
 * @property-read array $datas all object's datas
 * @property-read string $modelClassname @see $_modelClassname
 * @property-read array|string $modelIdFieldName modelClassname's idFieldName
 */
class Collection implements \Iterator, \Countable {

	/**
	 * @var string a model that this collection contains.
	 */
	protected static $_modelClassname = null;
	/**
	 * @var boolean true=returned objects will be registered in the Multiton object pool
	 * @todo implement this if demanded, for now all instances will be registered
	 */
	//protected $_registeredInstances = true;

	protected static $_storeRead = 'default';
	protected static $_storeWrite = 'default';

	/**
	 * @var CollectionXxxGetConfig|CollectionGetConfig|null this holds the Collection load extra params after get() is called
	 * @todo implement
	 */
	protected $_GetConfig = null;

	/**
	 * @var array[string]true,string,\Model model object registry, keyed by ID and value can be :
	 * 		- reference of object instance (managed or not, depending on load conditions)
	 * 		- true if data is set in model manager but not yet instanciated or referenced
	 * 		- string (or int) the same as the key, must be strict match (===) if data is not yet loaded
	 */
	protected $_instances = array();

	/**
	 * I return an appropriate builder object for the collection. If a specialized class is defined, then that. If not, a
	 * 	standard builder with my classname set (so it's aware of)
	 */
	public static function getBuilder() {
		// @todo
	}

	public static function getModelClassname() {
		if (is_null(static::$_modelClassname)) {
			$classname = get_called_class();
			$pos = strrpos($classname, 'Collection');
			// static::$_storeTable is not set and cannot be guessed
			if ($pos === false) {
				throw new \ClassDefinitionException();
			}
			$classname = substr($classname, 0, $pos);
			static::$_modelClassname = $classname;
		}
		return static::$_modelClassname;
	}

	/**
	 * @param CollectionGetConfig|array|null $Config - use it to configure Collection instance
	 * @param array $datas to directly send datas. If you send datas no load will occur automaticly
	 * @return \Collection
	 */
	public static function get($itemsOrFilterOrConfig=null, \CollectionGetConfig $Config=null) {

		if (!is_null($Config)) {
			if (is_object($itemsOrFilterOrConfig) && ($itemsOrFilterOrConfig instanceof \StoreFilter)) {
				$Config->filter = $itemsOrFilterOrConfig;
			}
			else {
				$Config->datas = $itemsOrFilterOrConfig;
			}
			return static::get($Config);
		}

		if (is_null($itemsOrFilterOrConfig)) {
			$Config = \CollectionGetConfig::get(array(
					'allowLoad' => false,
			));
		}
		elseif (is_array($itemsOrFilterOrConfig)) {
			$Config = \CollectionGetConfig::get(array(
					'datas' => $itemsOrFilterOrConfig,
			));
		}
		elseif (is_object($itemsOrFilterOrConfig) && ($itemsOrFilterOrConfig instanceof \CollectionGetConfig)) {
			$Config = $datasOrConfig;
		}
		else {
			throw new \InvalidArgumentException();
		}

		$Collection = new static();

		if (!empty($Config->filter)) {
			$Collection->load($Config);
		}
		elseif (!empty($Config->datas)) {
			$Collection->setItems($Config->datas);
		}

		$Collection->_setGetConfig($Config);

		return $Collection;

	}

	public static function getByIds($ids, \CollectionGetConfig $Config=null) {

		if (!is_array($ids)) {
			goto invalid;
		}

		foreach ($ids as &$eachId) {
			if (!is_string($eachId) && !is_integer($eachId)) {
				goto invalid;
			}
		}

		valid:
		$Config->datas = $ids;
		return static::get($Config);

		invalid:
		throw new \InvalidArgumentException();

	}
	public static function getByObjects($objects, \CollectionGetConfig $Config=null) {

		if (!is_array($ids)) {
			goto invalid;
		}

		foreach ($objects as &$eachObject) {
			$modelClassname = static::getModelClassname();
			if (!is_object($eachObject) || !($eachObject instanceof $modelClassname)) {
				goto invalid;
			}
		}

		valid:
		$Config->datas = $objects;
		return static::get($Config);

		invalid:
		throw new \InvalidArgumentException();

	}
	public static function getByFilter($filter, $Config=null) {

		if (!is_object($filter) || !($filter instanceof Camarera\StoreFilter)) {
			goto invalid;
		}

		valid:
		$Config->filter = $filter;
		return static::get($Config);

		invalid:
		throw new \InvalidArgumentException();

	}

	/**
	 * I set the last used get config and over what would be needed normally
	 * @param CollectionGetConfig $Config
	 * @return \Collection
	 */
	protected function _setGetConfig(\CollectionGetConfig $Config) {
		$this->_GetConfig = $Config;
		// @todo implement switching of managed instances or not
// 		if (in_array($Config->registeredInstances, array(true, false), true)) {
// 			$this->_registeredInstances = $Config->registeredInstances;
// 		}
		return $this;
	}

	protected function __construct() {}

	public function __get($key) {
		switch(true) {
			case $key === 'GetConfig':
				if (is_null($this->_GetConfig)) {
					$this->_GetConfig = \CollectionGetConfig::get();
				}
				return $this->_GetConfig;
			case $key === 'datas':
				return $this->getDatas();
			case $key === 'modelClassname':
				return static::getModelClassname();
			case $key === 'modelIdFieldName':
				$classname = static::getModelClassname();
				$idFieldName = $classname::getIdFieldName();
				return $idFieldName;
			default:
				throw new \MagicGetException($key, get_class($this));
		}
	}


	//////////////////////////////////////////////////////////////////////////
	// STORE
	//////////////////////////////////////////////////////////////////////////

	/**
	 * I return read or write store and instanciate if necessary (for the first
	 * 		call it will only contain identifier of store, so always use this
	 *		method to get Model's store)
	 * @param int $storeId ModelConfig::STORE_READ or ModelConfig::STORE_WRITE, null defaults to STORE_READ
	 * @throws \InvalidArgumentException
	 * @return \Store
	 */
	function getStore($storeId=null) {
		switch($storeId) {
			case \Model::STORE_READ:
			case null:
				if (is_string(self::$_storeRead)) {
					self::$_storeRead = \Camarera::getStore(self::$_storeRead);
				}
				return self::$_storeRead;
				// nobreak
			case \Model::STORE_WRITE:
				if (is_string(self::$_storeWrite)) {
					self::$_storeWrite = \Camarera::getStore(self::$_storeWrite);
				}
				return self::$_storeWrite;
				// nobreak
			default:
				throw new \InvalidArgumentException('Model::getStore(): no such store: ' . print_r($storeId,1));
		}
	}
	/**
	 * @return string I return the actual model's storetable (remember: without prefix, prefix belongs to store)
	 */
	function getStoreTable() {
		$ModelClassname = static::getModelClassname();
		return $ModelClassname::getStoreTable();
	}


	//////////////////////////////////////////////////////////////////////////
	// DATAS
	//////////////////////////////////////////////////////////////////////////

	/**
	 * replace or add datas to data array. overwrites exitsing data (removes object from isntances too!), etc
	 * @param array $datas $fieldName=>$val pairs of data, $fieldName is pure fieldname as in model (no tablename, etc)
	 * @param boolean $accumulate true=add data, otherwise just replace
	 * @return \Collection
	 */
	public function setItems($datas, $accumulate=false, $dataIsIndexed=false) {

		$modelClassname = static::getModelClassname();

		if (!$accumulate) {
			$this->_instances = array();
		}

		if ($dataIsIndexed);
		// map data to be indexed
		else {
			$datasX = array();
			foreach ($datas AS $data) {
				if (is_object($data) && (($data instanceof $modelClassname))) {
					$key = $data->ID;
				}
				elseif (is_array($data)) {
					$key = $modelClassname::calculateIdByArray($data);
				}
				elseif (is_string($data) || is_integer($data)) {
					$key = $data;
				}
				else {
					throw new \RuntimeException();
				}
				$datasX[$key] = $data;
			}
			$datas = $datasX;
		}

		// set true on ID key in $_instances and register actual data with manager
		foreach ($datas as $eachId=>$eachData) {
			if (!array_key_exists($eachId, $this->_instances)) {
				$this->_instances[$eachId] = true;
			}
			if (is_array($eachData)) {
				\ModelManager::set($modelClassname, $eachData, $eachId);
			}
		}

		$this->_isLoaded = true;

		return $this;

	}
	/**
	 * I add items
	 * @param unknown $items
	 * @throws \InvalidArgumentException
	 * @return \Collection
	 */
	public function addItems($items) {
		foreach ($items AS &$item) {
			if (is_object($item) && is_subclass_of($item, static::getModelClassname())) {
				$this->addObject($item);
			}
			elseif (is_array($item)) {
				$modelClass = static::getModelClassname();
				// @todo check 'registeredInstance' here?
				// @todo add check for model-specific load config? does it make sense?
				if (empty($AddArrayConfig)) {
					$AddArrayConfig = ModelGetConfig::get(array(
							'allowLoad' => false,
					));
				}
				$Object = $modelClass::get($item, $AddArrayConfig);
				$this->addObject($Object);
			}
			elseif (is_string($item) || is_integer($item)) {
				$this->_instances[$item] = true;
			}
			else {
				throw new \InvalidArgumentException();
			}
		}
		return $this;
	}
	/**
	 * I remove matching items
	 * @param (Model|array|string|integer)[] $items array with items of object and/or array. Objects will be removed
	 * 		as-is, for arrays, all matching items will be removed, or, if string or integer, the object with matching
	 * 		ID will be removed.
	 */
	public function removeItems($items) {
		foreach ($items AS &$item) {
			if (is_object($item) && is_subclass_of($item, static::getModelClassname)) {
				$this->removeObject($item);
			}
			elseif (is_array($item)) {
				$modelClass = static::getModelClassname();
				$ID = $modelClass::calculateIdByArray($item);
				$this->removeObjectById($ID);
			}
			elseif (is_string($item) || is_integer($item)) {
				$this->removeObjectById($item);
			}
			else {
				throw new \InvalidArgumentException();
			}
		}
		return $this;
	}
	/**
	 * I add an object to the current instances and datas pool
	 * @param \Model $Object
	 * @return \Collection
	 */
	public function addObject(\Model $Object) {
		$idFieldName = $this->modelIdFieldName;
		$ID = $Object->ID;
		if (!array_key_exists($ID, $this->_instances)) {
			$this->_instances[$ID] = true;
		}
		return $this;
	}
	/**
	 * I remove an object from the collection
	 * @param \Model $Object
	 * @return \Collection
	 */
	public function removeObject($IdOrObject) {
		if (is_string($IdOrObject) || is_integer($IdOrObject)) {
			$ID = $IdOrObject;
		}
		elseif (is_object($IdOrObject)) {
			$ID = $IdOrObject->ID;
		}
		else {
			throw new \InvalidArgumentException();
		}

		return $this->removeObjectById($ID);
	}
	/**
	 * I return an object of given ID or null
	 * @param int|string $ID
	 * @return \Model|null
	 */
	public function getObjectById($ID) {

		$modelClassname = static::getModelClassname();

		// instanciate if necessary
		if (isset($this->_instances[$ID]) && ($this->_instances[$ID] === true)) {
			$this->_instances[$ID] = \ModelManager::getObject($modelClassname, $ID);
		}
		elseif (isset($this->_instances[$ID]) && ($ID === $this->_instances[$ID])) {
			// @todo here I should do a load on the model but have some control over this
			throw new \UnImplementedException();
		}
		elseif (isset($this->_instances[$ID]) && is_object($this->_instances[$ID]));
		else {
			return null;
		}
		return $this->_instances[$ID];

	}
	/**
	 * I remove an object from the collection
	 * @param type $ID
	 */
	public function removeObjectById($ID) {
		if (isset($this->_instances[$ID])) {
			unset($this->_instances[$ID]);
		}
		return $this;
	}
	/**
	 * $this->_datas may contain more than one object's datas, and $_modelClassname can be used to retrieve different
	 *		Models from the collection. Here I return a pure collection clone which has the same objects as I
	 *		currently have. Please note that there is not much use of it until you're reusing original Collection object
	 */
	public function cloneCurrentCollection() {
		// @TODO medium implement
	}

	/**
	 * I return IDs of current items
	 * @return string[]
	 */
	public function getIds() {
		return array_keys($this->_instances);
	}
	public function getValues($fields=null) {
		// @todo implement
		die('@todo');
	}

	//////////////////////////////////////////////////////////////////////////
	// LOAD, SAVE(?)
	//////////////////////////////////////////////////////////////////////////

	/**
	 * I load a collection based on ... WHAT? :D
	 * @param CollectionGetConfig $Config
	 * @return \Collection
	 */
	public function load($filterOrDataOrConfig=null, \CollectionGetConfig $Config = null) {

		if (!is_null($Config)) {
			if (!is_o)
			if (is_object($filterOrDataOrConfig) && ($filterOrDataOrConfig instanceof Camarera\CollectionGetConfig)) {
				$Config->filter = $filterOrDataOrConfig;
			}
			elseif (is_array($filterOrDataOrConfig)) {
				$Config->datas = $filterOrDataOrConfig;
			}
			else {
				throw new \BadMethodCallException();
			}
			return static::load($Config);
		}

		if (is_null($filterOrDataOrConfig)) {
			$Config = CollectionGetConfig::get(array(
					'allowLoad' => true,
			));
		}
		elseif (is_array($filterOrDataOrConfig)) {
			$Config = \CollectionGetConfig::get(array(
					'filter' => $filterOrDataOrConfig,
					'allowLoad' => true,
			));
		}
		elseif (is_object($filterOrDataOrConfig) && ($filterOrDataOrConfig instanceof Camarera\CollectionGetConfig)) {
			$Config = $filterOrDataOrConfig;
		}
		else {
			throw new \InvalidArgumentException();
		}

		if (!$Config->allowLoad) {
			return null;
		}

		if (is_array($Config->filter)) {
			$filters = array();
			foreach ($Config->filter as $filter) {
				if (is_string($filter) || is_integer($filter)) {
					$filters[] = \StoreFilter::getEquals('_id', $filter);
				}
				elseif (is_array($filter)) {
					$filterData = array();
					foreach ($filter as $eachFilterKey=>$eachFilter) {
						$filterData[] = \StoreFilter::get('=', $eachFilterKey, $eachFilter);
					}
					$filters[] = StoreFilter::getAnd($filterData);
				}
			}
			$Config->filter = \StoreFilter::get('OR', null, $filters);

		}

		$datas = $this->getStore(\Model::STORE_READ)->loadCollection($this, $Config);

		$this->setItems($datas, $Config->accumulate);

		return $this;
	}
	/**
	 * load by raw query. If you do not generate the query by the store, make sure you send only compatible queries (eg.
	 *		do not send SQL query to an XML store)
	 * @param string $query
	 * @return $this
	 */
	function loadByQuery($query, \CollectionGetConfig $Config=null) {
		$datas = $this->getStore(\Model::STORE_READ)->queryData($query);
		$this->setItems($datas, $Config->accumulate);
		return $this;
	}


	//////////////////////////////////////////////////////////////////////////
	// IMPLEMENTING ARRAY AND ITERATOR
	//////////////////////////////////////////////////////////////////////////

	/**
	 * I return current element as an object instance. I create the instance if it's not done yet
	 * @return \Model some subclass of Model object, as in $this->_modelClassname
	 * @throws \RuntimeException
	 */
	function current() {

		$modelClassname = static::getModelClassname();

		if (!$this->valid()) {
			return false;
		}

		$ID = key($this->_instances);

		if (is_null($ID)) {
			return false;
		}

		return $this->getObjectById($ID);

	}
	function key() {
		return key($this->_instances);
	}
	function rewind() {
		reset($this->_instances);
		return $this->current();
	}
	function valid() {
		return current($this->_instances) === false ? false : true;
	}
	function next() {
		next($this->_instances);
		return $this->current();
	}

	function count() {
		return count($this->_instances);
	}

}
