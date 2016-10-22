<?php
/**
 * @author Vladimir V Petukhov
 * @version 1.0
 * @copyright Copyright (c) 2016, Vladimir V Petukhov
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License Version 3
 *
 * @class RapidStorage
 * @namespace components
 */

namespace components;

use components\values\FileExtMimeTranslator;
use components\values\FileInfo;
use components\DataAdapters\interfaces\DataAdapterInterface;
use components\FileStorageAdapters\interfaces\FileStorageAdapterInterface;
use components\values\FileStatus;
use yii\base\Component;

class RapidStorage extends Component implements FileStorageInterface
{
    /** @var  FileStorageAdapterInterface */
    protected $fileStorageAdapter;

    /** @var  DataAdapterInterface */
    protected $dataAdapter;

    protected $basePublicUrl;

    /**
     * @param FileStorageAdapterInterface $adapter
     */
    public function setFileStorageAdapter(FileStorageAdapterInterface $adapter)
    {
        $this->fileStorageAdapter = $adapter;
    }

    /**
     * @param DataAdapterInterface $adapter
     */
    public function setDataAdapter(DataAdapterInterface $adapter)
    {
        $this->dataAdapter = $adapter;
    }

    /**
     * @param string $url
     * @return boolean
     */
    public function setBasePublicUrl($url)
    {
        $this->basePublicUrl = $url;

        return true;
    }

    /**
     * @param string $sourcePath
     * @param bool $isPrivate
     * @param bool $removeSource
     * @param integer|null $deleteAt File TTL
     *
     * @return int|bool File ID or false
     */
    public function createFromPath($sourcePath, $isPrivate = false, $deleteAt = null, $removeSource = false)
    {
        if (!file_exists($sourcePath) || !is_readable($sourcePath) || ($removeSource && !is_writable($sourcePath))) {
            return false;
        }

        $sourcePathParts = pathinfo($sourcePath);

        $fileInfo = new FileInfo();
        $fileInfo->setName($sourcePathParts['basename']);
        $fileInfo->setSize(filesize($sourcePath));
        $fileInfo->setIsPrivate($isPrivate);

        if ($deleteAt) {
            $fileInfo->setDeleteAtTime($deleteAt);
        }

        $finfo = new \finfo(FILEINFO_MIME);

        if ($finfo) {
            $mime = $finfo->file($sourcePath);
        } else {
            $mime = FileExtMimeTranslator::getMimeByExtension($sourcePathParts['extension']);
        }

        $fileInfo->setMime($mime);

        $underTransaction = $this->dataAdapter->startTransaction();

        try {
            //NB!: fileInfo will be populated with created record ID
            if (!$this->dataAdapter->createFileRecord($fileInfo)) {
                $errorMessage = "Can't create record with dataAdapter(" . get_class($this->dataAdapter) . ").\n";
                $errorMessage .= "File record was: " . json_encode($fileInfo) . "\n\n";

                throw new \Exception($errorMessage);
            }

            if ($removeSource) {
                $result = $this->fileStorageAdapter->rename($fileInfo, $sourcePath);
            } else {
                $result = $this->fileStorageAdapter->copy($fileInfo, $sourcePath);
            }

            if (!$result) {
                $errorMessage = "Can't copy or rename file {$sourcePath} with fileStorageAdapter(".
                    get_class($this->fileStorageAdapter) . ").\n\n";

                throw new \Exception($errorMessage);
            }

            if ($underTransaction) {
                $this->dataAdapter->commitTransaction();
            }

            return $fileInfo->getId();
        } catch(\Exception $e) {
            $this->fileStorageAdapter->erase($fileInfo);

            if ($underTransaction) {
                $this->dataAdapter->rollbackTransaction();
            }
            //@ToDo logger
        }

        return false;
    }

    /**
     * @param string $content
     * @param bool $isPrivate
     * @param integer|null $deleteAt File TTL
     *
     * @return int|bool File ID or false
     */
    public function createFromContent($content, $isPrivate = false, $deleteAt = null)
    {
        $fileInfo = new FileInfo();
        $fileInfo->generateName();
        $fileInfo->setSize(strlen($content));
        $fileInfo->setIsPrivate($isPrivate);

        if ($deleteAt) {
            $fileInfo->setDeleteAtTime($deleteAt);
        }

        $finfo = new \finfo(FILEINFO_MIME);

        if ($finfo) {
            $mime = $finfo->buffer($content);
        } else {
            $mime = 'text/plain';
        }

        $fileInfo->setMime($mime);

        $underTransaction = $this->dataAdapter->startTransaction();

        try {
            //NB!: fileInfo will be populated with created record ID
            if (!$this->dataAdapter->createFileRecord($fileInfo)) {
                $errorMessage = "Can't create record with dataAdapter(" . get_class($this->dataAdapter) . ").\n";
                $errorMessage .= "File record was: " . json_encode($fileInfo) . "\n\n";

                throw new \Exception($errorMessage);
            }

            if (!$this->fileStorageAdapter->putContent($fileInfo, $content)) {
                $contentLength = strlen($content);
                $errorMessage = "Can't put content({$contentLength} byte) into file with fileStorageAdapter(".
                    get_class($this->fileStorageAdapter) . ").\n\n";

                throw new \Exception($errorMessage);
            }

            if ($underTransaction) {
                $this->dataAdapter->commitTransaction();
            }

            return $fileInfo->getId();
        } catch(\Exception $e) {
            $this->fileStorageAdapter->erase($fileInfo);

            if ($underTransaction) {
                $this->dataAdapter->rollbackTransaction();
            }
            //@ToDo logger
        }

        return false;
    }

