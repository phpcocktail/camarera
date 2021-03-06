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

class TestModelA extends \Model {

	use TraitTestModel;

	public static $fieldNames = array('_id','x1','x2','s1','s2');

	protected static $_fields = array(
		'x1' => array(
			'type' => 'integer',
			'validators' => array(
				'minVal' => 1,
				'maxVal' => 100,
			),
		),
		'x2' => array(
			'type' => 'integer',
			'validators' => array(
				'minVal' => 1,
				'maxVal' => 1000,
			),
		),
		's1' => array(
			'type' => 'string',
			'validators' => array(
				'minLen' => 1,
				'maxLen' => 10,
			),
		),
		's2',
	);

}
