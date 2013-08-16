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
 * Class TraitMagicGetMask is a child of TraitMagicGetXxx family, which help exposing protected variables eg. $this->_Foo
 * 		as $Bar->Foo This way you do not expose setting of these vars.
 * TraitMagicGetProtected will return any defined property
 * note: requires $this->_propertyExists($propertyName) defined (can be static, eg. as in TraitPropertyExists). Include
 * 		use \Camarera\TraitMagicGetProtected, \Camarera\TraitPropertyExists;
 * 		to resolve the dependency
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera\Trait
 * @since 1.1
 * @version 1.1
 */
trait TraitMagicGetProtected {

	/**
	 * I just am a wrapper to ___get(). Override this __get() in your class to add functionality, then in the default
	 * 		branch of the switch, call static::___get()
	 * @param $fieldName
	 * @return mixed
	 */
	public function __get($fieldName) {
		return static::___get($fieldName);
	}

	/**
	 * I return $this->_Foo if referenced as $Bar->Foo from the outside (assuming $this = $Bar)
	 * @param $fieldName
	 * @return mixed
	 * @throws \MagicGetException
	 */
	public function ___get($fieldName) {
		$propertyName = '_' . $fieldName;
		if ($this->_propertyExists($propertyName)) {
			return $this->$propertyName;
		}
		throw new \Camarera\MagicGetException();
	}

}
