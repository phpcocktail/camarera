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
 * enum field for models, will store zero or one element of a set of possible values
 *
 * @author t
 * @package Camarera\Field
 * @license DWTFYWT
 * @version 1.1
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
	 * I override parent construct and check for validValues config
	 * @throws \InvalidArgumentException
	 * @return \FieldEnum
	 */
	public static function build(array $config, $fieldName=null) {
		$Field = parent::build($config, $fieldName);
		if (empty($Field->validValues)) {
			throw new \InvalidArgumentException();
		}
		return $Field;
	}

	/**
	 * I check if value is valid
	 * @todo fix scope bug here
	 * @param mixed $value
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function setValue($value) {
		if (!in_array($value, $this->validValues)) {
			$value = null;
		}
		return $value;
	}

	public function addValue() {
		throw new \BadMethodCallException('cannot add to enum field');
	}
}
