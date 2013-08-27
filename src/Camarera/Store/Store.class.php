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
 * I define the base abstracts for StoreXxx drivers. Apart from very few implementation-dependent only things defined
 *	here should be accessed from the outside. CRUD methods for Models and Collections shall be called and wrapped by
 *	themselves only. Also, you can execute your own sql queries by execute() and query(). Note that Camarera currently
 *	does not include a query builder.
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera\Store
 * @version 1.1
 *
 * @property-read StoreConfig $Config
 * @property-read string $id
 * @property-read string $lastQuery
 * @property-read array $lastErrors
 * @property-read int $lastAffectedRwos
 */
abstract class Store {

	use
		\Camarera\TraitServeWithConfig,
		\Camarera\TraitMagicGetProtected,
		\Camarera\TraitPropertyExistsCached
	;

	/**
	 * @var mixed this has to contain the last query which was executed
	 */
	protected $_lastQuery = null;
	/**
	 * @var array if there were errors, they should be set in this array
	 */
	protected $_lastError = null;
	/**
	 * @var int the number of rows (or other element types) which have been effected by last query
	 */
	protected $_lastAffectedRows;

	/**
	 * @param \StoreConfig $Config
	 * @return static
	 */
	public static function serve(\StoreConfig $Config=null) {
		return static::_serve($Config);
	}

	/**
	 * I construct object then erase sensitive data from $Config. Thus, child classes must run code which need these
	 *	datas before calling this parent construct.
	 * @param type $Config
	 */
	protected function __construct($Config) {
		// erasing password and other sensitive data
		$this->_Config = $Config;
		if (!empty($this->_Config->password)) {
			$this->_Config->password = '***';
		}
		if (!empty($this->_Config->path)) {
			$this->_Config->path = '***';
		}
		if (isset($Config->tablePrefix)) {
			$this->_tablePrefix = $Config->tablePrefix;
		}
	}

	/**
	 * Some magic properties, as documented
	 * @param string $fieldName
	 * @return mixed
	 * @throws MagicGetException
	 */
	function __get($fieldName) {
		switch ($fieldName) {
			case 'id':
				return $this->_Config->id;
			default:
				return static::___get($fieldName);
		}
	}

	/**
	 * I execute a query (which currently can be a query string only, I exist to execute SQL queries indeed), without result
	 * @param string $query
	 * @return
	 */
	abstract public function execute($query);
	/**
	 * I execute a query and return result
	 */
	abstract public function query($query, $idField=null);

	/**
	 * I load a model's data based on current values.
	 * @todo I return model's data, but may load other data (eager load) which I will register in the object pool
	 * @param \Model $Object
	 * @param \ModelLoadConfig $LoadConfig
	 */
	abstract function loadModel(\Model $Object, \ModelLoadConfig $LoadConfig);
	/**
	 * I update an existing model. Update/create logic is in Model class.
	 * @param \Model $Object
	 * @param \ModelSaveConfig $SaveConfig
	 */
	abstract function updateModel(\Model $Object, \ModelSaveConfig $SaveConfig);
	/**
	 * I insert a new model record
	 * @param \Model $Object
	 * @param \ModelSaveConfig $SaveConfig
	 * @return mixed[]|false return false on error or throw exception, or return new insert id(s)
	 */
	abstract function createModel(\Model $Object, \ModelSaveConfig $SaveConfig);
	/**
	 * I delete a Model, based on ID. To delete multiple Models, use deleteCollection
	 * @param \Model $Object
	 * @param \ModelDeleteConfig $DeleteConfig
	 * @throws \BadMethodCallException
	 * @return bool true=success
	 */
	abstract function deleteModel(\Model $Object, \ModelDeleteConfig $DeleteConfig);

}
