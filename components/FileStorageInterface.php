<?php
/**
 * @author Vladimir V Petukhov
 * @version 1.0
 * @copyright Copyright (c) 2016, Vladimir V Petukhov
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License Version 3
 *
 * @interface FileStorageInterface
 * @namespace components
 */

namespace components;

interface FileStorageInterface
{
    /**
     * @param string $url
     * @return boolean
     */
    public function setBasePublicUrl($url);

    /**
     * @param string $sourcePath
     * @param bool $isPrivate
     * @param bool $removeSource
     * @param integer|null $deleteAt File TTL
     *
     * @return int|bool File ID or false
     */
    public function createFromPath($sourcePath, $isPrivate = false, $deleteAt = null, $removeSource = false);

    /**
     * @param string $content
     * @param bool $isPrivate
     * @param integer|null $deleteAt File TTL
     *
     * @return int|bool File ID or false
     */
    public function createFromContent($content, $isPrivate = false, $deleteAt = null);

    /**
     * @param integer $id
     * @param string  $sourcePath
     * @param bool    $append
     * @param bool    $removeSource
     *
     * @return bool
     */
    public function putFromPath($id, $sourcePath, $append = false, $removeSource = false);

    /**
     * @param integer $id
     * @param string  $content
     * @param bool    $append Append content to existed file content
     *
     * @return bool
     */
    public function putContent($id, $content, $append = false);

    /**
     * @param integer  $id
     *
     * @return string|bool If cannot read content
     */
    public function getContent($id);

    /**
     * @param integer  $id
     * @param string $filePath
     *
     * @return string|bool File path or false
     */
    public function getContentToFile($id, $filePath);

    /**
     * @param integer $id
     *
     * @return bool
     */
    public function exists($id);

    /**
     * @param integer $id
     *
     * @return bool
     */
    public function delete($id);

    /**
     * @param integer $id
     *
     * @return bool
     */
    public function restore($id);

    /**
     * @param integer $id
     *
     * @return int|bool File size or false
     */
    public function getSize($id);

    /**
     * @param integer $id
     *
     * @return int|bool An UNIX like timestamp or false
     */
    public function getMTime($id);

    /**
     * @param integer $id
     *
     * @return string|bool Mime string or false
     */
    public function getMime($id);

    /**
     * @param integer $id
     *
     * @return bool result
     */
    public function setPrivate($id);

    /**
     * @param integer $id
     *
     * @return bool result
     */
    public function setPublic($id);

    /**
     * @param integer $id
     * @param bool $absolute relative or absolute URL
     *
     * @return string|false Returns file url for public files, false otherwise
     */
    public function getUrl($id, $absolute = false);

    /**
     * Mark overdue files as deleted
     */
    public function processSetToDeleteFiles();

    /**
     * Physical erase of deleted files
     */
    public function removeDeletedFiles();
} 