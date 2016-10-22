<?php
/**
 * @author Vladimir V Petukhov
 * @version 1.0
 * @copyright Copyright (c) 2016, Vladimir V Petukhov
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License Version 3
 *
 * @class FileStatus
 * @namespace components\values
 */

namespace components\values;

use yii\base\InvalidParamException;

class FileStatus {
    const DELETED = 0;
    const BLOCKED = 1;
    const ACTIVE = 2;
    const LOST = 3;

    protected $value;

    /**
     * @param integer $value
     */
    public function __construct($value)
    {
        $this->setValue($value);
    }

    /**
     * List of allowed values
     * @return array
     */
    public static function getAllowedValues()
    {
        return [
            self::DELETED,
            self::BLOCKED,
            self::ACTIVE,
            self::LOST,
        ];
    }

    /**
     * @param integer $value
     *
     * @return bool
     */
    public static function isValid($value)
    {
        return in_array($value, self::getAllowedValues());
    }

    /**
     * @return bool
     */
    public  function isActive()
    {
        return $this->value === self::ACTIVE;
    }

    /**
     * @param integer $value
     *
     * @return bool
     */
    public static function checkIsActive($value)
    {
        return $value === self::ACTIVE;
    }

    /**
     * @return bool
     */
    public function isLost()
    {
        return $this->value === self::LOST;
    }

    /**
     * @param integer $value
     *
     * @return bool
     */
    public static function checkIsLost($value)
    {
        return $value === self::LOST;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->value === self::DELETED;
    }

    /**
     * @param integer $value
     *
     * @return bool
     */
    public static function checkIsDeleted($value)
    {
        return $value === self::DELETED;
    }

    /**
     * @return bool
     */
    public function isBlocked()
    {
        return $this->value === self::BLOCKED;
    }

    /**
     * @param integer $value
     *
     * @return bool
     */
    public static function checkIsBlocked($value)
    {
        return $value === self::BLOCKED;
    }

    /**
     * @param integer $value
     */
    public function setValue($value)
    {
        if (!self::isValid($value)) {
            throw new InvalidParamException("FileStatus wrong parameter is given!");
        }

        $this->value = $value;
    }

    /**
     * @return integer
     */
    public function getValue()
    {
        return $this->value;
    }
}