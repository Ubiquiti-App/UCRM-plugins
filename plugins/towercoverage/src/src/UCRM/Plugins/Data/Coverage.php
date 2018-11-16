<?php
declare(strict_types=1);

namespace UCRM\Plugins\Data;

use MVQN\XML\XmlElementClass;

/**
 * Class Coverage
 *
 * @package UCRM\Plugins\Data
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 * @final
 *
 * @method int    getId()
 * @method int    getCustomerDetailsId()
 * @method string getSiteName()
 * @method string getImageData()
 * @method float  getAzimuthSt()
 * @method float  getAzimuthEt()
 * @method float  getTiltSt()
 * @method float  getTiltEt()
 * @method float  getDistance()
 * @method float  getReceivedSignal()
 *
 */
final class Coverage extends XmlElementClass
{
    /**
     * @var int
     * @XmlElement ID
     */
    protected $id;

    /**
     * @var int
     * @XmlElement CustomerDetailsID
     */
    protected $customerDetailsId;

    /**
     * @noinspection SpellCheckingInspection
     * @var string
     * @XmlElement Sitename
     */
    protected $siteName;

    /**
     * @var string
     * @XmlElement ImageData
     * @Trim
     * @MIME
     */
    protected $imageData;

    /**
     * @var float
     * @XmlElement Azimuth_St
     */
    protected $azimuthSt;

    /**
     * @var float
     * @XmlElement Azimuth_Et
     */
    protected $azimuthEt;

    /**
     * @var float
     * @XmlElement Tilt_St
     */
    protected $tiltSt;

    /**
     * @var float
     * @XmlElement Tilt_Et
     */
    protected $tiltEt;

    /**
     * @var float
     * @XmlElement Distance
     */
    protected $distance;

    /**
     * @var float
     * @XmlElement ReceivedSignal
     */
    protected $receivedSignal;

}
