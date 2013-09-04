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
 * @since 1.0
 * @license DWTFYWT
 * @version 1.01
 */
namespace Camarera;

/**
 * ModelInfoManager is an info registry about Model metadata
 * @todo after initial development, check if extending the Model class is still needed??? (for accessing protected data)
 *
 * @author t
 * @package Camarera\Model
 * @since 1.1
 * @version 1.1
 *
 */
class ModelInfoManager extends Model {

	/**
	 * @var string[] these fieldnames must not be used because their getter/setter methods would collide with other
	 * 		\Model methods eg. getValue() If you have to store values on these fieldnames, map them so they have
	 * 		different PHP property name but this store field name
	 */
	protected static $_reservedFieldNames = array(
		'value', 'Value',
		'values', 'Values',
		'storedValues', 'StoredValues',
		'store', 'Store',
	);

	/**
	 * @var string[] name of classes which has been inflated already
	 */
	protected static $_inflatedClassnames = array();

	/**
	 * @var \Field[] I contain arrays of FieldXxx object instances for a given classname. The classname is the 1st index,
	 *		2nd is fieldname
	 */
	protected static $_fields = array();

	/**
	 * @var array[] I contain strings or arrays of strings, these are actual field names which make up the id field. If
	 * 		the id field consists of just one field, it will be eg. array('_id'). First index is classname, values are
	 * 		one string on one unindexed array of strings
	 */
	protected static $_idFieldNames = array();

	/**
	 * @var string[] strings of default store table names indexed by classname
	 */
	protected static $_storeTables = array();

	/**
	 * @var string[] names of related collection classes, indexed by model classname
	 */
	protected static $_collectionClassnames = array();

	/**
	 * I take a config array of field definitions and convert them to real FieldXxx objects
	 * @param string $classname
	 * @param array $fields
	 * @param array $idFieldnames staitc::$_idFieldnames from model class definition
	 * @throws \Exception|\InvalidArgumentException
	 * @throws \InvalidArgumentException
	 * @throws \ClassDefinitionException
	 */
	public static function inflate($classname, $fields, $idFieldnames, $storetable, $collectionClassname) {

		if (isset(static::$_inflatedClassnames[$classname])) {
			throw new \RuntimeException('classname ' . $classname . ' already inflated');
		}

		if (empty($fields) || !is_array($fields)) {
			throw new \ClassDefinitionException('initial field definition array is empty');
		}

		// inflate field objects
		foreach ($fields as $eachFieldname=>&$eachField) {

			$fieldnameToCheck = is_string($eachField) ? $eachField : $eachFieldname;

			if (in_array($fieldnameToCheck, static::$_reservedFieldNames, true)) {
				throw new \ClassDefinitionException('fieldname ' . $eachFieldname . ' is reserved and thus forbidden to use');
			}

			if (!preg_match($classname::FIELD_NAME_PATTERN, $fieldnameToCheck)) {
				throw new \ClassDefinitionException(
					'field name ' . $eachFieldname . ' does not match field name pattern: ' . $classname::FIELD_NAME_PATTERN
				);
			}

			// $eachField may already be a FieldXxx object, otherwise must be an array
			if (is_object($eachField) && ($eachField instanceof \Field));
			elseif (is_array($eachField)) {

				if (!empty($eachField['classname'])) {
					$fieldClassname = $eachField['classname'];
				}
				elseif (!empty($eachField['type'])) {
					$fieldClassname = '\Field' . ucFirst($eachField['type']);
				}
				else {
					throw new \ClassDefinitionException(
						'neither "classname" nor "type" is set in ' . $classname . ' field ' . $eachFieldname
					);
				};

				try {
					$eachField['fieldName'] = $eachFieldname;
					$eachField = $fieldClassname::serve($eachField);
				}
				catch (\InvalidArgumentException $e) {
					if (preg_match('/the field (.+) does not exist/', $e->getMessage())) {
						$msg = 'undefined config property found while inflating class ' . $classname . ' see previous exception for details';
						$e = new \ClassDefinitionException($msg, 0, $e);
					}
					throw $e;
				}


			}
			// allow shorthand declarations of just fieldname instead of config array
			elseif (is_string($eachField) && is_numeric($eachFieldname)) {
				$fields[$eachField] = \FieldString::serve(array('type'=>'string', 'fieldName' => $eachField));
				unset($fields[$eachFieldname]);
			}
			else {
				throw new \ClassDefinitionException('invalid field def in ' . $classname . ' field ' . $eachFieldname);
			}

		}

		$idFieldnames = !empty($idFieldnames) && (is_string($idFieldnames) || is_array($idFieldnames))
			? $idFieldnames
			: \Camarera::conf(('Field.id.name'));
		static::$_idFieldNames[$classname] = $idFieldnames;

		// if ID field is a single field, and missing, add it
		if (is_string($idFieldnames) && !array_key_exists($idFieldnames, $fields)) {
			$fields = array_reverse($fields, true);
			$fieldConfig = array(
				'type' => 'integer',
			);
			$fieldClassname = \Camarera::conf('Field.id.class');
			$fieldConfig['fieldName'] = $idFieldnames;
			$Field = $fieldClassname::serve($fieldConfig);
			$fields[$idFieldnames] = $Field;
			$fields = array_reverse($fields, true);
		}
		// else check if all ID fields exist
		elseif (is_array($idFieldnames)) {
			$missingFields = array();
			foreach ($idFieldnames AS $eachIdFieldname) {
				if (!array_key_exists($eachIdFieldname, $fields)) {
					$missingFields[] = $eachIdFieldname;
				}
			}
			if (!empty($missingFields)) {
				throw new \ClassDefinitionException(
					'fields ' . implode(',', $missingFields) . ' are ID fields but not defined in ' . $classname
				);
			}
		};

		static::$_fields[$classname] = $fields;

		if (empty($storetable)) {
			$storetable = \Util::camelCaseToUnderscores($classname);
		}
		static::$_storeTables[$classname] = $storetable;

		static::$_inflatedClassnames[$classname] = true;

		if (!is_null($collectionClassname));
		elseif (class_exists($collectionClassname = $classname . 'Collection'));
		else {
			$collectionClassname = 'Collection';
		}
		static::$_collectionClassnames[$classname] = $collectionClassname;

	}

