<?php

/**
 * Class TraitTestModel - to allow calling protected methods on classes
 * phpdoc needed so IDE recognizes these methods
 * @method static _getInitialFieldDefs
 * @method static _inflate
 */
trait TraitTestModel {
	public static function __callStatic($functionName, $args) {
		$classname = get_called_class();
		if (!method_exists($classname, $functionName)) {
			user_error('No such method: ' . $classname . '::' . $functionName);
		}
		return call_user_func_array(array($classname, $functionName), $args);
	}
	public function __call($functionName, $args) {
		if (!method_exists($this, $functionName)) {
			user_error('No such method: ' . get_class($this) . '->' . $functionName);
		}
		return call_user_func_array(array($this, $functionName), $args);
	}
}

