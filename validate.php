<?php

const UCRM_MAX_PLUGIN_URL_LENGTH = 255;

function ensureFileExists(string $file): int
{
    if (! file_exists($file)) {
        printf('Could not find required file "%s".' . PHP_EOL, $file);

        return 1;
    }

    return 0;
}

function ensureComposerLockExists(string $path): int
{
    $composerJson = $path . '/src/composer.json';
    $composerLock = $path . '/src/composer.lock';
    if (file_exists($composerJson) && ! file_exists($composerLock)) {
        $pluginName = basename($path);
        printf('%s: Found composer.json, but did not find "%s".' . PHP_EOL, $pluginName, $composerLock);

        return 1;
    }

    return 0;
}

function ensureArrayKeyExists(array $array, string ...$keys): int
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
    $manifest = parseManifest($manifestData);
    if (! $manifest) {
        printf('File "%s" is not a valid JSON.' . PHP_EOL, $file);
        ++$errors;

        return $errors;
    }

    $errors += ensureArrayKeyExists($manifest, 'information');
    $errors += ensureArrayKeyExists($manifest, 'information', 'name');

    $name = null;
    $directory = dirname($file, 2);
    $basename = pathinfo($directory, PATHINFO_BASENAME);

    if ($errors === 0) {
        $name = $manifest['information']['name'] ?? null;
        $zipFile = $directory . '/' . $name . '.zip';
        $errors += ensureFileExists($zipFile);
        $errors += ensureManifestMatches($zipFile, $manifest, $file);

        if ($basename !== $name) {
            printf('Directory name "%s" doesn\'t match the plugin name "%s".' . PHP_EOL, $basename, $name);
            ++$errors;
        }
    }

    if (! $name) {
        printf('Plugin name is required for "%s".' . PHP_EOL, $basename);
        ++$errors;

        return $errors;
    }

    $errors += validateManifestData($manifest, $name);

    $errors += validateManifestConfiguration($manifest, $name);

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

function validateManifestData(array $manifest, string $name): int
{
    $errors = 0;
    $errors += ensureArrayKeyExists($manifest, 'version');
    $errors += ensureArrayKeyExists($manifest, 'information', 'displayName');
    $errors += ensureArrayKeyExists($manifest, 'information', 'description');
    $errors += validateUrl($manifest, $name);
    $errors += ensureArrayKeyExists($manifest, 'information', 'version');
    $errors += ensureArrayKeyExists($manifest, 'information', 'author');

    return $errors;
}

function validateManifestConfiguration(array $manifest, string $name): int
{
    if (! array_key_exists('configuration', $manifest)) {
        return 0;
    }

    $errors = 0;

    $configurationKeys = [];
    foreach ($manifest['configuration'] as $configuration) {
        if (ensureArrayKeyExists($configuration, 'key') === 1) {
            ++$errors;

            continue;
        }

        $errors += ensureArrayKeyExists($configuration, 'label');

        if (in_array($configuration['key'], $configurationKeys, true)) {
            printf(
                'Manifest of "%s" plugin has duplicate configuration for item "%s".' . PHP_EOL,
                $name,
                $configuration['key']
            );

            ++$errors;
        } else {
            $configurationKeys[] = $configuration['key'];
        }
    }

    return $errors;
}

function ensureManifestMatches(string $zipFile, array $manifest, string $manifestFile): int
{
    $zipArchive = new ZipArchive();
    if (! $zipArchive->open(realpath($zipFile))) {
        printf('Could not open zipfile - invalid format: "%s"' . PHP_EOL, $zipFile);

        return 1;
    }

    $manifestZipString = $zipArchive->getFromName('manifest.json');
    unset($zipArchive);

    if (! $manifestZipString) {
        printf('Could not read manifest.json from zipfile "%s"' . PHP_EOL, $zipFile);

        return 1;
    }
    $manifestZip = parseManifest($manifestZipString);
    if (! $manifestZip) {
        printf('Could not parse manifest.json from zipfile "%s"' . PHP_EOL, $zipFile);

        return 1;
    }

    $arrayDifference = arrayRecursiveDiff($manifestZip, $manifest);
    if (count($arrayDifference) > 0) {
        printf(
            'Different manifest.json from zipfile "%s" and from "%s" - plugin not packed yet?' . PHP_EOL,
            $zipFile,
            $manifestFile
        );
        printArrayRecursiveDiff('', $arrayDifference, $manifest, $manifestZip);

        return 1;
    }

    return 0;
}

