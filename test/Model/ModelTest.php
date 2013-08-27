<?php

require_once(realpath(dirname(__FILE__) . '/../../vendor') . '/autoload.php');
require_once('classes/TraitTestModel.php');
require_once('classes/TestModelA.class.php');

use \Camarera\ModelMetaInfo;

/**
 * Class ModelTest
 * @runTestsInSeparateProcesses
 */
class ModelTest extends PHPUnit_Framework_TestCase {

	/**
	 * @covers Model::_getInitialFieldDefs
	 */
	function testGetInitialFieldDefs() {
		$fields = PHPUnit_Framework_Assert::readAttribute('TestModelA', '_fields');

		$this->assertEquals($fields, TestModelA::_getInitialFieldDefs());
	}

	/**
	 * @covers Model::_inflate
	 */
	function testInflate() {
		$this->assertFalse(\Camarera\ModelMetaInfo::isInflated('TestModelA'));
		$classname = TestModelA::_inflate();
		$this->assertTrue(\Camarera\ModelMetaInfo::isInflated('TestModelA'));
		$this->assertEquals('TestModelA', $classname);
		// shouldn't call ModelMetaInfo again, that would throw an exception actually
		TestModelA::_inflate();
	}

	/**
	 * @covers Model::field
	 * @covers Model::idFieldName
	 * @covers Model::storeTable
	 */
	function testField_idFieldName_storeTable() {
		TestModelA::_inflate();

		$Field = TestModelA::field(array('x1','x2'));
		$this->assertTrue(is_array($Field));
		$this->assertCount(2, $Field);

		$Field = TestModelA::field('x1');
		$this->assertInstanceOf('\Field', $Field);

		$Field = TestModelA::field();
		$this->assertTrue(is_array($Field));
		$this->assertCount(5, $Field);

		$this->assertEquals('_id', TestModelA::idFieldName());

		$this->assertEquals('test_model_a', TestModelA::storeTable());

	}

	/**
	 * @covers \Model::isRegisterable
	 */
	function testIsRegisterable() {
		$this->assertTrue(TestModelA::isRegisterable());
		$R = new ReflectionProperty('TestModelA', '_isRegisterable');
		$R->setAccessible(true);
		$R->setValue(false);
		$this->assertFalse(TestModelA::isRegisterable());
	}

	/**
	 * @covers Model::serve
	 */
	function testServe() {

		$M = TestModelA::serve();
		$this->assertEquals(array(), PHPUnit_Framework_Assert::readAttribute($M, '_values'));
		$this->assertEquals(array(), PHPUnit_Framework_Assert::readAttribute($M, '_storedValues'));

		$values = array(
			'x1' => 2,
			's1' => 'asdf',
		);
		$M = TestModelA::serve($values);
		$this->assertEquals($values, PHPUnit_Framework_Assert::readAttribute($M, '_values'));
		$this->assertEquals(array(), PHPUnit_Framework_Assert::readAttribute($M, '_storedValues'));

		$this->markTestIncomplete();

	}

	function test__call() {
		$this->markTestIncomplete();
	}

}