    /**
     * @param integer $id
     * @param string $sourcePath
     * @param bool $append
     * @param bool $removeSource
     *
     * @return bool
     */
    public function putFromPath($id, $sourcePath, $append = false, $removeSource = false)
    {
        if (!file_exists($sourcePath) || !is_readable($sourcePath) || ($removeSource && !is_writable($sourcePath))) {
            return false;
        }

        $fileInfo = $this->dataAdapter->getFileInfo($id);

        if (!$fileInfo) {
            return false;
        }

        $underTransaction = $this->dataAdapter->startTransaction();

        try {
            if (!$this->fileStorageAdapter->putContentFromFile($fileInfo, $sourcePath, $append)) {
                $errorMessage = "Can't put/append file to existed FileRecord with dataAdapter(" .
                    get_class($this->dataAdapter) . ").\n";
                $errorMessage .= "File record was: " . json_encode($fileInfo) . "\n";
                $errorMessage .= "Source file was: {$sourcePath}\n\n";

                throw new \Exception($errorMessage);
            }

            $actualFileInfo = $this->fileStorageAdapter->getActualFileInfo($fileInfo);

            if (!$actualFileInfo) {
                $errorMessage = "Can't get actual FileRecord with dataAdapter(" . get_class($this->dataAdapter) . ")\n";
                $errorMessage .= "File record was: " . json_encode($fileInfo) . "\n";

                throw new \Exception($errorMessage);
            }

            if (!$this->dataAdapter->updateFileInfo($fileInfo->getId(), $actualFileInfo)) {
                $errorMessage = "Can't update record with dataAdapter(" . get_class($this->dataAdapter) . ").\n";
                $errorMessage .= "File record was: " . json_encode($fileInfo) . "\nn";
                $errorMessage .= "File actual record was: " . json_encode($actualFileInfo) . "\n\n";

                throw new \Exception($errorMessage);
            }

            if ($removeSource) {
                unlink($sourcePath);
            }

            if ($underTransaction) {
                $this->dataAdapter->commitTransaction();
            }

            return true;
        } catch(\Exception $e) {
            $this->fileStorageAdapter->erase($fileInfo);

            if ($underTransaction) {
                $this->dataAdapter->rollbackTransaction();
            }
            //@ToDo logger
        }

        return false;
    }

    /**
     * @param integer $id
     * @param string $content
     * @param bool $append Append content to existed file content
     *
     * @return bool
     */
    public function putContent($id, $content, $append = false)
    {
        $fileInfo = $this->dataAdapter->getFileInfo($id);

        if (!$fileInfo) {
            return false;
        }

        //if operation overwrites content then change mime
        if (!$append) {
            $finfo = new \finfo(FILEINFO_MIME);

            if ($finfo) {
                $mime = $finfo->buffer($content);
            } else {
                $mime = 'text/plain';
            }

            $fileInfo->setMime($mime);
        }

        $underTransaction = $this->dataAdapter->startTransaction();

        try {
            if (!$this->fileStorageAdapter->putContent($fileInfo, $content, $append)) {
                $contentLength = strlen($content);
                $errorMessage = "Can't put content({$contentLength} byte) into file with fileStorageAdapter(".
                    get_class($this->fileStorageAdapter) . ").\n\n";

                throw new \Exception($errorMessage);
            }

            $actualFileInfo = $this->fileStorageAdapter->getActualFileInfo($fileInfo);

            if (!$actualFileInfo) {
                $errorMessage = "Can't get actual FileRecord with dataAdapter(" . get_class($this->dataAdapter) . ")\n";
                $errorMessage .= "File record was: " . json_encode($fileInfo) . "\n";

                throw new \Exception($errorMessage);
            }

            if (!$this->dataAdapter->updateFileInfo($fileInfo->getId(), $actualFileInfo)) {
                $errorMessage = "Can't update record with dataAdapter(" . get_class($this->dataAdapter) . ").\n";
                $errorMessage .= "File record was: " . json_encode($fileInfo) . "\nn";
                $errorMessage .= "File actual record was: " . json_encode($actualFileInfo) . "\n\n";

                throw new \Exception($errorMessage);
            }

            if ($underTransaction) {
                $this->dataAdapter->commitTransaction();
            }

            return $fileInfo->getId();
        } catch(\Exception $e) {
            $this->fileStorageAdapter->erase($fileInfo);

            if ($underTransaction) {
                $this->dataAdapter->rollbackTransaction();
            }
            //@ToDo logger
        }

        return false;
    }

