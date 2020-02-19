<?php

require_once(__DIR__ . '/src/UcrmPluginListGenerator.php');

$pluginDirectories = new CallbackFilterIterator(
    new DirectoryIterator(__DIR__ . '/plugins'),
    static function (DirectoryIterator $fileInfo) {
        return $fileInfo->isDir() && ! $fileInfo->isDot();
    }
);

$listGenerator = new UcrmPluginListGenerator($pluginDirectories);
echo $listGenerator->getJson() . PHP_EOL;
