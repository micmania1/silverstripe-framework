<?php

class FilesystemTest extends SapphireTest {

	public function getFilesystem() {
		$filesystem = new \SilverStripe\Framework\Filesystem\Filesystem('assets');
		return $filesystem->setPathSeparator('/')->setBasePath($this->getBaseFolder());
	}


	public function getBaseFolder() {
		return '/root/path/assets';
	}


	public function testMakeAbsolute() {
		$relative = 'test/path/';
		$absolute = $this->getBaseFolder() . '/test/path';
		$this->assertEquals($absolute, $this->getFilesystem()->makeAbsolute($relative));

		$relative = 'test/path/../';
		$absolute = $this->getBaseFolder() . '/test';
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
		$path = '/test/folder';
		$this->assertEquals('test/folder', $this->getFilesystem()->makeRelative($path));

		$path = $this->getBaseFolder() . '/allowed/../allowed/test';
		$this->assertEquals('allowed/test', $this->getFilesystem()->makeRelative($path));
	}


	public function testSandboxPath() {
		$path = $this->getBaseFolder() . '/test';
		$this->assertEquals($this->getBaseFolder() . '/test', $this->getFilesystem()->sandboxPath($path));

		$path = '/root';
		$this->assertEquals($this->getbaseFolder() . '/root', $this->getFilesystem()->sandboxPath($path));

		$this->setExpectedException('Exception');
		$this->getFilesystem()->sandboxPath($this->getbaseFolder() . '/../../../');
	}


	public function testIsBasePath() {
		$path = $this->getBaseFolder();
		$this->assertTrue($this->getFilesystem()->isBasePath($path));

		$path = '/root';
		$this->assertFalse($this->getFilesystem()->isBasePath($path));

		$path = $this->getBaseFolder() . '/test';
		$this->assertFalse($this->getFilesystem()->isBasePath($path));
	}

}
