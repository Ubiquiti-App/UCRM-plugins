<?php
/** @noinspection SpellCheckingInspection */declare(strict_types=1);

namespace UCRM\Plugins;

use UCRM\Common\SettingsBase;

/**
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 *
 * @method static bool|null getDevelopment()
 * @method static bool|null getVerboseLogging()
 * @method static string|null getApiKey()
 * @method static string|null getApiUsername()
 * @method static string|null getApiPassword()
 * @method static string|null getDuplicateMode()
 */
final class Settings extends SettingsBase
{
	/** @const string The name of this Project, based on the root folder name. */
	public const PROJECT_NAME = 'towercoverage';

	/** @const string The absolute path to this Project's root folder. */
	public const PROJECT_ROOT_PATH = 'C:\Users\rspaeth\Documents\PhpStorm\Projects\mvqn\ucrm-plugins\plugins\towercoverage';

	/** @const string The name of this Project, based on the root folder name. */
	public const PLUGIN_NAME = 'towercoverage';

	/** @const string The absolute path to the root path of this project. */
	public const PLUGIN_ROOT_PATH = 'C:\Users\rspaeth\Documents\PhpStorm\Projects\mvqn\ucrm-plugins\plugins\towercoverage\src';

	/** @const string The absolute path to the data path of this project. */
	public const PLUGIN_DATA_PATH = 'C:\Users\rspaeth\Documents\PhpStorm\Projects\mvqn\ucrm-plugins\plugins\towercoverage\src\data';

	/** @const string The absolute path to the source path of this project. */
	public const PLUGIN_SOURCE_PATH = 'C:\Users\rspaeth\Documents\PhpStorm\Projects\mvqn\ucrm-plugins\plugins\towercoverage\src\src';

	/** @const string The publicly accessible URL of this UCRM, null if not configured in UCRM. */
	public const UCRM_PUBLIC_URL = 'http://ucrm.dev.mvqn.net/';

	/** @const string The locally accessible URL of this UCRM, null if not configured in UCRM. */
	public const UCRM_LOCAL_URL = 'http://localhost/';

	/** @const string The publicly accessible URL assigned to this Plugin by the UCRM. */
	public const PLUGIN_PUBLIC_URL = 'http://ucrm.dev.mvqn.net/_plugins/template/public.php';

	/** @const string An automatically generated UCRM API 'App Key' with read/write access. */
	public const PLUGIN_APP_KEY = 'DrWL+nvuoqNW/TNSZ4OkXz+7YHxU3CZQjbFOo3JGS94sfVisiZi6rGWLuNYLuxh4';

	/**
	 * Development?
	 * @var bool|null If enabled, the system will force the plugin's environment to 'dev', regardless of the actual environment config.  NOTE: This should be disabled unless the plugin is not functioning correctly and debug information is needed!
	 */
	protected static $development;

	/**
	 * Verbose Logging?
	 * @var bool|null If enabled, will include verbose debug messages in the logs.
	 */
	protected static $verboseLogging;

	/**
	 * API Key
	 * @var string|null The API Key from TowerCoverage.com. If blank, allows submissions from any EUS API.
	 */
	protected static $apiKey;

	/**
	 * API Username
	 * @var string|null The API Username from TowerCoverage.com. If blank, allows submissions from any EUS API.
	 */
	protected static $apiUsername;

	/**
	 * API Password
	 * @var string|null The API Password from TowerCoverage.com. If blank, allows submissions from any EUS API.
	 */
	protected static $apiPassword;

	/**
	 * Duplicate Mode
	 * @var string|null Select the method for determining duplicate entries.
	 */
	protected static $duplicateMode;
}
