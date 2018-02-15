<?php

declare(strict_types=1);


namespace QBExport\Data;


class PluginData extends UcrmData
{
    /**
     * @var string
     */
    public $qbClientId;

    /**
     * @var string
     */
    public $qbClientSecret;

    /**
     * @var string
     */
    public $qbBaseUrl;

    /**
     * @var string
     */
    public $oauthRealmID;

    /**
     * @var string
     */
    public $oauthCode;

    /**
     * @var string
     */
    public $oauthRefreshToken;

    /**
     * @var string
     */
    public $oauthAccessToken;

    /**
     * @var string
     */
    public $oauthAccessTokenExpiration;

    /**
     * @var string
     */
    public $qbStateCSRF;

    /**
     * @var string
     */
    public $qbAuthorizationUrl;

    /**
     * @var string
     */
    public $lastExportedClientID;
}
