<?php

namespace SilverStripe\Framework\Filesystem;

class Filesystem extends \Object implements FilesystemInterface {

	private static $folder_create_mask = 0777;


	/**
	 * The absolute root path.
	 *
	 * @var string
	 */
	protected $basePath;


	/**
	 * The base URL mapped to basePath.
	 *
	 * @var stirng
	 */
	protected $baseUrl;


	/**
	 * Path separator for directories.
	 *
	 * @var string
	 */
	protected $pathSeparator = DIRECTORY_SEPARATOR;


	/**
	 * This takes a relative path from the web root and sets it as the base path.
	 *
	 * @param string $basePath
	 * @param string $baseUrl
	 */
	public function __construct($basePath, $baseUrl = null) {
		$this->setBasePath(\Director::baseFolder() . $this->getPathSeparator() . $basePath);
		$this->setBaseUrl(\Director::absoluteURL($baseUrl));
	}


	/**
	 * Get the current base path.
	 *
	 * @return string
	 */
	public function getBasePath() {
		return rtrim($this->basePath, '\\/');
	}


	/**
	 * Return the current base path.
	 *
	 * @param $path string
	 *
	 * @return static
	 */
	public function setBasePath($path) {
		$this->basePath = $path;
		return $this;
	}


	/**
	 * Get the current base URL
	 *
	 * @return stirng
	 */
	public function getBaseUrl() {
		return $this->baseUrl;
	}


	/**
	 * Set the base URL
	 *
	 * @param $baseUrl string
	 *
	 * @return static
	 */
	public function setBaseUrl($baseUrl) {
		$this->baseUrl = $baseUrl;
		return $this;
	}


	/**
	 * Get the current path separator.
	 *
	 * @return string
	 */
	public function getPathSeparator() {
		return $this->pathSeparator;
	}


	/**
	 * Set the current path separator
	 *
	 * @param $pathSeparator
	 *
	 * @return static
	 */
	public function setPathSeparator($pathSeparator) {
		$this->pathSeparator = $pathSeparator;
		return $this;
	}


	/**
	 * This resolves a relative path to its actual location. This allows symlinks to function normally.
	 * Caution: This does not sandbox the path to the current root. @see self::sandboxPath()
	 *
	 * @example /my/path/../ will return /my
	 * @example /my/path/./ will return /my/path
	 * @example /my/path/symlink/path will return /my/path/symlink/path
	 *
	 * @param $path string
	 *
	 * @return string
	 */
	public function resolvePath($path) {
		// Cleanup the path
		$path = $this->isAbsolute($path) ? $path : $this->makeAbsolute($path);

		// Split into parts (folders)
		$parts = explode($this->getPathSeparator(), $path);
		$parts = array_filter($parts);

		// Now we'll step through each folder and resolve to the correct path
		if(!empty($parts)) {
			$final = array();
			foreach($parts as $part) {
				// We don't need this (current directory)
				if($part == '.') continue;
				// Remove last directory part as we've moved up a directory.
				if($part == '..') {
					array_pop($final);
					continue;
				}
				// todo: Symlinks? Probably do more bad than good if we resolve those.
				// todo: We could introduce a setting which dictates how we treat symlinks?
				// todo: eg. They could be blocked if defined by the user. By user, I mean developer.
				array_push($final, $part);
			}
			return $this->getPathSeparator() . implode($this->getPathSeparator(), $final);
		}
		return $this->getBasePath();
	}


	/**
	 * Ensures that the given path is within the current root.
	 *
	 * @param $path string
	 *
	 * @return string
	 *
	 * @throws \Exception if the given path is outside of the current root.
	 */
	public function sandboxPath($path) {
		$path = $this->resolvePath($path);
		if(substr_compare($path, $this->getBasePath(), 0, strlen($this->getBasePath())) == 0) {
			return $path;
		}
		throw new \Exception($path . ' is not within the current root.');
		return $this->getBasePath();
	}


	/**
	 * Makes the path absolute.
	 *
	 * @param $path
	 *
	 * @return string
	 */
	public function makeAbsolute($path) {
		if(!$this->isAbsolute($path)) {
			$path = $this->getBasePath() . $this->getPathSeparator() . trim($path, '\\/');
		}
		return $this->resolvePath($path);
	}


