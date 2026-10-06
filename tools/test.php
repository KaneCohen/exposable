<?php

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
$version = Composer\InstalledVersions::getVersion('phpunit/phpunit');
$config = str_starts_with($version, '10.') ? 'phpunit10.xml.dist' : 'phpunit.xml.dist';
$command = [PHP_BINARY];
if (php_ini_loaded_file()) {
    array_push($command, '-c', php_ini_loaded_file());
}
array_push($command, '-d', 'error_reporting=32767', 'vendor/bin/phpunit', '-c', $config, ...array_slice($argv, 1));
$process = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $root);
exit(proc_close($process));