	/**
	 * I return if class $classname has been inflated already or not. Cheking this first eliminates the pass-around of
	 * 		all the variables inflate() needs
	 * @param $classname
	 * @return bool
	 */
	public static function isInflated($classname) {
		$ret = !empty(self::$_inflatedClassnames[$classname]);
		return $ret;
	}

	/**
	 * I return one field, a set of fields, or all fields. These are the field objects generated by inflate()
	 * @param string $classname
	 * @param null|string|string[] $field null-all fields, string-one field, array-some fields
	 * @return null|Field|Field[]
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	public static function getField($classname, $fieldnames=null) {
		if (!isset(static::$_fields[$classname])) {
			throw new \RuntimeException('class ' . $classname . ' hasn\'t been inflated');
		}
		if (is_null($fieldnames)) {
			return static::$_fields[$classname];
		}
		elseif (is_string($fieldnames)) {
			return empty(self::$_fields[$classname][$fieldnames])
				? null
				: self::$_fields[$classname][$fieldnames];
		}
		elseif (is_array($fieldnames) && count($fieldnames)) {
			$ret = array();
			foreach ($fieldnames as $eachField) {
				$ret[$eachField] = static::$_fields[$classname][$eachField];
			}
			return $ret;
		}
		throw new \InvalidArgumentException('invalid $fieldnames sent');
	}

	/**
	 * I return one string or array of id fieldname(s) in class $classname
	 * @param string $classname
	 * @return string|array
	 * @throws \RuntimeException
	 */
	public static function getIdFieldname($classname) {
		if (!isset(static::$_idFieldNames[$classname])) {
			throw new \RuntimeException('class ' . $classname . ' hasn\'t been inflated');
		}
		return static::$_idFieldNames[$classname];
	}

	/**
	 * I return the default store table in class $classname
	 * @param $classname
	 * @return array|string
	 * @throws \RuntimeException
	 */
	public static function getStoreTable($classname) {
		if (!isset(static::$_storeTables[$classname])) {
			throw new \RuntimeException('class ' . $classname . ' hasn\'t been inflated');
		}
		return static::$_storeTables[$classname];
	}

	/**
	 * I return a collection class name that can handle these models
	 * @param $classname
	 * @return string
	 */
	public static function getCollectionClassname($classname) {
		return static::$_collectionClassnames[$classname];
	}

}
