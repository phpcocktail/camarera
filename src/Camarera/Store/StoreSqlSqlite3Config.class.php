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
 * config class for sqlite3 store driver
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera\Store
 * @version 1.01
 */
 class StoreSqlSqlite3Config extends \StoreConfig {

 	/**
 	 * @var string path to database file. Filename comes from database name
 	 */
 	public $path=null;

 	/**
 	 * @var int file (DB) open mode, by default, it does not try to create
 	 */
 	public $openFlags = SQLITE3_OPEN_READWRITE;

 	/**
 	 * you can specify this for sqlite3, if you want
 	 * @var string
 	 */
 	public $encryptionKey=null;

}
