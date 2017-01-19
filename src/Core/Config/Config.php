<?php

namespace SilverStripe\Core\Config;

use SilverStripe\Core\Object;
use SilverStripe\Core\Manifest\ConfigStaticManifest;
use SilverStripe\Core\Manifest\ConfigManifest;
use micmania1\config\ConfigCollectionInterface;
use micmania1\config\MergeStrategy\Priority;
use Psr\Cache\CacheItemPoolInterface;
use UnexpectedValueException;
use stdClass;

class Config {

	/**
	 * source options bitmask value - merge all parent configuration in as
	 * lowest priority.
	 *
	 * @const
	 */
	const INHERITED = 0;

	/**
	 * source options bitmask value - only get configuration set for this
	 * specific class, not any of it's parents.
	 *
	 * @const
	 */
	const UNINHERITED = 1;

	/**
	 * source options bitmask value - inherit, but stop on the first class
	 * that actually provides a value (event an empty value).
	 *
	 * @const
	 */
	const FIRST_SET = 2;

	/**
	 * @const source options bitmask value - do not use additional statics
	 * sources (such as extension)
	 */
	const EXCLUDE_EXTRA_SOURCES = 4;

	/**
	 * @var Config
	 */
	protected static $instance;

	/**
	 * @var array
	 */
	protected $cache;

	/**
	 * @var ConfigCollectionInterface
	 */
	protected $collection;

	/**
	 * @var CacheItemPoolInterface;
	 */
	protected $persistentCache;

	/**
	 * In memory cache is used per-request to prevent unnecessary calls to cache
	 * which can have latency.
	 *
	 * @var array
	 */
	protected $memoryCache = [];

	/**
	 * @var Config - The config instance this one was copied from when
	 * Config::nest() was called.
	 */
	protected $nestedFrom = null;

	/**
	 * Get the current active Config instance.
	 *
	 * In general use you will use this method to obtain the current Config
	 * instance. It assumes the config instance has already been set.
	 *
	 * @return Config
	 */
	public static function inst() {
		return self::$instance;
	}

	/**
	 * Set the current active {@link Config} instance.
	 *
	 * {@link Config} objects should not normally be manually created.
	 *
	 * A use case for replacing the active configuration set would be for
	 * creating an isolated environment for unit tests.
	 *
	 * @param Config $instance New instance of Config to assign
	 * @return Config Reference to new active Config instance
	 */
	public static function set_instance($instance) {
		self::$instance = $instance;
		return $instance;
	}

	/**
	 * Make the newly active {@link Config} be a copy of the current active
	 * {@link Config} instance.
	 *
	 * You can then make changes to the configuration by calling update and
	 * remove on the new value returned by {@link Config::inst()}, and then discard
	 * those changes later by calling unnest.
	 *
	 * @return Config Reference to new active Config instance
	 */
	public static function nest() {
		$current = self::$instance;

		$new = clone $current;
		$new->nestedFrom = $current;
		return self::set_instance($new);
	}

	/**
	 * Change the active Config back to the Config instance the current active
	 * Config object was copied from.
	 *
	 * @return Config Reference to new active Config instance
	 */
	public static function unnest() {
		if (self::inst()->nestedFrom) {
			self::set_instance(self::inst()->nestedFrom);
		}
		else {
			user_error(
				"Unable to unnest root Config, please make sure you don't have mis-matched nest/unnest",
				E_USER_WARNING
			);
		}
		return self::inst();
	}

	/**
	 * Each copy of the Config object need's it's own cache, so changes don't
	 * leak through to other instances.
	 */
	public function __construct(
		ConfigCollectionInterface $collection,
		CacheItemPoolInterface $persistentCache
	) {
		$this->collection = $collection;
		$this->persistentCache = $persistentCache;
	}

	public function __clone() {
		$this->collection = clone $this->collection;
	}

	/**
	 * Get an accessor that returns results by class by default.
	 *
	 * Shouldn't be overridden, since there might be many Config_ForClass instances already held in the wild. Each
	 * Config_ForClass instance asks the current_instance of Config for the actual result, so override that instead
	 *
	 * @param $class
	 * @return Config_ForClass
	 */
	public function forClass($class) {
		return new Config_ForClass($class);
	}

