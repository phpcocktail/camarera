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

use \Camarera\ModelInfoManager;

// some test classes. Notice they come without field def, because that is provided programatically within each test
class Foo extends \Model {};
class FooBar extends \Model {};
class FooDamnBar extends \Model {};
class FooBarCollection {};

/**
 * Class ModelInfoManagerTest
 * @runTestsInSeparateProcesses
 */
class ModelInfoManagerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @covers ModelInfoManager::isInflated
	 */
	function testIsInflated() {
		$classname = 'TestModelA';
		$this->assertFalse(ModelInfoManager::isInflated($classname));
		$this->assertNotContains(
			$classname,
			array_keys(PHPUnit_Framework_Assert::readAttribute('ModelInfoManager', '_inflatedClassnames'))
		);
		$classname::serve();
		$this->assertTrue(ModelInfoManager::isInflated($classname));
		$inflatedClasses = PHPUnit_Framework_Assert::readAttribute('ModelInfoManager', '_inflatedClassnames');
		$this->assertTrue($inflatedClasses[$classname]);
	}

	/**
	 * @covers ModelInfoManager::getField
	 */
	function testGetField() {

		// if not yet inflated
		$classname = 'TestModelA';
		$classname::serve();

		// get one field
		$Field = ModelInfoManager::getField($classname, 'x1');
		$this->assertTrue($Field instanceof \FieldInteger);
		$this->assertEquals(
			'x1',
			$Field->fieldName
		);

		// get all fields
		$Fields = ModelInfoManager::getField($classname);
		$this->assertTrue(is_array($Fields));
		$this->assertEquals(
			$classname::$fieldNames,
			array_keys($Fields)
		);

		// get some fields
		$Fields = ModelInfoManager::getField($classname, array('x1','x2'));
		$this->assertTrue(is_array($Fields));
		$this->assertEquals(
			array('x1','x2'),
			array_keys($Fields)
		);

		// wrong fieldname
		$this->assertNull(ModelInfoManager::getField($classname, 'asd'));

	}
	/**
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage t been inflated
	 * @covers ModelInfoManager::getField
	 */
	function testGetFieldException1() {
		ModelInfoManager::getField('Foo');
	}
	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage invalid $fieldnames sent
	 * @covers ModelInfoManager::getField
	 */
	function testGetFieldException2() {
		$classname = 'TestModelA';
		$classname::serve();
		ModelInfoManager::getField($classname, array());
	}

	/**
	 * @expectedException \RuntimeException
	 * @covers ModelInfoManager::getIdFieldName
	 */
	function testGetIdFieldname() {

		// string ID
		$classname = 'TestModelA';
		$classname::serve();
		$this->assertEquals('_id', ModelInfoManager::getIdFieldname($classname));

		// array of fields ID
		$classname = 'TestModelB';
		$classname::serve();
		$this->assertEquals(array('s1','x1'), ModelInfoManager::getIdFieldname($classname));

		// non existing class: should throw
		$classname = 'TestModelFoo';
		ModelInfoManager::getIdFieldname($classname);

	}

	/**
	 * @expectedException \RuntimeException
	 * @covers ModelInfoManager::getStoreTable
	 */
	function testGetStoreTable() {

		$classname = 'TestModelA';
		$classname::serve();
		$this->assertEquals('test_model_a', ModelInfoManager::getStoreTable($classname));

		// non existing class: should throw
		$classname = 'TestModelFoo';
		ModelInfoManager::getStoreTable($classname);

	}

	/**
	 * @covers ModelInfoManager::inflate
	 */
	function testInflatedAlreadyException() {

		$classname = 'Foo';
		$fields = array('x1','x2','s1','s2');

		ModelInfoManager::inflate(
			$classname,
			$fields,
			null,
			null,
			null
		);

		try {
			ModelInfoManager::inflate(
				$classname,
				$fields,
				null,
				null,
				null
			);
		}
		catch (\RunTimeException $e) {
			if (strpos($e->getMessage(), 'already inflated') === false) {
				throw $e;
			}
		}
	}

	/**
	 * @expectedException \ClassDefinitionException
	 * @expectedExceptionMessage x3,x4 are ID fields but
	 * @covers ModelInfoManager::inflate
	 */
	function testInflateIdfieldException() {
		$fields = array('x1','x2','s1','s2');
		ModelInfoManager::inflate(
			'Foo',
			$fields,
			array('x1','x2','x3','x4'),
			null,
			null
		);
		$this->assertTrue(false);
	}

	/**
	 * @covers ModelInfoManager::inflate
	 */
	function testInflate() {
		$classname = 'FooBar';
		$fields = array(
			'x1' => array(
				'classname' => '\Field',
			),
			'x2',
			's1',
			's2'
		);
		ModelInfoManager::inflate(
			$classname,
			$fields,
			null,
			'FooBarTable',
			null
		);
		$values = PHPUnit_Framework_Assert::readAttribute('ModelInfoManager', '_idFieldNames');
		$this->assertEquals('_id', $values[$classname]);
		$values = PHPUnit_Framework_Assert::readAttribute('ModelInfoManager', '_storeTables');
		$this->assertEquals('FooBarTable', $values[$classname]);

		$classname = 'FooDamnBar';
		$fields = array(
			'x1',
			'x2',
			's1',
			's2'
		);
		ModelInfoManager::inflate(
			$classname,
			$fields,
			array('x1','x2'),
			null,
			null
		);
		$values = PHPUnit_Framework_Assert::readAttribute('ModelInfoManager', '_idFieldNames');
		$this->assertEquals(array('x1','x2'), $values[$classname]);
		$values = PHPUnit_Framework_Assert::readAttribute('ModelInfoManager', '_storeTables');
		$this->assertEquals('foo_damn_bar', $values[$classname]);
	}

	/**
	 * @dataProvider testInflateValidatorsProvider
	 * @covers ModelInfoManager::inflate
	 */
	function testInflateValidators($validators, $expectedInflatedValidator, $expectedAsDelegated) {
		$classname = 'FooBar';
		$fields = array(
			'x1' => array(
				'type' => 'integer',
				'validators' => $validators
			)
		);
		$delegatedValidators = ModelInfoManager::inflate(
			$classname,
			$fields,
			null,
			null,
			null
		);

		if ($expectedAsDelegated) {
//			print_r($delegatedValidators); die;
			foreach ($delegatedValidators as $eachValidator) {
				$this->assertEquals($expectedInflatedValidator, $eachValidator);
			}
		}
		else {
			print_r($delegatedValidators);
			$this->assertEmpty($delegatedValidators);
			$field = ModelInfoManager::getField($classname, 'x1');
			foreach ($field->validators as $eachValidator) {
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
	 * @covers ModelInfoManager::inflate
	 */
	function testInflateExceptions($fields, $exceptionClassname, $exceptionMessage) {
		try {
			ModelInfoManager::inflate(
				'Foo',
				$fields,
				null,
				null,
				null
			);
			$this->assertTrue(false);
		}
		catch (\Exception $e) {
			if (!($e instanceof $exceptionClassname) || (strpos($e->getMessage(), $exceptionMessage) === false)) {
				throw $e;
			}
		}
	}

	function inflateExceptionsProvider() {
		return array(
			array(
				array(),
				'\ClassDefinitionException',
				'initial field definition array',
			),
			array(
				array('value'),
				'\ClassDefinitionException',
				'forbidden'
			),
			array(
				array('-('),
				'\ClassDefinitionException',
				'field name pattern',
			),
			array(
				array(
					'a' => array()
				),
				'\ClassDefinitionException',
				'neither "classname" nor "type"',
			),
			array(
				array(
					'a' => array(
						'type' => 'integer',
						'foo' => 'bar',
					)
				),
				'\ClassDefinitionException',
				'undefined config property',
			),
			array(
				array(
					'a' => new stdClass(),
				),
				'\ClassDefinitionException',
				'invalid field def',
			),
		);
	}

	/**
	 * @covers ModelInfoManager::getCollectionClassname
	 */
	function testGetCollectionClassname() {
		$classname = 'FooBar';
		$fields = array(
			'x1' => array(
				'classname' => '\Field',
			),
			'x2',
			's1',
			's2'
		);
		ModelInfoManager::inflate(
			$classname,
			$fields,
			null,
			'FooBarTable',
			null
		);
		$this->assertEquals('FooBarCollection', ModelInfoManager::getCollectionClassname($classname));
	}
}
