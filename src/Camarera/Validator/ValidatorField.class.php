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
 */
namespace Camarera;

/**
 * ValidatorField is a set of methodsfor the field layer of validation. Each method should accept a value and zero or
 * 		more params and return true or false (and must not modify input value).
 * validators in this class can be referenced easily in the model's field def as eg.
 * 		'validators' => array(
 * 			'minVal' => 1,
 * 			'maxLength' => 2,
 * 			'notIn' => array(3,4,5),
 * 			array('notEquals', 6),
 * 			array('notEquals', 7),
 * 			array('notIn', 8, 9, 10),
 * 		),
 * all validators which does not state a classname and not found in the ValidatorField class will be delegated (to model
 * 		level validation)
 * to define your own field validator methods:
 *		extend ValidatorField in root namespace and add your methods, OR
 *		create your arbitrary class and reference it by eg. 'MyValidator::validate' => 1
 * 		AND define a constant for easy autocomplete access
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera\Validator
 * @version 1.1
 *
 */
class ValidatorField {

	// some validators to be delegated, for autocomplete
	const UNIQUE = 'unique';
	const UNIQUEWITH = 'uniqueWith';

	// NUMERIC VALIDATORS
	const MINVAL = 'minVal';
	public function minVal($value, $minVal) {
		return $value >= $minVal;
	}

	const MAXVAL = 'maxVal';
	public function maxVal($value, $maxVal) {
		return $value<= $maxVal;
	}

	const BETWEEN = 'between';
	public function between($value, $between, $inclusive=false) {
		if ($inclusive) {
			$ret = ($value >= reset($between)) && ($value <= end($between));
		}
		else {
			$ret = ($value > reset($between)) && ($value < end($between));
		}
		return $ret;
	}

	const EVEN = 'even';
	public function even($value) {
		return $value % 2 == 0;
	}

	const MULTIPLEOF = 'multipleOf';
	public function multipleOf($value, $divider) {
		return $value % $divider == 0;
	}

	const NEGATIVE = 'negative';
	public function negative($value) {
		return $value < 0;
	}

	const POSITIVE = 'positive';
	public function positive($value) {
		return $value > 0;
	}

	// STRING
	const MINLENGTH = 'minLength';
	public function minLength($value, $minLength) {
		return mb_strlen(\Util::toString($value)) >= $minLength;
	}

	const MAXLENGTH = 'maxLength';
	public function maxLength($value, $maxLength) {
		return mb_strlen(\Util::toString($value)) <= $maxLength;
	}

	const ALNUM = 'alnum';
	public function alnum($value) {
		return preg_match('/^[a-zA-Z0-9]*$/', $value) ? true : false;
	}

	const ALPHA = 'alpha';
	public function alpha($value) {
		return preg_match('/^[a-zA-Z]*$/', $value) ? true : false;
	}

	const DIGIT = 'digit';
	public function digit($value) {
		return preg_match('/^[0-9]*$/', $value) ? true : false;
	}

	const LOWERCASE = 'lowerCase';
	public function lowerCase($value) {
		return preg_match('/^\p{Lowercase_Letter}*$/', $value) ? true : false;
	}

	const UPPERCASE = 'upperCase';
	public function upperCase($value) {
		return preg_match('/^\p{Uppercase_Letter}*$/', $value) ? true : false;
	}

	const REGEXP = 'regexp';
	public function regexp($value, $pattern) {
		return preg_match($pattern, $value) ? true : false;
	}

	const NOWHITESPACE = 'noWhiteSpace';
	public function noWhiteSpace($value) {
		return preg_match('/^[\s]*$/', $value) ? false : true;
	}

	const SLUG = 'slug';
	public function slug($value) {
		return (!preg_match('/^[a-z0-9\-]*$/', $value) ||
			preg_match('/^-|--|-$/', $value)) ? false : true;
	}

	const STARTSWITH = 'startsWith';
	public function startsWith($value, $startsWith) {
		return strpos($value, $startsWith) === 0;
	}

	const ENDSWITH = 'endsWith';
	public function endsWith($value, $endsWith) {
		return mb_strpos($value, $endsWith) === (mb_strlen($value) - mb_strlen($endsWith));
	}

