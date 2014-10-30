<?php

use \SilverStripe\Framework\Filesystem\Filesystem;

class FilesystemTest extends SapphireTest {

	protected $filesystem;

	public function getFilesystem() {
		if($this->filesystem) return $this->filesystem;
		return $this->filesystem = new Filesystem('assets/', 'assets/');
	}


	public function getBaseFolder() {
		return $this->getFilesystem()->getBasePath();
	}


	public function testMakeAbsolute() {
		$relative = 'test/path/';
		$absolute = $this->getBaseFolder() . '/test/path';
		$this->assertEquals($absolute, $this->getFilesystem()->makeAbsolute($relative));

		$relative = 'test/path/../';
		$absolute = $this->getBaseFolder() . '/test';
		$this->assertEquals($absolute, $this->getFilesystem()->makeAbsolute($relative));

		$relative = 'FileTest-subfolder';
		$absolute = $this->getBaseFolder() . '/' . $relative;
		$this->assertEquals($absolute, $this->getFilesystem()->makeAbsolute($relative));
	}


	public function testResolvePath() {
		$relative = 'test/path/';
		$absolute = $this->getBaseFolder() . '/test/path';
		$this->assertEquals($absolute, $this->getFilesystem()->resolvePath($relative));

		$relative = 'test/path/../';
		$absolute = $this->getBaseFolder() . '/test';
		$this->assertEquals($absolute, $this->getFilesystem()->resolvePath($relative));

		$relative = 'test/path/../path/test';
		$absolute = $this->getBaseFolder() . '/test/path/test';
		$this->assertEquals($absolute, $this->getFilesystem()->resolvePath($relative));

		$relative = 'test/path/.';
		$absolute = $this->getBaseFolder() . '/test/path';
		$this->assertEquals($absolute, $this->getFilesystem()->resolvePath($relative));

		$base = $this->getBaseFolder();
		$this->assertEquals($base, $this->getFilesystem()->resolvePath($base));

		$this->assertEquals('/', $this->getFilesystem()->resolvePath('/'));
	}


	public function testMakeRelative() {
		$path = $this->getBaseFolder() . '/test';
		$this->assertEquals('test', $this->getFilesystem()->makeRelative($path));

		$path = $this->getBaseFolder() . '/test/folder';
		$this->assertEquals('test/folder', $this->getFilesystem()->makeRelative($path));

		// Test a folder that's already relative.
		$path = 'test/folder';
		$this->assertEquals('test/folder', $this->getFilesystem()->makeRelative($path));

		// Same as above but with leading slash.
		$path = $this->getBaseFolder() . '/test/folder';
		$this->assertEquals('test/folder', $this->getFilesystem()->makeRelative($path));

		$path = $this->getBaseFolder() . '/allowed/../allowed/test';
		$this->assertEquals('allowed/test', $this->getFilesystem()->makeRelative($path));
	}


	public function testIsSandboxed() {
		$path = $this->getBaseFolder() . '/test';
		$this->assertTrue($this->getFilesystem()->isSandboxed($path));

		$path = $this->getBaseFolder();
		$this->assertTrue($this->getFilesystem()->isSandboxed($path));

		$path = $this->getBaseFolder() . '/../';
		$this->assertFalse($this->getFilesystem()->isSandboxed($path));

		$path = $this->getbaseFolder() . '/../../../';
		$this->assertFalse($this->getFilesystem()->isSandboxed($path));
	}


	public function testSandboxPath() {
		$path = $this->getBaseFolder() . '/test';
		$this->assertEquals($this->getBaseFolder() . '/test', $this->getFilesystem()->sandboxPath($path));

		$path = 'root';
		$this->assertEquals($this->getbaseFolder() . '/root', $this->getFilesystem()->sandboxPath($path));

		$this->setExpectedException('Exception');
		$this->getFilesystem()->sandboxPath($this->getBaseFolder() . '/../../../');
	}


	public function testIsBasePath() {
		$path = $this->getBaseFolder();
		$this->assertTrue($this->getFilesystem()->isBasePath($path));

		$path = '/root';
		$this->assertFalse($this->getFilesystem()->isBasePath($path));

		$path = $this->getBaseFolder() . '/test';
		$this->assertFalse($this->getFilesystem()->isBasePath($path));
	}


	public function testIsAbsolute() {
		$relative = 'FileTest-subfolder';
		$absolute = $this->getFilesystem()->getBasePath() . '/' . $relative;

		$this->assertFalse($this->getFilesystem()->isAbsolute($relative));
		$this->assertTrue($this->getFilesystem()->isAbsolute($absolute));

		$this->assertTrue($this->getFilesystem()->isAbsolute('/'));
	}


	public function testGetCurrentDir() {
		$dir = 'test';
		$this->assertEquals($this->getBaseFolder() . '/test', $this->getFilesystem()->getCurrentDir($dir));

		$dir = 'test/Somefile.gif';
		$this->assertEquals($this->getBaseFolder() . '/test', $this->getFilesystem()->getCurrentDir($dir));
	}

	public function testGetFileExtension() {
		$path = 'test/Somefile.gif';
		$this->assertEquals('gif', $this->getFilesystem()->getFileExtension($path));

		$path = 'test/';
		$this->assertNull($this->getFilesystem()->getFileExtension($path));

		$path = 'Somefile.TXT';
		$this->assertEquals('txt', $this->getFilesystem()->getFileExtension($path));
	}


	public function testGetUrl() {
		$filesystem = $this->getFilesystem();
		$filesystem->setBaseUrl('http://localhost/assets');

		$path = 'test.pdf';
		$this->assertEquals('http://localhost/assets/' . $path, $filesystem->getUrl($path));
	}

	public function testGetAbsoluteUrl() {
		$filesystem = $this->getFilesystem();
		$filesystem->setBaseUrl('http://localhost/assets');

		$path = 'test.pdf';
		$this->assertEquals('http://localhost/assets/' . $path, $filesystem->getAbsoluteUrl($path));
	}


	public function testGetRelativeUrl() {
		$filesystem = $this->getFilesystem();
		$filesystem->setBaseUrl('http://localhost/assets');

		$path = 'test.pdf';
		$this->assertEquals('assets/' . $path, $filesystem->getRelativeUrl($path));
	}

}
