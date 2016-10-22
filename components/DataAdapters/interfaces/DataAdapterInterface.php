<?php
/**
 * @author Vladimir V Petukhov
 * @version 1.0
 * @copyright Copyright (c) 2016, Vladimir V Petukhov
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License Version 3
 *
 * @interface DataAdapterInterface
 * @namespace components\DataAdapters\interfaces
 */

namespace components\DataAdapters\interfaces;


use components\values\FileInfo;
use components\values\FileStatus;

interface DataAdapterInterface
{
    /**
     * @return bool
     */
    public function startTransaction();

    /**
     * @return bool
     */
    public function commitTransaction();

    /**
     * @return bool
     */
    public function rollbackTransaction();

    /**
     * @param FileInfo $fileInfo
     *
     * @return boolean
     */
    public function createFileRecord(FileInfo $fileInfo);

    /**
     * @param integer $id
     *
     * @return FileInfo|null
     */
    public function getFileInfo($id);

    /**
     * @param int       $id
     * @param FileInfo $actualInfo
     *
     * @return boolean
     */
    public function updateFileInfo($id, FileInfo $actualInfo);

    /**
     * @param integer $id
     *
     * @return boolean
     */
    public function exist($id);

    /**
     * @param integer      $id
     *s
     * @return boolean
     */
    public function delete($id);

    /**
     * @param integer $id
     *
     * @return boolean
     */
    public function restore($id);

    /**
     * @param int $status
     *
     * @return FileInfo[]|array
     */
    public function getFileRecords($status = FileStatus::ACTIVE);

    /**
     * @return int
     * @throws \yii\db\Exception
     */
    public function processSetToDeleteRecords();

    /**
     * @param integer $id
     *
     * @return bool
     */
    public function setPrivate($id);

    /**
     * @param integer $id
     *
     * @return bool
     */
    public function setPublic($id);
}
