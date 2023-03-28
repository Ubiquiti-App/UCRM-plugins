<?php
/*
 * @copyright Copyright (c) 2021 Ubiquiti Inc.
 * @see https://www.ui.com/
 */

declare(strict_types=1);

namespace BackupSyncDropbox\TokenProvider;

use BackupSyncDropbox\Utility\Strings;
use League\OAuth2\Client\Grant\RefreshToken;
use Spatie\Dropbox\TokenProvider;
use Stevenmaguire\OAuth2\Client\Provider\Dropbox;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;
use Ubnt\UcrmPluginSdk\Util\Json;

class DropboxTokenProvider implements TokenProvider
{
    /**
     * @var PluginLogManager
     */
    private $pluginLogManager;

    /**
     * @var PluginConfigManager
     */
    private $pluginConfigManager;

    public function __construct(PluginLogManager $pluginLogManager, PluginConfigManager $pluginConfigManager)
    {
        $this->pluginLogManager = $pluginLogManager;
        $this->pluginConfigManager = $pluginConfigManager;
    }

    public function getToken(): string
    {
        $config = $this->pluginConfigManager->loadConfig();
        $dropboxAccessToken = Strings::trimNonEmpty($config['dropboxAccessToken']);
        if ($dropboxAccessToken !== null) {
            return $config['dropboxAccessToken'];
        }

        $dropboxAppKey = Strings::trimNonEmpty($config['dropboxAppKey']);
        $dropboxAppSecret = Strings::trimNonEmpty($config['dropboxAppSecret']);
        if ($dropboxAppKey === null || $dropboxAppSecret === null) {
            throw new \RuntimeException('Please configure Dropbox App key and App secret.');
        }

        $oAuthClient = new Dropbox([
            'clientId' => $dropboxAppKey,
            'clientSecret' => $dropboxAppSecret,
        ]);

        $dropboxRefreshToken = Strings::trimNonEmpty($config['dropboxRefreshToken']);
        $dropboxAccessCode = Strings::trimNonEmpty($config['dropboxAccessCode']);

        if ($dropboxRefreshToken === null && $dropboxAccessCode === null) {
            // https://github.com/stevenmaguire/oauth2-dropbox/pull/12
            $authorizationUrl = str_replace(
                'https://api.dropbox.com/oauth2/authorize',
                'https://www.dropbox.com/oauth2/authorize',
                $oAuthClient->getAuthorizationUrl([
                    'token_access_type' => 'offline',
                ])
            );

            $this->pluginLogManager->appendLog(
                <<<MSG
                =========================================================================
                
                To finish setup, go to the following URL, allow access to the application
                and enter the generated Access Code into plugin settings:
                
                ${authorizationUrl}
                
                =========================================================================
                
                MSG
            );

            exit(0);
        }

        if ($dropboxRefreshToken === null && $dropboxAccessCode !== null) {
            $dropboxAccessToken = $oAuthClient->getAccessToken(
                'authorization_code',
                [
                    'code' => $config['dropboxAccessCode'],
                ]
            );

            $config['dropboxRefreshToken'] = $dropboxAccessToken->getRefreshToken();
            $config['dropboxAccessCode'] = null;

            file_put_contents(__DIR__ . '/../../data/config.json', Json::encode($config));
            $this->pluginConfigManager->updateConfig();
        } else {
            $dropboxAccessToken = $oAuthClient->getAccessToken(
                new RefreshToken(),
                [
                    'refresh_token' => $dropboxRefreshToken,
                ]
            );
        }

        return $dropboxAccessToken->getToken();
    }
}
