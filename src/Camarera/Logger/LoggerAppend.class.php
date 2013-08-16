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
 * I will aggregate log content and output in __destruct(), in html format
 *
 * @author t
 * @package Camarera\Logger
 * @license DWTFYWT
 * @version 1.1
 */
class LoggerAppend {

	/**
	 * I aggregate log content in this
	 * @var mixed[]
	 */
	protected $_buffer = array();

	/**
	 * template used for output. You can overwrite it
	 * @var string
	 */
	protected $_template = '
<style>
	.Camarera_LoggerAppend { background: #fc8; padding: 5px;}
</style>
<div class="Camarera_LoggerAppend"><pre>
###CONTENT###
</pre></div>';

	/**
	 * Singleton
	 * @var Camarera\LoggerAppend
	 */
	protected static $_Instance;

	/**
	 * singleton
	 * @return Camarera\LoggerAppend
	 */
	public static function instance() {
		if (is_null(static::$_Instance)) {
			static::$_Instance = new static();
		}
		return static::$_Instance;
	}

	protected function __construct() {}

	/**
	 * I print accumulated buffer
	 */
	public function __destruct() {
		echo str_replace('###CONTENT###', implode("\n", $this->_buffer), $this->_template);
	}

	/**
	 * I will format the log label with context info
	 * @param $var
	 * @param $context
	 * @return string
	 */
	protected static function _formatLog($var, $context) {
		if (is_null($context)) {
			return '' . $var;
		}
		elseif (!is_array($context)) {
			return '' . $var;
		}
		$tr = array();
		foreach ($context as $eachKey=>$eachValue) {
			$tr['{' . $eachKey . '}'] = '' . $eachValue;
		}
		return strtr($var, $tr);
	}

	/**
	 * I add entry to buffer
	 * @param type $var
	 * @return type
	 */
	public function log($logLevel, $var, $context=null) {
		$this->_buffer[] = static::_formatLog($var, $context);
		return;
	}

	public function emergency($message, array $context = array()) {
		return $this->log(\Camarera::LOG_EMERGENCY, $message, $context);
	}

	public function alert($message, array $context = array()) {
		return $this->log(\Camarera::LOG_ALERT, $message, $context);
	}

	public function critical($message, array $context = array()) {
		return $this->log(\Camarera::LOG_CRITICAL, $message, $context);
	}

    public function error($message, array $context = array()) {
		return $this->log(\Camarera::LOG_ERROR, $message, $context);
	}

    public function warning($message, array $context = array()) {
		return $this->log(\Camarera::LOG_WARNING, $message, $context);
	}

    public function notice($message, array $context = array()) {
		return $this->log(\Camarera::LOG_NOTICE, $message, $context);
	}

    public function info($message, array $context = array()) {
		return $this->log(\Camarera::LOG_INFORMATIONAL, $message, $context);
	}

    public function debug($message, array $context = array()) {
		return $this->log(\Camarera::LOG_DEBUG, $message, $context);
	}


	/**
	 * I set the template to be used
	 * @param string $templateString
	 * @return \LoggerAppend
	 */
	public function setTemplate($templateString) {
		$this->_template = $templateString;
		return $this;
	}


}
