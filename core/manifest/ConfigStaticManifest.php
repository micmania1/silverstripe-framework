<?php
/**
 * Allows access to config values set on classes using private statics.
 *
 * @package framework
 * @subpackage manifest
 */
class SS_ConfigStaticManifest {

	/**
	 * @param string $class
	 * @param string $name
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	public function get($class, $name, $default = null) {
		if(property_exists($class, $name)) {
			$property = new ReflectionProperty($class, $name);
			if($property->isStatic() && $property->isPrivate()) {
				$property->setAccessible(true);
				return $property->getValue();
			}
		}

		return $default;
	}

}
