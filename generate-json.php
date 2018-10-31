<?php

$pluginDirectories = new CallbackFilterIterator(
    new DirectoryIterator(__DIR__ . '/plugins'),
    function (DirectoryIterator $fileInfo) {
        return $fileInfo->isDir() && ! $fileInfo->isDot();
    }
);

$plugins = [];
foreach ($pluginDirectories as $directory) {
    $file = $directory->getPathname() . '/src/manifest.json';

    $manifest = json_decode(file_get_contents($file), true);
    $plugin = $manifest['information'];
    $plugin['zipUrl'] = sprintf(
        'https://github.com/Ubiquiti-App/UCRM-plugins/raw/master/plugins/%s/%s.zip',
        $plugin['name'],
        $plugin['name']
    );

    $plugins[$plugin['name']] = $plugin;
}

ksort($plugins);

echo json_encode(['plugins' => array_values(array_filter($plugins))], JSON_PRETTY_PRINT);
