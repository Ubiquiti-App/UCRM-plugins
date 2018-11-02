<?php
declare(strict_types=1);

namespace MVQN\UCRM\Plugins;

/**
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 *
 * @method static bool|null getSmtpUseHTML()
 * @method static string|null getClientTypes()
 * @method static string getClientRecipients()
 * @method static string|null getInvoiceRecipients()
 * @method static string|null getPaymentRecipients()
 * @method static string|null getQuoteRecipients()
 * @method static string|null getServiceRecipients()
 * @method static string getTicketRecipients()
 * @method static string|null getUserRecipients()
 * @method static string|null getWebhookRecipients()
 */
final class Settings extends SettingsBase
{
	/** @const string The absolute path to the root path of this project. */
	public const PLUGIN_ROOT_PATH = 'C:\Users\rspaeth\Documents\PhpStorm\Projects\mvqn\ucrm-plugins\plugins\notifications\zip';

	/** @const string The absolute path to the data path of this project. */
	public const PLUGIN_DATA_PATH = 'C:\Users\rspaeth\Documents\PhpStorm\Projects\mvqn\ucrm-plugins\plugins\notifications\zip\data';

	/** @const string The absolute path to the source path of this project. */
	public const PLUGIN_SOURCE_PATH = 'C:\Users\rspaeth\Documents\PhpStorm\Projects\mvqn\ucrm-plugins\plugins\notifications\zip\src';

	/** @const string The publicly accessible URL of this UCRM, null if not configured in UCRM. */
	public const UCRM_PUBLIC_URL = 'http://ucrm.dev.mvqn.net/';

	/** @const string An automatically generated UCRM API 'App Key' with read/write access. */
	public const PLUGIN_APP_KEY = '4LOxNWuUXlvk26C3puUrVcZU/wPK3jtmrytqY84JKN/Al7XmFAlN3nJ86Gp2wNU2';

	/**
	 * Use HTML?
	 * @var bool|null If enabled, will attempt to send messages in HTML format.
	 */
	protected static $smtpUseHTML;

	/**
	 * Client Types
	 * @var string|null The type of Client events for which notifications should be sent.
	 */
	protected static $clientTypes;

	/**
	 * Client Recipients
	 * @var string A comma separated list of email addresses to which Client notifications should be sent.
	 */
	protected static $clientRecipients;

	/**
	 * Invoice Recipients
	 * @var string|null A comma separated list of email addresses to which Invoice notifications should be sent.
	 */
	protected static $invoiceRecipients;

	/**
	 * Payment Recipients
	 * @var string|null A comma separated list of email addresses to which Payment notifications should be sent.
	 */
	protected static $paymentRecipients;

	/**
	 * Quote Recipients
	 * @var string|null A comma separated list of email addresses to which Quote notifications should be sent.
	 */
	protected static $quoteRecipients;

	/**
	 * Service Recipients
	 * @var string|null A comma separated list of email addresses to which Service notifications should be sent.
	 */
	protected static $serviceRecipients;

	/**
	 * Ticket Recipients
	 * @var string A comma separated list of email addresses to which Ticket notifications should be sent.
	 */
	protected static $ticketRecipients;

	/**
	 * User Recipients
	 * @var string|null A comma separated list of email addresses to which User notifications should be sent.
	 */
	protected static $userRecipients;

	/**
	 * Webhook Recipients
	 * @var string|null A comma separated list of email addresses to which Webhook notifications should be sent.
	 */
	protected static $webhookRecipients;
}
