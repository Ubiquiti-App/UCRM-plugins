<?php
declare(strict_types=1);

namespace MVQN\UCRM\Plugins\Controllers;

/**
 * Class ActionResult
 *
 * @package MVQN\UCRM\Plugins\Controllers
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
abstract class ActionResult
{
    /** @var string */
    public $html;

    /** @var mixed */
    public $debug;

    public function echoDebug(): void
    {
        if($this->debug !== null && $this->debug !== "")
        {
            if (is_array($this->debug))
                print_r($this->debug);
            else
                echo $this->debug."\n";
        }


    }

}