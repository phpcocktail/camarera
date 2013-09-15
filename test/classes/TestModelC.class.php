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

class TestModelC extends \Model {

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
				array(
					'callback' => array('FieldValidator','minVal'),
					'params' => array(1),
				),
				array(
					'callback' => array('FieldValidator','maxVal'),
					'params' => array(1000),
				),
			),
		),
		's1' => array(
			'type' => 'string',
			'validators' => array(
				'lengthBetween' => array(1,10),
				'uniqueWith' => 's2',
				'MyValidator::foo' => 1,
//				array('MyValidator::foo', 2),
//				array('MyValidator::bar', 3,4,5),
			),
		),
		's2' => array(
			'type' => 'string',
			'validators' => array(
				array(
					'callback' => array('FieldValidator','lengthBetween'),
					'params' => array(1,10),
				),
				array(
					'delegate' => true,
					'params' => array('uniqueWith','s1'),
				)
			)
		)
	);

	protected static function _getInitialFieldDefs() {
		$fieldDefs = static::$_fields;
		$fieldDefs['x1']['validators'][] = function($value) {
			return $value % 2 ? false : true;
		};
	}

}
