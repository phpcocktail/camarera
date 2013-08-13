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
 * Class TraitSingletonGlobal this trait uses a global singleton instance, from the root class down to its children. Eg.
 *        for a Request class all Request, RequestHttp, etc share the same Request singleton. Additional objects shall
 *        be retrieved by other means (eg. serve())
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera\Trait
 * @since 1.1
 * @version 1.1
 */
trait TraitSingletonGlobal {

	/**
	 * @var self singleton instance, get with static::instance()
	 */
	protected static $_Instance;

	/**
	 * I return the same global instance for all subclasses
	 *
	 * @return self
	 */
	final public static function instance() {
		if (is_null(self::$_Instance)) {
			self::$_Instance = static::_instance();
		}
		return self::$_Instance;
	}

	/**
	 * I shall be implemented to create an object based on current global environment (eg. a HTTP request)
	 *
	 * @return mixed
	 */
	abstract protected static function _instance();

}
