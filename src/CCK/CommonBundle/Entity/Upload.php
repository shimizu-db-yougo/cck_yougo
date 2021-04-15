<?php

namespace CCK\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Upload
 *
 * @ORM\Table(name="Upload")
 * @ORM\Entity(repositoryClass="CCK\CommonBundle\Repository\UploadRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Upload
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="version_id", type="integer", nullable=false)
     */
    private $versionId;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="string", length=100, nullable=false)
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="text", nullable=true)
     */
    private $fileName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetime", nullable=false)
     */
    private $createDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modify_date", type="datetime", nullable=true)
     */
    private $modifyDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="delete_date", type="datetime", nullable=true)
     */
    private $deleteDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="delete_flag", type="boolean", nullable=false, options={"default" = 0})
     */
    private $deleteFlag;

    /**
     * @var string
     *
     * @ORM\Column(name="contents", type="text", nullable=true)
     */
    private $contents;

    /**
     * construct
     */
    public function __construct(){
    	$this->deleteFlag = false;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreateDateValue()
    {
    	if(!$this->getCreateDate()){
    		$this->createDate = new \Datetime();
    	}
    }

    /**
     * @ORM\PreUpdate
     */
    public function setModifyDateValue()
    {
    	$this->modifyDate = new \Datetime();
    }


    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set versionId.
     *
     * @param int $versionId
     *
     * @return Upload
     */
    public function setVersionId($versionId)
    {
        $this->versionId = $versionId;

        return $this;
    }

    /**
     * Get versionId.
     *
     * @return int
     */
    public function getVersionId()
    {
        return $this->versionId;
    }

    /**
     * Set userId.
     *
     * @param string $userId
     *
     * @return Upload
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set fileName.
     *
     * @param string|null $fileName
     *
     * @return Upload
     */
    public function setFileName($fileName = null)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get fileName.
     *
     * @return string|null
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return Upload
     */
    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;

        return $this;
    }

    /**
     * Get createDate.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * Set modifyDate.
     *
     * @param \DateTime|null $modifyDate
     *
     * @return Upload
     */
    public function setModifyDate($modifyDate = null)
    {
        $this->modifyDate = $modifyDate;

        return $this;
    }

    /**
     * Get modifyDate.
     *
     * @return \DateTime|null
     */
    public function getModifyDate()
    {
        return $this->modifyDate;
    }

    /**
     * Set deleteDate.
     *
     * @param \DateTime|null $deleteDate
     *
     * @return Upload
     */
    public function setDeleteDate($deleteDate = null)
    {
        $this->deleteDate = $deleteDate;

        return $this;
    }

    /**
     * Get deleteDate.
     *
     * @return \DateTime|null
     */
    public function getDeleteDate()
    {
        return $this->deleteDate;
    }

    /**
     * Set deleteFlag.
     *
     * @param bool $deleteFlag
     *
     * @return Upload
     */
    public function setDeleteFlag($deleteFlag)
    {
        $this->deleteFlag = $deleteFlag;

        return $this;
    }

    /**
     * Get deleteFlag.
     *
     * @return bool
     */
    public function getDeleteFlag()
    {
        return $this->deleteFlag;
    }

    /**
     * Set contents.
     *
     * @param string|null $contents
     *
     * @return Upload
     */
    public function setContents($contents = null)
    {
        $this->contents = $contents;

        return $this;
    }

    /**
     * Get contents.
     *
     * @return string|null
     */
    public function getContents()
    {
        return $this->contents;
    }
}
