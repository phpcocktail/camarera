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
 * StoreSql class contains logic and template methods for SQL-based drivers such as StoreMysql for MySql. The query
 * 	generated here shall be good enough for all drivers. You can still override those methods for your drivers, if you
 *	would need. Basicly you'll have to define few abstract methods which depend on driver level calls.
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera\Store
 * @version 1.1
 */
abstract class StoreSql extends \Store {

	/**
	 * I escape the value properly, then add quotes around it. Not static since escaping depends on actual connection.
	 * @TODO make it more intelligent, eg. handle automatic serialization of non-scalars???
	 * @param mixed $value
	 * @param type $addQuotes
	 * @return string
	 */
	abstract public function escape($value, $addQuotes='"');

	/**
	 * I join an array's values escaped
	 * @param array $values
	 * @return string
	 */
	public function escapedImplode($values) {
		$ret = array();
		foreach($values AS $value) {
			$ret[] = static::escape($value);
		}
		return implode(',', $ret);
	}

	/**
	 * override this and put querying and error handling in it. Normally return false on error, or a resource or array
	 * 		which will be looped using the _resultNextRow() method
	 * @param string $query
	 * @return false|mixed
	 */
	abstract protected function _query($query);
	/**
	 * provide a method which returns the next available row, or false if there are no more
	 * @param mixed $result as returned by _query
	 * @return array column=>value pairs in array, not column shall contain table name too
	*/
	abstract protected function _resultNextRow($result);

	/**
	 * public query method, accepts raw queries. This may cause problems with diverse SQL drivers, so try not to use.
	 * @see \Store::query()
	 */
	public function query($query, $idFieldName=null) {

		$result = $this->_query($query);

		if ($result === false) {
			$ret = false;
		}
		else {
			$ret = array();
			while($row = $this->_resultNextRow($result)) {
				if (is_null($idFieldName)) {
					$ret[] = $row;
				}
				elseif (is_string($idFieldName)) {
					$id = $row[$idFieldName];
					$ret[$id] = $row;
				}
			}
		}
		return $ret;
	}

	/**
	 * override this if you have to get insert_id in advance (eg. selecting next sequence number, for pgsql)
	 * @return mixed[]|null return null if not used, otherwise return an array of id=>value pairs
	 */
	protected function _prepareInsert() {
		return null;
	}
	/**
	 * override this to get last insert ID
	 */
	abstract protected function _getInsertId();


	//////////////////////////////////////////////////////////////////////////
	// MODEL
	//////////////////////////////////////////////////////////////////////////

	/**
	 * @see \Store::loadModel()
	 */
	public function loadModel(\Model $Model, \ModelLoadConfig $LoadConfig) {
		$modelClassname = get_class($Model);
		$query = 'SELECT ' . $this->_loadModelGetFields($modelClassname, $LoadConfig) .
				' FROM ' . $this->_loadModelGetTables($modelClassname, $LoadConfig) .
				' WHERE ' . $this->_loadModelGetWhere($Model, $LoadConfig) .
				$this->_loadModelGetOrder($Model, $LoadConfig) .
				$this->_loadModelGetLimitOffset($Model, $LoadConfig);
		$data = $this->query($query);
		if (is_null($data)) {
			throw new \UnImplementedException();
		}
		elseif (is_array($data)) {
			$data = array_pop($data);
			$ret = array();
			if (!empty($data)) {
				foreach ($data AS $eachFieldName=>$eachValue) {
					$pos = strpos($eachFieldName, '.');
					$tablename = substr($eachFieldName, 0, $pos);
					// include data in ret unly if it was selected for this object
					// @todo handle additional data resulting of eager loading here
					if ($tablename === $Model::getStoreTable()) {
						$fieldName = substr($eachFieldName, $pos+1);
						$ret[$fieldName] = $eachValue;
					}
				}
			}
		}
		else {
			$ret = false;
		}
		return $ret;
	}

