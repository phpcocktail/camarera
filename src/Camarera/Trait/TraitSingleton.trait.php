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
 * Class TraitSingleton - classic singleton pattern
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera\Trait
 * @version 1.1
 */
trait TraitSingleton {

	/**
	 * @var self singleton instance, get with static::instance() note I have to be defined in all subclasses as well to
	 *        get class-based instances
	 */
	protected static $_Instance;

	/**
	 * I return the singleton per class instance
	 *
	 * @return self
	 */
	final public static function instance() {
		if (is_null(static::$_Instance)) {
			static::$_Instance = static::_instance();
		}
		return static::$_Instance;
	}

	/**
	 * I shall be implemented to create an object based on current global environment (eg. a HTTP request)
	 *
	 * @return mixed
	 */
	abstract protected static function _instance();

}