	/**
	 * Makes the path relative.
	 *
	 * @param $path
	 *
	 * @return string
	 */
	public function makeRelative($path) {
		// First we're going to resolve the path then make it relative.
		$path = $this->resolvePath($path);

		// Now make it relative
		$base = $this->getBasePath();
		$path = ltrim(substr($path, strlen($base)), '\\/');
		return trim($path, '\\/');
	}


	/**
	 * Checks to see if a path is absolute. This will only return true when in the current root.
	 *
	 * @param $path string
	 *
	 * @return bool
	 */
	public function isAbsolute($path) {
		$base = $this->getBasePath();
		return (substr($path, 0, strlen($base)) == $base);
	}


	/**
	 * Checks to see if the given path is the base folder.
	 *
	 * @param $path string
	 *
	 * @return bool
	 */
	public function isBasePath($path) {
		return ($this->makeAbsolute($path) == $this->getBasePath());
	}


	/**
	 * Returns the current directory of the given filename.
	 *
	 * @param $filename string
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getCurrentDir($filename) {
		$filename = $this->makeAbsolute($filename);
		if($this->isDir($filename)) return $this->sandboxPath($filename);
		if(!$this->getFileExtension($filename)) return $this->sandboxPath($filename);

		// Figure out the current directory - we're currently on a file.
		$parts = explode($this->getPathSeparator(), $filename);
		array_pop($parts);
		$path = implode($this->getPathSeparator(), $parts);
		return $this->sandboxPath($path);
	}

	
	/**
	 * Returns absolute url to the given filename
	 *
	 * @param $fileName
	 *
	 * @return string
	 */
	public function getAbsoluteUrl($fileName) {
		return \Controller::join_links($this->getBaseUrl(), $fileName);
	}


	/**
	 * Returns the url to the given filename
	 *
	 * @param $fileName
	 *
	 * @return string
	 */
	public function getUrl($fileName) {
		return \Director::makeRelative($this->getAbsoluteUrl($fileName));
	}


	public function has($path) {
		return file_exists($this->sandboxPath($path));
	}

	public function isFile($path) {
		return $this->has($path) && is_file($this->sandboxPath($path));
	}

	public function isDir($path) {
		return $this->has($path) && is_dir($this->sandboxPath($path));
	}

	public function delete($path, $includeContent = false) {
		if($this->isFile($path)) {
			return unlink($this->sandboxPath($path));
		} else if ($this->isDir($path)) {
			return $this->removeDir($path, $includeContent);
		}
		return false;
	}

	public function getFilesize($path) {
		return filesize($this->sandboxPath($path));
	}

	public function getFileExtension($path) {
		return pathinfo($this->sandboxPath($path), PATHINFO_EXTENSION);
	}

	public function createDir($path, $recursive = true, $config = array()) {
		$mode = isset($config['mode']) ? $config['mode'] : $this->config()->folder_create_mask;
		$path = $this->sandboxPath($path);
		if($path) {
			return $this->isDir($path) ? true : mkdir($path, $mode, $recursive);
		}
		return false;
	}

	public function removeDir($path, $includeContent = false) {
		if($this->isFolder($path)) {
			$content = array_diff(scandir($path), array('.', '..'));
			if(count($content)) {
				if($includeContent) {
					foreach($content as $file) {
						$file = rtrim($path, $this->getPathSeparator()) . $this->getPathSeparator() . $file;
						$this->delete($file, true);
					}
				}
			}
			return rmdir($this->resolvePath($path));
		}
		return false;
	}

	public function levelUp($path) {
		$path = trim($path, $this->getPathSeparator());
		$path = explode($this->getPathSeparator(), $path);
		array_pop($path);
		return $this->sandboxPath(implode($this->getPathSeparator(), $path));
	}

	public function getBaseName($path) {
		return basename($path);
	}


	/**
	 * Returns a directory iterator.
	 *
	 * @param $path string
	 * @param $recursive bool
	 *
	 * @return \DirectoryIterator|\RecursiveDirectoryIterator
	 * @throws \Exception
	 */
	protected function getDirectoryIterator($path, $recursive = false) {
		$path = $this->sandboxPath($path);
		if($recursive) {
			$directory = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
			return new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);
		} else {
			return new \DirectoryIterator($path);
		}
	}

}
