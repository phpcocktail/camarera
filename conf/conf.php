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
 * @since 1.01
 * @license DWTFYWT
 * @version 1.01
 */

/**
 * Camarera provides a common interface to configuration settings. Confs are multidimensional arrays, and their values
 *		are retrieved scoped, by means of module and environment. These scopes are cascaded, by retrieval or by load.
 * Confs must be loaded first
 * @see Camarera::loadConf()
 * loadConf will load the default file, and then look for environment specific one, loading it as well. It merges
 *		values with the original ones, overwriting originals. This config array is stored in Camarera::$_confCache. This
 *		is environment scoping and is resolved at load time. Note that merging is done by array_merge_recursive so
 *		arrays with numerical indices will be merged, not overwritten.
 * Values can be got by
 * @see Camarera::conf($key)
 * confs are sometimes mapped to Config objects, eg. the App.App.* config maps to the \Cocktail\ApplicationConfig class,
 *		but this is not mandatory. In these cases, the class definition (variables) define what the conf can contain.
 * Lazy loading: any element in the config array can be a lambda callback function. These may return one value or a
 *		multi-dimensional array. Using lambdas is useful when a config item depends on another, eg. an autoloader uses
 *		the localRoot config value. In these cases the lambda can reference the already loaded config, while plain code
 *		would be executed before any config value gets into the config cache.
 * There are four important, special elements in each config (but all can be omitted)
 *	1. the first chunk (key) must contain all common module dependent settings, eg. 'localRoot'. These chunks can be
 * 		reached in a cascading way, eg. \Camarera::conf('.env') will get the latest loaded value of these first chunks.
 *  2. '_autoloader' items of this array will be registered as autoloaders when the config file is loaded
 *  3. '_logger' items will be registered as well
 *  4. '_store' items will be registered as well
 *
 *  * @author t
 * @package Camarera
 * @version 1.01
 */

return array(
	'Camarera' => array(
		'version' => '1.01',
		'mtstamp' => microtime(true),
		'tstamp' => floor(microtime(true)),
		'localRoot' => CA_ROOT,
		'namespace' => 'Camarera',
	),
	'Field' => array(
		'id' => array(
			'class' => '\FieldInteger',
			'name' => '_id',
		),
		'password' => array(
			'method' => function() { return \FieldPassword::METHOD_SHA1; },
		),
	),
	'_store' => array(
	),
	'_logger' => array(
	),
);
