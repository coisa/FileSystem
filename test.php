<?php
function __autoload($class) {
	require $class . '.php';
}

use \FileSystem\FileSystem;
use \FileSystem\Folder;

echo "<pre>";

try {
	//Folder::create('./teste');
	echo Folder::size('./');
} catch(Exception $e) {
	echo $e->getMessage();
}