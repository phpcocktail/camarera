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
 * I am just a placeholder for typehinting in store methods, ensuring they are (more or less) the right kind of param.
 * Eg. ModelLoadConfig, ModelSaveConfig and ModelDeleteConfig all implement this. They are used in StoreXxx objects'
 *	LoadModel() SaveModel() CreateModel() DeleteModel() methods as typehinted parameters. These store methods share
 *	some inner methods, abstracted as functional patterns, and these used StoreMethodConfig for typehinting for
 *	interchangeability of params depending on which method is calling.
 * @author t
 * @package Camarera\Store
 * @version 1.01
 */
interface StoreMethodConfig {}
