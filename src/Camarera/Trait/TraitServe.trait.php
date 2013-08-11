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
 * @since 1.1
 * @license DWTFYWT
 * @version 1.1
 */
namespace Camarera;

/**
 * Class TraitServe it is a standard serve() function to avoid confusion. There shall be another one, for models/collections
 * 		serve() by default accepts only null to get an empty object OR ConfigXxx object to set it in $_Config (given
 * 		that property exists!!!)
 *
 * @package Camarera
 */
trait TraitServe {

	/**
	 * I create and return an instance
	 * @todo maybe array param should be accepted?
	 * @param null|Config
	 * @return static
	 */
	public static function serve($data=null) {
		if (is_null($data)) {
			$ret = new static;
		}
		elseif (is_object($data) && ($data instanceof \Config) && property_exists(get_called_class(), '_Config')) {
			$ret = new static;
			$ret->_Config = $data;
		}
		else{
			throw new \InvalidArgumentException();
		}
		return $ret;
	}

	/**
	 * I am protected, use serve()
	 */
	protected function __construct() {}

}
