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
 * StoreConfig - base config for store driver configs. Each StoreXxx driver must have a StoreXxxConfig class which
 *	extends me, StoreConfig. I defined most of the commonly used parameters, however, not all store drivers may use them.
 * @see Camarera\Store on example usage
 * @author t
 * @package Camarera\Store
 * @version 1.01
 * @property-read string $id internal ID of the store, in case you use more than one store you can refer them by this ID
 */
abstract class StoreConfig extends \Config {

	/**
	 * internal ID of the store, in case you use more than one store you can refer them by this ID
	 * @var string
	 */
	protected $_id = 'default';

	/**
	 * @var string fully qualified classname of the Store object
	 */
	public $_storeClassname;
	/**
	 * @var string encoding of the Store. Useful for DB connections as well as XML charsets too, etc
	 */
	public $encoding = 'utf8';

	// these will come handy with many Store types, handle them here :)
	/**
	 * @var string remote host URL (may be something else, depending on StoreXxx class implementation)
	 */
	public $host;
	/**
	 * @var int port to host
	 */
	public $port;
	/**
	 * @var boolean allow pconnect or not
	 */
	public $pConnect=false;

	/**
	 * @var string username for remote host auth
	 */
	public $username;
	/**
	 * @var string password for remote host auth
	 */
	public $password;
	/**
	 * @var string database name, or custom implementation
	 */
	public $database;
	/**
	 * @var string table prefix will be what it says for DB table names, otherwise not used, or custom implementation
	 */
	public $tablePrefix = '';

	/**
	 * I return store classname, $this->_storeClassname if set, otherwise try to autoguess.
	 * @return string|NULL
	 */
	public function getStoreClassname() {
		if (!empty($this->_storeClassname)) {
			return $this->_storeClassname;
		}
		$configClassname = get_class($this);
		if (preg_match('/^([^\\\\]+\\\\)*(StoreDriver[A-Z][a-z0-9]+)Config$/', $configClassname, $matches)) {
			return $matches[2];
		}
		throw new \ClassDefinitionException('StoreXxx classname could not be guessed, ' . $configClassname . ' class should override default getStoreClassname()');
	}

}
