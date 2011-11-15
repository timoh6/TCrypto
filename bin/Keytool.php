<?php
$error_level = E_ALL & E_STRICT;

//ini_set('error_reporting', $error_level);
ini_set('display_errors', 1);

$tcryptoLocation = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'TCrypto';

require $tcryptoLocation . DIRECTORY_SEPARATOR . 'Loader.php';
require 'apps.php';

$loader = new Loader();
$loader->register();

$settings = new TCrypto\Tools\SettingStore();
$cli = new TCrypto\Tools\Cli\Horde\Cli();
$optionParser = new TCrypto\Tools\ArrayOptionParser('keytool.config.php');
$helper = new TCrypto\Tools\Keytool\Filesystem();
$keyParser = new TCrypto\Tools\Keytool\KeyParser();

if (!$cli->runningFromCLI())
{
    $cli->fatal('This script must be run from the command line.');
}

$keyfilePermissions = $optionParser->getOption('keyfile_permissions');
$bytesInKeyName = (int) $optionParser->getOption('bytes_in_key_name') < 1 ? 3 : (int) $optionParser->getOption('bytes_in_key_name');
$keyMaxLifetime = (int) $optionParser->getOption('key_max_lifetime') < 1 ? 3600 : (int) $optionParser->getOption('key_max_lifetime');

$keyfileLocationTmp = $optionParser->getOption('keyfile_location');
if ($keyfileLocationTmp === null)
{
    $keyfileLocation = realpath($tcryptoLocation) . DIRECTORY_SEPARATOR . 'Keystore' . DIRECTORY_SEPARATOR . 'default';
}
else
{
    $keyfileLocation = $keyfileLocationTmp;
}

$settings->set('keyfile_permissions', $keyfilePermissions);
$settings->set('bytes_in_key_name', $bytesInKeyName);
$settings->set('keyfile', $keyfileLocation);
$settings->set('key_max_lifetime', $keyMaxLifetime);

$apps = array();
$apps[] = array('title' => 'Exit', 'function' => function($core) {$core->shutDown();});
$apps[] = array('title' => 'Add new keys', 'function' => $addKeys);
$apps[] = array('title' => 'Remove inactive keys', 'function' => $removeInactiveKeys);
$apps[] = array('title' => 'Change keyfile', 'function' => $selectKeyfile);
$apps[] = array('title' => 'Display key count', 'function' => $displayKeyCount);
$apps[] = array('title' => 'Display current keyfile', 'function' => $displayKeyfile);

$core = new TCrypto\Tools\Cli\AppCore($cli, $settings, $helper, $keyParser);
$app = new TCrypto\Tools\Cli\App($core, $apps);
$cli->clearScreen();
$app->run();

$cli->writeln($cli->bold('Have a nice day!'));