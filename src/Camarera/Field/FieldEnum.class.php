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
 * enum field for models, will store zero or one element of a set of possible values
 * @author t
 * @package Camarera\Field
 * @version 1.0
 *
 * @method FieldEnum setValidValues(array $validValues) I set valid values in $this->validValues
 */
class FieldEnum extends \Field {

	/**
	 * @see \Field::$_storeQuote
	 */
	protected $_storeQuote = '"';

	/**
	 *
	 * @var mixed[] a set of valid values
	 */
	public $validValues = array();

	/**
	 * I check if value is valid
	 * @param mixed $value
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public static function setValue(&$value) {
		if (!in_array($value, $this->validValues)) {
			throw new \InvalidArgumentException();
		}
		return $value;
	}

	/**
	 * I override parent construct and check for validValues config
	 * @throws \InvalidArgumentException
	 * @return \FieldEnum
	 */
	public static function get(array $config, $fieldName=null, $parentClassname=null) {
		$Field = parent::get($config, $fieldName=null, $parentClassname=null);
		if (empty($Field->validValues)) {
			throw new \InvalidArgumentException();
		}
		return $Field;
	}
}
