<?php

if (! class_exists(ZipArchive::class)) {
    echo 'This script requires zip PHP extension.' . PHP_EOL;
    exit(1);
}

$plugin = rtrim($argv[1] ?? '', '/');

if (! $plugin || ! preg_match('~^(?:[a-z-_]++)$~', $plugin)) {
    echo 'Plugin name was not specified or is invalid.' . PHP_EOL;
    exit(1);
}

$directory = __DIR__ . '/plugins/' . $plugin . '/src';

if (! is_dir($directory)) {
    $directory = __DIR__ . '/examples/' . $plugin . '/src';
    if (is_dir($directory)) {
        echo 'Specified plugin was not found in the "plugins" directory, using "examples" directory instead.' . PHP_EOL;
    } else {
        echo 'Specified plugin was not found in "plugins" nor "examples" directories.' . PHP_EOL;
        exit(1);
    }
}

chdir($directory);

if (file_exists($directory . '/composer.json')) {
    exec('composer validate --no-check-publish --no-interaction', $result, $exitCode);
    echo implode(PHP_EOL, $result);
    if ($exitCode !== 0) {
        exit($exitCode);
    }
    exec('composer install --classmap-authoritative --no-dev --no-interaction --no-suggest', $result, $exitCode);
    echo implode(PHP_EOL, $result);
    if ($exitCode !== 0) {
        exit($exitCode);
    }
}

$zipFile = $directory . '/../' . $plugin . '.zip';

if (file_exists($zipFile)) {
    unlink($zipFile);
}

$zip = new ZipArchive();

if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
    echo 'Can\'t open zip file.' . PHP_EOL;
    exit(1);
}

// add README, if present
$readmeFilename = $directory . '/../README.md';
$readme = new SplFileInfo($readmeFilename);
if ($readme->isReadable()) {
    @$zip->addFile($readme->getPathname(), $readme->getBasename());
}

$files = new CallbackFilterIterator(
    new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($directory)
    ),
    static function (SplFileInfo $fileInfo) {
        return ! $fileInfo->isDir();
    }
);

$reservedFiles = [
    '/ucrm.json',
    '/data/config.json',
    '/data/plugin.json',
    '/data/plugin.log',
    '/.ucrm-plugin-running',
    '/.ucrm-plugin-execution-requested',
];

/** @var SplFileInfo $fileInfo */
foreach ($files as $fileInfo) {
    $filename = substr(str_replace('\\', '/', $fileInfo->getPathname()), strlen($directory));

    if (in_array($filename, $reservedFiles, true)) {
        echo sprintf('Skipping reserved file "%s".', $filename) . PHP_EOL;
        continue;
    }

    if (strpos($filename, '/.') === 0) {
        echo sprintf('Skipping hidden file "%s".', $filename) . PHP_EOL;
        continue;
    }

    if (! $zip->addFile($fileInfo->getPathname(), ltrim($filename, '/'))) {
        echo sprintf('Unable to add file "%s".', $filename) . PHP_EOL;
        exit(1);
    }
}

$zip->close();
