<?php

namespace SilverStripe\Core\Config;

use SilverStripe\Core\Object;
use SilverStripe\Core\Manifest\ConfigStaticManifest;
use SilverStripe\Core\Manifest\ConfigManifest;
use micmania1\config\ConfigCollectionInterface;
use micmania1\config\ConfigCollection;
use UnexpectedValueException;
use stdClass;

class Config {

	/**
	 * @var ConfigCollectionInterface
	 */
	protected $collection;

	/**
	 * A marker instance for the "anything" singleton value. Don't access
	 * directly, even in-class, always use self::anything()
	 *
	 * @var Object
	 */
	private static $_anything = null;

	/**
	 * @var bool
	 */
	protected $collectConfigPHPSettings;

	/**
	 * @var bool
	 */
	protected $configPHPIsSafe;

	/**
	 * Get a marker class instance that is used to do a "remove anything with
	 * this key" by adding $key => Config::anything() to the suppress array
	 *
	 * @return Object
	 */
	public static function anything() {
		if (self::$_anything === null) {
			self::$_anything = new stdClass();
		}

		return self::$_anything;
	}

	// -- Source options bitmask --

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

	// -- get_value_type response enum --

	/**
	 * Return flag for get_value_type indicating value is a scalar (or really
	 * just not-an-array, at least ATM)
	 *
	 * @const
	 */
	const ISNT_ARRAY = 1;

	/**
	 * Return flag for get_value_type indicating value is an array.
	 * @const
	 */
	const IS_ARRAY = 2;

	/**
	 * Get whether the value is an array or not. Used to be more complicated,
	 * but still nice sugar to have an enum to compare and not just a true /
	 * false value.
	 *
	 * @param mixed $val The value
	 *
	 * @return int One of ISNT_ARRAY or IS_ARRAY
	 */
	protected static function get_value_type($val) {
		if (is_array($val)) {
			return self::IS_ARRAY;
		}

		return self::ISNT_ARRAY;
	}

	/**
	 * What to do if there's a type mismatch.
	 *
	 * @throws UnexpectedValueException
	 */
	protected static function type_mismatch() {
		throw new UnexpectedValueException('Type mismatch in configuration. All values for a particular property must'
			. ' contain the same type (or no value at all).');
	}

	/**
	 * @todo If we can, replace next static & static methods with DI once that's in
	 */
	protected static $instance;

	/**
	 * Get the current active Config instance.
	 *
	 * Configs should not normally be manually created.
	 *
	 * In general use you will use this method to obtain the current Config
	 * instance.
	 *
	 * @return Config
	 */
	public static function inst() {
		if (!self::$instance) {
			self::$instance = new Config();
		}

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
	 * @var array
	 */
	protected $cache;

	/**
	 * Each copy of the Config object need's it's own cache, so changes don't
	 * leak through to other instances.
	 */
	public function __construct($collection) {
		$this->collection = $collection;
		$this->cache = new ConfigCollection;
	}

	public function __clone() {
		$this->cache = clone $this->cache;
	}

	/**
	 * @var Config - The config instance this one was copied from when
	 * Config::nest() was called.
	 */
	protected $nestedFrom = null;

	/**
	 * @var array - Array of arrays. Each member is an nested array keyed as
	 * $class => $name => $value, where value is a config value to treat as
	 * the highest priority item.
	 */
	protected $overrides = array();

	/**
	 * @var array $suppresses Array of arrays. Each member is an nested array
	 * keyed as $class => $name => $value, where value is a config value suppress
	 * from any lower priority item.
	 */
	protected $suppresses = array();

	/**
	 * @var array
	 */
	protected $staticManifests = array();

	/**
	 * @param ConfigStaticManifest $manifest
	 */
	public function pushConfigStaticManifest(ConfigStaticManifest $manifest) {
		array_unshift($this->staticManifests, $manifest);

		$this->cache->clean();
	}

	/** @var [array] - The list of settings pulled from config files to search through */
	protected $manifests = array();

	/**
	 * Add another manifest to the list of config manifests to search through.
	 *
	 * WARNING: Config manifests to not merge entries, and do not solve before/after rules inter-manifest -
	 * instead, the last manifest to be added always wins
	 * @param ConfigManifest $manifest
	 */
	public function pushConfigYamlManifest(ConfigManifest $manifest) {
		array_unshift($this->manifests, $manifest);

		// Now that we've got another yaml config manifest we need to clean the cache
		$this->cache->clean();
		// We also need to clean the cache if the manifest's calculated config values change
		$manifest->registerChangeCallback(array($this->cache, 'clean'));

		// @todo: Do anything with these. They're for caching after config.php has executed
		$this->collectConfigPHPSettings = true;
		$this->configPHPIsSafe = false;

		$manifest->activateConfig();

		$this->collectConfigPHPSettings = false;
	}

	/** @var [Config_ForClass] - The list of Config_ForClass instances, keyed off class */
	static protected $for_class_instances = array();

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
		if (isset(self::$for_class_instances[$class])) {
			return self::$for_class_instances[$class];
		}
		else {
			return self::$for_class_instances[$class] = new Config_ForClass($class);
		}
	}

