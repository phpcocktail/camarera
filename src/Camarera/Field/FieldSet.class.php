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
 * set field for models, will store zero, one, or more elements of a set of possible values
 *
 * @author t
 * @package Camarera\Field
 * @license DWTFYWT
 * @version 1.1
 */
class FieldSet extends \FieldEnum {

	/**
	 * I set one or more values, with checking validity
	 * @param array|string $value array of values, or a single value in string or numeric
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	public function setValue($value) {
		$originalValue = $value;
		if (is_array($value));
		elseif (is_string($value)||is_numeric($value)) {
			$value = array($value);
		}
		else {
			$value = null;
		}

		$arrayValue = $value;
		$value = array_intersect($value, $this->validValues);
		if (array_diff($arrayValue, $value)) {
			$value = null;
		}
		return $value;
	}

	public function addValue($value, $addValue) {
		if (is_null($value)) {
			$value = array();
		}
		elseif (!is_array($value)) {
			throw new \BadMethodCallException();
		}
		if (is_array($addValue)) {
			if (!empty(array_diff($this->validValues, $addValue))) {
				throw new \InvalidArgumentException();
			}
			$value = array_merge($value, $addValue);
		}
		else {
			if (!in_array($addValue, $this->validValues)) {
				throw new \InvalidArgumentException();
			}
			$value[] = $addValue;
		}
		return $value;
	}

}
