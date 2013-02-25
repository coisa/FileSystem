<?php
namespace FileSystem;

use \FileSystem\FileSystem;
use \FileSystem\Exception\FolderException;

class Folder extends FileSystem
{
	protected function _create($mode = 0755, $recursive = true) {
		// @todo DirectoryIterate
		if (!$this->_exists() && !mkdir($this->_path, $mode, $recursive)) {
			throw new FolderException("Não foi possível criar o diretório '{$this->path}'.");
		}
		return $this;
	}

	protected function _read() {
		
	}
}