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
 * Model is the base class which your own classes should extend to form your models. This class has the base
 *	functionalities:
 *	* store and retrieve data, using a set of
 *  * field objects, which define properties of the model. Field objects are stored staticly and thus inflated only once
 *  * have a special ID field, (which can be an agregate of multiple fields)
 *  * load/save data from/to designated Store objects
 *  * can use model registry to apply previously fetched data
 *  * provide singleton access (if property definition in your class allows)
 *  * provide magic getters/setters/properties based on field definitions
 *  * define basic CRUD store operation wrappers
 *  * be a good base for collections
 *
 * note: if you want singleton functionality, add the \Camarera\TraitSingleton* trait to your class
 *
 * @author t
 * @package Camarera\Model
 * @version 1.01
 *
 * @property string $ID the unique ID of the object
 * @property-read boolean $isRegistered
 * @property-read \ModelLoadConfg $LastLoadConfig
 */
abstract class Model {

	/** @var string FIELD_NAME_PATTERN this is the pattern to match property names */
	const FIELD_NAME_PATTERN = '/^(_id)$|^([a-z]+[a-zA-Z0-9\_]*)$/';
	const FIELD_GETTER_PATTERN = '/^get([A-Z]+[a-zA-Z0-9\_]*)$/';
	const FIELD_SETTER_PATTERN = '/^set([A-Z]+[a-zA-Z0-9\_]*)$/';
	const FIELD_ADDER_PATTERN = '/^add([A-Z]+[a-zA-Z0-9\_]*)$/';


	/** @var \Field[] field objects */
	protected static $_fields = null;
	/** @var string[]|string you can define ID field name or names if ID is built of more than one field */
	protected static $_idFieldName = null;
	/** @var string I use this to implode id field values into uniqe ID, if there are more than one id fields */
	protected static $_idFieldGlue = '-';
	/**
	 * override this to specify default table name, or leave null to use the lowercase classname
	 * 		note it can be overridden in store access methods by proper configs
	 * @var string
	 */
	protected static $_storeTable = null;


	//////////////////////////////////////////////////////////////////////////
	// STATIC
	//////////////////////////////////////////////////////////////////////////

	/**
	 * this will be called by _inflate() to get raw field defs. You can override this to return field defs
	 *	dynamicly (if simple class array definition in self::$_fields is not enough)
	 * @return array
	 */
	protected static function _getInitialFieldDefs() {
		return static::$_fields;
	}

	/**
	 * I call ModelInfoManager::inflate() to initialize all static variables, convert field defs etc.
	 * I only call once, hence the check for isInflated()
	 *
	 * @return string current scope classname. just for convenience, because many times it's needed right after inflate()
	 * @throws \ClassDefinitionException - I throw this on all errors here
	 */
	protected static function _inflate() {
		$classname = get_called_class();
		if (!\ModelInfoManager::isInflated($classname)) {
			\ModelInfoManager::inflate(
				$classname,
				static::_getInitialFieldDefs(),
				static::$_idFieldName,
				static::$_storeTable,
				static::$_collectionClassname
			);
		}
		return $classname;
	}

	/**
	 * I get one or all fields
	 * @param null|string|string[] $fieldnames null = get all fields, string = get one field, array = get some fields
	 * @return array|\Field
	 * @throws \InvalidArgumentException
	 */
	public static function field($fieldnames=null) {
		$classname = static::_inflate();
		return \ModelInfoManager::getField($classname, $fieldnames);
	}
	/**
	 * I return all id field names in array. Usually there will be just one, the array is for composite ID support
	 * @return string[]
	 */
	public static function idFieldName() {
		$classname = static::_inflate();
		return \ModelInfoManager::getIdFieldname($classname);
	}
	/**
	 * I return store table. For now just a string, in future, may be array for particioned store model
	 * @return string|array
	 */
	public static function storeTable() {
		$classname = static::_inflate();
		return \ModelInfoManager::getStoreTable($classname);
	}


