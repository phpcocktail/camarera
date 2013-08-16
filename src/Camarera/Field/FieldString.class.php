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
 * string field for models
 *
 * @author t
 * @package Camarera\Field
 * @license DWTFYWT
 * @version 1.1
 */
class FieldString extends \Field {

	protected $_storeQuote = '"';

	/**
	 * I cast value to string
	 * @param mixed $value
	 * @return string
	 */
	public static function setValue(&$value) {
		$value = \Util::toString($value);
		return $value;
	}

}
