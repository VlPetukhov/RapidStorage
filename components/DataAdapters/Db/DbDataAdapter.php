<?php
/**
 * @author Vladimir V Petukhov
 * @version 1.0
 * @copyright Copyright (c) 2016, Vladimir V Petukhov
 * @license https://opensource.org/licenses/GPL-3.0 GNU Public License Version 3
 *
 * @class MySqlDataAdapters
 * @namespace components\DataAdapters\Db
 */

namespace components\DataAdapters\Db;

use components\values\FileExtMimeTranslator;
use components\values\FileInfo;
use components\DataAdapters\interfaces\DataAdapterInterface;
use components\values\FileStatus;
use PDO;
use yii\base\Component;
use yii\db\Connection;
use yii\db\Transaction;

class DbDataAdapter extends Component implements DataAdapterInterface
{
    /** @var  Connection */
    protected $db;
    /** @var  Transaction|null */
    protected $transaction;

    /**
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @param Connection $db
     */
    public function setDb(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $mime
     * @param int $id
     *
     * @return string
     */
    protected function generateFileName($id, $mime)
    {
        $fileName = base_convert($id, 10, 36);
        $fileExt = FileExtMimeTranslator::getExtensionsByMime($mime);

        if ($fileExt) {
            $fileName .= ".{$fileExt}";
        }

        return $fileName;
    }

    /**
     * @return string
     */
    public function generateTempName()
    {
        return '__unnamed__';
    }

    /**
     * @return bool
     */
    public function startTransaction()
    {
        if (!$this->transaction) {
            $this->transaction = $this->db->beginTransaction(Transaction::REPEATABLE_READ);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function commitTransaction()
    {
        if ($this->transaction) {
            $this->transaction->commit();
        }
    }

    /**
     * @return bool
     */
    public function rollbackTransaction()
    {
        if ($this->transaction) {
            $this->transaction->rollBack();
        }
    }

    /**
     * @param FileInfo $fileInfo
     *
     * @throws \Exception
     *
     * @throws \yii\db\Exception
     * @return boolean
     */
    public function createFileRecord(FileInfo $fileInfo)
    {
        $mimeId = $this->getMimeId($fileInfo->getMime());

        if (false === $mimeId && $this->createMime($fileInfo->getMime())) {
            $mimeId = $this->getMimeId($fileInfo->getMime());
        } else {
            throw new \Exception("DbDataAdapter: can't create mime '" . $fileInfo->getMime() ."'.");
        }

        $fileName = $fileInfo->getName();
        $regenerateFileName = false;

        if (!$fileName) {
            $fileName = $this->generateTempName();
            $regenerateFileName = true;
        }

        $sql = "INSERT INTO {{%rs_file_record}} (
                  [[name]], [[mime_id]], [[size]], [[is_private]], [[created_at]], [[updated_at]], [[delete_at_time]],
                  [[status]],[[status_time]]
                )
                VALUES
                (
                  :name, :mimeId, :size, :isPrivate, :createdAt, :updatedAt, :deleteAtTime, :status, :statusTime
                )";

        $result = (bool)$this->db->createCommand($sql)
            ->bindValue(':name', $fileName, PDO::PARAM_STR)
            ->bindValue(':mimeId', $mimeId, PDO::PARAM_INT)
            ->bindValue(':size', $fileInfo->getSize(), PDO::PARAM_INT)
            ->bindValue(':isPrivate', $fileInfo->getIsPrivate(), PDO::PARAM_BOOL)
            ->bindValue(':createdAt', time(), PDO::PARAM_INT)
            ->bindValue(':updatedAt', null, PDO::PARAM_NULL)
            ->bindValue(':deleteAtTime', $fileInfo->getDeleteAtTime(), PDO::PARAM_INT)
            ->bindValue(':status', $fileInfo->getStatus()->getValue(), PDO::PARAM_INT)
            ->bindValue(':statusTime', time(), PDO::PARAM_INT)
            ->execute();

        if ($result) {
            //Update FileInfo data and return it
            $fileId = $this->db->getLastInsertID();
            $fileInfo->setId($fileId);

            if ($regenerateFileName) {
                $newFileName = $this->generateFileName($fileId, $fileInfo->getMime());

                $sql = "UPDATE {{%rs_file_record}} SET [[name]] = :name WHERE [[id]] = :id LIMIT 1";

                $result = $this->db->createCommand($sql)
                    ->bindValue(':name', $newFileName, PDO::PARAM_STR)
                    ->bindValue(':id', $fileId, PDO::PARAM_INT)
                    ->execute();

                if ($result) {
                    $fileName = $newFileName;
                }
            }

            $fileInfo->setName($fileName);

            return $fileInfo;
        }

        return false;
    }

    /**
     * @param integer $id
     * @param int $status
     *
     * @return FileInfo|null
     */
    public function getFileInfo($id, $status = FileStatus::ACTIVE)
    {
        $statusCondition = 'AND fr.[[status]] = :status';

        if (false === $status) {
            $statusCondition = '';
        }

        $sql = "SELECT fr.[[id]] AS id, [[name]], [[mime]], [[size]], [[is_private]], [[status]], [[updated_at]], [[delete_at_time]]
                FROM {{%rs_file_record}} AS fr LEFT OUTER JOIN {{%rs_mime}} AS m ON fr.[[mime_id]] = m.[[id]]
                WHERE fr.[[id]] = :id {$statusCondition} LIMIT 1";

        $command = $this->db->createCommand($sql)
            ->bindValue(':id', $id, PDO::PARAM_INT);

        if (false !== $status) {
            $command->bindValue(':status', $status, PDO::PARAM_INT);
        }

        // NB! Not documented but valid way of using of yii\db\Command
        // Force setting fetch mode to getting class
        $result = $command->queryOne([PDO::FETCH_CLASS, FileInfo::className()]);

        if ($result) {
//            $response = new FileInfo;
//            $response->setId($result['id']);
//            $response->setName($result['name']);
//            $response->setMime($result['mime']);
//            $response->setSize($result['size']);
//            $response->setIsPrivate($result['is_private']);
//            $response->setStatus($result['status']);
//            $response->setUpdatedAt($result['updated_at']);
//            $response->setDeleteAtTime($result['delete_at_time']);

            return $result;
        }

        return null;
    }

    /**
     * @param integer  $id
     * @param FileInfo $actualInfo
     *
     * @throws \Exception
     * @throws \yii\db\Exception
     *
     * @return boolean
     */
    public function updateFileInfo($id, FileInfo $actualInfo)
    {
        $setSqlPart = [];

        $setName = !is_null($actualInfo->getName());

        if ($setName) {
            $setSqlPart[] = '[[name]] = :name';
        }

        $setMime = !is_null($actualInfo->getMime());

        $mimeId = null;

        if ($setMime) {
            $mimeId = $this->getMimeId($actualInfo->getMime());

            if (false === $mimeId && $this->createMime($actualInfo->getMime())) {
                $mimeId = $this->getMimeId($actualInfo->getMime());
            } else {
                throw new \Exception("DbDataAdapter: can't create mime '" . $actualInfo->getMime() ."'.");
            }

            $setSqlPart[] = '[[mime_id]] = :mimeId';
        }

        $setSize = !is_null($actualInfo->getSize());

        if ($setSize) {
            $setSqlPart[] = '[[size]] = :size';
        }

        $setIsPrivate = !is_null($actualInfo->getIsPrivate());

        if ($setIsPrivate) {
            $setSqlPart[] = '[[is_private]] = :isPrivate';
        }

        $setStatus = !is_null($actualInfo->getStatus());

        if ($setStatus) {
            $setSqlPart[] = '[[status]] = :status';
        }

        $setDeleteAtTime = !is_null($actualInfo->getDeleteAtTime());

        if ($setDeleteAtTime) {
            $setSqlPart[] = '[[delete_at_time]] = :deleteAtTime';
        }

        if (!($setName || $setMime || $setSize || $setIsPrivate || $setStatus || $setDeleteAtTime)) {
            //Nothing to do
            return true;
        }

        $sql = "UPDATE {{%rs_file_record}} SET [[updated_at]] = :updatedAt, " . implode(',', $setSqlPart) .
               " WHERE [[id]] = :id LIMIT 1";

        $query = $this->db->createCommand($sql)
            ->bindValue(':id', $id,PDO::PARAM_INT)
            ->bindValue(':updatedAt', time(), PDO::PARAM_NULL);

        if ($setName) {
            $query->bindValue(':name', $actualInfo->getName(), PDO::PARAM_STR);
        }

        if ($setMime) {
            $query->bindValue(':mimeId', $mimeId, PDO::PARAM_INT);
        }

        if ($setSize) {
            $query->bindValue(':size', $actualInfo->getSize(), PDO::PARAM_INT);
        }

        if ($setIsPrivate) {
            $query->bindValue(':isPrivate', $actualInfo->getIsPrivate(), PDO::PARAM_BOOL);
        }

        if ($setStatus) {
            $query->bindValue(':status', $actualInfo->getStatus()->getValue(), PDO::PARAM_INT);
        }

        if ($setDeleteAtTime) {
            $query->bindValue(':deleteAtTime', $actualInfo->getDeleteAtTime(), PDO::PARAM_INT);
        }

        return (bool)$query->execute();
    }

    /**
     * @param integer $id
     *
     * @return boolean
     */
    public function exist($id)
    {
        $sql = "SELECT 1 FROM {{%rs_file_record}} WHERE [[id]] = :id LIMIT 1";

        $command = $this->db->createCommand($sql)
            ->bindValue(':id', $id, PDO::PARAM_INT);

        return (bool)$command->queryScalar();
    }

    /**
     * @param integer $id
     *
     * @return boolean
     */
    public function delete($id)
    {
        $sql = "UPDATE {{%rs_file_record}} SET [[status]] = :status, [[status_time]] = :statusTime
                WHERE [[id]] = :id LIMIT 1";

        $command = $this->db->createCommand($sql)
            ->bindValue(':id', $id, PDO::PARAM_INT)
            ->bindValue(':status', FileStatus::DELETED, PDO::PARAM_INT)
            ->bindValue(':statusTime', time(), PDO::PARAM_INT);

        return (bool)$command->execute();
    }

    /**
     * @param integer $id
     *
     * @return boolean
     */
    public function restore($id)
    {
        $sql = "UPDATE {{%rs_file_record}} SET [[status]] = :status, [[status_time]] = :statusTime
                WHERE [[id]] = :id LIMIT 1";

        $command = $this->db->createCommand($sql)
            ->bindValue(':id', $id, PDO::PARAM_INT)
            ->bindValue(':status', FileStatus::ACTIVE, PDO::PARAM_INT)
            ->bindValue(':statusTime', time(), PDO::PARAM_INT);

        return (bool)$command->execute();
    }

    /**
     * @param int $status
     *
     * @return FileInfo[]|array
     */
    public function getFileRecords($status = FileStatus::ACTIVE)
    {
        $sql = "SELECT fr.[[id]] AS id, [[name]], [[mime]], [[size]], [[is_private]], [[status]], [[updated_at]], [[delete_at_time]]
                FROM {{%rs_file_record}} AS fr LEFT OUTER JOIN {{%rs_mime}} AS m ON fr.[[mime_id]] = m.[[id]]
                WHERE fr.[[status]] = :status";

        $command = $this->db->createCommand($sql)
            ->bindValue(':status', $status, PDO::PARAM_INT);

        // NB! Not documented but valid way of using of yii\db\Command
        // Force setting fetch mode to getting class
        return $command->queryAll([PDO::FETCH_CLASS, FileInfo::className()]);
    }

    /**
     * @return int
     * @throws \yii\db\Exception
     */
    public function processSetToDeleteRecords()
    {
        $sql = "UPDATE {{%rs_file_record}} SET [[status]] = :status, [[status_time]] = :statusTime
                WHERE [[delete_at_time]] <= :time";

        $time = time();

        return $this->db->createCommand($sql)
            ->bindValue(':status', FileStatus::DELETED, PDO::PARAM_INT)
            ->bindValue(':statusTime', $time, PDO::PARAM_INT)
            ->bindValue(':delete_at_time', $time, PDO::PARAM_INT)
            ->execute();
    }

    /**
     * @param integer $id
     *
     * @return bool
     */
    public function setPrivate($id)
    {
        $sql = "UPDATE {{%rs_file_record}} SET [[is_private]] = TRUE WHERE [[id]] = :id LIMIT 1";

        return (bool)$this->db->createCommand($sql)
            ->bindValue(':id', $id, PDO::PARAM_INT)
            ->execute();
    }

    /**
     * @param integer $id
     *
     * @return bool
     */
    public function setPublic($id)
    {
        $sql = "UPDATE {{%rs_file_record}} SET [[is_private]] = FALSE WHERE [[id]] = :id LIMIT 1";

        return (bool)$this->db->createCommand($sql)
            ->bindValue(':id', $id, PDO::PARAM_INT)
            ->execute();
    }


//  ****************
//  * MIME routine *
//  ****************

    /**
     * @param string $mime
     *
     * @return bool
     * @throws \yii\db\Exception
     */
    public function createMime($mime)
    {
        if (false !== $this->getMimeId($mime)) {
            return false;
        }

        $sql = "INSERT INTO {{%rs_mime}} ([[mime]]) VALUES  (:mime)";

        return (bool)$this->db->createCommand($sql)
            ->bindValue(':mime', $mime, PDO::PARAM_STR)
            ->execute();
    }

    /**
     * @param integer $id
     *
     * @return string|false
     */
    public function getMimeById($id)
    {
        $sql = "SELECT [[mime]] from {{%rs_mime}} WHERE [[id]] = :mimeId";

        return $this->db->createCommand($sql)
            ->bindValue(':mimeId', $id, PDO::PARAM_INT)
            ->queryScalar();
    }

    /**
     * Returns Mime ID or false
     * @param string $mime
     *
     * @return integer|false
     */
    public function getMimeId($mime)
    {
        $sql = "SELECT [[id]] from {{%rs_mime}} WHERE [[mime]] = :mime";

        return $this->db->createCommand($sql)
            ->bindValue(':mime', $mime, PDO::PARAM_STR)
            ->queryScalar();
    }

    /**
     * Physical removing mime and its file records
     *
     * @param integer $mimeId
     *
     *
     * @return boolean
     */
    public function eraseMime($mimeId)
    {
        $sql = "DELETE FROM {{%rs_mime}} WHERE [[id]] = :mimeId LIMIT 1";

        return (bool)$this->db->createCommand($sql)
            ->bindValue(':mimeId', $mimeId, PDO::PARAM_INT)
            ->execute();
    }
}
