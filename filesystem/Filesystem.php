<?php

use \League\Flysystem\Handler;
use \League\Flysystem\PluginInterface;

/**
 * A collection of static methods for manipulating the filesystem.
 *
 * @package framework
 * @subpackage filesystem
 */
class Filesystem extends Object implements FilesystemInterface {


	/**
	 * @var \League\Flysystem\Filesystem
	 */
	protected $backend;


	/**
	 * Create a new Filesystem Instance.
	 *
	 * @param $backend \League\Flysystem\Filesystem
	 */
	public function __construct(\League\Flysystem\Filesystem $backend) {
		$this->backend = $backend;
		parent::__construct();
	}



	/* ReadInterface */
	public function has($path) {
		return $this->getBackend()->has($path);
	}

	public function read($path) {
		return $this->getBackend()->read($path);
	}

	public function readStream($path) {
		return $this->getBackend()->readStream($path);
	}

	public function listContents($directory = '', $recursive = false) {
		return $this->getBackend()->listContents($directory, $recursive);
	}

	public function getMetadata($path) {
		return $this->getBackend()->getMetadata($path);
	}

	public function getSize($path) {
		return $this->getBackend()->getSize($path);
	}

	public function getMimetype($path) {
		return $this->getBackend()->getMimetype($path);
	}

	public function getTimestamp($path) {
		return $this->getBackend()->getTimestamp($path);
	}

	public function getVisibility($path) {
		return $this->getBackend()->getVisibility($path);
	}


	/* AdapaterInterface */
	public function write($path, $contents, $config = null) {
		return $this->getBackend()->getTimestamp($path, $contents, $config);
	}

	public function update($path, $contents, $config = null) {
		return $this->getBackend()->update($path, $contents, $config);
	}

	public function writeStream($path, $resource, $config = null) {
		return $this->getBackend()->writeStream($path, $resource, $config);
	}

	public function updateStream($path, $resource, $config = null) {
		return $this->getBackend()->updateStream($path, $contents, $config);
	}

	public function rename($path, $newPath) {
		return $this->getBackend()->rename($path, $newPath);
	}

	public function copy($path, $newPath) {
		return $this->getBackend()->copy($path, $newPath);
	}

	public function delete($path) {
		return $this->getBackend()->delete($path);
	}

	public function deleteDir($dir) {
		return $this->getBackend()->deleteDir($dir);
	}

	public function createDir($dir, $options = null) {
		return $this->getBackend()->createDir($dir, $options);
	}

	public function setVisibility($path, $visibility) {
		return $this->getBackend()->setVisibility($path, $visibility);
	}


	/* FilesystemInterface */
	public function put($path, $contents, $config = null) {
		return $this->getBackend()->put($path, $contents, $config);
	}

	public function putStream($path, $resource, $config = null) {
		return $this->getBackend()->putStream($path, $contents, $config);
	}

	public function readAndDelete($path) {
		return $this->getBackend()->readAndDelete($path);
	}

	public function listFiles($directory = '', $recursive = false) {
		return $this->getBackend()->listFIles($directory, $recursive);
	}

	public function listPaths($director = '', $recursive = false) {
		return $this->getBackend()->listPaths($directory, $recursive);
	}

	public function listWith(array $keys = array(), $directory = '', $recursive = false) {
		return $this->getBackend()->listWith($keys, $directory, $recursive);
	}

	public function getWithMetadata($path, array $metaData) {
		return $this->getBackend()->getWithMetadata($path, $metaData);
	}

	public function get($path, Handler $handler = null) {
		return $this->getBackend()->get($path, $handler);
	}

	public function flushCache() {
		return $this->getBackend()->flushCache();
	}

	public function addPlugin(PluginInterface $plugin) {
		return $this->getBackend()->addPlugin($plugin);
	}

	/**
	 * @deprecated 3.3 makeFolder is deprecated. Please use xxx
	 * 
	 * @todo figure out replacement method
	 */
	public static function makeFolder($folder) {
		Deprecation::notice('3.3', 'makeFolder is deprecated. Please use xxx');
	}

	/**
	 * @deprecated 3.3 removeFolder is deprecated.
	 */
	public static function removeFolder($folder, $contentsOnly = false) {
		Deprecation::notice('3.3', 'removeFolder is deprecated. Please use xxx');
	}

	/**
	 * @deprecated 3.3 fixFiles is deprecated.
	 */
	public static function fixfiles() {
		Deprecation::notice('3.3', 'fixFiles is deprecated. Please use xxx');
	}

	/**
	 * @deprecated 3.3 folderModTime is deprecated.
	 */
	public static function folderModTime($folder, $extensionList = null, $recursiveCall = false) {
		Deprecation::notice('3.3', 'folderModTime is deprecated. Please use xxx.');
	}

	/**
	 * @deprecated 3.3 isAbsolute is deprecated.
	 */
	public static function isAbsolute($filename) {
		Deprecation::notice('3.3', 'isAbsolute is deprecated. Please use xxx.');
	}

	/**
	 * @deprecated 3.3 sync is deprecated.
	 */
	public static function sync($folderID = null, $syncLinkTracking = true) {
		Deprecation::notice('3.3', 'sync is deprecated. Please use xxx.');
	}

}