	/**
	 * Merge a lower priority associative array into an existing higher priority associative array, as per the class
	 * docblock rules
	 *
	 * It is assumed you've already checked that you've got two associative arrays, not scalars or sequential arrays
	 *
	 * @param $dest array - The existing high priority associative array
	 * @param $src array - The low priority associative array to merge in
	 */
	public static function merge_array_low_into_high(&$dest, $src) {
		foreach ($src as $k => $v) {
			if (!$v) {
				continue;
			}
			else if (is_int($k)) {
				$dest[] = $v;
			}
			else if (isset($dest[$k])) {
				$newType = self::get_value_type($v);
				$currentType = self::get_value_type($dest[$k]);

				// Throw error if types don't match
				if ($currentType !== $newType) self::type_mismatch();

				if ($currentType == self::IS_ARRAY) self::merge_array_low_into_high($dest[$k], $v);
				else continue;
			}
			else {
				$dest[$k] = $v;
			}
		}
	}

	/**
	 * Merge a higher priority assocative array into an existing lower priority associative array, as per the class
	 * docblock rules.
	 *
	 * Much more expensive that the other way around, as there's no way to insert an associative k/v pair into an
	 * array at the top of the array
	 *
	 * @static
	 * @param $dest array - The existing low priority associative array
	 * @param $src array - The high priority array to merge in
	 */
	public static function merge_array_high_into_low(&$dest, $src) {
		$res = $src;
		self::merge_array_low_into_high($res, $dest);
		$dest = $res;
	}

	public static function merge_high_into_low(&$result, $value) {
		$newType = self::get_value_type($value);

		if (!$result) {
			$result = $value;
		}
		else {
			$currentType = self::get_value_type($result);
			if ($currentType !== $newType) self::type_mismatch();

			if ($currentType == self::ISNT_ARRAY) $result = $value;
			else self::merge_array_high_into_low($result, $value);
		}
	}

	public static function merge_low_into_high(&$result, $value, $suppress) {
		$newType = self::get_value_type($value);

		if ($suppress) {
			if ($newType == self::IS_ARRAY) {
				$value = self::filter_array_by_suppress_array($value, $suppress);
				if (!$value) return;
			}
			else {
				if (self::check_value_contained_in_suppress_array($value, $suppress)) return;
			}
		}

		if (!$result) {
			$result = $value;
		}
		else {
			$currentType = self::get_value_type($result);
			if ($currentType !== $newType) self::type_mismatch();

			if ($currentType == self::ISNT_ARRAY) return; // PASS
			else self::merge_array_low_into_high($result, $value);
		}
	}

	public static function check_value_contained_in_suppress_array($v, $suppresses) {
		foreach ($suppresses as $suppress) {
			list($sk, $sv) = $suppress;
			if ($sv === self::anything() || $v == $sv) return true;
		}
		return false;
	}