function printArrayRecursiveDiff(string $keyPrefix, array $arrayDifference, array $manifest, array $manifestZip, int $depth = 0): void
{
    ++$depth;
    if ($depth > 50) {
        return;
    }
    foreach ($arrayDifference as $key => $value) {
        if (array_key_exists($key, $manifest) && array_key_exists($key, $manifestZip) && is_array($manifest[$key])) {
            printArrayRecursiveDiff(
                $key . ':',
                arrayRecursiveDiff($manifest[$key], $manifestZip[$key]),
                $manifest[$key],
                $manifestZip[$key],
                $depth
            );
        } else {
            printf(
                "\t%s%s%s\t\t file: %s%s\t\t zip:  %s%s",
                $keyPrefix,
                $key,
                PHP_EOL,
                array_key_exists($key, $manifest) ? (is_array($manifest[$key]) ? 'Array' : $manifest[$key]) : '(none)',
                PHP_EOL,
                array_key_exists($key, $manifestZip) ? (is_array($manifestZip[$key]) ? 'Array' : $manifestZip[$key]) : '(none)',
                PHP_EOL
            );
        }
    }
}

function arrayRecursiveDiff(array $aArray1, array $aArray2, int $depth = 0): array
{
    $aReturn = [];
    ++$depth;
    if ($depth > 50) {
        return $aReturn;
    }

    // as per @mhitza at https://stackoverflow.com/a/3877494/19746
    foreach ($aArray1 as $mKey => $mValue) {
        if (array_key_exists($mKey, $aArray2)) {
            if (is_array($mValue)) {
                $aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey], $depth);
                if (count($aRecursiveDiff)) {
                    $aReturn[$mKey] = $aRecursiveDiff;
                }
            } elseif ($mValue !== $aArray2[$mKey]) {
                $aReturn[$mKey] = $mValue;
            }
        } else {
            $aReturn[$mKey] = $mValue;
        }
    }
    foreach ($aArray2 as $mKey => $mValue) {
        if (array_key_exists($mKey, $aArray1)) {
            if (is_array($mValue)) {
                $aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray1[$mKey], $depth);
                if (count($aRecursiveDiff)) {
                    $aReturn[$mKey] = $aRecursiveDiff;
                }
            } elseif ($mValue !== $aArray1[$mKey]) {
                $aReturn[$mKey] = $mValue;
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
            printf('Url for plugin "%s" should be "%s".' . PHP_EOL, $name, $correctUrl);
            ++$errors;
        }

        if (stringLength($url) > UCRM_MAX_PLUGIN_URL_LENGTH) {
            printf('Url for plugin "%s" should be at most %d characters long.' . PHP_EOL, $name, UCRM_MAX_PLUGIN_URL_LENGTH);
            ++$errors;
        }
    }

    return $errors;
}

function stringLength(string $string): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($string, 'UTF-8');
    }

    printf('Warning: missing extension: %s' . PHP_EOL, 'mbstring');

    return strlen($string);
}

function validatePlugin(SplFileInfo $pluginDirectory): int
{
    $path = $pluginDirectory->getPathname();

    $errors = 0;

    $errors += validatePhp($path);
    $errors += ensureFileExists($path . '/README.md');
    $errors += ensureFileExists($path . '/src/main.php');
    $errors += ensureComposerLockExists($path);
    $errors += validateManifest($path . '/src/manifest.json');

    return $errors;
}

function checkPluginsJson(): int
{
    ob_start();
    require __DIR__ . '/generate-json.php';
    $correctJson = ob_get_clean();

    $pluginsFile = __DIR__ . '/plugins.json';
    if (ensureFileExists($pluginsFile) === 0) {
        $currentJson = file_get_contents($pluginsFile);

        if (json_decode($currentJson, true) === json_decode($correctJson, true)) {
            return 0;
        }
    }
    printf(
        'The "plugins.json" file is not up to date. Run `php generate-json.php > plugins.json` to update it.' . PHP_EOL
    );

    return 1;
}

function validatePhp(string $path): int
{
    $errors = 0;
    $files = new CallbackFilterIterator(
        new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        ),
        function (SplFileInfo $fileInfo) {
            return (! $fileInfo->isDir())
                && (stripos($fileInfo->getBasename(), '.php') === strlen($fileInfo->getBasename()) - 4)
                && (strpos($fileInfo->getPathname(), '/src/vendor/') === false);
        }
    );
    /** @var SplFileInfo $file */
    foreach ($files as $file) {
        $output = [];
        $result = null;
        exec(
            escapeshellcmd(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()),
            $output,
            $result
        );
        if ($result !== 0) {
            ++$errors;
        }
    }

    return $errors;
}

$pluginDirectories = new CallbackFilterIterator(
    new DirectoryIterator(__DIR__ . '/plugins'),
    function (DirectoryIterator $fileInfo) {
        return $fileInfo->isDir() && ! $fileInfo->isDot();
    }
);

$errors = 0;
foreach ($pluginDirectories as $directory) {
    $errors += validatePlugin($directory);
}

// Temporary skipped
// $errors += checkPluginsJson();

printf('Found %d error%s.' . PHP_EOL, $errors, $errors === 1 ? '' : 's');

exit($errors);
