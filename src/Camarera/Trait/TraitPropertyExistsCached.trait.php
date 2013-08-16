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
 * Class TraitPropertyExists has a cached property (class definition level) lookup
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera\Trait
 * @since 1.1
 * @version 1.1
 */
trait TraitPropertyExistsCached {

	/**
	 * @var array cache field names of this class
	 */
	protected static $_cachedDefinedProperties = array();

	/**
	 * I cache and return defined properties of the class. This is an optimization, when eg. creating a config object, each
	 * 		supplied param will use _propertyExists() so with this cache we gain some speed. And, much speed on repeated
	 * 		creation of the same class.
	 */
	protected static function _getDefinedProperties() {
		$classname = get_called_class();
		if (empty(static::$_cachedDefinedProperties[$classname])) {
			$properties = get_class_vars($classname);
			foreach ($properties as $eachPropertyName => $eachProperty) {
				if (substr($eachPropertyName, 0, 2) == '__') {
					unset($properties[$eachPropertyName]);
				}
			}
			static::$_cachedDefinedProperties[$classname] = array_keys($properties);
		}
		return static::$_cachedDefinedProperties[$classname];
	}

	/**
	 * I tell if $this->$var exists or not. For efficiency, I use a pre-generated lookup list
	 * @param string $fieldName fieldname to check
	 * @return bool true=property exists
	 */
	protected static function _propertyExists($fieldName) {
		return in_array($fieldName, static::_getDefinedProperties());
	}

}
