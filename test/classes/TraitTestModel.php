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
		return call_user_func_array(array($classname, $functionName), $args);
	}
	public function __call($functionName, $args) {
		return call_user_func_array(array($this, $functionName), $args);
	}
}

