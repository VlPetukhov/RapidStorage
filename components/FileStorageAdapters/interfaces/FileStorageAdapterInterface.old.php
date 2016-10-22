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

interface _FileStorageAdapterInterface {

    /**
     * @param \components\values\FileInfo $fileData
     * @param string $sourcePath
     * @param bool   $removeSource
     *
     * @return int|bool
     */
    public function putFromPath(FileInfo $fileData, $sourcePath, $removeSource = false);

    /**
     * @param \components\values\FileInfo $fileData
     * @param string $content
     * @param bool   $append Append content to existed file content
     * @param string|null $fileName - desired file name. If it is null then name will be generated automatically.
     *
     * @return int|bool
     */
    public function putContent(FileInfo $fileData, $content, $append = false, $fileName = null);

    /**
     * @param FileInfo $fileData
     *
     * @return string|bool If cannot read content
     */
    public function getContent(FileInfo $fileData);

    /**
     * @param FileInfo $fileData
     * @param string $path
     * @param boolean $overwrite
     *
     * @return string|bool File path or false
     */
    public function getToPath(FileInfo $fileData, $path, $overwrite = false);

    /**
     * @param FileInfo $fileData
     *
     * @return bool
     */
    public function fileExist(FileInfo $fileData);

    /**
     * @param \components\values\FileInfo $fileData
     *
     * @return bool
     */
    public function delete(FileInfo $fileData);

    /**
     * @param \components\values\FileInfo $sourceFileData
     * @param \components\values\FileInfo $targetFileData
     *
     * @return bool
     */
    public function rename(FileInfo $sourceFileData, FileInfo $targetFileData);



    /**
     * @param FileInfo $fileData
     * @return bool
     */
    public function makePublic(FileInfo $fileData);

    /**
     * @param FileInfo $fileData
     * @return bool
     */
    public function makePrivate(FileInfo $fileData);

    /**
     * @param FileInfo $fileData
     *
     * @return int|bool File size or false
     */
    public function getSize(FileInfo $fileData);

    /**
     * @param \components\values\FileInfo $fileData
     *
     * @return int|bool An UNIX like timestamp or false
     */
    public function getMTime(FileInfo $fileData);

    /**
     * @param FileInfo $fileData
     *
     * @return string|bool Mime string or false
     */
    public function getMime(FileInfo $fileData);


    /**
     * @param FileInfo $fileData
     *
     * @return string|bool File full path or false
     */
    public function getFileFullPath(FileInfo $fileData);

    /**
     * The key consist of 3 fields which represents a three level structure.
     * Each first and second levels contains subdirectories (4096 each).
     * Second level contains files. Total storage can contain up to approx. 687 milliards files, but
     * real quantity files will be restricted by integer index value.
     *
     * @param FileInfo $fileData
     *
     * @return string Generates file key
     */
    public function generateFileKey(FileInfo $fileData);

    /**
     * @return string Adapter Root path
     */
    public function getPublicStorageRootPath();

    /**
     * @return string Adapter Root url
     */
    public function getPublicStorageRootUrl();
}
