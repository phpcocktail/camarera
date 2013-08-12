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
 * I am a filter for a query, for internal usage
 * @author t
 * @package Camarera\Store
 * @version 1.01
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

	protected $_operator;
	protected $_field;
	protected $_data=array();

	/**
	 * I return a filter instance based on params. Note there is not much validation on submitted data, you may want to
	 * 		use getOperatorname() functions instead
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

	public static function getAnd($data) {
		static::_checkIsFilterArray($data);
		return static::build('AND', null, $data);
	}
	public static function getOr($data) {
		static::_checkIsFilterArray($data);
		return static::build('OR', null, $data);
	}
	public static function getGt($field, $value) {
		return static::build('>', $field, $value);
	}
	public static function getGte($field, $value) {
		return static::build('>=', $field, $value);
	}
	public static function getLt($field, $value) {
		return static::build('<', $field, $value);
	}
	public static function getLte($field, $value) {
		return static::build('<=', $field, $value);
	}
	public static function getEquals($field, $value) {
		return static::build('=', $field, $value);
	}
	public static function getNot($field, $value) {
		return static::build('!=', $field, $value);
	}
	public static function getBetween($field, $min, $max) {
		return static::build('BETWEEN', $field, array($min, $max));
	}
	public static function getNotBetween($field, $min, $max) {
		return static::build('NOT BETWEEN', $field, array($min, $max));
	}
	public static function getIn($field, $values) {
		return static::build('IN', $field, $values);
	}
	public static function getNotIn($field, $values) {
		return static::build('NOT IN', $field, $values);
	}
	public static function getLike($field, $value) {
		return static::build('LIKE', $field, $value);
	}
	public static function getNotLike($field, $value) {
		return static::build('NOT LIKE', $field, $value);
	}
	public static function getRegexp($field, $value) {
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
