<?php
declare(strict_types=1);

namespace MVQN\Collections;

/**
 * Class Collectible
 *
 * @package MVQN\Collections
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
abstract class Collectible implements \JsonSerializable
{
    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed Data to be serialized by <b>json_encode</b>, which can be of any type except a resource.
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * Overrides the default string representation of the class.
     *
     * @return string Returns a JSON representation of this Model.
     */
    public function __toString()
    {
        // Return the array as a JSON string.
        return json_encode($this, JSON_UNESCAPED_SLASHES);
    }

}
