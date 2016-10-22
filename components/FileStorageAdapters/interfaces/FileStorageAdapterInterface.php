<?php
/**
 * @author Vladimir V Petukhov
 * @version 1.0
 * @copyright Copyright (c) 2016, Vladimir V Petukhov
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License Version 3
 *
 * @interface FileStorageAdapterInterface
 * @namespace common\components\RapidStorage\FileStorageAdapters\interfaces
 */

namespace components\FileStorageAdapters\interfaces;

use components\values\FileInfo;

interface FileStorageAdapterInterface
{
    /**
     * @param FileInfo $fileInfo
     *
     * @return FileInfo|false - new FileInfo object with actual file information
     */
    public function getActualFileInfo(FileInfo $fileInfo);

    /**
     * @param FileInfo $fileInfo
     * @param string   $sourcePath
     *
     * @return boolean
     */
    public function copy(FileInfo $fileInfo, $sourcePath);

    /**
     * @param FileInfo $fileInfo
     * @param string   $sourcePath
     *
     * @return boolean
     */
    public function rename(FileInfo $fileInfo, $sourcePath);

    /**
     * @param FileInfo $fileInfo
     *
     * @return boolean
     */
    public function erase(FileInfo $fileInfo);

    /**
     * @param FileInfo $fileInfo
     * @param string   $content
     * @param bool     $append
     *
     * @return bool
     */
    public function putContent(FileInfo $fileInfo, $content, $append = false);

    /**
     * @param FileInfo $fileInfo
     * @param string   $sourcePath
     * @param bool     $append
     *
     * @return boolean
     */
    public function putContentFromFile(FileInfo $fileInfo, $sourcePath, $append = false);

    /**
     * @param FileInfo $fileInfo
     *
     * @return string|false
     */
    public function getContent(FileInfo $fileInfo);

    /**
     * @param FileInfo $fileInfo
     * @param string   $destinationPath
     *
     * @return bool
     */
    public function getContentToFile(FileInfo $fileInfo, $destinationPath);

    /**
     * @param FileInfo $fileInfo
     *
     * @return string|false
     */
    public function getUrl(FileInfo $fileInfo);

    /**
     * @param FileInfo $fileInfo
     *
     * @return bool
     */
    public function makePrivate(FileInfo $fileInfo);

    /**
     * @param FileInfo $fileInfo
     *
     * @return bool
     */
    public function makePublic(FileInfo $fileInfo);
}