	//////////////////////////////////////////////////////////////////////////
	// INSTANCE MANAGEMENT
	//////////////////////////////////////////////////////////////////////////

	/**
	 * @var boolean controls if this class is managable or not. Setting false is useful if you use the model without
	 * 		store capabilities, eg. an input validator model shouldn't be managed in object pool, just used locally
	 * @see \ModelInstanceManager
	 */
	protected static $_isRegisterable = true;
	/**
	 * @var boolean tells if this instance is registered or not
	 * @see \ModelInstanceManager
	 */
	protected $_isRegistered = false;
	/**
	 * @var \ModelLoadConfig the last Config used in a get() or load()
	 */
	protected $_LastLoadConfig = null;

	/**
	 * read-only access
	 * @return boolean
	 */
	public static function isRegisterable() {
		return static::$_isRegisterable;
	}

	/**
	 * I return an instance
	 * @param null|int|string|array|ModelLoadConfig $config depending on $config I will return various results
	 * 		null - empty object
	 * 		int|string - object with that ID @see setId()
	 * 		array - key=>value pairs with which returned object will be initialized with
	 * 		ModelLoadConfig - same as calling with (null, $Config)
	 * @param ModelLoadConfig $Config get options. @see ModelLoadConfig for options
	 * @return \Model
	 */
	public static function serve($dataOrConfig=null, $Config=null) {

		static::_inflate();

		// if there are 2 params, map it to 1-param call
		if (!is_null($Config) && ($Config instanceof \ModelLoadConfig)) {
			$Config->data = $dataOrConfig;
			return static::serve($Config);
		}

		// now we only have $dataOrConfig, make one sane $Config object

		// Model::serve() - returns empty object
		if (is_null($dataOrConfig)) {
			if (!is_null($Config)) {
				throw new \BadMethodCallException('Model::serve() - $Config must be null if $dataOrConfig is null');
			}
			$Config = \ModelLoadConfig::serve(array('allowLoad'=>false));
		}
		// Model::serve(1, array('x'=>2,'y'=>3)) - returns object of ID=1 and rest of data applied
		elseif ((is_string($dataOrConfig) || is_integer($dataOrConfig)) && is_array($Config)) {
			$data = $Config + array(static::idFieldName() => $dataOrConfig);
			$Config = \ModelLoadConfig::serve(array('data'=>$data, 'allowLoad'=>false));
		}
		// Model::serve(1) - returns object with id=1
		// Model::serve(array('x1'=>1)) - returns object with x1=1
		elseif (is_string($dataOrConfig) || is_integer($dataOrConfig) || is_array($dataOrConfig)) {
			$Config = \ModelLoadConfig::serve(array(
					'data' => $dataOrConfig,
					'allowLoad' => false,
			));
		}
		// Model::serve($Config) - returns object according to $Config
		elseif (is_object($dataOrConfig) && ($dataOrConfig instanceof \ModelLoadConfig)) {
			$Config = $dataOrConfig;
		}
		else {
			throw new \InvalidArgumentException();
		}

		// load data is empty, create empty object
		if (!isset($Config->data)) {
			$Model = new static;
		}
		// if object is registered, but not loadable, try getting from ModelInstanceManager
		elseif (!empty($Config->data) &&
			static::$_isRegisterable &&
			$Config->registeredInstance &&
			!$Config->allowLoad &&
			($Model = \ModelInstanceManager::get(get_called_class(), $Config->data, true))
		);
		// $Config->data is PK value
		elseif (is_integer($Config->data) || is_string($Config->data)) {
			$Model = new static;
			$Model->setId($Config->data);
			if ($Config->allowLoad) {
				$result = $Model->load($Config);
				if ($result && $Config->registeredInstance) {
					$Model->registerInstance();
				}
			}
		}
		elseif (is_array($Config->data)) {
			$Model = new static;
			$Model->setValue($Config->data, true);
			if ($Config->allowLoad) {
				$result = $Model->load($Config);
				if ($result && $Config->registeredInstance) {
					$Model->registerInstance();
				}
			}
		}

		$Model->_LastLoadConfig = $Config;

		return $Model;
	}

