<?php

require_once(__DIR__ . '/src/UcrmPluginListGenerator.php');
require_once(__DIR__ . '/src/UcrmPluginValidator.php');

$pluginDirectories = new CallbackFilterIterator(
    new DirectoryIterator(__DIR__ . '/plugins'),
    static function (DirectoryIterator $fileInfo) {
        return $fileInfo->isDir() && ! $fileInfo->isDot();
    }
);

$validator = new UcrmPluginValidator(
    $pluginDirectories,
    __DIR__ . '/plugins.json'
);
$errors = $validator->getErrors();

printf(
    'Found %d error%s.' . PHP_EOL,
    $errors,
    $errors === 1 ? '' : 's'
);

exit($errors);