	const VERSION = 'version';
	public function version($value, $minVersion=null) {
		if (!is_null($minVersion)) {
			die('@todo implement this');
		}
		return preg_match('/^[0-9](\.[0-9]+)*$/', $value) ? true : false;
	}


	// LENGTH, QUANTITY
	const MINCOUNT = 'minCount';
	public function minCount($value, $minCount) {
		if (is_null($value)) {
			$ret = true;
		}
		elseif (!is_array($value)) {
			$ret = false;
		}
		else {
			$ret = count($value) >= $minCount;
		}
		return $ret;
	}

	const MAXCOUNT = 'maxCount';
	public function maxCount($value, $maxCount) {
		if (is_null($value)) {
			$ret = true;
		}
		elseif (!is_array($value)) {
			$ret = false;
		}
		else {
			$ret = count($value) <= $maxCount;
		}
		return $ret;
	}

	const MINOCCURRENCE = 'minOccurrence';
	public function minOccurrence($value, $needle, $minCount) {
		if (is_string($value)) {
			$ret = count(explode($needle, $value)) - 1 >= $minCount;
		}
		elseif (is_array($value)) {
			$count = 0;
			foreach ($value as $eachValue) {
				$count+= $eachValue == $needle ? 1 : 0;
			}
			$ret = $count >= $minCount;
		}
		else {
			$ret = false;
		}
		return $ret;
	}

	const MAXOCCURRENCE = 'maxOccurrence';
	public function maxOccurrence($value, $needle, $maxCount) {
		if (is_string($value)) {
			$ret = count(explode($needle, $value)) - 1 <= $maxCount;
		}
		elseif (is_array($value)) {
			$count = 0;
			foreach ($value as $eachValue) {
				$count+= $eachValue == $needle ? 1 : 0;
			}
			$ret = $count >= $maxCount;
		}
		else {
			$ret = false;
		}
		return $ret;
	}

	// VALUE CONTENT
	const MPTY = 'mpty';
	public function mpty($value) {
		return empty($value);
	}

	/**
	 * @var string I am a synonim for NOTNULL
	 */
	const REQUIRED = 'required';
	/**
	 * I am a synonim for notNull()
	 * @param mixed
	 * @return bool
	 */
	public function required($value) {
		return !is_null($value);
	}

	const NOTNULL = 'notNull';
	public function notNull($value) {
		return !is_null($value);
	}

	const EQUALS = 'equals';
	public function equals($value, $equals) {
		return $value == $equals;
	}

	const EXACTEQUALS = 'exactEquals';
	public function exactEquals($value, $exactEquals) {
		return $value === $exactEquals;
	}

	const IN = 'in';
	public function in($value, $values) {
		return in_array($value, $values);
	}

	const EXACTIN = 'exactIn';
	public function exactIn($value, $values) {
		return in_array($value, $values, true);
	}

	const CONTAINS = 'contains';
	public function contains($value, $contains) {
		$value = \Util::toString($value);
		$contains = \Util::toString($contains);
		return mb_strpos($value, $contains) === false;
	}

	// VALUE TYPE
	const NUMEIRC = 'numeric';
	public function isNumeric($value) {
		return is_numeric($value);
	}

	const ISSTRING = 'isString';
	public function isString($value) {
		return is_string($value);
	}

	const JSON = 'json';
	public function json($value) {
		return is_null(@json_decode($value));
	}

	// LOGIC AND AGREGATE
	const ALWAYS = 'always';
	public function always($value) {
		return true;
	}

	const NEVER = 'never';
	public function never($value) {
		return false;
	}

	const ANYOF = 'anyOf';
	public function anyOf($value, $validatorDefinitions) {
		die('@todo implement');
	}

	const ALLOF = 'allOf';
	public function allOf($value, $validatorDefinitions) {
		die('@todo implement');
	}

	const NONEOF = 'noneOf';
	public function noneOf($value, $validatorDefinitions) {
		die('@todo implement');
	}

	const LEASTOF = 'leastOf';
	public function least($value, $validatorDefinitions) {
		die('@todo implement');
	}

	const MOSTOF = 'mostOf';
	public function mostOf($value, $validatorDefinitions) {
		die('@todo');
	}

	const XOF = 'xof';
	public function xof($value, $numberOfValids, $validatorDefinitions) {
		die('@todo');
	}

}

