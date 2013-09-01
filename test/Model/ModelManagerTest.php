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

use \Camarera\ModelMetaInfo;
use \Camarera\ModelManager;

/**
 * Class ModelManagerTest
 * @runTestsInSeparateProcesses
 */
class ModelManagerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @covers ModelManager::get
	 * @covers ModelManager::set
	 */
	function testGetSet() {
		$classname = 'TestModelA';
		$data = array(
			'_id' => 1,
			'x1' => 10,
			's1' => 'a',
		);
		ModelManager::set($classname, $data);
		$registry = PHPUnit_Framework_Assert::readAttribute('ModelManager', '_registry');
		$this->assertEquals($data, $registry[$classname][1]);
		$this->assertEquals($data, ModelManager::get($classname, 1, false));
		$M = ModelManager::get($classname, 1);
		$this->assertInstanceOf('\TestModelA', $M);

		$data2 = array(
			'_id' => 2,
			'x1' => 20,
			's1' => 'b',
		);
		ModelManager::set($classname, $data2, 2);
		$registry = PHPUnit_Framework_Assert::readAttribute('ModelManager', '_registry');
		$this->assertEquals($data2, $registry['TestModelA'][2]);
		$this->assertEquals($data2, ModelManager::get($classname, 2, false));

		$data3 = array(
			'_id' => 3,
			'x1' => 30,
			's1' => 'c',
		);
		$M = TestModelA::serve($data3);
		ModelManager::set($classname, $M, 3);
		$this->assertEquals($M, ModelManager::get($classname, 3));

		$data4 = array(
			'_id' => 4,
			'x1' => 40,
			's1' => 'd',
		);
		ModelManager::set($classname, $data4);
		$findData = array('x1'=>40, 's1'=>'d');
		$this->assertEquals($data4, ModelManager::get($classname, $findData, false));

		ModelManager::get($classname, 4);
		$this->assertEquals($data4, ModelManager::get($classname, $findData, false));

	}

	/**
	 * @covers ModelManager::get
	 * @expectedException BadMethodCallException
	 */
	function testGetException1() {
		$classname = 'TestModelA';
		ModelManager::get($classname, array());
	}

	/**
	 * @covers ModelManager::set
	 * @expectedException InvalidArgumentException
	 */
	function testSetException1() {
		$classname = 'stdClass';
		$M = new stdClass();
		ModelManager::set($classname, $M);
	}

	/**
	 * @covers ModelManager::set
	 * @expectedException InvalidArgumentException
	 */
	function testSetException2() {
		$classname = 'TestModelA';
		ModelManager::set($classname, null);
	}

	/**
	 * @covers ModelManager::set
	 * @expectedException InvalidArgumentException
	 */
	function testSetException3() {
		$classname = 'TestModelA';
		$data = array(
			'x1' => 10,
			's1' => 'a',
		);
		ModelManager::set($classname, $data);
	}

	/**
	 * @covers ModelManager::getObject
	 * @covers ModelManager::getData
	 */
	function testGetDataGetObject() {
		$classname = 'TestModelA';
		$data = array(
			'_id' => 1,
			'x1' => 10,
			's1' => 'a',
		);
		ModelManager::set($classname, $data);
		$registry = PHPUnit_Framework_Assert::readAttribute('ModelManager', '_registry');
		$this->assertEquals($data, $registry[$classname][1]);
		$this->assertEquals($data, ModelManager::getData($classname, 1));

		$M = ModelManager::getObject($classname, 1);
		$this->assertInstanceOf($classname, $M);
		$this->assertEquals(1, $M->ID);
		$registry = PHPUnit_Framework_Assert::readAttribute('ModelManager', '_registry');
		$this->assertEquals($M, $registry[$classname][1]);

		$M = ModelManager::getObject($classname, 1);
		$this->assertInstanceOf($classname, $M);

		$this->assertEquals($data, ModelManager::getData($classname, 1));
	}

}
