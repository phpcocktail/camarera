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
 * Validator is just a common abstract with some definitions
 *
 * @author t
 * @license DWTFYWT
 * @package Camarera\Validator
 * @version 1.1
 *
 */
class Validator {

	/**
	 * @var bool if true, validator's value will be negated
	 */
	const OPTION_NEGATED = 'negated';
	/**
	 * @var bool if true, further validations in current scope will be skipped if current one fails
	 */
	const OPTION_STOP_ON_ERROR = 'stopOnError';
	/**
	 * @var bool current validation will be skipped if there was already an error in the current scope
	 */
	const OPTION_SKIP_ON_ERROR = 'skipOnError';
	/**
	 * @var string custom error message can be set
	 */
	const OPTION_CUSTOM_MESSAGE = 'customMessage';

	/**
	 * @return string[] I return all possible config key in validator
	 */
	public static function validValidatorOptionsKeys() {
		return array(
			self::OPTION_NEGATED,
			self::OPTION_STOP_ON_ERROR,
			self::OPTION_SKIP_ON_ERROR,
			self::OPTION_CUSTOM_MESSAGE,
		);
	}

	/**
	 * @param $EachField
	 * @param $validatorClassname
	 * @return array
	 * @throws \ClassDefinitionException
	 */
	public static function inflateValidators(
		\Field $Field,
		$validatorClassname
	) {

		$inflatedValidators = array();
		$delegatedValidators = array();
		$validOptionsKeys = \Validator::validValidatorOptionsKeys();

		foreach ($Field->validators as $eachIndex=>$eachValidator) {

			// first, make a common format of array('callback'=>...,'params'=>...,'options'=>...)

			// 'unique'
			if (is_numeric($eachIndex) && is_scalar($eachValidator)) {
				$eachValidator = array(
					'callback' => $eachValidator,
					'params' => array(),
					'options' => array(),
				);
			}
			// 'unique'=>true
			elseif (is_scalar($eachIndex) && is_bool($eachValidator)) {
				$eachValidator = array(
					'callback' => $eachIndex,
					'params' => array(),
					'options' => array(),
				);
			}
			// array(...)
			elseif (is_numeric($eachIndex) && is_array($eachValidator)) {

				$keys = array_keys($eachValidator);

				// array($callback, $params, $options)
				if ((count($eachValidator) == 3) &&
					is_numeric($keys[0]) &&
					is_numeric($keys[1]) && is_array($eachValidator[1]) &&
					is_numeric($keys[2]) && is_array($eachValidator[2])
				) {
					$eachValidator = array(
						'callback' => $eachValidator[0],
						'params' => $eachValidator[1],
						'options' => $eachValidator[2],
					);
				}
				// array($callback, $param)
				elseif ((count($eachValidator) == 2) &&
					is_numeric($keys[0]) &&
					is_numeric($keys[1]) && is_scalar($eachValidator[1])
				) {
					$eachValidator = array(
						'callback' => $eachValidator[0],
						'params' => array($eachValidator[1]),
						'options' => array(),
					);
				}
				// array($callback, $params)
				elseif ((count($eachValidator) == 2) &&
					is_numeric($keys[0]) &&
					is_numeric($keys[1])
				) {
					$eachValidator = array(
						'callback' => $eachValidator[0],
						'params' => $eachValidator[1],
						'options' => array(),
					);
				}
				// array('callback'=>...,'params'=>..., 'options'=>...)
				elseif ((count($eachValidator) == 3) &&
					($keys == array('callback', 'params', 'options')) &&
					is_array($eachValidator['params']) &&
					is_array($eachValidator['options'])
				){}
				// array('callback'=>...,'params'=>...
				elseif ((count($eachValidator) == 2) &&
					($keys == array('callback', 'params')) &&
					is_array($eachValidator['params'])
				) {
					$eachValidator['options'] = array();
				}
				else {
					$allNumeric = true;
					foreach ($eachValidator as $eachKey=>$trash) {
						if (!is_numeric($eachKey)) {
							$allNumeric = false;
							break;
						}
					}
					if ($allNumeric && count($eachValidator)) {
						$eachValidator = array(
							'callback' => array_shift($eachValidator),
							'params' => $eachValidator,
							'options' => array(),
						);
					}
					else {
						throw new \ClassDefinitionException;
					}
				}
			}
			// 'minVal' => 1
			elseif (is_scalar($eachIndex) && is_scalar($eachValidator)) {
				$eachValidator = array(
					'callback' => $eachIndex,
					'params' => array($eachValidator),
					'options' => array(),
				);
			}
			// 'between' => array(2,3)
			elseif (is_scalar($eachIndex) && is_array($eachValidator)) {
				$eachValidator = array(
					'callback' => $eachIndex,
					'params' => $eachValidator,
					'options' => array(),
				);
			}
			// unrecognized format
			else {
				throw new \ClassDefinitionException;
			}

			// if callback is string, check if first character is ! for negation
			if (is_string($eachValidator['callback']) &&
				($eachValidator['callback'][0] == '!')
			) {
				$eachValidator['options']['negated'] = true;
				$eachValidator['callback'] = mb_substr($eachValidator['callback'], 1);
			}

			// if callback is specified in classname::method format
			if (is_string($eachValidator['callback']) &&
				preg_match('/^(\p{L}+)::(\p{L}+)$/', $eachValidator['callback'], $matches)
			) {
				$eachValidator['callback'] = array(
					$matches[1],
					$matches[2],
				);
			}

			// if callback doesn't have class definition, check if it's in current validator. if not, delegate
			if (is_string($eachValidator['callback']) &&
				method_exists($validatorClassname, $eachValidator['callback'])
			) {
				$eachValidator['callback'] = array(
					$validatorClassname,
					$eachValidator['callback'],
				);
			};

			// $options can be empty (and will be for most cases)
			if (empty($eachValidator['options'])) {
				unset($eachValidator['options']);
			}
			else {
				// this shouldn't happen.
				// @codeCoverageIgnoreStart
				if (!is_array($eachValidator['options'])) {
					throw new \RuntimeException;
				}
				// @codeCoverageIgnoreEnd
				$invalidOptionsKeys = array_diff(
					array_keys($eachValidator['options']),
					$validOptionsKeys
				);
				if (!empty($invalidOptionsKeys)) {
					throw new \ClassDefinitionException('invalid validator options keys: ' . implode(', ', $invalidOptionsKeys));
				}
			}

			// if callback is still string, delegate it
			if (is_string($eachValidator['callback'])) {
				// this shouldn't happen.
				// @codeCoverageIgnoreStart
				if (!is_array($eachValidator['params'])) {
					throw new \RuntimeException;
				}
				// @codeCoverageIgnoreEnd
				array_unshift($eachValidator['params'], $Field->fieldName);
				$eachValidator['callback'] = array(
					'delegated',
					$eachValidator['callback'],
				);
				$delegatedValidators[] = $eachValidator;
			}
			// otherwise add to current inflated validators
			else {
				$inflatedValidators[] = $eachValidator;
			}

		}

		return array($inflatedValidators, $delegatedValidators);
	}

}

