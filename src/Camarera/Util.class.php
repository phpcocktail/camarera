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
 * Some utility methods
 * @author t
 * @package Camarera
 * @version 1.01
 */
class Util {

	//////////////////////////////////////////////////////////////////////////
	// STRING
	/////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * I convert a camelcase string to all lowercase with underscores
	 *	eg. thisCamelCase will be this_camel_case
	 * @param string $camelString
	 * @return type
	 */
	public static function camelCaseToUnderscores($camelString) {
		$underscores = '';
		if (!is_string($camelString)) {
			trigger_error('Camarera::camelCaseToUnderScores() expects parameter of string', E_USER_WARNING);
		}
		else {
			for ($i=0; $i<mb_strlen($camelString); $i++) {
				if (preg_match('/[A-Z]/', $camelString[$i])) {
					$underscores.= ($i == 0) || (mb_substr($underscores, -1) == '_')
						? '' : '_';
					$underscores.= mb_strtolower($camelString[$i]);
				}
				else {
					$underscores.= $camelString[$i];
				}
			}
		}
		return $underscores;
	}

	/**
	* I convert an underscore string to camelcase eg. this_camel_case will be thisCamelCase
	* @param type $underScoreString
	* @return type
	*/
	public static function underScoresToCamelCase($underScoreString) {
		$camels = '';
		if (!is_string($underScoreString)) {
			trigger_error('Camarera::underScoresToCamelCase() expects parameter of string', E_USER_WARNING);
		}
		else {
			for ($i=0; $i<mb_strlen($underScoreString); $i++) {
				if (($underScoreString[$i] == '_') && mb_strlen($camels)) {
					$i++;
					if ($i >= mb_strlen($underScoreString)) {
						break;
					}
					$camels.= mb_strtoupper($underScoreString[$i]);
				}
				else {
					$camels.= $underScoreString[$i];
				}
			}
		}
		return $camels;
	}

	/**
	 * I will return the first part of a camelcase string or the string itself if there is just one part.
	 * eg. 'Camelcase'=>'Camelcase', 'CamelCase'=>'Camel', 'camelCase'=>'camel', 'camelcase'=>'camelcase'
	 * @param string $camelcaseString
	 * @return string
	 */
	public static function firstCamelPart($camelcaseString) {
		for ($i=1; $i<mb_strlen($camelcaseString); $i++) {
			if (mb_strtoupper($camelcaseString[$i]) === $camelcaseString[$i]) {
				return mb_substr($camelcaseString, 0, $i);
			}
		}
		return $camelcaseString;
	}

	/**
	 * I will put a slash '/' (or specified string) in front of all uppercase letters in a string
	 * @param string $camelcaseString
	 */
	public static function camelcaseToSlashes($camelcaseString, $slashString='/') {
		$ret = '';
		for($i=0; $i<mb_strlen($camelcaseString); $i++) {
			if (preg_match('/^[A-Z]$/', $camelcaseString[$i])) {
				$ret.= $slashString;
			}
			$ret.= $camelcaseString[$i];
		}
		return $ret;
	}

	/**
	 * I strip off a camel part off the end of $camelCaseString (eg. CamelCaseString => CamelCase)
	 * @param string $camelCaseString
	 * @return string
	 */
	public static function stripCamelPart($camelCaseString) {
		$ords = array(
			'a' => 97,
			'z' => 122,
			'A' => 65,
			'Z' => 90,
		);
		while (mb_strlen($camelCaseString)) {
			$last = ord(mb_substr($camelCaseString, -1));
			if (($last>=$ords['a']) && ($last<=$ords['z'])) {
				$camelCaseString = mb_substr($camelCaseString, 0, -1);
				continue;
			}
			elseif (($last>=$ords['A']) && ($last<=$ords['Z'])) {
				$camelCaseString = mb_substr($camelCaseString, 0, -1);
				break;
			}
			break;
		};
		return $camelCaseString;
	}

	/**
	 * I make sure $content is printable by echo. Won't touch scalars, will convert array and object with toString()
	 *	method, otherwise return null.
	 * @param mixed $content
	 * @return mixed
	 */
	public static function toString($content) {
		if (is_scalar($content)) {
			$content = '' . $content;
		}
		elseif (is_array($content)) {
			foreach ($content as &$eachContent) {
				$eachContent = static::toString($eachContent);
			}
			$content = implode($content);
		}
		elseif (is_object($content) && method_exists($content, 'toString')) {
			$content = $content->toString();
		}
		else {
			$content = null;
		}
		return $content;
	}

	//////////////////////////////////////////////////////////////////////////
	// ARRAY
	/////////////////////////////////////////////////////////////////////////////////////////

	protected static function _arrayMergeRecursive(&$a1, &$a2) {
		$ret = $a1;
		foreach ($a2 as $eachKey=>$eachVal) {
			if (is_array($eachVal) && isset($ret[$eachKey]) && is_array($ret[$eachKey])) {
				$ret[$eachKey] = static::_arrayMergeRecursive($ret[$eachKey], $eachVal);
			}
			else {
				$ret[$eachKey] = $eachVal;
			}
		}
		return $ret;
	}

	public static function arrayMergeRecursive(&$a1, &$a2) {
		return static::_arrayMergeRecursive($a1, $a2);
	}

	//////////////////////////////////////////////////////////////////////////
	// FILESYS
	/////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * I process one folder, calling myself recursively on subfolders
	 * @param string $path
	 * @return array
	 */
	public static function _scanDirRecursive($path) {
		$entries = array_diff(scandir($path), array('.', '..'));
		foreach ($entries as $eachEntryKey=>$eachEntry) {
			$eachSubPath = $path . '/' . $eachEntry;
			if (is_dir($eachSubPath)) {
				unset($entries[$eachEntryKey]);
				$entries[$eachEntry] = static::_scanDirRecursive($eachSubPath);
			}
		}
		return $entries;
	}

	/**
	 * I scan a folder for entries and return an array of them (multi dimensional if recursive specified)
	 * @param string $path path to folder to scan
	 * @param bool $recusive false = return simple array of folder entries, no distinction between files and subfolders
	 * 		true = return multidimensional array where files are string entries while subfolders are arrays of entries
	 * 		recursively
	 * @return array
	 */
	public static function scanDir($path, $recusive=true) {
		return $recusive
			? array($path => static::_scanDirRecursive($path))
			: scandir($path);
	}

}
