<?php

require_once(__DIR__ . '/src/UcrmPluginValidator.php');

$pluginDirectories = new CallbackFilterIterator(
    new DirectoryIterator(__DIR__ . '/plugins'),
    static function (DirectoryIterator $fileInfo) {
        return $fileInfo->isDir() && ! $fileInfo->isDot();
    }
);
$validator = new UcrmPluginValidator();
$errors = $validator->getErrors($pluginDirectories);

printf('Found %d error%s.' . PHP_EOL, $errors, $errors === 1 ? '' : 's');

exit($errors);
