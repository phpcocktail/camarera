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
 * This autoloader tries to load src in the root namespace by trying to aliase a class from various namespaces. It
 *	will default to load src from \Camarera namespace into root.
 *
 * @author t
 * @package Camarera\Autoloader
 * @license DWTFYWT
 * @version 1.01
 */
class AutoloaderNamespaceAliaser {

	/**
	 * @var array of arrays containing aliased namespace - existing namespace pairs
	 * eg. array(array('Foo','Bar')) will load Bar\MyClass and alias it as Foo\MyClass
	 */
	protected static $_aliases = array();

	/**
	 * @var bool I must register myself only once, this is the semaphore
	 */
	protected static $_isRegistered=false;

	/**
	 * I try to autoload a class by looking up in the defined namespaces and if exists there, I import by class_alias
	 * @param $classname
	 * @return bool true if class loaded
	 */
	public static function autoload($classname) {
		$classname = trim($classname, '\\');
		foreach (static::$_aliases as $eachAlias) {
			if (($eachAlias[0]=='')) {
				if (strpos($classname, '\\')) {
					continue;
				}
			}
			elseif (strpos($classname, $eachAlias[0]) !== 0) {
				die('BBBBBBBBBBB');
				continue;
			}
			$fullClassname = $eachAlias[1] . '\\' . $classname;
			\Camarera::log(
				\Camarera::LOG_INFORMATIONAL,
				'AutoloaderNamespaceAliaser: try ' . $fullClassname . ' for ' . $classname
			);
			if (class_exists($fullClassname)) {
				\Camarera::log(
					\Camarera::LOG_INFORMATIONAL,
					'AutoloaderNamespaceShifter: FOUND, aliasing ' . $fullClassname . ' as ' . $classname
				);
				class_alias($fullClassname, $classname);
				return true;
			}
		}
	}

	/**
	 * I register a namespace alias
	 * @param $aliasedNamespace usually shall be empty '' to import classes to root namespace
	 * @param $existingNamespace shall be the namespace where the class is actually defined
	 */
	public static function registerAlias($aliasedNamespace, $existingNamespace) {
		$alias = array(trim($aliasedNamespace, '\\'), trim($existingNamespace, '\\'));
		if (!in_array($alias, static::$_aliases)) {
			array_unshift(static::$_aliases, $alias);
		}
	}

	/**
	 * I register the autoloader with spl
	 */
	public static function register() {
		if (!static::$_isRegistered) {
			spl_autoload_register(array(get_called_class(), 'autoload'));
			static::$_isRegistered = true;
		}
	}

}