	static protected function check_key_or_value_contained_in_suppress_array($k, $v, $suppresses) {
		foreach ($suppresses as $suppress) {
			list($sk, $sv) = $suppress;
			if (($sk === self::anything() || $k == $sk) && ($sv === self::anything() || $v == $sv)) return true;
		}
		return false;
	}

	static protected function filter_array_by_suppress_array($array, $suppress) {
		$res = array();

		foreach ($array as $k => $v) {
			$suppressed = self::check_key_or_value_contained_in_suppress_array($k, $v, $suppress);

			if (!$suppressed) {
				if (is_numeric($k)) $res[] = $v;
				else $res[$k] = $v;
			}
		}

		return $res;
	}

	protected $extraConfigSources = array();

	public function extraConfigSourcesChanged($class) {
		unset($this->extraConfigSources[$class]);
		$this->cache->clean("__{$class}");
	}

	/**
	 * Get the config value associated for a given class and property
	 *
	 * This merges all current sources and overrides together to give final value
	 * todo: Currently this is done every time. This function is an inner loop function, so we really need to be
	 * caching heavily here.
	 *
	 * @param $class string - The name of the class to get the value for
	 * @param $name string - The property to get the value for
	 * @param int $sourceOptions Bitmask which can be set to some combintain of Config::UNINHERITED,
	 *                           Config::FIRST_SET, and Config::EXCLUDE_EXTENSIONS.
	 *
	 *   Config::UNINHERITED does not include parent classes when merging configuration fragments
	 *   Config::FIRST_SET stops inheriting once the first class that sets a value (even an empty value) is encoutered
	 *   Config::EXCLUDE_EXTRA_SOURCES does not include any additional static sources (such as extensions)
	 *
	 *   Config::INHERITED is a utility constant that can be used to mean "none of the above", equvilient to 0
	 *   Setting both Config::UNINHERITED and Config::FIRST_SET behaves the same as just Config::UNINHERITED
	 *
	 * should the parent classes value be merged in as the lowest priority source?
	 * @param $result mixed Reference to a variable to put the result in. Also returned, so this can be left
	 *                             as null safely. If you do pass a value, it will be treated as the highest priority
	 *                             value in the result chain
	 * @param $suppress array Internal use when called by child classes. Array of mask pairs to filter value by
	 * @return mixed The value of the config item, or null if no value set. Could be an associative array,
	 *                      sequential array or scalar depending on value (see class docblock)
	 */
	protected $values = [];
	public function get($class, $name, $sourceOptions = 0) {
		$key = $class.$name.$sourceOptions;
		if(isset($this->values[$key])) {
			return $this->values[$key];
		}

		$class = strtolower($class);
		$classConfig = $this->collection->get($class);
		$value = isset($classConfig[$name]) ? $classConfig[$name] : null;

		// If we have a non-array and non-null value we're done here. We can't merge anything
		// after that and this class instance takes priority.
		if(!is_array($value) && !is_null($value)) {
			return $this->values[$key] = $value;
		}

		// Include any extensions if applicable
		if (($sourceOptions & self::EXCLUDE_EXTRA_SOURCES) != self::EXCLUDE_EXTRA_SOURCES) {
			$extensions = isset($classConfig['extensions']) ? $classConfig['extensions'] : null;
			if(is_array($extensions)) {
				foreach($extensions as $extensionClass) {
					if($includeExtensions) {
						$mask = self::UNINHERITED | self::EXCLUDE_EXTRA_SOURCES;
					} else {
						$mask = self::UNINHERITED;
					}

					$extraConfig = $this->get($extensionClass, $name, $mask);
					if (is_null($extraConfig)) {
						continue;
					}

					if (!is_array($extraConfig)) {
						throw new \Exception('Trying to merge non-array into an array');
					}

					$value = array_merge($extraConfig, $value);
				}
			}
		}

        if (
            ($sourceOptions & self::UNINHERITED) != self::UNINHERITED &&
            (($sourceOptions & self::FIRST_SET) != self::FIRST_SET || $result === null)
        ) {
			$parent = $class;
			while($parent = get_parent_class($parent)) {
				$parentValue = $this->get($parent, $name, self::UNINHERITED);

				// If its not an array we can't merge so the value is considered final
				if(!is_array($parentValue)) {
					break;
				}

				// Merge the parent value into the current value (current value takes priority)
				$value = array_merge($parentValue, $value);
			}
		}

		return $this->values[$key] = $value;
	}

