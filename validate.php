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

function requireFile(string $file): int
{
    if (! file_exists($file)) {
        printf('Could not find required file "%s".' . PHP_EOL, $file);
        return 1;
    }

    return 0;
}

function requireArrayKey(array $array, string... $keys): int
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

    $errors += requireFile($file);

    if ($errors) {
        return $errors;
    }

    $manifestData = file_get_contents($file);
    $manifest = json_decode($manifestData, true);

    $errors += requireArrayKey($manifest, 'information');
    $errors += requireArrayKey($manifest, 'information', 'name');

    if ($errors === 0) {
        $name = $manifest['information']['name'];
        $directory = dirname(dirname($file));
        $errors += requireFile($directory . '/' . $name . '.zip');
        $basename = pathinfo($directory, PATHINFO_BASENAME);

        if ($basename !== $name) {
            printf('Directory name "%s" doesn\'t match the plugin name "%s".' . PHP_EOL, $basename, $name);
            $errors += 1;
        }
    }

    $errors += requireArrayKey($manifest, 'version');
    $errors += requireArrayKey($manifest, 'information', 'displayName');
    $errors += requireArrayKey($manifest, 'information', 'description');
    $errors += requireArrayKey($manifest, 'information', 'url');
    $errors += requireArrayKey($manifest, 'information', 'version');
    $errors += requireArrayKey($manifest, 'information', 'ucrmVersionCompliancy');
    $errors += requireArrayKey($manifest, 'information', 'ucrmVersionCompliancy', 'min');
    $errors += requireArrayKey($manifest, 'information', 'author');



    return $errors;
}

function validatePlugin(SplFileInfo $pluginDirectory): int
{
    $path = $pluginDirectory->getPathname();

    $errors = 0;

    $errors += requireFile($path . '/README.md');
    $errors += requireFile($path . '/src/main.php');
    $errors += validateManifest($path . '/src/manifest.json');

    return $errors;
}

$errors = 0;
foreach (findPluginDirectories() as $directory) {
    $errors += validatePlugin($directory);
}

printf('Found %d errors.' . PHP_EOL, $errors);

exit($errors);
