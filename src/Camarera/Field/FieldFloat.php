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
 * float field for models
 *
 * @author t
 * @package Camarera\Field
 * @license DWTFYWT
 * @version 1.1
 */
class FieldFloat extends \Field {

	/**
	 * I cast to double
	 * @param mixed $value
	 * @return double
	 */
	public function setValue($value) {
		$value = (double) $value;
		return $value;
	}

}