	/**
	 * Get the value of a config property class.name
	 *
	 * @var string $class
	 * @var string $name
	 * @var int $sourceOptions
	 *
	 * @return mixed
	 */
	public function get($class, $name = null, $sourceOptions = 0) {
		if(($sourceOptions & self::FIRST_SET) == self::FIRST_SET) {
			throw new \Exception(sprintf('Using FIRST_SET on %s.%s', $class, $name));
		}

		// Have we got a cached value? Use it if so
		$key = md5(strtolower($class.$sourceOptions));

		if(isset($this->memoryCache[$key])) {
			$classConfig = $this->memoryCache[$key];

			// If no name is passed, return all config
			if(is_null($name)) {
				return $this->memoryCache[$key];
			}

			return isset($classConfig[$name]) ? $classConfig[$name] : null;
		}

		$item = $this->persistentCache->getItem($key);
		if(!$item->isHit()) {
			// Go and get entire class config (uncached)
			$classConfig = $this->getClassConfig($class, $sourceOptions);

			$item->set($classConfig);
			$this->persistentCache->saveDeferred($item);
		}

		$this->memoryCache[$key] = $item->get();

		// If no name is passed, we return all config
		if(is_null($name)) {
			return $this->memoryCache[$key];
		}

		// Return only the config for the given name
		return isset($this->memoryCache[$key][$name]) ?
			$this->memoryCache[$key][$name]
			: null;
	}

	/**
	 * Get the class config for the given class with the given source options
	 *
	 * @param string $class
	 * @param int $sourceOtions
	 *
	 * @return array|null
	 */
	public function getClassConfig($class, $sourceOptions = 0) {
		$classConfig = $this->collection->get($class);

		if($this->shouldApplyExtraConfig($class, $sourceOptions)) {
			$this->applyExtraConfig($class, $sourceOptions, $classConfig);
		}

		if($this->shouldInheritConfig($class, $sourceOptions, $classConfig)) {
			$this->applyInheritedConfig($class, $sourceOptions, $classConfig);
		}

		return $classConfig;
	}

	/**
	 * Applied config to a class from its extensions
	 *
	 * @param string $class
	 * @param int $sourceOptions
	 * @param mixed $classConfig
	 */
	protected function applyExtraConfig($class, $sourceOptions, &$classConfig) {
		$extraSources = Object::get_extra_config_sources($class);
		if(empty($extraSources)) {
			return;
		}

		$priority = new Priority;
		foreach($extraSources as $source) {
			if(is_string($source)) {
				$source = $this->getClassConfig(
					$source,
					self::UNINHERITED | self::EXCLUDE_EXTRA_SOURCES
				);
			}

			if(is_array($source)) {
				if(is_null($classConfig) || !is_array($classConfig)) {
					$classConfig = $source;
					continue;
				}

				$classConfig = $priority->mergeArray($classConfig, $source);
			} else if (!is_null($source)) {
				$classConfig = $source;
			}
		}
	}

	/**
	 * Adds the inherited config to a class config
	 *
	 * @param string $class
	 * @param int $sourceOptions
	 * @param mixed $classConfig
	 */
	protected function applyInheritedConfig($class, $sourceOptions, &$classConfig) {
		$parent = get_parent_class($class);
		if ($parent) {
			$parentConfig = $this->getClassConfig($parent, $sourceOptions);

			if(is_array($classConfig) && is_array($parentConfig)) {
				$strategy = new Priority;
				$classConfig = $strategy->mergeArray($classConfig, $parentConfig);
			} else if(is_null($classConfig) && !is_null($parentConfig)) {
				$classConfig = $parentConfig;
			}
		}
	}

	/**
	 * A check to test if we should include extra config (data extensions)
	 *
	 * @param stirng $class
	 * @param int $sourceOptions
	 * @param mixed $result
	 *
	 * @return boolean
	 */
	protected function shouldApplyExtraConfig($class, $sourceOptions) {
		if($class instanceof Extension) {
			return false;
		}

		return ($sourceOptions & self::EXCLUDE_EXTRA_SOURCES) != self::EXCLUDE_EXTRA_SOURCES;
	}

	/**
	 * A check to test if we should inherit config from parent classes for the given
	 * source option.
	 *
	 * @param stirng $class
	 * @param int $sourceOptions
	 * @param mixed $classConfig
	 *
	 * @return boolean
	 */
	protected function shouldInheritConfig($class, $sourceOptions, &$classConfig) {
		return class_exists($class)
			&& ($sourceOptions & self::UNINHERITED) != self::UNINHERITED
			&& (($sourceOptions & self::FIRST_SET) != self::FIRST_SET || $classConfig === null);
	}

	public function update($class, $name, $val) {
		// echo sprintf('Config->update() called on %s.%s', $class, $name) . '<br />';
		return;
	}

	public function remove($class, $name, $key = null, $value = null) {
		// echo sprintf('Config->remove() called on %s.%s', $class, $name) . '<br />';;
		return;
	}

}
