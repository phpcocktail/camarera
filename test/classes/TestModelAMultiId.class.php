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
 * @license DWTFYWT
 * @version 1.1
 */

class TestModelAMultiId extends \Model {

	use TraitTestModel;

	public static $fieldNames = array('idx','ids','x1','x2','s1','s2');

	protected static $_idFieldName = array('idx','ids');

	protected static $_fields = array(
		'idx' => array(
			'type' => 'integer',
		),
		'ids' => array(
			'type' => 'string',
		),
		'x1' => array(
			'type' => 'integer',
			'minVal' => 1,
			'maxVal' => 10,
		),
		'x2' => array(
			'type' => 'integer',
			'minVal' => 1,
			'maxVal' => 100,
		),
		's1' => array(
			'type' => 'string',
			'minLen' => 3,
			'maxLen' => 10,
		),
		's2',
	);

}
