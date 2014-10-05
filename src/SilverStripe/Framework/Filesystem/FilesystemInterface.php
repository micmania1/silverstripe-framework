<?php

namespace SilverStripe\Framework\Filesystem;

/**
 * Interface FilesystemInterface
 *
 * This should be implemented to provide new file systems.
 *
 * @package SilverStripe\Framework\Filesystem
 */
interface FilesystemInterface {

	/**
	 * Set the base path of our filesystem.
	 *
	 * @param $basePath string
	 */
	public function __construct($basePath);


	/**
	 * Checks to see if a file or folder exists.
	 *
	 * @param $path
	 *
	 * @return boolean
	 */
	public function has($path);


	/**
	 * Checks to see if a file exists. This excludes directories.
	 *
	 * @param $path
	 *
	 * @return boolean
	 */
	public function isFile($path);


	/**
	 * Checks to see if a directory exists.
	 *
	 * @param $path
	 *
	 * @return boolean
	 */
	public function isDir($path);


	/**
	 * Deletes a file or folder. This won't delete a folder if it contains files.
	 *
	 * @param $path
	 * @param $includeContent boolean
	 *
	 * @return boolean
	 */
	public function delete($path, $includeContent = false);


	/**
	 * Returns the current file size as bytes.
	 *
	 * @param $path
	 *
	 * @return int
	 */
	public function getFilesize($path);


	/**
	 * Fetches the file extension and returns it as a stirng.
	 *
	 * @param $path
	 *
	 * @return string
	 */
	public function getFileExtension($path);


	/**
	 * Create a new directory.
	 *
	 * @param $path
	 * @param bool $recursive
	 * @param array $config
	 *
	 * @return mixed
	 */
	public function createDir($path, $recursive = true, $config = array());


	/**
	 * Remove Directory.
	 *
	 * @param $path
	 *
	 * @return mixed
	 */
	public function removeDir($path, $includeContent = false);


	/**
	 * Return the relative URL when possible.
	 *
	 * @param $filename
	 *
	 * @return mixed
	 */
	public function getUrl($filename);


	/**
	 * Return the absolute URL.
	 *
	 * @param $filename
	 *
	 * @return mixed
	 */
	public function getAbsoluteUrl($filename);

}
