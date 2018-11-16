<?php

function ensureFileExists(string $file): int
{
    if (! file_exists($file)) {
        printf('Could not find required file "%s".'.PHP_EOL, $file);

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
                printf('Manifest does not contain required key "%s".'.PHP_EOL, implode('.', $keys));

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
    $manifest = parseManifest($manifestData);
    if (! $manifest) {
        printf('File "%s" is not a valid JSON.'.PHP_EOL, $file);
        ++$errors;

        return $errors;
    }

    $errors += ensureArrayKeyExists($manifest, 'information');
    $errors += ensureArrayKeyExists($manifest, 'information', 'name');

    $name = null;
    if ($errors === 0) {
        $name = $manifest['information']['name'];
        $directory = dirname($file, 2);
        $zipFile = $directory.'/'.$name.'.zip';
        $errors += ensureFileExists($zipFile);
        $errors += ensureManifestMatches($zipFile, $manifest, $file);

        $basename = pathinfo($directory, PATHINFO_BASENAME);

        if ($basename !== $name) {
            printf('Directory name "%s" doesn\'t match the plugin name "%s".'.PHP_EOL, $basename, $name);
            ++$errors;
        }
    }

    $errors += validateManifestData($manifest, $name);

    return $errors;
}

function parseManifest(string $manifestString): ?array
{
    $manifest = json_decode($manifestString, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }

    return $manifest;
}

/**
 * @param $manifest
 * @param int $errors
 * @param $name
 * @return int
 */
function validateManifestData(array $manifest, string $name): int
{
    $errors = 0;
    $errors += ensureArrayKeyExists($manifest, 'version');
    $errors += ensureArrayKeyExists($manifest, 'information', 'displayName');
    $errors += ensureArrayKeyExists($manifest, 'information', 'description');
    $errors += validateUrl($manifest, $name);
    $errors += ensureArrayKeyExists($manifest, 'information', 'version');
    $errors += ensureArrayKeyExists($manifest, 'information', 'ucrmVersionCompliancy');
    $errors += ensureArrayKeyExists($manifest, 'information', 'ucrmVersionCompliancy', 'min');
    $errors += ensureArrayKeyExists($manifest, 'information', 'author');

    return $errors;
}

function ensureManifestMatches(string $zipFile, array $manifest, string $manifestFile): int
{
    $zipHandle = zip_open(realpath($zipFile));
    if (! is_resource($zipHandle)) {
        printf('Could not open zipfile - invalid format: "%s"'.PHP_EOL, $zipFile);

        return 1;
    }

    $manifestZipString = '';
    while ($zipEntry = zip_read($zipHandle)) {
        if (zip_entry_name($zipEntry) !== 'manifest.json') {
            continue;
        }
        $manifestZipString = zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
        break;
    }

    if (! $manifestZipString) {
        printf('Could not read manifest.json from zipfile "%s"'.PHP_EOL, $zipFile);

        return 1;
    }
    $manifestZip = parseManifest($manifestZipString);
    if (! $manifestZip) {
        printf('Could not parse manifest.json from zipfile "%s"'.PHP_EOL, $zipFile);

        return 1;
    }

    $arrayDifference = arrayRecursiveDiff($manifestZip, $manifest);
    if (count($arrayDifference) > 0) {
        printf(
            'Different manifest.json from zipfile "%s" and from "%s" - plugin not packed yet?'.PHP_EOL,
            $zipFile,
            $manifestFile
        );
        printArrayRecursiveDiff('', $arrayDifference, $manifest, $manifestZip);

        return 1;
    }

    return 0;
}

function printArrayRecursiveDiff($keyPrefix, array $arrayDifference, array $manifest, array $manifestZip): void
{
    foreach ($arrayDifference as $key => $value) {
        if (is_array($manifest[$key])) {
            printArrayRecursiveDiff(
                $key.':',
                arrayRecursiveDiff($manifest[$key], $manifestZip[$key]),
                $manifest[$key],
                $manifestZip[$key]
            );
        } else {
            printf(
                "\t%s%s%s\t\t file: %s%s\t\t zip:  %s%s",
                $keyPrefix,
                $key,
                PHP_EOL,
                $manifest[$key] ?? '(none)',
                PHP_EOL,
                $manifestZip[$key] ?? '(none)',
                PHP_EOL
            );
        }
    }
}

function arrayRecursiveDiff($aArray1, $aArray2)
{
    $aReturn = array();

    foreach ($aArray1 as $mKey => $mValue) {
        if (array_key_exists($mKey, $aArray2)) {
            if (is_array($mValue)) {
                $aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                if (count($aRecursiveDiff)) {
                    $aReturn[$mKey] = $aRecursiveDiff;
                }
            } else {
                if ($mValue != $aArray2[$mKey]) {
                    $aReturn[$mKey] = $mValue;
                }
            }
        } else {
            $aReturn[$mKey] = $mValue;
        }
    }

    return $aReturn;
}

function validateUrl(array $manifest, ?string $name): int
{
    $errors = 0;

    $errors += ensureArrayKeyExists($manifest, 'information', 'url');

    if ($errors === 0 && $name !== null) {
        $url = $manifest['information']['url'];
        $correctUrl = sprintf(
            'https://github.com/Ubiquiti-App/UCRM-plugins/tree/master/plugins/%s',
            $name
        );

        if ($url !== $correctUrl) {
            printf('Url for plugin "%s" should be "%s".'.PHP_EOL, $name, $correctUrl);
            ++$errors;
        }
    }

    return $errors;
}

function validatePlugin(SplFileInfo $pluginDirectory): int
{
    $path = $pluginDirectory->getPathname();

    $errors = 0;

    $errors += ensureFileExists($path.'/README.md');
    $errors += ensureFileExists($path.'/src/main.php');
    $errors += validateManifest($path.'/src/manifest.json');

    return $errors;
}

function checkPluginsJson(): int
{
    ob_start();
    require __DIR__.'/generate-json.php';
    $correctJson = ob_get_clean();

    $currentJson = file_get_contents(__DIR__.'/plugins.json');

    if (json_decode($currentJson, true) === json_decode($correctJson, true)) {
        return 0;
    }

    printf(
        'The "plugins.json" file is not up to date. Run `php generate-json.php > plugins.json` to update it.'.PHP_EOL
    );

    return 1;
}

$pluginDirectories = new CallbackFilterIterator(
    new DirectoryIterator(__DIR__.'/plugins'),
    function (DirectoryIterator $fileInfo) {
        return $fileInfo->isDir() && ! $fileInfo->isDot();
    }
);

$errors = 0;
foreach ($pluginDirectories as $directory) {
    $errors += validatePlugin($directory);
}

$errors += checkPluginsJson();

printf('Found %d errors.'.PHP_EOL, $errors);

exit($errors);
