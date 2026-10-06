<?php

error_reporting(E_ALL);
require __DIR__.'/../vendor/autoload.php';

// Compile all package declarations under E_ALL before PHPUnit installs its
// handler, so implicit nullable declarations cannot slip past the suite.
set_error_handler(static function ($severity, $message, $file, $line) {
    if (!str_starts_with(realpath($file), realpath(__DIR__.'/../src').DIRECTORY_SEPARATOR)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../src')) as $file) {
    if ($file->getExtension() === 'php') {
        require_once $file->getPathname();
    }
}
restore_error_handler();
