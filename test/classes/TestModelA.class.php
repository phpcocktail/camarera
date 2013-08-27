<?php

class TestModelA extends \Model {

	use TraitTestModel;

	public static $fieldNames = array('_id','x1','x2','s1','s2');

	protected static $_fields = array(
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
