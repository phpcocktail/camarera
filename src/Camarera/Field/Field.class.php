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
 * basic field for models, typed fields should have a definition class which extends this
 * @author t
 * @package Camarera\Field
 * @version 1.0
 *
 * @property-read string $storeQuote see: $_storeQuote
 */
class Field extends \Config {

	/**
	 * @var string name of the field in the model
	 */
	protected $_fieldName = '';

	/**
	 * @var string I will use this for quoting field values in store (sql) queries. Leave empty for numeric values, set to " otherwise
	 * 		note: should be protected or private static but FieldXxx does not have inflating mechanism which could check
	 * 		if that field is defined. So it goes dynamic for now
	 */
	protected $_storeQuote = '';

	/**
	 * @var mixed when an empty object is created, this value will be used (but not when loading partial datasets)
	 */
	public $default = null;

	/**
	 * @var boolean this controls if field is readable by magic getters (may have further effects, eg. when listing
	 *	object properties in applications)
	 */
	public $readable = true;
	/**
	 * @var boolean this controls if field is writable by magis setters (may have further effects, eg. when editing
	 *	an object in a form, in applications)
	 */
	public $writable = true;

	/**
	 * @var boolean if true, must have a value set
	 */
	public $mandatory=false;

	/**
	 * @var numeric minimum value accepted
	 */
	public $minVal = null;
	/**
	 * @var numeric maximum value accepted
	 */
	public $maxVal = null;
	/**
	 * @var numeric minimum length (strings) accepted
	 */
	public $minLength = null;
	/**
	 * @var numeric maximum length (strings) accepted
	 */
	public $maxLength = null;

	/**
	 * @var string[] I am mandatory if any element of the array is present in the request
	 * 		an array item can be an array too, which means all the fields in the request must be present
	 */
	public $mandatoryWith = null;

	/**
	 * @var string[] like $mandatoryWith, but I am required if those params are present and evaluate to true,1,on
	 */
	public $mandatoryOn = null;

	/**
	 * @var boolean value must be unique in the store (not well implemented yet)
	 * @todo check and fix its implementation
	 */
	public $unique = false;

	/** @var string this is just to let this param be in the getter config */
	protected $classname = null;
	/**
	 * @var string this is used in config only too, if set, overwrites $classname by FieldType (thus you can simply use
	 *		'type'='int' instead of 'classname'=>'\FieldInt'
	 */
	protected $type = null;

	/**
	 * I get an instance
	 * @param unknown $config
	 * @param string $fieldName
	 * @param string $parentClassname
	 * @return \Field
	 */
	public static function get(array $config, $fieldName=null) {
		$Field = parent::get($config);
		if (!is_null($fieldName)) {
			$Field->_fieldName = $fieldName;
		}
		// no defaulting here, we use defaulting only when a certain parameter is needed but not set
		return $Field;
	}

	/**
	 * the actual internal value will be passed through this when exposing data. Eg. a datim field which stores
	 *	timestamps internally may convert the timestamp to a date here. Or, clear password field value. Usually not needed.
	 * @param mixed $value
	 * @return mixed
	 */
	public static function getValue($value) {
		return $value;
	}

	/**
	 * any value to be set will be run through this. You can implement eg. validation, typecasting, or even password
	 *	hashing here
	 * @param mixed $value
	 * @return mixed
	 */
	public static function setValue(&$value) {
		return $value;
	}

	public function __get($fieldName) {
		switch (true) {
			case $fieldName === 'storeQuote':
				return $this->_storeQuote;
			default:
				return parent::__get($fieldName);
		}
	}

	/**
	 * I validate a value, but do only validations which require the value. If validation depends on other params or
	 * 		model specific things, it must go into validateInModel()
	 * @param mixed $value
	 */
	public function validate($value) {
		$hasValue = isset($value) && !empty($value);

		$errors = array();

		if ($this->mandatory && !$hasValue) {
			$errors['mandatory'] = null;
		}
		if (isset($this->minVal) && ($value<$this->minVal)) {
			$errors['minVal'] = $this->minVal;
		}
		if (isset($this->maxVal) && ($value>$this->maxVal)) {
			$errors['maxVal'] = $this->maxVal;
		}
		if (isset($this->minLength) && (strlen($value)<$this->minLength)) {
			$errors['minLength'] = $this->minLength;
		}
		if (isset($this->maxLength) && (strlen($value)>$this->maxLength)) {
			$errors['maxLength'] = $this->maxLength;
		}
		return $errors;
	}

	public function validateInModel($value, \Model $Model) {
		$hasValue = isset($value) && !empty($value);

		$errors = array();

		if (is_array($this->mandatoryWith)) {
			foreach ($this->mandatoryWith as $eachMandatoryWith) {
				if (is_array($eachMandatoryWith)) {
					die('implement');
				}
				else{
					die('implement');
//					if (!empty($Model->$eachMandatoryWith))
				}
			}
		}

		return $errors;

	}

}
