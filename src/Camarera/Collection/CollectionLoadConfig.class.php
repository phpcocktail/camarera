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
 */
namespace Camarera;

/**
 * collection get/load behaviour config
 * @author t
 * @package Camarera\Collection
 * @version 1.01
 */
class CollectionLoadConfig extends \Config implements \Camarera\StoreMethodConfig {

	/**
	 * @var boolean set to true to perform data load from store
	 */
	public $allowLoad = false;

	/**
	 * @var string[] only these fields will be loaded, if not null, loaded model will not be a managed instance
	 */
	public $loadFields;
	/**
	 * @var string the order in sql format '{fieldname} (ASC|DESC)'
	 */
	public $loadOrder;
	/**
	 * @var int limit the number of loaded items
	 */
	public $loadLimit;
	/**
	 * @var int offset, for eg. paging
	 */
	public $loadOffset;

	/**
	 * @var array[string]mixed key=>value pairs of data to be used as filters while loading
	 */
	public $filter;

	/**
	 * @var boolean if true, loaded data will be appended, otherwise it replaces current datas
	 */
	public $accumulate = false;

	/**
	 * @var int[],string[],Model[] datas to load by
	 */
	public $datas;

}
