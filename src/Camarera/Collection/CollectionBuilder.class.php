<?php

namespace Camarera;

/**
 * CollectionBuilder it is yet a draft of a class which would take lot of responsibility from Collection, usually functions
 * 	you normally use once on a given collection
 * It extends Collection so it can manipulate protected object data (needed? if not, can be just a static class @todo)
 *
 * @package Camarera
 */
class CollectionBuilder extends Collection {

	protected $_collectionClassname = 'Collection';

	final protected function __construct(){}

	/**
	 * I return an appropriate builder instance
	 * @param string|null $classname if string, I will use given classname for collection to build
	 * @return \Collection|static
	 */
	public static function get($classname=null) {
		if (!is_null($classname)) {
			$builderClassname = $classname . 'Builder';
			if (class_exists($builderClassname)) {
				$Builder = $builderClassname::get();
			}
			else {
				$Builder = new static;
				$Builder->_collectionClassname = $classname;
			}
		}
		else {
			$Builder = new static;
		}
		return $Builder;
	}

	/**
	 * @param $ids
	 * @param \CollectionGetConfig $Config
	 * @return \Collection
	 * @throws \InvalidArgumentException
	 */
	public function getByIds($ids, \CollectionGetConfig $Config) {}

}
