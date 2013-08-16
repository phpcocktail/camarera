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
 * @version 1.01
 */
namespace Camarera;

/**
 * Base config class, convenient to extend and thus form data containers. Configs do:
 *
 *  * ensure easy and clear inline-creation of configurables (by chained setXxx() methods)
 *
 *  * should be used in place of config arrays, for real configuration-like usage, or just as enclosing params to a method
 *
 *  * should be used in usage scope (in the object/method it is passed to), and not directly (eg. don't molest a Model's
 *  	Field->$default unless you have reason,)
 *
 * 	* have real properties defined in PHP, public or protected (tip: use @property docs for easy autocomplete)
 *
 *  * by default, properties with names like $someThing are considered config properties (see $_fieldNameMask)
 *
 *  * protected properties are also exposed for getting, but not for setting (can be set through setProperty(), though)
 *  	protected properties can be accessed by $Object->propertyName instead of declared $_propertyName
 *
 *  * magic getXxx()/setXxx()/addXxx(), latter two can be chained for "fluid" objects
 *
 *  * you can define your getter/setter/magic methods over some properties and thus override default behaviour (eg.
 *		value checking or converting if necessary)
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera
 * @version 1.01
 *
 * <code>
 * class ManyParams extends \Config {
 *		public $param1;
 *		public $param2;
 *		...
 *		public $paramX;
 *
 *		// @return ManyParams (add proper phpdoc in live code to get nice autocomplete, cannot nest here in example)
 *		public static function serve($config=null) {
 *			return parent::serve($config);
 *		}
 * }
 *
 * // example on how to use as fluid object
 * $Foo->bar(
 *		ManyParams::serve()
 *			->setParam1('value1')
 *			->setParam2('value2')
 *			...
 *			->setParamX('value x')
 * );
 *
 * // this oldschool example is the same IDD
 * $ManyParams = ManyParams::serve();
 * $ManyParams->param1 = 'value1';
 * $ManyParams->param2 = 'value2';
 * ...
 * $ManyParams->paramX = 'value x';
 * $Foo->bar($ManyParams);
 * </code>
 *
 */
abstract class Config {

	use \Camarera\TraitServe,
		\Camarera\TraitMagicGetMask,
		\Camarera\TraitMagicGetterSetterMask,
		\Camarera\TraitPropertyExistsCached
	;

}

class ConfigException extends \LogicException{};