	protected function _loadModelGetFields($modelClassname, \Camarera\StoreMethodConfig $LoadConfig) {
		$ModelFields = array_keys($modelClassname::field());
		$configFields = $LoadConfig->loadFields;
		if (($configFields === '*') || is_null($configFields)) {
			$loadFields = $ModelFields;
		}
		else {
			$loadFields = array_intersect($ModelFields, $configFields);
		}
		foreach ($loadFields as &$loadField) {
			$loadField =
					$this->_Config->tablePrefix . $modelClassname::getStoreTable() . '.' . $loadField .
					' AS ' .
					'`' . $modelClassname::getStoreTable() . '.' . $loadField . '`';
		}
		// @todo map to sql fieldnames as set in model fields' config sql alias
		return implode(',', $loadFields);
	}
	protected function _loadModelGetTables($modelClassname, \Camarera\StoreMethodConfig $LoadConfig) {
		$table = $this->_Config->tablePrefix . $modelClassname::getStoreTable();
		return $table;
	}
	protected function _loadModelGetWhere(\Model $Model, \Camarera\StoreMethodConfig $LoadConfig) {
		$wheres = $this->_loadModelGetWhereData($Model, $LoadConfig);
		foreach ($wheres as $eachFieldName=>&$eachWhere) {
			$eachWhere = $eachFieldName . ' = ' . $eachWhere;
		}
		$concat = ' AND ';
		$ret = implode($concat, $wheres);
		return $ret;
	}
	protected function _loadModelGetWhereData(\Model $Model, \Camarera\StoreMethodConfig $LoadConfig, $withTableNames=true) {
		$whereData = array();
		$modelValues = $Model->getValue(null);
		foreach ($modelValues as $eachFieldName=>$eachFieldValue) {
			$quotes = $Model::field($eachFieldName)->storeQuote;
			$whereData[($withTableNames ? $this->_Config->tablePrefix . $Model::getStoreTable() . '.' : '') . $eachFieldName] = static::escape($eachFieldValue, $quotes);
		}
		return $whereData;
	}
	protected function _loadModelGetOrder($modelClassname, \Camarera\StoreMethodConfig $LoadConfig) {
		// note $modelClassname is provided for uniformity only, and for later features (eg. order by pk in $Config)
		$ret = '';
		if (empty($LoadConfig->loadOrder));
		elseif (is_string($LoadConfig->loadOrder)) {
			$ret =  $LoadConfig->loadOrder;
		}
		elseif (is_array($LoadConfig->loadOrder)) {
			$ret = array();
			foreach ($LoadConfig->loadOrder as $eachKey=>$eachValue) {
				$ret[] = $eachKey . ' ' . $eachValue;
			}
			$ret = implode(', ', $ret);
		}
		$ret = (stripos(trim($LoadConfig->loadOrder), 'ORDER') === 0 ? '' : ' ORDER BY ') . $ret;
		return '';
	}
	protected function _loadModelGetLimitOffset($modelClassname, \Camarera\StoreMethodConfig $LoadConfig) {
		return ' LIMIT 1 ';
	}

	/**
	 * @see \Store::saveModel()
	 */
	public function updateModel(\Model $Model, \ModelSaveConfig $SaveConfig) {

		if (is_array($Model::idFieldName())) {
			throw new \UnImplementedException();
		}

		$id = $Model->ID;

		$data = $this->_updateModelGetData($Model, $SaveConfig, empty($id));
		$sets = array();
		foreach ($data as $eachField=>$eachValue) {
			$quotes = $Model::field($eachField)->storeQuote;
			$sets[] = ' ' . $eachField . '=' . $this->escape($eachValue, $quotes);
		}

		$wheres = array();
		$idFieldName = (array) $Model::idFieldName();
		foreach ($idFieldName as $eachIdFieldName) {
			$quotes = $Model::field($eachIdFieldName)->storeQuote;
			$wheres[] = $eachIdFieldName . '=' . $this->escape($Model->getValue($eachIdFieldName), $quotes);
		}

		$query = 'UPDATE ' . $this->_Config->tablePrefix . $Model->getStoreTable() .
					' SET ' . implode(',', $sets) .
					' WHERE ' . implode(' AND ', $wheres);

		$ret = $this->execute($query);

		if ($ret === false);
		else {
			$Model->setStoredValues();
		}

		return $ret;

	}

	protected function _updateModelGetData(\Model $Model, \ModelSaveConfig $SaveConfig, $isInsert=true) {
		// @todo implement $SaveConfig save field filter here
		$saveFields = $Model->getValue(null);
		$idFieldName = (array) $Model::idFieldName();
		foreach ($idFieldName AS $eachIdFieldName) {
			unset($saveFields[$eachIdFieldName]);
		}
		return $saveFields;
	}

	/**
	 * @see \Store::createModel()
	 * @return mixed[]|false
	 */
	public function createModel(\Model $Model, \ModelSaveConfig $SaveConfig) {

		$whereData = $this->_loadModelGetWhereData($Model, $SaveConfig, false);

		// if you have to get insert id before actual insert, override _prepareInsert() and make it return the new id(s).
		$insertData = $this->_prepareInsert();
		if (!is_null($insertData)) {
			$whereData = array_merge($whereData, $insertData);
		}

		$query = 'INSERT ' .
					' INTO ' . $this->_Config->tablePrefix . $Model->getStoreTable() .
					' (' . implode(',', array_keys($whereData)) . ') VALUES (' . implode(',', $whereData) . ')';

		$result = $this->execute($query);
		if ($result === false) {
			return $result;
		}

		if (is_null($insertData)) {
			$idFieldName = $Model::idFieldName();
			if (!is_string($idFieldName)) {
				throw new \RuntimeException('$fieldName should be string, for multiple ID fields you should override ' . get_class($this) . '::_prepareInsert()');
			}
			$insertId = $this->_getInsertId();
			$insertData = array (
					$idFieldName => $Model::field($idFieldName)->setValue($insertId),
			);
		}

		$Model->setID($insertData);

		$Model->setStoredValues();

		return $insertData;

	}

