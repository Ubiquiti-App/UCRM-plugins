<?php
declare(strict_types=1);

namespace MVQN\UCRM\Plugins\Controllers;

/**
 * Class EmailActionResult
 *
 * @package MVQN\UCRM\Plugins\Controllers
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
class EmailActionResult extends ActionResult
{
    /** @var string */
    public $text;

    /** @var array */
    public $recipients;

    /** @var string */
    public $subject;

}