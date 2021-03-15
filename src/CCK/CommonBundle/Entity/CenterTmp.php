<?php

namespace CCK\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CenterTmp
 *
 * @ORM\Table(name="CenterTmp")
 * @ORM\Entity(repositoryClass="CCK\CommonBundle\Repository\CenterTmpRepository")
 * @ORM\HasLifecycleCallbacks
 */
class CenterTmp
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
     * @ORM\Column(name="main_term_id", type="integer", nullable=false)
     */
    private $mainTermId;

    /**
     * @var string
     *
     * @ORM\Column(name="index_term", type="text", nullable=true)
     */
    private $indexTerm;

    /**
     * @var integer
     *
     * @ORM\Column(name="yougo_flag", type="integer", nullable=true)
     */
    private $yougoFlag;

    /**
     * @var integer
     *
     * @ORM\Column(name="year", type="integer", length=4, nullable=true)
     */
    private $year;

    /**
     * @var integer
     *
     * @ORM\Column(name="main_exam", type="integer", nullable=true)
     */
    private $mainExam;

    /**
     * @var integer
     *
     * @ORM\Column(name="sub_exam", type="integer", nullable=true)
     */
    private $subExam;

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
     * Set mainTermId.
     *
     * @param int $mainTermId
     *
     * @return CenterTmp
     */
    public function setMainTermId($mainTermId)
    {
        $this->mainTermId = $mainTermId;

        return $this;
    }

    /**
     * Get mainTermId.
     *
     * @return int
     */
    public function getMainTermId()
    {
        return $this->mainTermId;
    }

    /**
     * Set indexTerm.
     *
     * @param string|null $indexTerm
     *
     * @return CenterTmp
     */
    public function setIndexTerm($indexTerm = null)
    {
        $this->indexTerm = $indexTerm;

        return $this;
    }

    /**
     * Get indexTerm.
     *
     * @return string|null
     */
    public function getIndexTerm()
    {
        return $this->indexTerm;
    }

    /**
     * Set yougoFlag.
     *
     * @param int|null $yougoFlag
     *
     * @return CenterTmp
     */
    public function setYougoFlag($yougoFlag = null)
    {
        $this->yougoFlag = $yougoFlag;

        return $this;
    }

    /**
     * Get yougoFlag.
     *
     * @return int|null
     */
    public function getYougoFlag()
    {
        return $this->yougoFlag;
    }

    /**
     * Set year.
     *
     * @param int|null $year
     *
     * @return CenterTmp
     */
    public function setYear($year = null)
    {
        $this->year = $year;

        return $this;
    }

    /**
     * Get year.
     *
     * @return int|null
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set mainExam.
     *
     * @param int|null $mainExam
     *
     * @return CenterTmp
     */
    public function setMainExam($mainExam = null)
    {
        $this->mainExam = $mainExam;

        return $this;
    }

    /**
     * Get mainExam.
     *
     * @return int|null
     */
    public function getMainExam()
    {
        return $this->mainExam;
    }

    /**
     * Set subExam.
     *
     * @param int|null $subExam
     *
     * @return CenterTmp
     */
    public function setSubExam($subExam = null)
    {
        $this->subExam = $subExam;

        return $this;
    }

    /**
     * Get subExam.
     *
     * @return int|null
     */
    public function getSubExam()
    {
        return $this->subExam;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return CenterTmp
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
     * @return CenterTmp
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
     * @return CenterTmp
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
     * @return CenterTmp
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
