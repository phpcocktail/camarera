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
trait TraitServeWithConfig {

	/**
	 * @var \Config if you override this in your subclass with proper comment you'll get nice autocompletion
	 */
	protected $_Config;

	/**
	 * I create and return an instance
	 * @todo maybe array param should be accepted?
	 * @param null|Config
	 * @return static
	 */
	public static function serve($dataOrConfig=null) {
		if (is_null($dataOrConfig)) {
			$ret = new static();
		}
		elseif (is_array($dataOrConfig)) {
			$classname = get_called_class();
			if ($pos = strpos($classname, '\\')) {
				$classname = substr($classname, $pos);
			}
			$configClassname = $classname . 'Config';
			$Config = $configClassname::get($dataOrConfig);
			$ret = new static($Config);
		}
		elseif (is_object($dataOrConfig) && ($dataOrConfig instanceof \Config)) {
			$ret = new static($dataOrConfig);
		}
		else{
			throw new \InvalidArgumentException();
		}
		return $ret;
	}

	/**
	 * I am protected, use serve()
	 */
	protected function __construct(\Config $Config=null) {
		if (is_null($Config)) {
			$classname = get_class($this);
			if ($pos=strpos($classname, '\\')) {
				$classname = substr($classname, $pos);
			}
			$configClassname = $classname . 'Config';
			$Config = $configClassname::get($Config);
		}
		$this->_Config = $Config;
	}

	/**
	 * I am abstract so I have to be implemented in subclasses, with proper documentation for autocomplete etc.
	 * @return \Config
	 */
	abstract public function getConfig();

}
