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
 * Config class for mysqli store. See StoreConfig on available
 * @author t
 * @package Camarera\Store
 * @version 1.01
 * @method getSocket()
 * @method setSocket()
 */
 class StoreDriverMysqliConfig extends \StoreConfig {

 	/**
 	 * @var string you can specify the socket here
 	 */
 	public $socket;

}
