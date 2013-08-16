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
class FieldPassword extends \FieldString {

	const METHOD_MD5 = 'md5';
	const METHOD_SHA1 = 'sha1';
	const METHOD_CUSTOM = '';

	/**
	 * @var string current method for encryption, as per consts
	 */
	protected static $_method;

	/**
	 * I return current encrypt method, fetch from config if not set explicitly. Note that encrypt method setting is
	 * 		static so if you need diverse encryptions you have to handle it on app level and use setRaw()
	 * @return mixed|string
	 */
	public static function getMethod() {
		if (is_null(static::$_method)) {
			static::$_method = \Camarera::conf('Field.password.method');
		}
		return static::$_method;
	}

	/**
	 * I encrypt value
	 * @param mixed $value
	 * @return string
	 */
	public static function setValue(&$value) {
		switch(static::getMethod()) {
			case static::METHOD_MD5:
				$value = md5((string)$value);
				break;
			case static::METHOD_SHA1:
				$value = sha1((string)$value);
				break;
		}
		return $value;
	}

}