    /**
     * @param integer $id
     *
     * @return string|bool If cannot read content
     */
    public function getContent($id)
    {
        $fileInfo = $this->dataAdapter->getFileInfo($id);

        if (!$fileInfo) {
            return false;
        }

        return $this->fileStorageAdapter->getContent($fileInfo);
    }

    /**
     * @param integer $id
     * @param string $destinationPath
     *
     * @return string|bool File path or false
     */
    public function getContentToFile($id, $destinationPath)
    {
        $fileInfo = $this->dataAdapter->getFileInfo($id);

        if (!$fileInfo) {
            return false;
        }

        return $this->fileStorageAdapter->getContentToFile($fileInfo, $destinationPath);
    }

    /**
     * @param integer $id
     *
     * @return bool
     */
    public function exists($id)
    {
        return $this->dataAdapter->exist($id);
    }

    /**
     * @param integer $id
     *
     * @return bool
     */
    public function delete($id)
    {
        return $this->dataAdapter->delete($id);
    }

    /**
     * @param integer $id
     *
     * @return bool
     */
    public function restore($id)
    {
        return $this->dataAdapter->restore($id);
    }

    /**
     * @param integer $id
     *
     * @return int|bool File size or false
     */
    public function getSize($id)
    {
        $fileInfo = $this->dataAdapter->getFileInfo($id);

        if (!$fileInfo) {
            return false;
        }

        return $fileInfo->getSize();
    }

    /**
     * @param integer $id
     *
     * @return int|bool An UNIX like timestamp or false
     */
    public function getMTime($id)
    {
        $fileInfo = $this->dataAdapter->getFileInfo($id);

        if (!$fileInfo) {
            return false;
        }

        return $fileInfo->getUpdatedAt();
    }

    /**
     * @param integer $id
     *
     * @return string|bool Mime string or false
     */
    public function getMime($id)
    {
        $fileInfo = $this->dataAdapter->getFileInfo($id);

        if (!$fileInfo) {
            return false;
        }

        return $fileInfo->getMime();
    }

    /**
     * @param integer $id
     *
     * @return bool result
     */
    public function setPrivate($id)
    {
        $fileInfo = $this->dataAdapter->getFileInfo($id);

        if (!$fileInfo || $fileInfo->getIsPrivate()) {
            return false;
        }

        $this->dataAdapter->startTransaction();
        $this->dataAdapter->setPrivate($fileInfo->getId());

        if ($this->fileStorageAdapter->makePrivate($fileInfo)) {
            $this->dataAdapter->commitTransaction();

            return true;
        }

        $this->dataAdapter->rollbackTransaction();

        return false;
    }

    /**
     * @param integer $id
     *
     * @return bool result
     */
    public function setPublic($id)
    {
        $this->dataAdapter->startTransaction();
        $this->dataAdapter->setPublic($id);

        if ($this->fileStorageAdapter->makePublic($id)) {
            $this->dataAdapter->commitTransaction();

            return true;
        }

        $this->dataAdapter->rollbackTransaction();

        return false;
    }

    /**
     * @param integer $id
     * @param bool $absolute relative or absolute URL
     *
     * @return string|false Returns file url for public files, false otherwise
     */
    public function getUrl($id, $absolute = false)
    {
        $fileInfo = $this->dataAdapter->getFileInfo($id);

        if (!$fileInfo || $fileInfo->getIsPrivate()) {
            return false;
        }

        $url = $this->fileStorageAdapter->getUrl($fileInfo);

        if (!$url) {
            return false;
        }

        if ($absolute) {
            $url = $this->basePublicUrl . '/' . $url;
        }

        return $url;
    }

    /**
     * Mark overdue files as deleted
     */
    public function processSetToDeleteFiles()
    {
        $this->dataAdapter->processSetToDeleteRecords();
    }

    /**
     * Physical erase of deleted files
     */
    public function removeDeletedFiles()
    {
        $files = $this->dataAdapter->getFileRecords(FileStatus::DELETED);

        /** @var FileInfo $file */
        foreach ($files as $file) {
            $this->fileStorageAdapter->erase($file);
        }
    }
}
