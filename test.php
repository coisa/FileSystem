<?php
function __autoload($class) {
    require $class . '.php';
}

use \FileSystem\Folder;

echo "<pre>";

try {
	Folder::create('./teste');
} catch(Exception $e) {
	echo $e->getMessage();
}