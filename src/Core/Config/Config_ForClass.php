<?php

namespace SilverStripe\Core\Config;

class Config_ForClass
{

	/**
	 * @var string $class
	 */
	protected $class;

	/**
	 * key/value array of config
	 *
	 * @var array
	 */
	protected $config;

	/**
	 * A flag to know if we've initialised this class
	 *
	 * @var boolean
	 */
	protected $init = false;

	/**
	 * @param string $class
	 */
	public function __construct($class)
	{
		$this->class = $class;
	}

	protected function init()
	{
		$this->config = Config::inst()->get($this->class);
		$this->init = true;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if(!$this->init) {
			$this->init();
		}

		$name = strtolower($name);
		return isset($this->config[$name]) ? $this->config[$name] : null;
	}

	/**
	 * @param string $name
	 * @param mixed $val
	 */
	public function __set($name, $val)
	{
		$this->update($name, $val);
	}

	/**
	 * Explicit pass-through to Config::update()
	 *
	 * @param string $name
	 * @param mixed $val
	 * @return $this
	 */
	public function update($name, $val)
	{
		Config::inst()->update($this->class, $name, $val);
		return $this;
	}

		/**
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		$name = strtolower($name);
		return isset($this->config[$name]);
	}

	/**
	 * @param string $name
	 * @param int $sourceOptions
	 * @return mixed
	 */
	public function get($name, $sourceOptions = 0)
	{
		return Config::inst()->get($this->class, $name, $sourceOptions);
	}

	/**
	 * Remove the given config key
	 *
	 * @param string $name
	 * @return $this
	 */
	public function remove($name) {
		Config::inst()->remove($this->class, $name);
		return $this;
	}

	/**
	 * @param string
	 *
	 * @return Config_ForClass
	 */
	public function forClass($class)
	{
		return Config::inst()->forClass($class);
	}
}
