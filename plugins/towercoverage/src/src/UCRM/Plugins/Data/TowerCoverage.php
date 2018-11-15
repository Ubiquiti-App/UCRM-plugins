<?php
declare(strict_types=1);

namespace UCRM\Plugins\Data;

use MVQN\XML\XmlElementClass;

/**
 * Class TowerCoverage
 *
 * @package UCRM\Plugins\Data
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 * @final
 *
 * @method CustomerDetails getCustomerDetails()
 *
 */
final class TowerCoverage extends XmlElementClass
{
    /**
     * @var CustomerDetails
     * @XmlElement CustomerDetails
     */
    protected $customerDetails;
}