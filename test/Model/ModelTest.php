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
require_once('classes/TestModelAMultiId.class.php');

use \Camarera\ModelInfoManager;
use \Camarera\ModelInstanceManager;
use \CamareraModelLoadConfig;

class TestModelANonRegisterable extends TestModelA {
	protected static $_isRegisterable = false;
}

class TestModelAStoreFixture extends \StoreSql {
	const CASE_LOAD_RETURN_NORMAL = 1;
	const CASE_LOAD_RETURN_NULL = 2;
	const CASE_LOAD_RETURN_FALSE = 3;
	function loadModel(\Model $Object, \ModelLoadConfig $LoadConfig) {
		switch($Object->ID) {
			case static::CASE_LOAD_RETURN_NORMAL:
				return array('_id'=>1,'x1'=>99,'x2'=>1,'s1'=>'a','s2'=>'a');
			case static::CASE_LOAD_RETURN_NULL:
				return null;
			case static::CASE_LOAD_RETURN_FALSE:
				return false;
			default:
				throw new \RuntimeException;
		}
	}

	function updateModel(\Model $Object, \ModelSaveConfig $SaveConfig) {
		switch ($Object->ID) {
			default:
				throw new \RuntimeException;
		}
	}

	function escape($value, $addQuote='"'){}
	function _query($query) {}
	function _resultNextRow($result) {}
	function _getInsertId() {}
	function execute($query) {}
}
class TestModelAStoreFixtureConfig extends \StoreConfig{}

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
		$this->assertFalse(ModelInfoManager::isInflated('TestModelA'));
		$classname = TestModelA::_inflate();
		$this->assertTrue(ModelInfoManager::isInflated('TestModelA'));
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
	 * @covers Model::isRegisterable
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
	 * @covers Model::__construct
	 */
	function testServe() {

		$M = TestModelA::serve(
			array(
				'x1' => 2,
				's1' => 'asdf',
			),
			ModelLoadConfig::serve()
		);
		$this->assertInstanceOf('Model', $M);

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
		$this->assertFalse($M->LastLoadConfig->allowLoad);

		$M->_id = 1;
		$M->registerInstance();

		// serve(1) should load only if registerable
		$M2 = TestModelA::serve(1);
		$this->assertTrue($M === $M2);
		$this->assertFalse($M2->LastLoadConfig->allowLoad);

		// load with data not in manager
		$Config = ModelLoadConfig::serve()
			->setData(array('x1'=>1,'s1'=>'asdf'));
		$M = TestModelA::serve($Config);
		$this->assertNull($M->ID);

		// load with data in manager
		$Config = ModelLoadConfig::serve()
			->setData($values);
		$M = TestModelA::serve($Config);
		$this->assertTrue(1 === $M->ID);

		$Config = ModelLoadConfig::serve()
			->setData($values)
			->setRegisteredInstance(false);
		$M = TestModelA::serve($Config);
		$this->assertNull($M->ID);

		$id = 13;
		$M = TestModelA::serve($id);
		$this->assertTrue($id === $M->ID);

		$id = '14';
		$M = TestModelA::serve($id);
		$this->assertTrue((int)$id === $M->ID);

		$id = 15;
		$data = array('x1'=>1, 's1'=>'a');
		$M = TestModelA::serve($id, $data);
		$this->assertEquals($id, $M->ID);
		$this->assertEquals(1, $M->x1);

	}
	/**
	 * @covers Model::serve
	 */
	function testServeWithLoad() {

		$Config = \ModelLoadConfig::serve()->setAllowLoad(true);

		$M = TestModelA::serve(1, $Config);
		$this->assertEquals(1, $M->ID);
		$this->assertEquals(10, $M->x1);
		$this->assertEquals(1010, $M->x2);
		$this->assertEquals('a', $M->s1);
		$this->assertEquals('aa', $M->s2);

		$M = TestModelA::serve(array('x1'=>20,'x2'=>2020), $Config);
		$this->assertEquals(2, $M->ID);
		$this->assertEquals(20, $M->x1);
		$this->assertEquals(2020, $M->x2);
		$this->assertEquals('b', $M->s1);
		$this->assertEquals('bb', $M->s2);
	}
	/**
	 * @covers Model::serve
	 * @expectedException BadMethodCallException
	 */
	function testServeException1() {
		$M = TestModelA::serve(null, 1);
	}
	/**
	 * @covers Model::serve
	 * @expectedException InvalidArgumentException
	 */
	function testServeException2() {
		$M = TestModelA::serve(new stdClass());
	}

	/**
	 * @covers Model::registerInstance
	 * @expectedException \BadMethodCallException
	 * @expectedExceptionMessage cannot be registered by declaration of
	 */
	function testRegisterInstance() {

		$classname = 'TestModelA';

		$M = TestModelA::serve(array('_id'=>1,'x1'=>1,'s1'=>'a'));
		$this->assertTrue(get_class($M) === $classname);

		$this->assertNull(ModelInstanceManager::get($classname, 1));

		$M->registerInstance();

		$this->assertInstanceOf($classname, ModelInstanceManager::get($classname, 1));

		// throw exception if object doesn't have ID
		$M = TestModelANonRegisterable::serve(array('x1'=>2,'s1'=>'b'));
		$this->assertTrue(is_object($M));

		$M->registerInstance();

	}

	/**
	 * @covers \Model::__get
	 */
	function test__get() {
		$classname = 'TestModelA';
		$data = array('x1'=>1,'s1'=>'a');
		$M = TestModelA::serve(1, $data);

		$this->assertEquals($M->getID(), $M->ID);
		$this->assertEquals(false, $M->isRegistered);
		$this->assertEquals(null, $M->isValid);
		$this->assertTrue($M->LastLoadConfig instanceof ModelLoadConfig);
		$this->assertEquals(1, $M->x1);
		$this->assertEquals('a', $M->s1);
		$this->assertNull($M->x2);
	}
	/**
	 * @covers Model::__get
	 * @expectedException \Camarera\MagicGetException
	 */
	function test__getException() {
		$M = TestModelA::serve();
		$M->_foo;
	}

	/**
	 * @covers Model::__set
	 */
	function test__set() {
		$classname = 'TestModelA';
		$data = array('x1'=>1,'s1'=>'a');
		$M = TestModelA::serve(1, $data);

		$this->assertEquals(1, $M->getId());
		$M->ID = 2;
		$this->assertEquals(2, $M->getId());

		$this->assertEquals(1, $M->x1);
		$M->x1 = 3;
		$this->assertEquals(3, $M->x1);

		$this->assertNull($M->x2);
		$M->x2 = 4;
		$this->assertEquals(4, $M->x2);
	}
	/**
	 * @covers Model::__set
	 * @expectedException \Camarera\MagicGetException
	 */
	function test__setException() {
		$M = TestModelA::serve();
		$M->_foo = 1;
	}

	/**
	 * @covers Model::__call
	 */
	function test__call() {
		$classname = 'TestModelA';
		$data = array('x1'=>1,'s1'=>'a');
		$M = TestModelA::serve(1, $data);

		$this->assertEquals(1, $M->x1);
		$M->setX1(2);
		$this->assertEquals(2, $M->x1);

		$this->assertEquals(2, $M->getX1());

		$M->addX1(3);
		$this->assertEquals(5, $M->x1);

	}
	/**
	 * @covers Model::__call
	 * @expectedException MagicCallException
	 */
	function test__callException() {
		$M = TestModelA::serve();
		$M->fooBar();
	}

	/**
	 * @covers Model::_setRaw
	 */
	function test_setRaw() {
		$M = TestModelA::serve();
		$M->x1 = 1;
		$this->assertTrue(1 === $M->x1);
		$M->x1 = '2';
		$this->assertTrue(2 === $M->getValue('x1'));
		$M->_setRaw('x1', '3');
		$M->_setRaw('x1', '4', true);
		$this->assertTrue('3' === $M->getValue('x1'));
		$this->assertTrue('4' === $M->getValue('x1', true));
	}

	/**
	 * @covers Model::getID
	 */
	function testGetID() {
		$M = TestModelA::serve(1);
		$this->assertTrue(1 === $M->getID());

		$M = TestModelAMultiId::serve(array('idx'=>1,'ids'=>'a'));
		$this->assertTrue('1-a' === $M->getID());
	}

	/**
	 * @covers  Model::calculateIdByArray
	 */
	function testCalculateIdByArray() {
		$data = array(
			'_id' => 1,
			'x1' => 2,
		);
		$this->assertTrue(1 === TestModelA::calculateIdByArray($data));
		$data = array(
			'idx' => 2,
			'ids' => 'b',
		);
		$this->assertTrue('2-b' === TestModelAMultiId::calculateIdByArray($data));
	}

	/**
	 * @covers Model::setID
	 */
	function testSetID() {
		$M = TestModelA::serve();
		$this->assertNull($M->getID());
		$M->setID(1);
		$this->assertTrue(1 === $M->getID());
		$M->setID('2');
		$this->assertTrue(2 === $M->getID());

		$M = TestModelAMultiId::serve();
		$this->assertNull($M->getID());
		$M->setID(array('idx'=>1,'ids'=>'a'));
		$this->assertTrue('1-a' === $M->getID());

		$M->setID('3-c');
		$this->assertTrue('3-c' === $M->getID());
		$this->assertTrue(3 === $M->idx);
		$this->assertTrue('c' === $M->ids);
	}

	/**
	 * @covers Model::setID
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage $_idFieldGlue not defined in
	 */
	function testSetIDException1() {
		$M = TestModelAMultiId::serve();
		$R = new \ReflectionProperty('TestModelA', '_idFieldGlue');
		$R->setAccessible(true);
		$R->setValue('');
		$M->setID('1-a');
	}

	/**
	 * @covers Model::setID
	 * @expectedException \InvalidArgumentException
	 */
	function testSetIDException2() {
		$M = TestModelAMultiId::serve();
		$M->setID('1-a');
		$M->setID('2-b-x');
	}

	/**
	 * @covers Model::setID
	 * @expectedException BadMethodCallException
	 */
	function testSetIdException3() {
		$M = TestModelAMultiId::serve();
		$M->setID(new stdClass());
	}

	/**
	 * @covers Model::getValue
	 */
	function testGetValue() {
		$data = array(
			'x1' => 1,
			'x2' => 2,
			's1' => 'a',
			's2' => 'b',
		);
		$M = TestModelA::serve($data);
		$this->assertTrue($data === $M->getValue());
		$this->assertTrue(2 === $M->getValue('x2'));
		$this->assertTrue(array('x1'=>1,'s1'=>'a') === $M->getValue(array('x1','s1')));
		$this->assertTrue(array('x1'=>1,'s1'=>'a') === $M->getValue(array('x1','s1','a')));
	}

	/**
	 * @covers Model::getValue
	 * @expectedException Camarera\MagicGetException
	 */
	function testGetValueException1() {
		$data = array(
			'x1' => 1,
			'x2' => 2,
			's1' => 'a',
			's2' => 'b',
		);
		$M = TestModelA::serve($data);
		$M->getValue('a');
	}

	/**
	 * @covers Model::getValue
	 * @expectedException \BadMethodCallException
	 */
	function testGetValueException2() {
		$data = array(
			'x1' => 1,
			'x2' => 2,
			's1' => 'a',
			's2' => 'b',
		);
		$M = TestModelA::serve($data);
		$M->getValue(new stdClass());
	}

	/**
	 * @covers Model::setValue
	 * @covers Model::_setValue
	 * @covers Model::_setValues
	 */
	function testSetValue() {
		$M = TestModelA::serve();
		$this->assertEquals(array(), $M->getValue());
		$M->setValue('x1',1);
		$this->assertEquals(array('x1'=>1), $M->getValue());
		$M->setValue(array('x2'=>2,'s1'=>'a'));
		$this->assertEquals(array('x1'=>1,'x2'=>2,'s1'=>'a'), $M->getValue());
		$M->setValue(array('x2'=>2,'s1'=>'a'), true);
		$this->assertEquals(array('x2'=>2,'s1'=>'a'), $M->getValue());
	}

	/**
	 * @covers Model::setValue
	 * @covers Model::_setValue
	 * @covers Model::_setValues
	 * @expectedException \Camarera\MagicSetException
	 */
	function testSetValueException1() {
		$M = TestModelA::serve();
		$M->setValue('x3',1);
	}

	/**
	 * @covers Model::setValue
	 * @covers Model::_setValue
	 * @covers Model::_setValues
	 * @expectedException \Camarera\MagicSetException
	 */
	function testSetValueException2() {
		$M = TestModelA::serve();
		$M->setValue('x3',1,false);
	}

	/**
	 * @covers Model::_setValues
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage argument not array
	 */
	function test_setValuesException1() {
		$M = TestModelA::serve();
		$M->_setValues(null);
	}

	/**
	 * @covers Model::_setValues
	 * @expectedException \Camarera\MagicSetException
	 * &expectedExceptionMessage property x3 does not exist
	 */
	function test_setValuesException2() {
		$M = TestModelA::serve();
		$M->_setValues(array('x1'=>1,'x3'=>3));
	}

	/**
	 * @covers Model::_setValues
	 */
	function test_setValuesException3() {
		$M = TestModelA::serve();
		$M->_setValues(array('x1'=>1,'x3'=>3), false, false);
	}

	/**
	 * @covers Model::addValue
	 */
	function testAddValue() {
		$data = array(
			'x1' => 1,
		);
		$M = TestModelA::serve($data);
		$this->assertTrue(1 === $M->x1);
		$M->addValue('x1', 2);
		$this->assertTrue(3 === $M->x1);
	}

	/**
	 * @covers Model::addValue
	 * @expectedException \UnImplementedException
	 */
	function testAddValueException1() {
		$M = TestModelA::serve();
		$M->addValue('x1', 1, 1);
	}

	/**
	 * @covers Model::addValue
	 * @expectedException \BadMethodCallException
	 */
	function testAddValueException2() {
		$M = TestModelA::serve();
		$M->addValue(1, 1);
	}

	/**
	 * @covers Model::addValue
	 * @expectedException \InvalidArgumentException
	 */
	function testAddValueException3() {
		$M = TestModelA::serve();
		$M->addValue('x3', 1);
	}

	/**
	 * @covers Model::setStoredValues
	 */
	function testSetStoredValues() {
		$data = array(
			'x1' => 1,
			'x2' => 2,
		);
		$M = TestModelA::serve($data);
		$R = new \ReflectionProperty($M, '_storedValues');
		$R->setAccessible(true);
		$this->assertTrue(array() === $R->getValue($M));
		$M->setStoredValues();
		$this->assertTrue($data === $R->getValue($M));
	}

	/**
	 * @covers Model::isDirty
	 */
	function testIsDirty() {
		$M = TestModelA::serve();
		$this->assertFalse($M->isDirty());
		$M->x1 = 1;
		$this->assertTrue($M->isDirty());
		$M->setStoredValues();
		$this->assertFalse($M->isDirty());
		$M->x1 = 2;
		$this->assertTrue($M->isDirty());
	}

	/**
	 * @covers Model::isFieldDirty
	 */
	function testIsFieldDirty() {
		$data = array(
			'x1' => 1,
			'x2' => 2,
		);
		$M = TestModelA::serve($data);
		$this->assertTrue($M->isFieldDirty('x1'));

		$M->setStoredValues();
		$this->assertFalse($M->isFieldDirty('x1'));
		$M->x1 = 2;
		$this->assertTrue($M->isFieldDirty('x1'));
		$M->setStoredValues();
		$this->assertFalse($M->isFieldDirty('x1'));

		$this->assertEquals(array('x1'=>false,'x2'=>false), $M->isFieldDirty(array('x1','x2')));
		$M->x1 = 3;
		$this->assertEquals(array('x1'=>true,'x2'=>false), $M->isFieldDirty(array('x1','x2')));
		$M->x2 = 3;
		$this->assertEquals(array('x1'=>true,'x2'=>true), $M->isFieldDirty(array('x1','x2')));
	}

	/**
	 * @covers Model::isFieldDirty
	 * @expectedException \BadMethodCallException
	 */
	function testIsFieldDirtyException() {
		$M = TestModelA::serve();
		$M->isFieldDirty(null);
	}

	/**
	 * @covers Model::valuesContain
	 */
	function testValuesContain() {
		$data = array(
			'x1' => 1,
			'x2' => 2,
			's1' => 'a',
		);
		$M = TestModelA::serve($data);
		$this->assertTrue($M->valuesContain(array('x1'=>1)));
		$this->assertTrue($M->valuesContain(array('x1'=>1,'x2'=>2)));
		$this->assertFalse($M->valuesContain(array('x1'=>1,'x2'=>3)));
		$this->assertTrue($M->valuesContain(array('x1'=>1,'x2'=>2,'s1'=>'a')));
		$this->assertFalse($M->valuesContain(array('x1'=>1,'x2'=>3,'s1'=>'a')));
		$this->assertFalse($M->valuesContain(array('x1'=>1,'x2'=>2,'s1'=>'b')));
	}

	/**
	 * @covers Model::store
	 */
	function testStore() {
		$M = TestModelA::serve();
		$Store = $M::store(\Model::STORE_READ);
		$this->assertInstanceOf('Store', $Store);
		$Store = $M::store(\Model::STORE_WRITE);
		$this->assertInstanceOf('Store', $Store);

		$R = new \ReflectionProperty($M, '_storeRead');
		$R->setAccessible(true);
		$this->assertNotEquals('foo', $R->getValue($M));
		$M->store(\Model::STORE_READ, 'foo');
		$this->assertEquals('foo', $R->getValue($M));

		$R = new \ReflectionProperty($M, '_storeWrite');
		$R->setAccessible(true);
		$this->assertNotEquals('foo', $R->getValue($M));
		$M->store(\Model::STORE_WRITE, 'foo');
		$this->assertEquals('foo', $R->getValue($M));
	}

	/**
	 * @covers Model::store
	 * @expectedException InvalidArgumentException
	 */
	function testStoreException() {
		$M = TestModelA::serve();
		$M->store(null);
	}

	/**
	 * @covers Model::_getStore
	 */
	function test_getStore() {
		$M = TestModelA::serve();

		$R = new \ReflectionProperty($M, '_storeRead');
		$R->setAccessible(true);
		$this->assertEquals('default', $R->getValue($M));

		$S = $M->store(\Model::STORE_READ);
		$this->assertInstanceOf('Store', $S);
		$this->assertNotEquals('default', $R->getValue($M));
		$this->assertEquals($S, $R->getValue($M));

		$this->assertEquals($S, $M->store(\Model::STORE_READ));

		$R = new \ReflectionProperty($M, '_storeWrite');
		$R->setAccessible(true);
		$this->assertEquals('default', $R->getValue($M));

		$S = $M->store(\Model::STORE_WRITE);
		$this->assertInstanceOf('Store', $S);
		$this->assertNotEquals('default', $R->getValue($M));
		$this->assertEquals($S, $R->getValue($M));

		$this->assertEquals($S, $M->store(\Model::STORE_WRITE));

	}

	/**
	 * @covers Model::_getStore
	 * @expectedException \InvalidArgumentException
	 */
	function test_getStoreException() {
		$M = TestModelA::serve();
		$M->store('foo');
	}

	/**
	 * @covers Model::_setStore
	 */
	function test_setStore() {
		$M = TestModelA::serve();
		$S = $M->store(\Model::STORE_READ);

		$R = new \ReflectionProperty($M, '_storeRead');
		$R->setAccessible(true);
		$this->assertEquals($S, $R->getValue($M));

		$M->_setStore(\Model::STORE_READ, 'default');
		$this->assertEquals('default', $R->getValue($M));

		$M->_setStore(\Model::STORE_READ, $S);
		$this->assertEquals($S, $R->getValue($M));


		$S = $M->store(\Model::STORE_WRITE);

		$R = new \ReflectionProperty($M, '_storeWrite');
		$R->setAccessible(true);
		$this->assertEquals($S, $R->getValue($M));

		$M->_setStore(\Model::STORE_WRITE, 'default');
		$this->assertEquals('default', $R->getValue($M));

		$M->_setStore(\Model::STORE_WRITE, $S);
		$this->assertEquals($S, $R->getValue($M));
	}

	/**
	 * @covers Model::_setStore
	 * @expectedException \BadMethodCallException
	 */
	function test_setStoreException1() {
		$M = TestModelA::serve();
		$M->store(\Model::STORE_READ, new stdClass());
	}

	/**
	 * @covers Model::_setStore
	 * @expectedException \InvalidArgumentException
	 */
	function test_setStoreException2() {
		$M = TestModelA::serve();
		$M->store('foo', 'default');
	}

	/**
	 * @covers Model::load
	 */
	function testLoad() {

		$FixtureStore = \TestModelAStoreFixture::serve();

		$M = TestModelA::serve(TestModelAStoreFixture::CASE_LOAD_RETURN_NORMAL);
		$M->store(\Model::STORE_READ, $FixtureStore);
		$ret = $M->load();
		$this->assertTrue($ret);
		$this->assertEquals(99, $M->x1);

		$M = TestModelA::serve(TestModelAStoreFixture::CASE_LOAD_RETURN_NULL);
		$M->store(\Model::STORE_READ, $FixtureStore);
		$ret = $M->load();
		$this->assertNull($ret);

		$M = TestModelA::serve(TestModelAStoreFixture::CASE_LOAD_RETURN_FALSE);
		$M->store(\Model::STORE_READ, $FixtureStore);
		$ret = $M->load();
		$this->assertFalse($ret);

	}

	/**
	 * @covers Model::save
	 */
	function testSave() {
		$this->markTestIncomplete();
	}

}
