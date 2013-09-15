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

require_once(realpath(dirname(__FILE__) . '/../../vendor') . '/autoload.php');
require_once('classes/TraitTestModel.php');
require_once('classes/TestModelA.class.php');
require_once('classes/TestModelB.class.php');

use \Camarera\Validator;

/**
 * Class ModelInfoManagerTest
 * @runTestsInSeparateProcesses
 */
class ValidatorTest extends PHPUnit_Framework_TestCase {

	/**
	 * @covers Validator::validValidatorOptionsKeys
	 */
	function testValidValidatorOptionsKeys() {
		$validValidatorOptionsKeys = Validator::validValidatorOptionsKeys();
		$this->assertTrue(is_array($validValidatorOptionsKeys));
		$this->assertCount(4, $validValidatorOptionsKeys);
	}

	/**
	 * @dataProvider testInflateValidatorsProvider
	 * @covers Validator::inflateValidators
	 */
	function testInflateValidators($validators, $expectedInflatedValidator, $expectedAsDelegated) {

		$Field = \Field::serve(
			array(
				'fieldName'=>'x1',
				'validators' => $validators,
			)
		);

		$validatorClassname = 'ValidatorField';

		list($inflatedValidators, $delegatedValidators) =
			\Validator::inflateValidators(
				$Field,
				$validatorClassname
			);

		if ($expectedAsDelegated) {
			$this->assertEmpty($inflatedValidators);
			foreach ($delegatedValidators as $eachValidator) {
				$this->assertEquals($expectedInflatedValidator, $eachValidator);
			}
		}
		else {
			$this->assertEmpty($delegatedValidators);
			foreach ($inflatedValidators as $eachValidator) {
				$this->assertEquals($expectedInflatedValidator, $eachValidator);
			}
		}

	}

	function testInflateValidatorsProvider() {
		$ret = array(
			array(
				array(
					'minVal' => 1,
					'ValidatorField::minVal' => 1,
					array('minVal', 1),
					array('minVal', array(1)),
					array('callback'=>'minVal','params'=>array(1)),
					array('callback'=>'minVal','params'=>array(1),'options'=>array()),
					array('callback'=>'ValidatorField::minVal','params'=>array(1),'options'=>array()),
				),
				array(
					'callback' => array('ValidatorField', 'minVal'),
					'params' => array(1),
				),
				false
			),
			array(
				array(
					'minVal' => array(1),
				),
				array(
					'callback' => array('ValidatorField', 'minVal'),
					'params' => array(1),
				),
				false
			),
			array(
				array(
					'!minVal' => 1,
					array('!minVal', 1),
					array('!minVal', array(1)),
					array('minVal', array(1), array('negated'=>true)),
					array('!minVal', array(1), array('negated'=>false)),
				),
				array(
					'callback' => array('ValidatorField', 'minVal'),
					'params' => array(1),
					'options' => array('negated' => true),
				),
				false
			),
			array(
				array(
					'!minVal' => array(1),
				),
				array(
					'callback' => array('ValidatorField', 'minVal'),
					'params' => array(1),
					'options' => array('negated' => true),
				),
				false
			),
			array(
				array(
					'always',
					array('always', array(), array()),
				),
				array(
					'callback' => array('ValidatorField', 'always'),
					'params' => array(),
				),
				false
			),
			array(
				array(
					'!always',
					array('!always', array(), array()),
					array('always', array(), array('negated'=>true)),
				),
				array(
					'callback' => array('ValidatorField', 'always'),
					'params' => array(),
					'options' => array('negated' => true),
				),
				false
			),
			array(
				array(
					'between' => array(2,3),
					array('between', 2, 3),
					array('between', array(2, 3), array()),
				),
				array(
					'callback' => array('ValidatorField', 'between'),
					'params' => array(2,3),
				),
				false
			),
			array(
				array(
					'!between' => array(2,3),
					array('!between', 2, 3),
					array('!between', array(2, 3)),
					array('!between', array(2, 3), array('negated'=>false)),
				),
				array(
					'callback' => array('ValidatorField', 'between'),
					'params' => array(2,3),
					'options' => array('negated'=>true),
				),
				false
			),
			array(
				array(
					'unique',
					'unique' => true,
				),
				array(
					'callback' => array('delegated', 'unique'),
					'params' => array('x1'),
				),
				true
			)
		);
//		$ret = array_slice($ret, 0 ,1);
		return $ret;
	}

	/**
	 * @dataProvider inflateExceptionsProvider
	 * @param $validators
	 * @param $expectedExceptionClass
	 * @covers Validator::inflateValidators
	 */
	function testInflateExceptions($validators, $expectedExceptionClass) {

		$Field = \Field::serve(
			array(
				'fieldName'=>'x1',
				'validators' => $validators,
			)
		);

		$validatorClassname = 'ValidatorField';

		try {
			list($inflatedValidators, $delegatedValidators) =
				\Validator::inflateValidators(
					$Field,
					$validatorClassname
				);
		}
		catch (\Exception $e) {
			$this->assertEquals($expectedExceptionClass, get_class($e));
		}

	}

	function inflateExceptionsProvider() {
		$ret = array(
			array(
				array(
					array('between', 2, 'foo'=>3),
				),
				'Camarera\ClassDefinitionException',
			),
			array(
				array(
//					array('between', new stdClass),
					new stdClass,
				),
				'Camarera\ClassDefinitionException',
			),
			array(
				array(
					array('between', array(2,3), array('foo'=>'bar')),
				),
				'Camarera\ClassDefinitionException',
			),
			array(
				array(
					array('delegate', null, array()),
				),
				'Camarera\ClassDefinitionException',
			),
		);
//		$ret = array_slice($ret, 1, 1);
		return $ret;
	}

}