	/**
	 * register instance with the Manager
	 * @return \Model
	 */
	public function registerInstance() {
		if (!$this->isRegisterable()) {
			throw new \BadMethodCallException('Model::registerInstance() - class ' . get_class($this) . ' cannot be registered by declaration of static::$_isRegisterable');
		}
		\ModelInstanceManager::set(get_class($this), $this);
		return $this;
	}


	//////////////////////////////////////////////////////////////////////////
	// CONSTRUCT, MAGIC
	//////////////////////////////////////////////////////////////////////////

	/**
	 * I inflate my class if necessary, and set initial values
	 */
	protected function __construct() {
		static::_inflate();
	}

	public function __get($field) {
		switch(true) {
			case $field === 'ID':
				return $this->getID();
			case $field === 'isRegistered':
				return $this->_isRegistered;
			case $field === 'isValid':
				return $this->_isValid;
			case $field === 'LastLoadConfig':
				return $this->_LastLoadConfig;
			//case $field === 'isLoaded':
			// magic field value getters
			case (preg_match(static::FIELD_NAME_PATTERN, $field)):
				return $this->getValue($field);
			default:
				throw new \MagicGetException($field, get_class($this));
		}
	}
	public function __set($field, $value) {
		switch (true) {
			case $field === 'ID':
				$this->setID($value);
				break;
			case preg_match(static::FIELD_NAME_PATTERN, $field):
				$this->setValue($field, $value);
				break;
			default:
				throw new \MagicSetException($field, get_class($this));
		}
	}
	/**
	 * implement default setters and getters based on field names
	 * @param string $method
	 * @param mixed $arguments
	 * @throws MagicCallException
	 * @return mixed
	 */
	public function __call($method, $arguments) {
//		$getterPattern = strtr(lcFirst($method), array('/^' => '/^get(', '$/' => ')$/'));
//		$setterPattern = strtr(lcFirst($method), array('/^' => '/^set(', '$/' => ')$/'));
//		$getterPattern = '/^get([A-Z]+[a-zA-Z0-9]*)$/';
//		$setterPattern = '/^set([A-Z]+[a-zA-Z0-9]*)$/';
		$params = func_get_args();
		array_shift($params);
		switch (true) {
			case preg_match(static::FIELD_GETTER_PATTERN, $method, $matches):
				return $this->getValue(lcfirst($matches[1]));
			case preg_match(static::FIELD_SETTER_PATTERN, $method, $matches):
				return $this->setValue(lcfirst($matches[1]), reset($arguments));
			case preg_match(static::FIELD_ADDER_PATTERN, $method, $matches):
				return $this->addValue(lcfirst($matches[1]), reset($arguments));
			default:
				throw new \MagicCallException($method, get_class($this));
		}
	}


	//////////////////////////////////////////////////////////////////////////
	// DATA
	//////////////////////////////////////////////////////////////////////////

	/**
	 * @var array[string]mixed array of actual field values, keyed by fieldname
	 */
	protected $_values = array();
	/**
	 * @var array[string]mixed array of last loaded/saved field values
	 */
	protected $_storedValues = array();

	/**
	 * I set a value without casting it to the field's internal type. You don't want to use this, use setValue()
	 * @param $field
	 * @param $value
	 * @param bool $storedValue
	 * @return $this
	 */
	protected function _setRaw($field, $value, $storedValue=false) {
		if ($storedValue) {
			$this->_storedValues[$field] = $value;
		}
		else {
			$this->_values[$field] = $value;
		}
		return $this;
	}

