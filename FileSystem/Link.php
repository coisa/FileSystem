<?php
namespace FileSystem;

use \FileSystem\FileSystem;
use \FileSystem\Exception\LinkException;

class Link extends FileSystem
{
	public function __construct($path, $link) {
		if (!FileSystem::exists($path)) {
			throw new FileSystemException();
		}
		
		
	}
}