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
 * TraitMagicGetMask will validate the field name against staitc::$__fieldNameMask
 * note: requires $this->_propertyExists($propertyName) defined (can be static, eg. as in TraitPropertyExists). Include
 * 		use \Camarera\TraitMagicGetMask, \Camarera\TraitPropertyExists;
 * 		to resolve the dependency
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera\Trait
 * @since 1.1
 * @version 1.1
 */
trait TraitMagicGetMask {

	/**
	 * @var string preg pattern used by magic property identification. Override if default pattern doesn not match your
	 *		coding style. By default, eg. using $Object->$foo either matches a public property in $Object, or, calls
	 *		magic __get('foo') which in turn looks for a protected $_foo property in $Object
	 */
	protected static $__fieldNameMask = '/^[A-Za-z0-9]*$/';

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
	 * I return $this->_Foo if referenced as $Bar->Foo from the outside (assuming $this = $Bar) but only if $fieldName
	 * 		matches static::$_fieldNameMask
	 * @param $fieldName
	 * @return mixed
	 * @throws \MagicGetException
	 */
	protected function ___get($fieldName) {
		// @todo time preg_match vs $this->_propertyExists()maybe the order shall be reverse for a speed gain (in case
			// preg_match proves slow)
		if (preg_match(self::$__fieldNameMask, $fieldName) && $this->_propertyExists('_' . $fieldName)) {
			$fieldName = '_' . $fieldName;
			return $this->$fieldName;
		}
		throw new MagicGetException($fieldName, get_class($this));
	}

}
