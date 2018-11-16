<?php
declare(strict_types=1);

namespace UCRM\Plugins\Data;

use MVQN\XML\XmlElementClass;

/**
 * Class CustomerLinkInfo
 *
 * @package UCRM\Plugins\Data
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 * @final
 *
 * @method string getFiberIncludes()
 * @method Coverage[] getCoverages()
 *
 */
final class CustomerLinkInfo extends XmlElementClass
{
    /**
     * @noinspection SpellCheckingInspection
     * @var string
     * @XmlElement fiberincludes
     */
    protected $fiberIncludes;

    /**
     * @var Coverage[]
     * @XmlElement coverage
     */
    protected $coverages;


}
