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
 * Config class for mysqli store. See StoreConfig on available
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera\Store
 * @version 1.1
 */
 class StoreSqlMysqliConfig extends \StoreConfig {

 	/**
 	 * @var string you can specify the socket here
 	 */
 	public $socket;

}
