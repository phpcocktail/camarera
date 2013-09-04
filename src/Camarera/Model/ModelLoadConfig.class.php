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
 * I hold options for Model get/load
 * @author t
 * @package Camarera\Model
 * @version 1.1
 *
 * @method $this setLoadFields(string|array|null $fields)
 * @method $this setAllowLoad(bool $allowLoad)
 * @method $this setLoadOrder($loadOrder)
 * @method $this setRegisteredInstance(bool $registeredInstance)
 * @method $this setData(array $data)
 */
class ModelLoadConfig extends \Config implements \Camarera\StoreMethodConfig {

	/**
	 * @var string|array only these fields will be loaded, may be null or array of fieldnames
	 */
	public $loadFields;

	/**
	 * @var boolean if false, stores will not be used to get the object (but manager will be, if available)
	 */
	public $allowLoad = false;

	/**
	 * @var null|string|array[string]string load order.
	 * 		null=none, string=directly included order, array=$fieldName=>[ASC|DESC] pairs
	 * @example "name ASC _id DESC"
	 * @example array('name'=>'ASC', '_id'=>'DESC')
	 */
	public $loadOrder = null;

	/**
	 * @var boolean controls whether ModelInstanceManager shall be used to get object/data or forced load shall happen
	 */
	public $registeredInstance = true;

	/**
	 * @var array[string]mixed data used in get or load
	 */
	public $data = null;

}