	/**
	 * @see \Store::deleteModel()
	 */
	public function deleteModel(\Model $Model, \ModelDeleteConfig $DeleteConfig) {
		$query = 'DELETE ' .
				' FROM ' . $this->_Config->tablePrefix . $Model->getStoreTable() .
				' WHERE ' . $this->_loadModelGetWHere($Model, $DeleteConfig);
	}


	//////////////////////////////////////////////////////////////////////////
	// COLLECTION
	//////////////////////////////////////////////////////////////////////////

	public function loadCollection(\Collection $Collection, \CollectionGetConfig $LoadConfig) {
		$modelClassname = $Collection::getModelClassname();
		$query = 'SELECT ' . $this->_loadModelGetFields($modelClassname, $LoadConfig) .
				' FROM ' . $this->_loadModelGetTables($modelClassname, $LoadConfig) .
				' WHERE ' . $this->_loadCollectionGetWhere($Collection, $LoadConfig) .
				$this->_loadModelGetOrder($modelClassname, $LoadConfig) .
				$this->_loadCollectionGetLimitOffset($Collection, $LoadConfig);
		$datas = $this->query($query);
		if (is_null($datas)) {
			throw new \UnImplementedException();
		}
		elseif (is_array($datas)) {
			$ret = array();
			foreach ($datas AS $dataKey=>$data) {
				if (!empty($data)) {
					foreach ($data AS $eachFieldName=>$eachValue) {
						$pos = strpos($eachFieldName, '.');
						$tablename = substr($eachFieldName, 0, $pos);
						// include data in ret unly if it was selected for this object
						// @todo handle additional data resulting of eager loading here
						if ($tablename === $modelClassname::getStoreTable()) {
							$fieldName = substr($eachFieldName, $pos+1);
							$ret[$dataKey][$fieldName] = $eachValue;
						}
					}
				}
				else die('FU');
			}
		}
		return $ret;
	}

	protected function _compileFilters(StoreFilter $filter, $tableName=null) {
		$tablePrefix = is_null($tableName) ? '' : $tableName . '.';
		$operator = $filter->getOperator();
		$field = $filter->getField();
		$data = $filter->getData();
		switch($operator) {
			case 'AND':
			case 'OR':
				$compiledData = $data;
				foreach ($compiledData AS &$eachData) {
					$eachData = $this->_compileFilters($eachData, $tableName);
				}
				$ret = ' (' . implode(') ' . $operator . ' (', $compiledData) . ') ';
				break;
			case '>':
			case '<':
			case '=':
			case '!=':
				$ret = $tablePrefix . $field . ' ' . $operator . $this->escape($data);
				break;
			case 'BETWEEN':
			case 'NOT BETVEEN':
				$ret = $tablePrefix . $field . ' ' . $operator . ' ' . $this->escape($data[0]) . ' AND ' . $this->escape($data[1]);
				break;
			case 'IN':
			case 'NOT IN':
				$ret = $tablePrefix . $field . ' ' . $operator . ' (' . $this->escapedImplode($data) . ') ';
				break;
			default:
				throw new \RuntimeException('invalid operator: ' . $operator);
		}
		return $ret;
	}

	protected function _loadCollectionGetWhere(Collection $Collection, CollectionLoadConfig $LoadConfig) {
		if (empty($LoadConfig->filter)) {
			$ret = ' 1 ';
		}
		else {
			$modelClassname = $Collection::getModelClassname();
			$ret = $this->_compileFilters($LoadConfig->filter, $this->_Config->tablePrefix . $modelClassname::getStoreTable());
		}
		return $ret;
	}
	protected function _loadCollectionGetOrder(Collection $Collection, CollectionLoadConfig $LoadConfig) {
		if (!empty($LoadConfig->loadOrder)) {
			$order = is_array($LoadConfig->loadOrder) ? implode(',', $LoadConfig->loadOrder) : $LoadConfig->loadOrder;
			$ret = ' ORDER BY ' . $order;
		}
		else {
			$ret = '';
		}
		return $ret;
	}
	protected function _loadCollectionGetLimitOffset(Collection $Collection, CollectionLoadConfig $LoadConfig) {
		$limit = isset($LoadConfig->loadLimit) ? $LoadConfig->loadLimit : null;
		$offset = isset($LoadConfig->loadOffset) ? $LoadConfig->loadOffset : null;
		$ret = '';
		if ($limit) {
			$ret.= ' LIMIT ' . $limit;
		};
		if ($offset) {
			$ret.= ' OFFSET ' . $offset;
		}
		return $ret;
	}


}
