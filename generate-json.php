<?php

$pluginDirectories = new CallbackFilterIterator(
    new DirectoryIterator(__DIR__ . '/plugins'),
    function (DirectoryIterator $fileInfo) {
        return $fileInfo->isDir() && ! $fileInfo->isDot();
    }
);

$plugins = [];
/** @var SplFileInfo $directory */
foreach ($pluginDirectories as $directory) {
    $file = $directory->getPathname() . '/src/manifest.json';

    $manifest = json_decode(file_get_contents($file), true, 50);
    if (json_last_error() !== JSON_ERROR_NONE) {
        fwrite(STDERR, sprintf('Skipped file "%s": %s' . PHP_EOL, $file, json_last_error_msg()));
        continue;
    }
    $plugin = $manifest['information'];
    $plugin['name'] = $plugin['name'] ?? sprintf('(name missing in %s manifest)', $directory->getBaseName());
    $plugin['zipUrl'] = sprintf(
        'https://github.com/Ubiquiti-App/UCRM-plugins/raw/master/plugins/%s/%s.zip',
        $plugin['name'],
        $plugin['name']
    );
    fwrite(STDERR, sprintf("\t%s\t%s\t\t%s\n", $plugin['version'] ?? '(no version)', $plugin['name'], $plugin['url'] ?? '(URL missing)'));

    $plugins[$plugin['name']] = $plugin;
}

ksort($plugins);

fwrite(STDERR, 'Plugins found: ' . count($plugins) . "\n");
echo json_encode(['plugins' => array_values(array_filter($plugins))], JSON_PRETTY_PRINT) . PHP_EOL;
