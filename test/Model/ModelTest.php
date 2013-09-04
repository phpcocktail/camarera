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

use \Camarera\ModelInfoManager;

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
		$this->assertFalse(\Camarera\ModelInfoManager::isInflated('TestModelA'));
		$classname = TestModelA::_inflate();
		$this->assertTrue(\Camarera\ModelInfoManager::isInflated('TestModelA'));
		$this->assertEquals('TestModelA', $classname);
		// shouldn't call ModelInfoManager again, that would throw an exception actually
		TestModelA::_inflate();
		$fields = PHPUnit_Framework_Assert::readAttribute('ModelInfoManager', '_fields');
		$this->assertInstanceOf('\Field', reset($fields['TestModelA']));
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

		// serve empty
		$M = TestModelA::serve();
		$this->assertEquals(array(), PHPUnit_Framework_Assert::readAttribute($M, '_values'));
		$this->assertEquals(array(), PHPUnit_Framework_Assert::readAttribute($M, '_storedValues'));

		// serve by values
		$values = array(
			'x1' => 2,
			's1' => 'asdf',
		);
		$M = TestModelA::serve($values);
		$this->assertEquals($values, PHPUnit_Framework_Assert::readAttribute($M, '_values'));
		$this->assertEquals(array(), PHPUnit_Framework_Assert::readAttribute($M, '_storedValues'));
		$this->assertFalse($M->LastGetConfig->allowLoad);

		$M->_id = 1;
		$M->registerInstance();

		// serve(1) should load only if registerable
		$M2 = TestModelA::serve(1);
		$this->assertTrue($M === $M2);
		$this->assertFalse($M2->LastGetConfig->allowLoad);

		// load with data not in manager
		$Config = \ModelLoadConfig::serve()
			->setData(array('x1'=>1,'s1'=>'asdf'));
		$M = TestModelA::serve($Config);
		$this->assertEquals(null, $M->ID);

		// load with data in manager
		$Config = \ModelLoadConfig::serve()
			->setData($values);
		$M = TestModelA::serve($Config);
		$this->assertEquals('1', $M->ID);

		$Config = \ModelLoadConfig::serve()
			->setData($values)
			->setRegisteredInstance(false);
		$M = TestModelA::serve($Config);
		$this->assertEquals(null, $M->ID);

		$this->markTestIncomplete();

	}

	function test__call() {



		$this->markTestIncomplete();
	}

}