	/**
	 * I return (actual) ID value
	 * @return string
	 */
	public function getID() {
		$id = null;
		$idFieldName = static::idFieldName();
		if (is_string($idFieldName)) {
			$id = $this->getValue($idFieldName);
		}
		elseif (is_array($idFieldName)) {
			$idFields = array();
			$hasValue = false;
			foreach ($idFieldName as $eachIdFieldName) {
				$eachValue = $this->getValue($eachIdFieldName);
				$hasValue = $hasValue || !is_null($eachValue);
				$idFields[] = $eachValue;
			}
			$id = $hasValue ? implode(static::$_idFieldGlue, $idFields) : null;
		}
		return $id;
	}
	/**
	 * static version of getID()
	 * @param array $data
	 * @return string the ID in string or null
	 */
	public static function calculateIdByArray(array $data) {
		$id = null;
		$idFieldName = static::idFieldName();
		if (is_string($idFieldName)) {
			$id = array_key_exists($idFieldName, $data) ? $data[$idFieldName] : null;
		}
		elseif (is_array($idFieldName)) {
			$idFields = array();
			$hasValue = false;
			foreach ($idFieldName as $eachIdFieldName) {
				$eachValue = null;
				if (array_key_exists($eachIdFieldName, $data)) {
					$eachValue = $data[$eachIdFieldName];
					$hasValue = true;
				}
				$idFields[] = $eachValue;
			}
			$id = $hasValue ? implode(static::$_idFieldGlue, $idFields) : null;
		}
		return $id;
	}
	/**
	 * I try to set ID field values. I can only set if:
	 * 		there is just one ID field, OR
	 * 		the $id is an array according to $idFieldNames
	 * 		the $id is a clearly explodable string, like what you'd get with getID
	 *
	 * @param string $id
	 * @throws \RuntimeException
	 * @throws \BadMethodCallException
	 * @return \Model
	 */
	public function setID($id) {

		if (is_array($id)) {

			foreach ($id as $eachKey=>$eachValue) {
				$this->setValue($eachKey, $eachValue);
			}

		}
		elseif (is_string($id) || is_integer($id)) {

			if (empty(static::$_idFieldGlue)) {
				throw new \RuntimeException('static::$_idFieldGlue not defined in ' . get_called_class());
			}

			$idFieldName = static::idFieldName();
			if (is_string($idFieldName)) {
				$this->setValue($idFieldName, $this->field($idFieldName)->setValue($id));
			}
			elseif (is_array($idFieldName)) {
				if ((substr_count($id, static::$_idFieldGlue)+1) !== count($idFieldName)) {
					throw new \InvalidArgumentException('id ' . $id . ' invalid');
				}
				$idParts = explode(static::$_idFieldGlue, $id);
				foreach ($idFieldName as $eachKey => $eachIdFieldName) {
					$idPart = $idParts[$eachKey];
					$idPart = $this->field($eachIdFieldName)->setValue($idPart);
					$this->setValue($eachIdFieldName, $idPart);
				}
			}
		}
		else {
			throw new \BadMethodCallException('$id should be string or array');
		}

		return $this;
	}

