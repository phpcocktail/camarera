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
 * Class TraitServe it is a standard serve() function to avoid confusion. There shall be another one, for models/collections
 *        serve() by default accepts only null to get an empty object OR ConfigXxx object to set it in $_Config (given
 *        that property exists!!!)
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera\Trait
 * @since 1.1
 * @version 1.1
 */
trait TraitServeWithConfig {

	/**
	 * @var \Config if you override this in your subclass with proper comment you'll get nice autocompletion
	 */
	protected $_Config;

	/**
	 * I return an instance, indeed I am just a wrapper for _serve.
	 * Override this class with proper type casting on $dataOrConfig, eg. "serve(\StoreConfig $Config=null)"
	 *
	 * @param null|array|Config
	 * @return static
	 * @throws \InvalidArgumentException
	 */
	public static function serve($dataOrConfig = null) {
		return static::_serve($dataOrConfig);
	}

	/**
	 * I return an instance, indeed I am just a wrapper for _serve.
	 * Override this class with proper type casting on $dataOrConfig, eg. "serve(\StoreConfig $Config)"
	 *
	 * @param null|array|Config
	 * @return static
	 * @throws \InvalidArgumentException
	 */
	protected static function _serve($dataOrConfig) {
		if (is_null($dataOrConfig)) {
			$classname = get_called_class();
			if ($pos = strpos($classname, '\\')) {
				$classname = substr($classname, $pos);
			}
			$configClassname = $classname . 'Config';
			$Config = $configClassname::serve();
			$ret = new static($Config);
		}
		elseif (is_array($dataOrConfig)) {
			$classname = get_called_class();
			if ($pos = strpos($classname, '\\')) {
				$classname = substr($classname, $pos);
			}
			$configClassname = $classname . 'Config';
			$Config = $configClassname::serve($dataOrConfig);
			$ret = new static($Config);
		}
		elseif (is_object($dataOrConfig) && ($dataOrConfig instanceof \Config)) {
			$ret = new static($dataOrConfig);
		}
		else {
			throw new \InvalidArgumentException();
		}
		return $ret;
	}

	/**
	 * I am protected, use serve()
	 */
	protected function __construct(\Config $Config) {
		$this->_Config = $Config;
	}

	/**
	 * I return some protected field from object. Actually, only $this->Config
	 *
	 * @param $key
	 * @throws \MagicGetException
	 */
	public function __get($key) {
		switch ($key) {
			case 'Config':
				return $this->_Config;
			default:
				throw new \MagicGetException();
		}
	}

}
