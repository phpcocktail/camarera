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
 * special enum field for booleain (on/off) fields
 * @author t
 * @package Camarera\Field
 * @version 1.0
 *
 */
class FieldBool extends \Field {

	/**
	 * @see \Field::$_storeQuote
	 */
//	protected $_storeQuote = '"';

	/**
	 * I check if value is valid
	 * @param mixed $value
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public static function setValue(&$value) {
		$value = $value ? true : false;
		return $value;
	}

}
