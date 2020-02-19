<?php

class UcrmPluginListGenerator
{
    /**
     * @var CallbackFilterIterator
     */
    private $pluginDirectories;

    public function __construct(CallbackFilterIterator $pluginDirectories)
    {
        $this->pluginDirectories = $pluginDirectories;
    }

    public function getList(): array
    {
        $plugins = [];
        /** @var SplFileInfo $directory */
        foreach ($this->pluginDirectories as $directory) {
            $file = $directory->getPathname() . '/src/manifest.json';

            if (! is_readable($file)) {
                $this->log(sprintf('Cannot access manifest "%s"' . PHP_EOL, $file));
                continue;
            }

            $manifest = json_decode(file_get_contents($file), true, 50);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log(sprintf('Skipped file "%s": %s' . PHP_EOL, $file, json_last_error_msg()));
                continue;
            }
            $plugin = $manifest['information'];
            $plugin['name'] = $plugin['name'] ?? sprintf('(name missing in %s manifest)', $directory->getBaseName());
            $plugin['zipUrl'] = sprintf(
                'https://github.com/Ubiquiti-App/UCRM-plugins/raw/master/plugins/%s/%s.zip',
                $plugin['name'],
                $plugin['name']
            );
            $this->log(sprintf(
                "\t%s\t%s\t\t%s\n",
                $plugin['version'] ?? '(no version)',
                $plugin['name'],
                $plugin['url'] ?? '(URL missing)'
            ));

            $plugins[$plugin['name']] = $plugin;
        }

        ksort($plugins);

        $this->log('Plugins found: ' . count($plugins) . "\n");
        return ['plugins' => array_values(array_filter($plugins))];
    }

    private function log($text): void {
        fwrite(STDERR, $text);
    }
}

$pluginDirectories = new CallbackFilterIterator(
    new DirectoryIterator(__DIR__ . '/plugins'),
    static function (DirectoryIterator $fileInfo) {
        return $fileInfo->isDir() && ! $fileInfo->isDot();
    }
);

$listGenerator = new UcrmPluginListGenerator($pluginDirectories);
echo json_encode($listGenerator->getList(), JSON_PRETTY_PRINT) . PHP_EOL;
