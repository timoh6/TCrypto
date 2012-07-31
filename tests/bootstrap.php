<?php
chdir(__DIR__);
error_reporting(E_ALL | E_STRICT);

// Load TCrypto files
require_once '../library/TCrypto/Loader.php';
$loader = new TCrypto\Loader('TCrypto');
$loader->register();