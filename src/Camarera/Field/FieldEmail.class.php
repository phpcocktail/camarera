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
 * timestamp field for models, will store datims in unix timestamp format
 * @author t
 * @package Camarera\Field
 * @version 1.01
 */
class FieldEmail extends \FieldString {

	/**
	 * I check if email address is valid
	 * @param mixed $value
	 * @param \Model $model
	 * @return array
	 */
	public function validate($value, $model) {
		$errors = parent::validate($value, $model);
		if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
			$errors[$this->_fieldName][] = 'email';
		}
		return $errors;
	}

}
