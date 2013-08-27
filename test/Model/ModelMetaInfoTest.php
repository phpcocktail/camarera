<?php

require_once(realpath(dirname(__FILE__) . '/../../vendor') . '/autoload.php');
require_once('classes/TraitTestModel.php');
require_once('classes/TestModelA.class.php');
require_once('classes/TestModelB.class.php');

use \Camarera\ModelMetaInfo;

// some test classes. Notice they come without field def, because that is provided programatically within each test
class Foo extends \Model {};
class FooBar extends \Model {};
class FooDamnBar extends \Model {};

/**
 * Class ModelMetaInfoTest
 * @runTestsInSeparateProcesses
 */
class ModelMetaInfoTest extends PHPUnit_Framework_TestCase {

	/**
	 * @covers ModelMetaInfo::isInflated
	 */
	function testIsInflated() {
		$classname = 'TestModelA';
		$this->assertFalse(ModelMetaInfo::isInflated($classname));
		$this->assertNotContains(
			$classname,
			array_keys(PHPUnit_Framework_Assert::readAttribute('ModelMetaInfo', '_inflatedClassnames'))
		);
		$classname::serve();
		$this->assertTrue(ModelMetaInfo::isInflated($classname));
		$inflatedClasses = PHPUnit_Framework_Assert::readAttribute('ModelMetaInfo', '_inflatedClassnames');
		$this->assertTrue($inflatedClasses[$classname]);
	}

	/**
	 * @expectedException \RuntimeException
	 * @covers ModelMetaInfo::getField
	 */
	function testGetField() {

		// if not yet inflated
		$classname = 'TestModelA';
		$classname::serve();

		// get one field
		$Field = ModelMetaInfo::getField($classname, 'x1');
		$this->assertTrue($Field instanceof \FieldInteger);
		$this->assertEquals(
			'x1',
			$Field->fieldName
		);

		// get all fields
		$Fields = ModelMetaInfo::getField($classname);
		$this->assertTrue(is_array($Fields));
		$this->assertEquals(
			$classname::$fieldNames,
			array_keys($Fields)
		);

		// get some fields
		$Fields = ModelMetaInfo::getField($classname, array('x1','x2'));
		$this->assertTrue(is_array($Fields));
		$this->assertEquals(
			array('x1','x2'),
			array_keys($Fields)
		);

		// wrong fieldname
		$this->assertNull(ModelMetaInfo::getField($classname, 'asd'));

		try {
			ModelMetaInfo::getField($classname, array());
			$this->assertTrue(false);
		}
		catch (\InvalidArgumentException $e) {}

		ModelMetaInfo::getField('Foo');
	}

	/**
	 * @expectedException \RuntimeException
	 * @covers ModelMetaInfo::getIdFieldName
	 */
	function testGetIdFieldname() {

		// string ID
		$classname = 'TestModelA';
		$classname::serve();
		$this->assertEquals('_id', ModelMetaInfo::getIdFieldname($classname));

		// array of fields ID
		$classname = 'TestModelB';
		$classname::serve();
		$this->assertEquals(array('s1','x1'), ModelMetaInfo::getIdFieldname($classname));

		// non existing class: should throw
		$classname = 'TestModelFoo';
		ModelMetaInfo::getIdFieldname($classname);

	}

	/**
	 * @expectedException \RuntimeException
	 * @covers ModelMetaInfo::getStoreTable
	 */
	function testGetStoreTable() {

		$classname = 'TestModelA';
		$classname::serve();
		$this->assertEquals('test_model_a', ModelMetaInfo::getStoreTable($classname));

		// non existing class: should throw
		$classname = 'TestModelFoo';
		ModelMetaInfo::getStoreTable($classname);

	}

	/**
	 * @covers ModelMetaInfo::inflate
	 */
	function testInflatedAlreadyException() {

		$classname = 'Foo';
		$fields = array('x1','x2','s1','s2');

		ModelMetaInfo::inflate(
			$classname,
			$fields,
			null,
			null
		);

		try {
			ModelMetaInfo::inflate(
				$classname,
				$fields,
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
	 * @covers ModelMetaInfo::inflate
	 */
	function testInflateExceptions($fields, $exceptionClassname, $exceptionMessage) {
		try {
			ModelMetaInfo::inflate(
				'Foo',
				$fields,
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
	 * @covers ModelMetaInfo::inflate
	 */
	function testInflateIdfieldException() {
		$fields = array('x1','x2','s1','s2');
		ModelMetaInfo::inflate(
			'Foo',
			$fields,
			array('x1','x2','x3','x4'),
			null
		);
		$this->assertTrue(false);
	}

	/**
	 * @covers ModelMetaInfo::inflate
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
		ModelMetaInfo::inflate(
			$classname,
			$fields,
			null,
			'FooBarTable'
		);
		$values = PHPUnit_Framework_Assert::readAttribute('ModelMetaInfo', '_idFieldNames');
		$this->assertEquals('_id', $values[$classname]);
		$values = PHPUnit_Framework_Assert::readAttribute('ModelMetaInfo', '_storeTables');
		$this->assertEquals('FooBarTable', $values[$classname]);

		$classname = 'FooDamnBar';
		$fields = array(
			'x1',
			'x2',
			's1',
			's2'
		);
		ModelMetaInfo::inflate(
			$classname,
			$fields,
			array('x1','x2'),
			null
		);
		$values = PHPUnit_Framework_Assert::readAttribute('ModelMetaInfo', '_idFieldNames');
		$this->assertEquals(array('x1','x2'), $values[$classname]);
		$values = PHPUnit_Framework_Assert::readAttribute('ModelMetaInfo', '_storeTables');
		$this->assertEquals('foo_damn_bar', $values[$classname]);
	}
}
