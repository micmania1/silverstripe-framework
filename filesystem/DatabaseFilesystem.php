<?php

use \League\Flysystem\FileNotFoundException;

/**
 * This will sync a filesystem to the database. When retrieving files they must belong to
 * the Filesystem that we're currently using. This is done by storing the adapter in the database.
 *
 * Note: All database writes should be dependant upon filesystem changes. Filesystem always takes precedence.
 *
 * @package framework
 * @subpackage filesystem
 *
 * @todo Test if $path->Filename is sufficient.
 */
class DatabaseFilesystem extends Filesystem {

	protected function applyAdapterFilter(DataList $dataList) {
		return $this->filter('AdapaterClassName', get_class($this->getAdapter()));
	}


	/**
	 * Responsible for getting a file from the database.
	 *
	 * @return mixed
	 */
	protected function getFileObjectByPath($path) {
		$dataList = File::get()->filter('Filename', $path);
		return $dataList->applyAdapterFilter($dataList);
	}



	/**
	 * Checks the input parameter. If a string is given then it looks for the path.
	 *
	 * @param $path
	 *
	 * @return mixed
	 */
	protected function resolveFileObject($path) {
		if(is_string($path)) {
			return $this->getFileObjectByPath($path);
		} else if ($path instanceof FileInterface) {
			return $path;
		}
		throw new FilesystemException("Invalid path provided.");
	}



	/**
	 * Checks that $path exists in the database for the correct adapter. If so, it'll check
	 * if the file physically exists on the Filesystem.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function has($path) {
		$file = $this->resolveFileObject($path);
		if($file) {
			return parent::has($file->Filename);
		}
		return false;
	}



	/**
	 * If a path is provided a check is made to ensure the file already exists before writing without
	 * touching the database. If $path is an instanceof File then $path is saved to the database and
	 * the file is written.
	 *
	 * @param string|File $path
	 * @param string $contents
	 * @param null $config
	 *
	 * @return bool
	 * @throws FilesystemException
	 */
	public function write($path, $contents, $config = null) {
		$file = $this->resolveFileObject($path);
		if($file) {
			$file->write();
			return parent::writeStream($file->Filename, $contents, $config);
		}
		throw new FilesystemException("Unable to find file in the database.");
		return false;
	}



	public function writeStream($path, $resource, $config = null) {
		$file = $this->resolveFileObject($path);
		if($file) {
			$file->write();
			return parent::writeStream($file->Filename, $contents, $config);
		}
		throw new FilesystemException("Unable to find file in the database.");
		return false;
	}


	public function put($path, $contents, $config = null) {
		$file = $this->resolveFileObject($path);
		if($file) {
			$file->write();
			return parent::put($file->Filename, $contents, $config);
		}
		throw new FilesystemException("Unable to find file in the database.");
		return false;
	}


	public function putStream($path, $contents, $config = null) {
		$file = $this->resolveFileObject($path);
		if($file) {
			$file->write();
			return parent::putStream($file->Filename, $contents, $config);
		}
		throw new FilesystemException("Invalid path provided.");
		return false;
	}



	/**
	 * A little more functionality is required here to ensure we delete from the filesystem and
	 * from the database. We treat these separately.
	 *
	 * @param string|FileInterface $path
	 *
	 * @return string|void
	 */
	public function readAndDelete($path) {
		$file = $this->resolveFileObject($path);
		if($file) {
			$file->write();
			return parent::readAndDelete($file->Filename);
		} else if (is_string($path)) {
			return parent::readAndDelete($path);
		}
		throw new FileNotFoundException("Unable to find file.");
	}


	public function update($path, $contents, $config = null) {
		$file = $this->resolveFileObject($path);
		if($file) {
			return parent::update($file->Filename);
		}
		throw new FileNotFoundException("Unable to find file.");
		return false;
	}


	public function updateStream($path, $resource, $config = null) {
		$file = $this->resolveFileObject($path);
		if($file) {
			return parent::updateStream($file->Filename, $resource, $config);
		}
		throw new FileNotFoundException("Unable to find file.");
		return false;
	}


	public function read($path) {
		$file = $this->resolveFileObject($path);
		if($file) {
			return parent::read($file->Filename);
		}
		throw new FileNotFoundException("Unable to find file.");
		return false;
	}

	public function readStream($path) {
		$file = $this->resolveFileObject($path);
		if($file) {
			return parent::readStream($file->Filename);
		}
		throw new FileNotFoundException("Unable to find file.");
		return false;
	}

	public function rename($path, $newPath) {
		$file = $this->resolveFileObject($path);
		if($file) {
			if($this->has($newPath)) {
				throw new FileNotFoundException("The a file already exists with that path.");
				return false;
			} else {
				if(parent::rename($file->Filename, $newPath)) {
					$file->Filename = $newPath;
					$file->write();
					return true;
				}
				// Exception already thrown
				return false;
			}
		}
		throw new FileNotFoundException("Unable to find file.");
		return false;
	}

	public function copy($path, $newPath) {
		$file = $this->resolveFileObject($path);
		if($file) {
			$newFile = clone $file;
			if(parent::copy($path, $newPath)) {
				$newFile->Filename = $newPath;
				$newFile->write();
				return true;
			}
			// Exception already thrown
			return false;
		}
		throw new FileNotFoundException("Unable to find file.");
		return false;
	}

	public function delete($path) {
		$file = $this->resovleFileObject($path);
		if($file) {
			if(parent::delete($path)) {
				$file->delete();
				return true;
			}
			// Exception already thrown
			return false;
		}
		throw new FileNotFoundException("Unable to find file.");
		return false;
	}

	public function deleteDir($dirName) {
		$folder = $this->resolveFileObject($dirName);
		if($folder instanceof FolderInterface) {
			if(parent::deleteDir($dirName)) {
				$folder->delete();
				return true;
			}
			// Exception already thrown.
			return false;
		}
		throw new FileNotFoundException("Unable to find file.");
		return false;
	}

	public function createDir($path, $options = null) {
		$folder = $this->resolveFileObject($path);
		if($folder instanceof FolderInterface) {
			if(parent::createDir($folder->Filename, $options)) {
				$folder->write();
				return true;
			}
			// Exception already thrown
			return false;
		}
		throw new FilesystemException("Unable to create directory.");
		return false;
	}

}
