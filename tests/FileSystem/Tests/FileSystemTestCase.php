<?php
namespace FileSystem\Tests;

use FileSystem\FileSystem;

class FileSystemTestCase extends \PHPUnit_Framework_TestCase
{
	public function testRelativePathExists() {
		$this->assertTrue(FileSystem::exists('./'));
	}
}