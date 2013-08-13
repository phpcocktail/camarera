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
 * I am a filter for a query, for internal usage. Indeed, I am just a representational object
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera\Store
 * @version 1.1
 */
class StoreFilter {

	/**
	 * @var string[] list of available operators
	 */
	protected static $_availableOperators = array(
			'AND','OR',
			'>','>=','<','<=','=','!=','BETWEEN','NOT BETWEEN',
			'IN','NOT IN',
			'LIKE','NOT LIKE','REGEXP'
	);

	/**
	 * @var string eg. 'AND'
	 */
	protected $_operator;
	/**
	 * @var string fieldname, or null
	 */
	protected $_field;
	/**
	 * @var array data for comparision, if one
	 */
	protected $_data=array();

	/**
	 * I return a filter instance based on params. Note there is not much validation on submitted data, you may want to
	 * 		use get{Operatorname}() functions instead
	 * @param string $operator
	 * @param array $data
	 * @throws \InvalidArgumentException
	 * @return \StoreFilter
	 */
	public static function build($operator, $field, $data) {

		if (!in_array($operator, static::$_availableOperators)) {
			throw new \InvalidArgumentException();
		}

		$StoreFilter = new static;
		$StoreFilter->_operator = $operator;
		$StoreFilter->_field = $field;
		$StoreFilter->_data = $data;

		return $StoreFilter;

	}

	/**
	 * I check if $data is an array of StoreFilter objects
	 * @param StoreFilter[] $data
	 * @throws \InvalidArgumentException
	 */
	protected static function _checkIsFilterArray($data) {
		if (!is_array($data)) {
			throw new \InvalidArgumentException();
		}
		foreach ($data AS $filter) {
			if (!is_object($filter) || !is_a($filter, 'Camarera\StoreFilter')) {
				throw new \InvalidArgumentException();
			}
		};
	}

	/**
	 * I combine filters by 'AND'
	 * @param \StoreFilter[] $data filters to be combined
	 * @return \StoreFilter
	 */
	public static function buildAnd($data) {
		static::_checkIsFilterArray($data);
		return static::build('AND', null, $data);
	}
	/**
	 * I combine filters by 'OR'
	 * @param \StoreFilter[] $data filters to be combined
	 * @return \StoreFilter
	 */
	public static function buildOr($data) {
		static::_checkIsFilterArray($data);
		return static::build('OR', null, $data);
	}
	public static function buildGt($field, $value) {
		return static::build('>', $field, $value);
	}
	public static function buildGte($field, $value) {
		return static::build('>=', $field, $value);
	}
	public static function buildLt($field, $value) {
		return static::build('<', $field, $value);
	}
	public static function buildLte($field, $value) {
		return static::build('<=', $field, $value);
	}
	public static function buildEquals($field, $value) {
		return static::build('=', $field, $value);
	}
	public static function buildNot($field, $value) {
		return static::build('!=', $field, $value);
	}
	public static function buildBetween($field, $min, $max) {
		return static::build('BETWEEN', $field, array($min, $max));
	}
	public static function buildNotBetween($field, $min, $max) {
		return static::build('NOT BETWEEN', $field, array($min, $max));
	}
	public static function buildIn($field, $values) {
		return static::build('IN', $field, $values);
	}
	public static function buildNotIn($field, $values) {
		return static::build('NOT IN', $field, $values);
	}
	public static function buildLike($field, $value) {
		return static::build('LIKE', $field, $value);
	}
	public static function buildNotLike($field, $value) {
		return static::build('NOT LIKE', $field, $value);
	}
	public static function buildRegexp($field, $value) {
		return static::build('REGEXP', $field, $value);
	}

	protected function __construct() {}

	public function getOperator() {
		return $this->_operator;
	}
	public function getField() {
		return $this->_field;
	}
	public function getData() {
		return $this->_data;
	}

}
