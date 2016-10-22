<?php
/**
 * @author Vladimir V Petukhov
 * @version 1.0
 * @copyright Copyright (c) 2016, Vladimir V Petukhov
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License Version 3
 *
 * @class LocalAdapter
 * @namespace components\FileStorageAdapters\Local
 */

namespace components\FileStorageAdapters\Local;

use components\values\FileInfo;
use components\FileStorageAdapters\interfaces\FileStorageAdapterInterface;
use DirectoryIterator;
use Yii;
use yii\base\Component;

class LocalAdapter extends Component implements FileStorageAdapterInterface
{
    public $filesPerDirectory = 4096;

    protected $publicRootUrl;
    protected $fullPathToPublicRoot;
    protected $fullPathToPrivateRoot;

    /**
     * @param string $publicRootPath
     * @param string $publicRootUrl
     * @param string $privateRootPath
     * @param array $config
     * @throws \Exception
     */
    public function __construct($publicRootPath, $publicRootUrl, $privateRootPath, $config = [])
    {
        parent::__construct($config);

        $this->setPublicRootUrl($publicRootUrl);
        $this->setPublicRootPath($publicRootPath);
        $this->setPrivateRootPath($privateRootPath);
    }

    /**
     * @param string $path
     * @return string
     * @throws \Exception
     */
    public function setPublicRootPath($path)
    {
        $targetPath = Yii::getAlias($path);

        if (!$this->prepareDirectory($targetPath))
        {
            throw new \Exception('LocalAdapter: Can\'t prepare directory ' . $targetPath);
        }

        $this->fullPathToPublicRoot = $targetPath;

        return $this->fullPathToPublicRoot;
    }

    /**
     * @param string $url
     * @return string
     * @throws \Exception
     */
    public function setPublicRootUrl($url)
    {
        $this->publicRootUrl = $url;

        return $this->publicRootUrl;
    }

    /**
     * @param string $path
     * @return string
     * @throws \Exception
     */
    public function setPrivateRootPath($path)
    {
        $targetPath = Yii::getAlias($path);

        if (!$this->prepareDirectory($targetPath))
        {
            throw new \Exception('LocalAdapter: Can\'t prepare directory ' . $targetPath);
        }

        $this->fullPathToPrivateRoot = $targetPath;

        return $this->fullPathToPrivateRoot;
    }

    /**
     * @param string $directoryPath
     * @return bool
     */
    private function prepareDirectory($directoryPath)
    {
        $path = dirname($directoryPath);

        if (file_exists($path) && is_writable($path)) {
            return true;
        }

        return @mkdir($path, 0777, true);
    }

    /**
     * @param string $dir
     * @return bool
     */
    private function isDirEmpty($dir) {
        foreach (new DirectoryIterator($dir) as $fileInfo) {
            if($fileInfo->isDot()) continue;
            return false;
        }
        return true;
    }

    /**
     * @param string $filePath
     */
    private function clearPath($filePath)
    {
        $dirPath = dirname($filePath);

        if (!$this->isDirEmpty($dirPath)) {
            return;
        }

        rmdir($dirPath);

        $parentDirPath = dirname($dirPath);

        if (!$this->isDirEmpty($parentDirPath)) {
            return;
        }

        rmdir($parentDirPath);

        $grandParentDirPath = dirname($parentDirPath);

        if (!$this->isDirEmpty($grandParentDirPath)) {
            return;
        }

        rmdir($grandParentDirPath);
    }

    /**
     * @param \components\values\FileInfo $fileData
     *
     * @return string
     */
    protected function getDirectoryPath(FileInfo $fileData)
    {
        $fullPath = ($fileData->getIsPrivate()) ? $this->fullPathToPrivateRoot : $this->fullPathToPublicRoot;
        $fullPath .= DIRECTORY_SEPARATOR .$this->generateFileKey($fileData) . DIRECTORY_SEPARATOR;

        $fullPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $fullPath);

