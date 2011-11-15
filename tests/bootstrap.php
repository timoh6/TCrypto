<?php
chdir(__DIR__);
error_reporting(E_ALL | E_STRICT);

require_once '../library/TCrypto/Loader.php';
$loader = new Loader('TCrypto');
$loader->register();
