<?php
/**
 * File Information envelop
 *
 * @author Vladimir V Petukhov
 * @version 1.0
 * @copyright Copyright (c) 2016, Vladimir V Petukhov
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License Version 3
 *
 * @class FileInfo
 * @namespace components\values
 *
 * @property integer $id
 * @property string $name
 * @property string $mime
 * @property integer $size
 *
 * @write-property boolean $isPrivate
 * @read-property boolean $isPrivate
 *
 * @write-property integer $status
 * @read-property FileStatus $status
 *
 * @write-property integer $deleteAtTime
 * @read-property integer $deleteAtTime
 */

namespace components\values;

use yii\base\Object;

class FileInfo extends Object
{
    const FILE_NAME_MAX_LEN = 8;

    protected $id;
    protected $name;
    protected $mime;
    protected $size;
    protected $updatedAt;
    protected $isPrivate = false;
    protected $status;
    protected $deleteAtTime;

    protected $allowedSymbols = '1234567890abcdefghijklmnopqrstuvwxyz_';


    /**
     * @return string
     */
    public function generateName()
    {
        $name = '';
        $allowedSymbolsStrLen = strlen($this->allowedSymbols) - 1;

        for ($i = 0; $i < self::FILE_NAME_MAX_LEN; $i++) {
            $name .= $this->allowedSymbols[mt_rand(0, $allowedSymbolsStrLen)];
        }

        return $name;
    }

    /**
     * @param integer $value
     */
    public function setId($value)
    {
        $this->id = (int)$value;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $value
     */
    public function setName($value)
    {
        $this->name = (string)$value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $value
     */
    public function setMime($value)
    {
        $this->mime = (string)$value;
    }

    /**
     * @return string
     */
    public function getMime()
    {
        return $this->mime;
    }

    /**
     * Helper method for PDO queries
     * @param integer $value
     */
    public function setUpdated_at($value)
    {
        $this->setUpdatedAt($value);
    }

    /**
     * @param integer $value
     */
    public function setUpdatedAt($value)
    {
        $this->updatedAt = (string)$value;
    }

    /**
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param integer $value
     */
    public function setSize($value)
    {
        $this->size = (int)$value;
    }

    /**
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Helper method for PDO queries
     * @param boolean $value
     */
    public function setIs_public($value)
    {
        $this->setIsPrivate($value);
    }

    /**
     * @param boolean $value
     */
    public function setIsPrivate($value)
    {
        $this->isPrivate = (bool)$value;
    }

    /**
     * @return boolean
     */
    public function getIsPrivate()
    {
        return $this->isPrivate;
    }

    /**
     * @param integer $value
     */
    public function setStatus($value)
    {
        $this->status = new FileStatus((int)$value);
    }

    /**
     * @return FileStatus
     */
    public function getStatus()
    {
        if (!$this->status) {
            $this->status = new FileStatus(FileStatus::ACTIVE);
        }

        return $this->status;
    }

    /**
     * Helper method for PDO queries
     * @param integer $value
     */
    public function setDelete_at_time($value)
    {
        $this->setDeleteAtTime($value);
    }

    /**
     * @param integer $value
     */
    public function setDeleteAtTime($value)
    {
        $this->deleteAtTime = (int)$value;
    }

    /**
     * @return integer
     */
    public function getDeleteAtTime()
    {
        return $this->deleteAtTime;
    }
}
