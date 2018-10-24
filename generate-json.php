<?php

function findPluginDirectories(): Traversable
{
    return new CallbackFilterIterator(
        new DirectoryIterator(__DIR__ . '/plugins'),
        function (DirectoryIterator $fileInfo) {
            return $fileInfo->isDir() && ! $fileInfo->isDot();
        }
    );
}

$plugins = [];
foreach (findPluginDirectories() as $directory) {
    $file = $directory->getPathname() . '/src/manifest.json';

    $manifest = json_decode(file_get_contents($file), true);

    $plugins[] = $manifest['information'];
}

echo json_encode(['plugins' => array_values(array_filter($plugins))], JSON_PRETTY_PRINT);
