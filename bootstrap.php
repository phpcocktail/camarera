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
 * @package Camarera
 * @version 1.01
 */
namespace Camarera;

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Defines. Define these before bootstrapping Camarera to override.
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Camarera root folder, where it is installed. Should not be overridden.
if (!defined('CA_ROOT')) {
	define('CA_ROOT', realpath(dirname(__FILE__) ));
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// I will always need these, skip autoload overhead and handle exceptions in naming. Also import them to proper namespace
//	if they don't exist already
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (!class_exists('Camarera\Camarera', false)) {
	require_once(CA_ROOT . '/src/Camarera/Camarera.class.php');
	class_alias('Camarera\Camarera', 'Camarera');
}
$classesToLoad = array(
	array('Config', 'Config.class.php'),
	array('Util', 'Util.class.php'),
	array('AutoloaderNamespaceAliaser', 'Autoloader/AutoloaderNamespaceAliaser.class.php'),
);
foreach ($classesToLoad as $classData) {
	$classname = $classData[0];
	$filename = $classData[1];
	if (class_exists($classname)) {
		continue;
	}
	require CA_ROOT . '/src/Camarera/' . $filename;
}

\Camarera\AutoloaderNamespaceAliaser::register();

\Camarera::loadConf('Camarera', CA_ROOT . '/conf/conf.php');

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// set date/timezone @todo this should be moved to config in some smart way...
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (ini_get('date.timezone') == '') {
	date_default_timezone_set('Europe/Berlin');
}