	/**
	 * Update a configuration value
	 *
	 * Configuration is modify only. The value passed is merged into the existing configuration. If you want to
	 * replace the current array value, you'll need to call remove first.
	 *
	 * @param string $class The class to update a configuration value for
	 * @param string $name The configuration property name to update
	 * @param mixed $val The value to update with
	 *
	 * Arrays are recursively merged into current configuration as "latest" - for associative arrays the passed value
	 * replaces any item with the same key, for sequential arrays the items are placed at the end of the array, for
	 * non-array values, this value replaces any existing value
	 *
	 * You will get an error if you try and override array values with non-array values or vice-versa
	 */
	public function update($class, $name, $val) {
		if(is_null($val)) {
			$this->remove($class, $name);
		} else {
			if (!isset($this->overrides[0][$class])) $this->overrides[0][$class] = array();

			if (!array_key_exists($name, $this->overrides[0][$class])) {
				$this->overrides[0][$class][$name] = $val;
			} else {
				self::merge_high_into_low($this->overrides[0][$class][$name], $val);
			}
		}

		$this->cache->clean("__{$class}__{$name}");
	}

	/**
	 * Remove a configuration value
	 *
	 * You can specify a key, a key and a value, or neither. Either argument can be Config::anything(), which is
	 * what is defaulted to if you don't specify something
	 *
	 * This removes any current configuration value that matches the key and/or value specified
	 *
	 * Works like this:
	 *   - Check the current override array, and remove any values that match the arguments provided
	 *   - Keeps track of the arguments passed to this method, and in get filters everything _except_ the current
	 *     override array to exclude any match
	 *
	 * This way we can re-set anything removed by a call to this function by calling set. Because the current override
	 * array is only filtered immediately on calling this remove method, that value will then be exposed. However,
	 * every other source is filtered on request, so no amount of changes to parent's configuration etc can override a
	 * remove call.
	 *
	 * @param string $class The class to remove a configuration value from
	 * @param string $name The configuration name
	 * @param mixed $key An optional key to filter against.
	 *   If referenced config value is an array, only members of that array that match this key will be removed
	 *   Must also match value if provided to be removed
	 * @param mixed $value And optional value to filter against.
	 *   If referenced config value is an array, only members of that array that match this value will be removed
	 *   If referenced config value is not an array, value will be removed only if it matches this argument
	 *   Must also match key if provided and referenced config value is an array to be removed
	 *
	 * Matching is always by "==", not by "==="
	 */
	public function remove($class, $name, $key = null, $value = null) {
		if (func_num_args() < 3) {
			$key = self::anything();
		}
		if (func_num_args() < 4) {
			$value = self::anything();
		}

		$suppress = array($key, $value);

		if (isset($this->overrides[0][$class][$name])) {
			$value = $this->overrides[0][$class][$name];

			if (is_array($value)) {
				$this->overrides[0][$class][$name] = self::filter_array_by_suppress_array($value, array($suppress));
			}
			else {
				if (self::check_value_contained_in_suppress_array($value, array($suppress))) {
					unset($this->overrides[0][$class][$name]);
				}
			}
		}

		if (!isset($this->suppresses[0][$class])) $this->suppresses[0][$class] = array();
		if (!isset($this->suppresses[0][$class][$name])) $this->suppresses[0][$class][$name] = array();

		$this->suppresses[0][$class][$name][] = $suppress;

		$this->cache->clean("__{$class}__{$name}");
	}

}
