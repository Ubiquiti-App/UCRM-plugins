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

    /** @var array */
    public $debug;



    public function echoDebug(): void
    {
        if($this->debug !== null && $this->debug !== [])
        {
            foreach($this->debug as $entry)
            {
                if (is_array($entry))
                    print_r($entry);
                else
                    echo $entry."\n";
            }
        }
    }

}