	/**
	 * I get one value, if is readable (otherwise, you have to take care of getting that value by a getter)
	 * @todo implement is_readable
	 * @param null|string|array $field field name or names to get, null to return all
	 * @throws \MagicGetException
	 * @return null|array|mixed field or fields based on param $field
	 */
	public function getValue($field=null, $storedValue=false) {

		if (is_null($field)) {
			$field = array_keys($storedValue ? $this->_storedValues : $this->_values);
		}

		if (is_string($field)) {
			$classname = get_class($this);
			if (!\ModelInfoManager::getField($classname, $field)) {
				throw new MagicGetException($field, get_class($this));
			}
			$ret = null;
			if (array_key_exists($field, $this->_values)) {
				// I call the field get filter/processor
				$ret = $storedValue ? $this->_storedValues[$field] : $this->_values[$field];
			}
		}
		elseif (is_array($field)) {
			$ret = array();
			foreach ($field as $eachField) try {
				$ret[$eachField] = $this->getValue($eachField, $storedValue);
			}
			catch (\Exception $e) {};
		}
		else {
			throw new \BadMethodCallException('invalid parameter for getValue, only string and array are valid');
		}

		return $ret;

	}
	/**
	 * unified setter for one value or for array of values. depending on number of params I forward to setValue or
	 * 		_setValues. I exists so the naming only interferes with field name 'Value' and not with 'Values'
	 * @param string|array $fieldOrData
	 * @param null|mixed $valueOrReplace @see _setValues()
	 * @param null|bool $throw @see _setValues()
	 */
	public function setValue($fieldOrData, $valueOrReplace=null, $throw=true) {
		if (is_array($fieldOrData)) {
			return $this->_setValues($fieldOrData, $valueOrReplace, $throw);
		}
		else {
			return $this->_setValue($fieldOrData, $valueOrReplace);
		}
	}
	/**
	 * set one value, if it is writeable (otherwise, you have to take care of setting that parameter)
	 * @param string $field
	 * @param mixed $value
	 * @throws MagicSetException
	 * @return \Model
	 */
	protected function _setValue($field, $value) {
		$classname = get_class($this);
		if (!array_key_exists($field, \ModelInfoManager::getField($classname))) {
			throw new \MagicSetException($field, get_class($this));
		}

		// I call field object's set validator/processor. Normally it returns $value unintact, otherwise modifies it
		$Field = $this->field($field);
		$this->_values[$field] = $Field->setValue($value);
		return $this;
	}
	/**
	 * I apply an array of field=>value pairs
	 * @param array $values field=>value pairs
	 * @param boolean $replace if true, I replace current data, otherwise append
	 * @param boolean $throw if true, errors will be thrown (field not found, etc), otherwise just apply as can
	 * @throws \InvalidArgumentException
	 * @throws \Exception
	 * @return \Model
	 */
	protected function _setValues($values, $replace=false, $throw=true) {
		if (!is_array($values)) {
			throw new \InvalidArgumentException("setValues argument not array");
		}
		if ($replace) {
			$this->_values = array();
		}
		foreach ($values as $eachFieldName=>&$eachValue) {
			try {
				$this->setValue($eachFieldName, $eachValue);
			}
			catch (\Exception $e) {
				if ($throw) {
					throw $e;
				}
			}
		}
		return $this;
	}
	/**
	 * unified adder
	 */
	public function addValue($field, $addValue, $position=null) {
		if (!is_null($position)) {
			throw new \UnImplementedException();
		}
		if (!is_string($field)) {
			throw new \BadMethodCallException();
		}
		elseif (!($Field = $this->field($field))) {
			throw new \InvalidArgumentException();
		}
		$value = $this->getValue($field);
		$this->_values[$field] = $Field->addValue($value, $addValue);
		return $this;
	}
	/**
	 * I store current values as the stored ones
	 * @return \Model
	 */
	public function setStoredValues() {
		$this->_storedValues = $this->_values;
		return $this;
	}

	/**
	 * check if I am dirty (at least field value have been changed since creation/last save/load
	 * @return boolean true if I am dirty
	 */
	public function isDirty() {
		return $this->_values === $this->_storedValues ? false : true;
	}

	/**
	 * I tell if one or a set of fields is/are dirty or not
	 *
	 * @param string|string[] $fieldname field name or array of field names
	 * @return array|bool
	 * @throws \InvalidArgumentException
	 */
	public function isFieldDirty($fieldname) {
		if (is_array($fieldname)) {
			$ret = array();
			foreach ($fieldname AS $eachField) {
				$ret[$eachField] = $this->isFieldDirty($eachField);
			}
		}
		elseif (is_string($fieldname)) {
			$ret = true;
			if (array_key_exists($fieldname, $this->_values) &&
					array_key_exists($fieldname, $this->_storedValues) &&
					($this->_values[$fieldname] == $this->_storedValues[$fieldname])) {
				$ret = false;
			}
		}
		else {
			throw new \BadMethodCallException('field name invalid');
		}
		return $ret;
	}

	/**
	 * simle array functions which tells if current values contain the given dataset or not
	 * @param data $data as in $this->_values, must have the proper keys
	 * @return boolean
	 */
	function valuesContain($data) {
		return count(array_diff_assoc($data, $this->_values)) ? false : true;
	}


