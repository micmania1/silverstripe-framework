<?php

namespace SilverStripe\Framework\Filesystem;

class Filesystem extends \Object implements FilesystemInterface {

	private static $folder_create_mask = 0777;


	protected $basePath;


	public function __construct($basePath) {
		$this->basePath = realpath($basePath);
	}

	public function getAbsoluteBasePath() {
		return Director::baseFolder() . $this->getRealtiveBasePath();
	}

	public function getRelativeBasePath() {
		return realpath($this->basePath);
	}

	public function has($path) {
		return file_exists($path);
	}

	public function isFile($path) {
		return $this->has($path) && is_file($path);
	}

	public function isDir($path) {
		return $this->has($path) && is_dir($path);
	}

	public function delete($path, $includeContent = false) {
		if($this->isFile($path)) {
			return unlink($path);
		} else if ($this->isDir($path)) {
			return $this->removeDir($path, $includeContent);
		}
		return false;
	}

	public function getFilesize($path) {
		return filesize($path);
	}

	public function getFileExtension($path) {
		return pathinfo($path, PATHINFO_EXTENSION);
	}

	public function createDir($path, $recursive = true, $config = array()) {
		$mode = isset($config['mode']) ? $config['mode'] : $this->config()->folder_create_mask;
		return mkdir($path, $mode, $recursive);
	}

	public function removeDir($path, $includeContent = false) {
		if($this->isFolder($path)) {
			$content = array_diff(scandir($path), array('.', '..'));
			if(count($content)) {
				if($includeContent) {
					foreach($content as $file) {
						$file = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
						$this->delete($file, true);
					}
				}
			}
			return rmdir($path);
		}
		return false;
	}

	public function getAbsoluteUrl($fileName) {
		return \Director::absoluteUrl($this->getUrl($fileName));
	}

	public function getUrl($fileName) {
		return \Controller::join_links(\Director::baseURL(), $fileName);
	}

}
