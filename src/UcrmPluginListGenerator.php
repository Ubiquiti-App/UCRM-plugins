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

    public function getJson(): string
    {
        return json_encode(
                [
                    'plugins' => array_values(
                        array_filter(
                            $this->getList()
                        )
                    ),
                ],
                JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
            );
    }

    /**
     * @return string[]
     */
    public function getList(): array
    {
        $plugins = [];
        /** @var SplFileInfo $directory */
        foreach ($this->pluginDirectories as $directory) {
            $file = $directory->getPathname() . '/src/manifest.json';

            if (
                ! is_readable($file)
                || ($fileContent = file_get_contents($file)) === false
            ) {
                $this->log(sprintf('Cannot access manifest "%s"' . PHP_EOL, $file));
                continue;
            }

            try {
                $manifest = json_decode($fileContent, true, 50, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                $this->log(sprintf('Skipped file "%s": %s' . PHP_EOL, $file, $exception->getMessage()));
                continue;
            }
            $plugin = $manifest['information'];
            $plugin['name'] = $plugin['name'] ?? sprintf('(name missing in %s manifest)', $directory->getBasename());
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

        return $plugins;
    }

    private function log(string $text): void
    {
        fwrite(STDERR, $text);
    }
}