	//////////////////////////////////////////////////////////////////////////
	// STORES
	//////////////////////////////////////////////////////////////////////////

	const STORE_READ = 1;
	const STORE_WRITE = 2;
	protected static $_storeRead = 'default';
	protected static $_storeWrite = 'default';

	/**
	 * I get store (1 param) or set store (2 params)
	 * @param $storeId
	 * @param null $storeOrStoreName
	 * @return \Store|void
	 */
	public static function Store($storeId, $storeOrStoreName=null) {
		if (func_num_args() == 2) {
			return static::_setStore($storeId, $storeOrStoreName);
		}
		else {
			return static::_getStore($storeId);
		}
	}

	/**
	 * I return the associated store object. I get instance from Camarera if necessary
	 * @param int $storeId
	 * @throws \InvalidArgumentException
	 * @return \Store
	 */
	protected static function _getStore($storeId) {
		switch($storeId) {
			case self::STORE_READ:
				if (is_string(static::$_storeRead)) {
					static::$_storeRead = Camarera::getStore(static::$_storeRead);
				}
				$ret = static::$_storeRead;
				break;
			case self::STORE_WRITE:
				if (is_string(static::$_storeWrite)) {
					static::$_storeWrite = Camarera::getStore(static::$_storeWrite);
				}
				$ret = static::$_storeWrite;
				break;
			default:
				throw new \InvalidArgumentException('Model::Store(): no such store: ' . print_r($storeId,1));
		}
		return $ret;
	}
	/**
	 * set an arbitrary store by id or by object instance
	 * @param int $storeId read or write self::STORE_READ or STORE_WRITE
	 * @param string|\Store $storeOrStoreName store by id or store instance
	 * @throws \BadMethodCallException
	 * @throws \InvalidArgumentException
	 */
	protected static function _setStore($storeId, $storeOrStoreName) {
		if (is_string($storeOrStoreName));
		elseif (is_object($storeOrStoreName) && is_subclass_of($storeOrStoreName, 'Camarera\Store'));
		else {
			throw new \BadMethodCallException('Model::setStore(): 2nd param must be string store ID or a store object');
		};
		switch($storeId) {
			case self::STORE_READ:
				static::$_storeRead = $storeOrStoreName;
				break;
			case self::STORE_WRITE:
				static::$_storeWrite = $storeOrStoreName;
				break;
			default:
				throw new \InvalidArgumentException('Model::Store(): no such store: ' . print_r($storeId,1));
		}
		return;
	}


	//////////////////////////////////////////////////////////////////////////
	// ACTIVERECORD
	//////////////////////////////////////////////////////////////////////////

	/**
	 * @var null|string|array after a load, I'll set the loaded fields (or '*' for all)
	 */
	protected $_loadedFields = null;

	/**
	 * I load a model
	 * @param \ModelLoadConfig $LoadConfig
	 * @return bool
	 * @throws UnImplementedException
	 */
	public function load(\ModelLoadConfig $LoadConfig=null) {
		if (is_null($LoadConfig)) {
			$LoadConfig = \ModelLoadConfig::serve();
		}
		$data = $this->_getStore(static::STORE_READ)->loadModel($this, $LoadConfig);
		if ($data === false) {
			// @todo
//			throw new UnImplementedException();
			return false;
		}
		elseif (is_array($data) && !empty($data)) {
			$this->_values = array();
			foreach ($data as $eachFieldName=>&$eachValue) {
				$this->_values[$eachFieldName] = $eachValue;
			}
			$this->setStoredValues();
			$this->_loadedFields = $LoadConfig->loadFields;
			$this->_LastLoadConfig = $LoadConfig;
			return true;
		}
		else {
			return null;
		}
	}

