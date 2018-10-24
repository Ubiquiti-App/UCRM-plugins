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

function ensureFileExists(string $file): int
{
    if (! file_exists($file)) {
        printf('Could not find required file "%s".' . PHP_EOL, $file);
        return 1;
    }

    return 0;
}

function ensureArrayKeyExists(array $array, string... $keys): int
{
    $count = count($keys);
    $i = 1;
    foreach ($keys as $key) {
        if (! array_key_exists($key, $array)) {
            if ($i === $count) {
                printf('Manifest does not contain required key "%s".' . PHP_EOL, implode('.', $keys));
                return 1;
            }

            return 0;
        }

        $array = $array[$key];
        ++$i;
    }

    return 0;
}

function validateManifest(string $file): int
{
    $errors = 0;

    $errors += ensureFileExists($file);

    if ($errors) {
        return $errors;
    }

    $manifestData = file_get_contents($file);
    $manifest = json_decode($manifestData, true);

    $errors += ensureArrayKeyExists($manifest, 'information');
    $errors += ensureArrayKeyExists($manifest, 'information', 'name');

    if ($errors === 0) {
        $name = $manifest['information']['name'];
        $directory = dirname(dirname($file));
        $errors += ensureFileExists($directory . '/' . $name . '.zip');
        $basename = pathinfo($directory, PATHINFO_BASENAME);

        if ($basename !== $name) {
            printf('Directory name "%s" doesn\'t match the plugin name "%s".' . PHP_EOL, $basename, $name);
            $errors += 1;
        }
    }

    $errors += ensureArrayKeyExists($manifest, 'version');
    $errors += ensureArrayKeyExists($manifest, 'information', 'displayName');
    $errors += ensureArrayKeyExists($manifest, 'information', 'description');
    $errors += ensureArrayKeyExists($manifest, 'information', 'url');
    $errors += ensureArrayKeyExists($manifest, 'information', 'version');
    $errors += ensureArrayKeyExists($manifest, 'information', 'ucrmVersionCompliancy');
    $errors += ensureArrayKeyExists($manifest, 'information', 'ucrmVersionCompliancy', 'min');
    $errors += ensureArrayKeyExists($manifest, 'information', 'author');



    return $errors;
}

function validatePlugin(SplFileInfo $pluginDirectory): int
{
    $path = $pluginDirectory->getPathname();

    $errors = 0;

    $errors += ensureFileExists($path . '/README.md');
    $errors += ensureFileExists($path . '/src/main.php');
    $errors += validateManifest($path . '/src/manifest.json');

    return $errors;
}

$errors = 0;
foreach (findPluginDirectories() as $directory) {
    $errors += validatePlugin($directory);
}

printf('Found %d errors.' . PHP_EOL, $errors);

exit($errors);
