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
 * timestamp field for models, will store datims in unix timestamp format
 *
 * @author t
 * @package Camarera\Field
 * @license DWTFYWT
 * @version 1.1
 */
class FieldTimestamp extends \Field {

	/**
	 * I cast to timestamp
	 * @param int|string $value
	 * @return int
	 * @throws \InvalidArgumentException
	 */
	public function setValue($value) {
		$originalValue = $value;
		$value = strtotime($value);
		if ($value === false) {
			$value = null;
		}
		return $value;
	}

	public function addValue($value, $addValue) {
		$addValue = strtotime($addValue, 0);
		if ($addValue === false);
		else {
			$value = $value + $addValue;
		}
		return $value;
	}

}
