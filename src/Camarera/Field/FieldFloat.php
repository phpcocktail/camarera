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
 * float field for models
 * @author t
 * @package Camarera\Field
 * @version 1.01
 */
class FieldFloat extends \Field {

	/**
	 * I cast to double
	 * @param mixed $value
	 * @return double
	 */
	public static function setValue(&$value) {
		$value = (double) $value;
		return $value;
	}

}