        return $fullPath;
    }

    /**
     * @param \components\values\FileInfo $fileData
     *
     * @return string
     */
    protected function getFullPath(FileInfo $fileData)
    {
        $filePath = $this->getDirectoryPath($fileData) . $fileData->getName();

        return $filePath;
    }

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
    public function generateFileKey(FileInfo $fileData)
    {
        $id = $fileData->getId();

        $level2 = ceil($id / $this->filesPerDirectory);
        $level1 = ceil($level2 / $this->filesPerDirectory);
        $level2 = $level2 - $level1 * $this->filesPerDirectory;

        $level1Str = base_convert($this->filesPerDirectory - $level1, 10, 36);
        $level2Str = base_convert($this->filesPerDirectory - $level2, 10, 36);
        $fileContainer = base_convert($id, 10, 36);

        return "$level1Str/$level2Str/$fileContainer";
    }

    /**
     * @return string Adapter Root path
     */
    public function getPublicStorageRootPath()
    {
        return $this->fullPathToPublicRoot;
    }

    /**
     * @return string Adapter Root url
     */
    public function getPublicStorageRootUrl()
    {
        return $this->publicRootUrl;
    }

    /**
     * @param FileInfo $fileInfo
     *
     * @return FileInfo|false - new FileInfo object with actual file information
     */
    public function getActualFileInfo(FileInfo $fileInfo)
    {
        $filePath = $this->getFullPath($fileInfo);

        if (!file_exists($filePath)) {
            return false;
        }

        $response = new FileInfo();
        $response->setId($fileInfo->getId());
        $response->setName($fileInfo->getName());

        $finfo = new \finfo(FILEINFO_MIME);

        if ($finfo) {
            $mime = $finfo->file($filePath);
            $response->setMime($mime);
        }

        $response->setSize(filesize($filePath));
        $response->setUpdatedAt($fileInfo->getUpdatedAt());
        $response->setIsPrivate($fileInfo->getIsPrivate());
        $response->setStatus($fileInfo->getStatus()->getValue());
        $response->setDeleteAtTime($fileInfo->getDeleteAtTime());

        return $response;
    }

    /**
     * @param FileInfo $fileInfo
     * @param string   $sourcePath
     *
     * @return boolean
     */
    public function copy(FileInfo $fileInfo, $sourcePath)
    {
        $filePath = $this->getFullPath($fileInfo);
        $this->prepareDirectory($filePath);

        return @copy($sourcePath, $filePath);
    }

    /**
     * @param FileInfo $fileInfo
     * @param string   $sourcePath
     *
     * @return boolean
     */
    public function rename(FileInfo $fileInfo, $sourcePath)
    {
        $filePath = $this->getFullPath($fileInfo);
        $this->prepareDirectory($filePath);

        return @rename($sourcePath, $filePath);
    }

    /**
     * @param FileInfo $fileInfo
     *
     * @return boolean
     */
    public function erase(FileInfo $fileInfo)
    {
        $filePath = $this->getFullPath($fileInfo);
        $result = @unlink($filePath);

        if ($result) {
            $this->clearPath($filePath);
        }

        return $result;
    }

    /**
     * @param FileInfo $fileInfo
     * @param string   $content
     * @param bool     $append
     *
     * @return bool
     */
    public function putContent(FileInfo $fileInfo, $content, $append = false)
    {
        $mode = $append ? FILE_APPEND : null;

        $filePath = $this->getFullPath($fileInfo);
        $this->prepareDirectory($filePath);

        return file_put_contents($filePath, $content, $mode);
    }

    /**
     * @param FileInfo $fileInfo
     * @param string   $sourcePath
     * @param bool     $append
     *
     * @return boolean
     */
    public function putContentFromFile(FileInfo $fileInfo, $sourcePath, $append = false)
    {
        $destPath = $this->getFullPath($fileInfo);

        $this->prepareDirectory($destPath);

        if ($append) {
            $result = file_put_contents($destPath, file_get_contents($sourcePath), FILE_APPEND);
        } else {
            $result = @copy($sourcePath, $destPath);
        }

        return $result;
    }

    /**
     * @param FileInfo $fileInfo
     *
     * @return string|false
     */
    public function getContent(FileInfo $fileInfo)
    {
        return file_get_contents($this->getFullPath($fileInfo));
    }

    /**
     * @param FileInfo $fileInfo
     * @param string   $destinationPath
     *
     * @return bool
     */
    public function getContentToFile(FileInfo $fileInfo, $destinationPath)
    {
        return @copy($this->getFullPath($fileInfo), $destinationPath);
    }

    /**
     * @param FileInfo $fileInfo
     *
     * @return string|false
     */
    public function getUrl(FileInfo $fileInfo)
    {
        if ($fileInfo->getIsPrivate() || !$fileInfo->getStatus()->isActive()) {
            return false;
        }

        return $this->publicRootUrl . '/' . $this->generateFileKey($fileInfo) . '/' . $fileInfo->getName();
    }

    /**
     * @param FileInfo $fileInfo
     * @param bool $isPrivate
     *
     * @return bool
     */
    protected function changeVisibility(FileInfo $fileInfo, $isPrivate = false)
    {
        $sourcePath = $this->getFullPath($fileInfo);

        if (!file_exists($sourcePath)) {
            return false;
        }

        $destinationFileInfo = clone $fileInfo;
        $destinationFileInfo->setIsPrivate($isPrivate);

        $destinationPath = $this->getFullPath($destinationFileInfo);
        $this->prepareDirectory($destinationPath);

        if (@rename($sourcePath, $destinationPath)) {
            $this->clearPath($sourcePath);

            return true;
        }

        $this->clearPath($destinationPath);

        //ToDo Logger

        return false;
    }

    /**
     * @param FileInfo $fileInfo
     *
     * @return bool
     */
    public function makePrivate(FileInfo $fileInfo)
    {
        return $this->changeVisibility($fileInfo, true);
    }

    /**
     * @param FileInfo $fileInfo
     *
     * @return bool
     */
    public function makePublic(FileInfo $fileInfo)
    {
        return $this->changeVisibility($fileInfo, false);
    }
}
