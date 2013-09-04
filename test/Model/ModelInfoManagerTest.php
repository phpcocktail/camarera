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
	 * @expectedException \RuntimeException
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

		try {
			ModelInfoManager::getField($classname, array());
			$this->assertTrue(false);
		}
		catch (\InvalidArgumentException $e) {}

		ModelInfoManager::getField('Foo');
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
