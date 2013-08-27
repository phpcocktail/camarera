<?php

class TestModelB extends \Model {

	use TraitTestModel;

	public static $fieldNames = array('x1','x2','s1','s2');

	protected static $_idFieldName = array('s1', 'x1');

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
