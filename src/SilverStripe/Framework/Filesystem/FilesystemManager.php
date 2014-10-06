<?php

namespace SilverStripe\Framework\Filesystem;

/**
 * Class FilesystemManager
 *
 *
 *
 * @package SilverStripe\Framework\Filesystem
 */
class FilesystemManager extends \Object {

	protected static $inst;

	/**
	 * A cached of instantiated filesystems
	 *
	 * @var array
	 */
	protected $filesystem_instances = array();


	public static function inst() {
		if(self::$inst) return self::$inst;

		$className = get_called_class();
		return self::$inst = \Injector::inst()->create($className);
	}


	public function get($name) {
		if(array_key_exists($name, $this->filesystem_instances)) {
			return $this->filesysten_instances[$name];
		} else {
			// Try and instantiate from config
			$filesystems = \Config::inst()->get(get_class($this), 'filesystems');
			if($filesystems && is_array($filesystems) && isset($filesystems[$name])) {
				$config = $filesystems[$name];
				if(isset($config['class'])) {
					$args = (isset($config['construct']) && is_array($config['construct']))
						? $config['construct'] : array();
					$className = substr($config['class'], 2);
					$filesystem = \Injector::inst()->createWithArgs($className, $args);
					return $this->filesystems_instances[$name] = $filesystem;
				}
			}
		}
		return false;
	}

}
