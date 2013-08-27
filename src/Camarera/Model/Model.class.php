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
 * @property-read string[] $idFieldName name of id fields
 * @property-read boolean $isDirty
 * @property-read boolean $isRegistered
 * @property-read string $storeTable
 */
abstract class Model {

	/** @var string FIELD_NAME_PATTERN this is the pattern to match property names */
	const FIELD_NAME_PATTERN = '/^(\_id)|([a-z]+[a-zA-Z0-9]*)$/';

	/** @var \Field[] field objects */
	protected static $_fields = null;
	/** @var string[]|string you can define ID field name or names if ID is built of more than one field */
	protected static $_idFieldName = null;
	/** @var string I use this to implode id field values into uniqe ID, if there are more than one id fields */
	protected static $_idFieldGlue = '_';
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
	 * I call ModelMetaInfo::inflate() to initialize all static variables, convert field defs etc.
	 * I only call once, hence the check for isInflated()
	 *
	 * @return string current scope classname. just for convenience, because many times it's needed right after inflate()
	 * @throws \ClassDefinitionException - I throw this on all errors here
	 */
	protected static function _inflate() {
		$classname = get_called_class();
		if (!\ModelMetaInfo::isInflated($classname)) {
			\ModelMetaInfo::inflate(
				$classname,
				static::_getInitialFieldDefs(),
				static::$_idFieldName,
				static::$_storeTable
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
		return \ModelMetaInfo::getField($classname, $fieldnames);
	}
	/**
	 * I return all id field names in array. Usually there will be just one, the array is for composite ID support
	 * @return string[]
	 */
	public static function idFieldName() {
		$classname = static::_inflate();
		return \ModelMetaInfo::getIdFieldname($classname);
	}
	/**
	 * I return store table. For now just a string, in future, may be array for particioned store model
	 * @return string|array
	 */
	public static function storeTable() {
		$classname = static::_inflate();
		return \ModelMetaInfo::getStoreTable($classname);
	}


	//////////////////////////////////////////////////////////////////////////
	// INSTANCE MANAGEMENT
	//////////////////////////////////////////////////////////////////////////

	/**
	 * @var boolean controls if this class is managable or not. Setting false is useful if you use the model without
	 * 		store capabilities, eg. an input validator model shouldn't be managed in object pool, just used locally
	 * @see \ModelManager
	 */
	protected static $_isRegisterable = true;
	/**
	 * @var boolean tells if this instance is registered or not
	 * @see \ModelManager
	 */
	protected $_isRegistered = false;
	/**
	 * @var \ModelLoadConfig the last Config used in a get() or load()
	 */
	protected $_lastGetConfig = null;

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
	public static function serve($dataOrConfig=null, \ModelLoadConfig $Config=null) {

		static::_inflate();

		// if there are 2 params, map it to 1-param call
		if (!is_null($Config)) {
			$Config->data = $dataOrConfig;
			return static::serve($Config);
		}

		// now we only have $dataOrConfig, make one sane $Config object
		if (is_null($dataOrConfig)) {
			$Config = \ModelLoadConfig::serve(array('allowLoad'=>false));
		}
		elseif (is_string($dataOrConfig) || is_integer($dataOrConfig) || is_array($dataOrConfig)) {
			$Config = \ModelLoadConfig::serve(array(
					'data' => $dataOrConfig,
					'allowLoad' => false,
			));
		}
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
		// @todo add registerable case here. old code left comented for sample
//		elseif (!empty($Config->data) &&
//			static::$_isRegisterable &&
//			$Config->registeredInstance &&
//			$Config->allowLoad &&
//			($Model = \ModelManager::get(get_called_class(), $Config->data)));
		// $Config->data is PK value
		elseif (is_integer($Config->data) || is_string($Config->data)) {
			debug_print_backtrace(); die('TEST ME1');
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

		$Model->_lastGetConfig = $Config;

		return $Model;
	}

	/**
	 * register instance with the Manager
	 * @return \Model
	 */
	public function registerInstance() {
		\ModelManager::set(get_class($this), $this);
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
			case $field === 'idFieldName':
				return static::idFieldName();
			case $field === 'isRegistered':
				return $this->_isRegistered;
			case $field === 'storeTable':
				return $this->storeTable();
			case $field === 'isValid':
				return $this->_isValid;
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
		$getterPattern = strtr(lcFirst($method), array('/^' => '/^get(', '$/' => ')$/'));
		$setterPattern = strtr(lcFirst($method), array('/^' => '/^set(', '$/' => ')$/'));
		$getterPattern = '/^get([A-Z]+[a-zA-Z0-9]*)$/';
		$setterPattern = '/^set([A-Z]+[a-zA-Z0-9]*)$/';
		$params = func_get_args();
		array_shift($params);
		switch (true) {
			case preg_match($getterPattern, $method, $matches):
				return $this->getValue(lcfirst($matches[1]));
			case preg_match($setterPattern, $method, $matches):
				return $this->setValue(lcfirst($matches[1]), reset($arguments));
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
	 * define a public rawGet() in your model simply calling this _getRaw() if you want to open. Not recommended, though
	 * @param string $field
	 * @param boolean $storedValue if true I return stored value otherwise actual value
	 * @return mixed
	 */
	protected function _getRaw($field, $storedValue=false) {
		return $storedValue ? $this->_storedValues[$field] : $this->_values[$field];
	}
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
			foreach ($idFieldName as $eachIdFieldName) {
				$idFields[] = $this->getValue($eachIdFieldName);
			}
			$id = implode(static::$_idFieldGlue, $idFields);
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
			foreach ($idFieldName as $eachIdFieldName) {
				$idFields[] = array_key_exists($eachIdFieldName, $data) ? $data[$idFieldName] : null;
			}
			$id = implode(static::$_idFieldGlue, $idFields);
		}
		return $id;
	}
	/**
	 * I try to set ID field values. I can only set if there is just one ID field,
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
			// @todo this should be examined based on the type(s) of id field(s)
			elseif (!is_string($id) && !is_integer($id)) {
				throw new \BadMethodCallException('id ' . print_r($id,1) . ' invalid');
			}

			$idFieldName = static::idFieldName();
			if (is_string($idFieldName)) {
				$this->setValue($idFieldName, $this->field($idFieldName)->setValue($id));
			}
			// @todo test this
			elseif (is_array($idFieldName)) {
				if ((substr_count($id, static::$_idFieldGlue)+1) !== count($idFieldName)) {
					throw new \BadMethodCallException('id ' . $id . ' invalid');
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

		if (is_array($field)) {
			$ret = array();
			foreach ($field as $eachField) try {
				$ret[$eachField] = $this->getValue($eachField, $storedValue);
			}
			catch (\Exception $e) {};
		}
		elseif (is_string($field)) {
			if (!array_key_exists($field, static::$_fields)) {
				throw new MagicGetException($field, get_class($this));
			}
			$ret = null;
			if (array_key_exists($field, $this->_values)) {
				// I call the field get filter/processor
				$ret = $this->field($field)->getValue($storedValue ? $this->_storedValues[$field] : $this->_values[$field]);
			}
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
	public function setValue($fieldOrData, $valueOrReplace=null, $throw=null) {
		if (is_array($fieldOrData)) {
			return $this->_setValues($fieldOrData, $valueOrReplace, $throw);
		}
		else {
			return $this->_setValue($fieldOrData, $valueOrReplace);
		}
	}
	/**
	 * set one value, if it is writeable (otherwise, you have to take care of setting that parameter)
	 * @todo implement is_writable
	 * @param unknown $field
	 * @param unknown $value
	 * @throws MagicSetException
	 * @return \Model
	 */
	protected function _setValue($field, $value) {
		if (!array_key_exists($field, static::$_fields)) {
			throw new \MagicSetException($field, get_class($this));
		}

		// I call field object's set validator/processor. Normally it returns $value unintact, otherwise modifies it
		$Field = $this->field($field);
		$Field->setValue($value);
		$this->_values[$field] = $value;
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
		foreach ($values as $eachFieldName=>&$eachValue) try {
			$this->setValue($eachFieldName, $eachValue);
		}
		catch (\Exception $e) {
			if ($throw) {
				throw $e;
			}
		}
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
		return $this->_values === $this->_storedValues ? true : false;
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
			$ret = $fieldname;
			foreach ($ret AS &$eachField) {
				$eachField = $this->isFieldDirty($eachField);
			}
		}
		elseif (is_string($fieldname)) {
			$ret = array_key_exists($fieldname, $this->_values) &&
					array_key_exists($fieldname, $this->_storedValues) &&
					($this->_values[$fieldname] == $this->_storedValues[$fieldname])
				? true : false;
		}
		else {
			throw new \InvalidArgumentException('field name invalid');
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
	 * I return the associated store object. I get instance from Camarera if necessary
	 * @param int $storeId
	 * @throws \InvalidArgumentException
	 * @return \Store
	 */
	public static function getStore($storeId) {
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
				throw new \InvalidArgumentException('Model::getStore(): no such store: ' . print_r($storeId,1));
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
	public static function setStore($storeId, $storeOrStoreName) {
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
				throw new \InvalidArgumentException('Model::getStore(): no such store: ' . print_r($storeId,1));
		}
		return;
	}


	//////////////////////////////////////////////////////////////////////////
	// ACTIVERECORD
	//////////////////////////////////////////////////////////////////////////

	/**
	 * I load a model
	 * @param \ModelLoadConfig $LoadConfig
	 * @return bool
	 * @throws UnImplementedException
	 */
	public function load(\ModelLoadConfig $LoadConfig=null) {
		if (is_null($LoadConfig)) {
			$LoadConfig = \ModelLoadConfig::get();
		}
		$data = $this->getStore(static::STORE_READ)->loadModel($this, $LoadConfig);
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
			return true;
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
			$ret = $this->getStore(static::STORE_WRITE)->updateModel($this, $SaveConfig);
		}
		// otherwise, insert
		else {
			$ret = $this->getStore(static::STORE_WRITE)->createModel($this, $SaveConfig);
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
		$ret = $this->getStore(static::STORE_WRITE)->deleteModel($this, $DeleteConfig);
		return $ret ? true : false;
	}

	//////////////////////////////////////////////////////////////////////////
	// VALIDATION
	//////////////////////////////////////////////////////////////////////////

	protected $_isValid = true;

	protected $_validationErrors = array();

	public function validate() {
		$this->_isValid = true;
		$this->_validationErrors = array();
		$validationErrors = array();
		foreach (static::$_fields as $eachFieldName=>$EachField) {
			try {
				$value = array_key_exists($eachFieldName, $this->_values)
					? $this->_values[$eachFieldName]
					: null;
				$errors = $EachField->validate($value, $this);
				if (!empty($errors)) {
					$validationErrors[$eachFieldName] = array_merge_recursive(
							$this->_validationErrors,
							$errors
					);
				}
			}
			catch (\FieldValidationException $e) {
				$validationErrors[$eachFieldName][] = $e->getMessage();
			}
		}
		if (count($validationErrors)) {
			$this->_isValid = false;
			$this->_validationErrors = $validationErrors;
			#echop($validationErrors); die('HUBU');
		}
		return $this;
	}

}

class FieldValidationException {}
class ModelValidationException {}
