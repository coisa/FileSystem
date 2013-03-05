<?php
namespace FileSystem;

use \FileSystem\Exception\FileSystemException;

class FileSystem
{
	protected $_path;
	
	public function __construct($path, $create = true, $mode = 0755) {
		$this->_cd($path);
		
		if ($create && !$this->_exists() && method_exists($this, '_create')) {
			$this->_create($mode);
		}
	}
	
	public function __call($name, $args) {
		$method = "_{$name}";
		
		if (method_exists($this, $method)) {
			return call_user_func_array(array($this, $method), $args);
		}
	}
	
	public static function __callStatic($name, $args) {
		$method = "_{$name}";

		if (method_exists(get_called_class(), $method)) {
			$path = array_shift($args);

			$class = get_called_class();
			$self = new $class($path, false);

			return call_user_func_array(array($self, $method), $args);
		}
	}

	protected function _pwd() {
		return $this->_path;
	}

	protected function _cd($path) {
		$this->_path = realpath($path);
		
		return $this;
	}
	
	protected function _exists() {
		return file_exists($this->_path);
	}
	
	protected function _chmod($mode = 0755, $recursive = true) {
		if ($recursive && $this->_isDir()) {
			if ($handle = @opendir($this->_path)) {
				while (false !== ($item = readdir($handle))) {
					$path = rtrim($this->_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item;

					if (!chmod($path, intval($mode, 8))) {
						throw new FileSystemException("Não foi possível trocar as permissões de acesso do caminho '{$path}' para '{$mode}'.");
					}

					if (is_dir($path)) {
						$fileSystem = new FileSystem($path, false);
						$fileSystem->chmod($mode);
					}
				}
				closedir($handle);
			}
		} else {
			if (!chmod($this->_path, intval($mode, 8))) {
				throw new FileSystemException("Não foi possível trocar as permissões de acesso do caminho '{$this->_path}' para '{$mode}'.");
			}
		}
		return $this;
	}
	
	protected function _chown($user, $recursive = true) {
		if ($recursive && $this->_isDir()) {
			if ($handle = @opendir($this->_path)) {
				while (false !== ($item = readdir($handle))) {
					$path = rtrim($this->_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item;

					if (!chown($path, $user)) {
						throw new FileSystemException("Não foi possível trocar as permissões de usuário do caminho '{$path}' para '{$user}'.");
					}

					if (is_dir($path)) {
						$fileSystem = new FileSystem($path, false);
						$fileSystem->chown($user, $recursive);
					}
				}
				closedir($handle);
			}
		} else {
			if (!chown($this->_path, $user)) {
				throw new FileSystemException("Não foi possível trocar as permissões de usuário do caminho '{$this->_path}' para '{$user}'.");
			}
		}
		return $this;
	}
	
	protected function _chgrp($group, $recursive = true) {
		if ($recursive && $this->_isDir()) {
			if ($handle = @opendir($this->_path)) {
				while (false !== ($item = readdir($handle))) {
					$path = rtrim($this->_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item;

					if (!chgrp($path, $user)) {
						throw new FileSystemException("Não foi possível trocar as permissões de grupo do caminho '{$path}' para '{$group}'.");
					}

					if (is_dir($path)) {
						$fileSystem = new FileSystem($path, false);
						$fileSystem->chgrp($user, $recursive);
					}
				}
				closedir($handle);
			}
		} else {
			if (!chgrp($this->_path, $user)) {
				throw new FileSystemException("Não foi possível trocar as permissões de grupo do caminho '{$this->_path}' para '{$group}'.");
			}
		}
		return $this;
	}
	
	protected function _cp($to, $mode = 0755) {
		return $this->_copy($to, $mode);
	}
	
	protected function _copy($to, $mode = 0755) {
		// @todo not overwrite if file exists
		if ($this->_isDir()) {
			$toDir = Folder::create($to, $mode);

			if ($handle = @opendir($this->_path)) {
				while (false !== ($item = readdir($handle))) {
					$from = rtrim($this->_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item;
					$to = rtrim($toDir->pwd(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item;

					if (!$toDir->isWritable()) {
						throw new FileSystemException("Não foi possível copiar a origem '{$from}' para o destino '{$to}', pois o destino não tem permissão de escrita.");
					}

					if (is_file($from)) {
						if (copy($from, $to)) {
							chmod($to, intval($mode, 8));
							touch($to, filemtime($from));
						} else {
							throw new FileSystemException("Não foi possível copiar o arquivo '{$from}' para o destino '{$to}'.");
						}
					}

					if (is_dir($from)) {
						$old = umask(0);

						if (mkdir($to, $mode)) {
							umask($old);
							$old = umask(0);
							chmod($to, intval($mode, 8));
							umask($old);

							$fileSystem = new FileSystem($from, false);
							$fileSystem->copy($to, $mode);
						} else {
							throw new FileSystemException("Não foi possível copiar a pasta '{$from}' para o destino '{$to}'.");
						}
					}
				}
				closedir($handle);
			}
		} else {
			if (copy($this->_path, $to)) {
				chmod($to, intval($mode, 8));
				touch($to, filemtime($this->_path));
			} else {
				throw new FileSystemException("Não foi possível copiar a origem '{$this->_path}' para o destino '{$to}'.");
			}
		}
		return $this;
	}
	
	protected function _mv($to, $overwrite = true) {
		return $this->_move($to, $overwrite);
	}
	
	protected function _move($to, $overwrite = true) {
		if ($overwrite === false && file_exists($to)) {
			throw new FileSystemException("Não foi possível mover a origem '{$this->_path}' para o destino '{$to}', pois o caminho destino já existe e não deve ser substituído.");
		}

		if (!rename($this->_path, $to)) {
			throw new FileSystemException("Não foi possível mover a origem '{$this->_path}' para o destino '{$to}'.");
		}

		$this->_cd($to);

		return $this;
	}
	
	protected function _rm() {
		$this->_remove();
	}
	
	protected function _remove() {
		if ($this->_isDir()) {
			try {
				$directory = new RecursiveDirectoryIterator($this->_path, RecursiveDirectoryIterator::CURRENT_AS_SELF);
				$iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::CHILD_FIRST);
			} catch(Exception $e) {
				throw new FileSystemException("Não foi possível remover o caminho '{$this->_path}'.");
			}

			foreach ($iterator as $item) {
				$filePath = $item->getPathname();

				if ($item->isFile() || $item->isLink()) {
					if (!@unlink($filePath)) {
						throw new FileSystemException("Não foi possível remover o caminho '{$filePath}'.");
					}
				} elseif ($item->isDir() && !$item->isDot()) {
					if (!@rmdir($filePath)) {
						throw new FileSystemException("Não foi possível remover o caminho '{$filePath}'.");
					}
				}
			}

			$path = rtrim($this->_path, DS);
			
			if (!@rmdir($this->_path)) {
				throw new FileSystemException("Não foi possível remover o caminho '{$this->_path}'.");
			}
		} else {
			unlink($this->_path);
		}
		return $this;
	}

	protected function _size() {
		if (!$this->_exists()) {
			throw new FileSystemException("Não foi possível calcular o tamanho do caminho. O caminho '{$this->_path}' parece não existir.");
		}

		if ($this->_isDir()) {
			$size = 0;

			if ($handle = @opendir($this->_path)) {
				while (false !== ($item = readdir($handle))) {
					if ($item === '.' || $item === '..') {
						continue;
					}

					$path = rtrim($this->_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item;

					if (is_file($path)) {
						$size += filesize($path);
					}

					if (is_dir($path)) {
						$fileSystem = new FileSystem($path, false);
						$size += $fileSystem->size();
					}
				}
				closedir($handle);
			}
		} else {
			$size = filesize($this->_path);
		}

		return $size;
	}
	
	protected function _isDir() {
		return is_dir($this->_path);
	}
	
	protected function _isFile() {
		return is_file($this->_path);
	}
	
	protected function _isLink() {
		return is_link($this->_path);
	}
	
	protected function _isWritable() {
		return is_writable($this->_path);
	}
	
	protected function _isReadable() {
		return is_readable($this->_path);
	}
	
	protected function _isExecutable() {
		return is_executable($this->_path);
	}
}