	/**
	 * I save a model data
	 * @param \ModelSaveConfig $SaveConfig
	 * @return bool|false|\mixed[]
	 * @throws \UnImplementedException
	 */
	public function save(\ModelSaveConfig $SaveConfig=null) {
		// @todo I should validate the model first
		// @todo I should apply default values on empty fields here ! ?
		if (is_null($SaveConfig)) {
			$SaveConfig = \ModelSaveConfig::get();
		}
		// has id: update
		if (strlen($this->ID)) {
			$ret = $this->_getStore(static::STORE_WRITE)->updateModel($this, $SaveConfig);
		}
		// otherwise, insert
		else {
			$ret = $this->_sgetStore(static::STORE_WRITE)->createModel($this, $SaveConfig);
		}
		if ($ret === false) {
			// @todo
			throw new \UnImplementedException();
			return false;
		}
		return $ret;
	}

	/**
	 * I delete a record. ModelLoadConfig seems feasible to encapsulate delete options (I'll need eager delete later) but
	 * 		this may change.
	 * @param \ModelLoadConfig $LoadConfig
	 * @throws \RuntimeException
	 * @return boolean true on success
	 */
	public function delete(\ModelDeleteConfig $DeleteConfig=null) {
		if (is_null($DeleteConfig)) {
			$DeleteConfig = \ModelDeleteConfig::get();
		}
		$id = $this->getID();
		if (empty($id)) {
			throw new \RuntimeException('cannot delete ' . get_class($this) . ' object without ID');
		}
		$ret = $this->_getStore(static::STORE_WRITE)->deleteModel($this, $DeleteConfig);
		return $ret ? true : false;
	}

	//////////////////////////////////////////////////////////////////////////
	// VALIDATION
	//////////////////////////////////////////////////////////////////////////

	protected $_isValid = null;

	protected $_validationErrors = array();

	public function validate() {
		$this->_isValid = true;
		$this->_validationErrors = array();
		$uniqueChecks = array();
		$validationErrors = array();
		$classname = get_class($this);
		$fields = \ModelInfoManager::getField($classname);
		foreach ($fields as $eachFieldName=>$EachField) {
			try {
				if ($EachField->unique) {
					$uniqueChecks[] = $eachFieldName;
				}
				if ($EachField->uniqueWith) {
					$check = array($eachFieldName, $EachField->uniqueWith);
					if (!in_array($check, $uniqueChecks)) {
						$uniqueChecks[] = $check;
					}
				}
				$value = array_key_exists($eachFieldName, $this->_values)
					? $this->_values[$eachFieldName]
					: null;
				$errors = $EachField->validate($value, $this);
				if (!empty($errors)) {
					$validationErrors[$eachFieldName] = $errors;
				}
			}
			catch (\FieldValidationException $e) {
				$validationErrors[$eachFieldName][] = $e->getMessage();
			}
		}

		// unique checks

		if (count($validationErrors)) {
			$this->_isValid = false;
			$this->_validationErrors = $validationErrors;
			#echop($validationErrors); die('HUBU');
		}
		return $this;
	}

	//////////////////////////////////////////////////////////////////////////
	// MORE
	//////////////////////////////////////////////////////////////////////////

	/**
	 * @var string name of class which contains these objects. Leave empty for autoguess or fill to speed up a bit.
	 */
	protected static $_collectionClassname = null;

	/**
	 * I return an empty collection which can handle these models. I guess the classname by:
	 * 			static::$_collectionClassname
	 * 			{__CLASS__}Collection
	 * 			Collection
	 * 		in this order. If $_collectionClassname is set, it must be valid as it is not checked. If {__CLASS__}Collection
	 * 		does not exists, Collection will be used with the proper ModelClassname set
	 */
	public static function collectionClassname() {
		$classname = static::_inflate();
		return \ModelInfoManager::getCollectionClassname($classname);
	}

	/**
	 * @return Collection I return a CollectionXxx instance which holds these models
	 */
	public static function collection(\CollectionGetConfig $Config=null) {
		$classname = static::_inflate();
		$collectionClassname = \ModelInfoManager::getCollectionClassname($classname);
//		$Collection = $collectionClassname::serve();
		$Collection = $collectionClassname::get($Config);
		$Collection->setModelClassname($classname);
		return $Collection;
	}

}

class FieldValidationException {}
class ModelValidationException {}
