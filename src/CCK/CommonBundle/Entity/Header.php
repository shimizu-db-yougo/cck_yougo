<?php

namespace CCK\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Header
 *
 * @ORM\Table(name="Header")
 * @ORM\Entity(repositoryClass="CCK\CommonBundle\Repository\HeaderRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Header
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
     * @var integer
     *
     * @ORM\Column(name="header_id", type="integer", nullable=false)
     */
    private $headerId;

    /**
     * @var integer
     *
     * @ORM\Column(name="hen", type="integer", nullable=false)
     */
    private $hen;

    /**
     * @var integer
     *
     * @ORM\Column(name="sho", type="integer", nullable=false)
     */
    private $sho;

    /**
     * @var integer
     *
     * @ORM\Column(name="dai", type="integer", nullable=false)
     */
    private $dai;

    /**
     * @var integer
     *
     * @ORM\Column(name="chu", type="integer", nullable=false)
     */
    private $chu;

    /**
     * @var integer
     *
     * @ORM\Column(name="ko", type="integer", nullable=false)
     */
    private $ko;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", nullable=true)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", nullable=false)
     */
    private $sort;

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
     * @return Header
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
     * Set headerId.
     *
     * @param int $headerId
     *
     * @return Header
     */
    public function setHeaderId($headerId)
    {
        $this->headerId = $headerId;

        return $this;
    }

    /**
     * Get headerId.
     *
     * @return int
     */
    public function getHeaderId()
    {
        return $this->headerId;
    }

    /**
     * Set hen.
     *
     * @param int $hen
     *
     * @return Header
     */
    public function setHen($hen)
    {
        $this->hen = $hen;

        return $this;
    }

    /**
     * Get hen.
     *
     * @return int
     */
    public function getHen()
    {
        return $this->hen;
    }

    /**
     * Set sho.
     *
     * @param int $sho
     *
     * @return Header
     */
    public function setSho($sho)
    {
        $this->sho = $sho;

        return $this;
    }

    /**
     * Get sho.
     *
     * @return int
     */
    public function getSho()
    {
        return $this->sho;
    }

    /**
     * Set dai.
     *
     * @param int $dai
     *
     * @return Header
     */
    public function setDai($dai)
    {
        $this->dai = $dai;

        return $this;
    }

    /**
     * Get dai.
     *
     * @return int
     */
    public function getDai()
    {
        return $this->dai;
    }

    /**
     * Set chu.
     *
     * @param int $chu
     *
     * @return Header
     */
    public function setChu($chu)
    {
        $this->chu = $chu;

        return $this;
    }

    /**
     * Get chu.
     *
     * @return int
     */
    public function getChu()
    {
        return $this->chu;
    }

    /**
     * Set ko.
     *
     * @param int $ko
     *
     * @return Header
     */
    public function setKo($ko)
    {
        $this->ko = $ko;

        return $this;
    }

    /**
     * Get ko.
     *
     * @return int
     */
    public function getKo()
    {
        return $this->ko;
    }

    /**
     * Set name.
     *
     * @param string|null $name
     *
     * @return Header
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set sort.
     *
     * @param int $sort
     *
     * @return Header
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort.
     *
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return Header
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
     * @return Header
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
     * @return Header
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
     * @return Header
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
}
