<?php

namespace CCK\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SubTerm
 *
 * @ORM\Table(name="SubTerm")
 * @ORM\Entity(repositoryClass="CCK\CommonBundle\Repository\SubTermRepository")
 * @ORM\HasLifecycleCallbacks
 */
class SubTerm
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
     * @ORM\Column(name="sub_term", type="text", nullable=true)
     */
    private $subTerm;

    /**
     * @var boolean
     *
     * @ORM\Column(name="red_letter", type="boolean", nullable=false, options={"default" = 0})
     */
    private $redLetter;

    /**
     * @var integer
     *
     * @ORM\Column(name="text_frequency", type="integer", nullable=false)
     */
    private $textFrequency;

    /**
     * @var integer
     *
     * @ORM\Column(name="center_frequency", type="integer", nullable=false)
     */
    private $centerFrequency;

    /**
     * @var boolean
     *
     * @ORM\Column(name="news_exam", type="boolean", nullable=false, options={"default" = 0})
     */
    private $newsExam;

    /**
     * @var string
     *
     * @ORM\Column(name="delimiter", type="string", length=4, nullable=true)
     */
    private $delimiter;

    /**
     * @var string
     *
     * @ORM\Column(name="kana", type="text", nullable=true)
     */
    private $kana;

    /**
     * @var string
     *
     * @ORM\Column(name="delimiter_kana", type="string", length=4, nullable=true)
     */
    private $delimiterKana;

    /**
     * @var string
     *
     * @ORM\Column(name="index_add_letter", type="string", length=4, nullable=true)
     */
    private $indexAddLetter;

    /**
     * @var string
     *
     * @ORM\Column(name="index_kana", type="text", nullable=true)
     */
    private $indexKana;

    /**
     * @var integer
     *
     * @ORM\Column(name="nombre", type="integer", nullable=false)
     */
    private $nombre;

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
     * @return SubTerm
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
     * Set subTerm.
     *
     * @param string|null $subTerm
     *
     * @return SubTerm
     */
    public function setSubTerm($subTerm = null)
    {
        $this->subTerm = $subTerm;

        return $this;
    }

    /**
     * Get subTerm.
     *
     * @return string|null
     */
    public function getSubTerm()
    {
        return $this->subTerm;
    }

    /**
     * Set redLetter.
     *
     * @param bool $redLetter
     *
     * @return SubTerm
     */
    public function setRedLetter($redLetter)
    {
        $this->redLetter = $redLetter;

        return $this;
    }

    /**
     * Get redLetter.
     *
     * @return bool
     */
    public function getRedLetter()
    {
        return $this->redLetter;
    }

    /**
     * Set textFrequency.
     *
     * @param int $textFrequency
     *
     * @return SubTerm
     */
    public function setTextFrequency($textFrequency)
    {
        $this->textFrequency = $textFrequency;

        return $this;
    }

    /**
     * Get textFrequency.
     *
     * @return int
     */
    public function getTextFrequency()
    {
        return $this->textFrequency;
    }

    /**
     * Set centerFrequency.
     *
     * @param int $centerFrequency
     *
     * @return SubTerm
     */
    public function setCenterFrequency($centerFrequency)
    {
        $this->centerFrequency = $centerFrequency;

        return $this;
    }

    /**
     * Get centerFrequency.
     *
     * @return int
     */
    public function getCenterFrequency()
    {
        return $this->centerFrequency;
    }

    /**
     * Set newsExam.
     *
     * @param bool $newsExam
     *
     * @return SubTerm
     */
    public function setNewsExam($newsExam)
    {
        $this->newsExam = $newsExam;

        return $this;
    }

    /**
     * Get newsExam.
     *
     * @return bool
     */
    public function getNewsExam()
    {
        return $this->newsExam;
    }

    /**
     * Set delimiter.
     *
     * @param string|null $delimiter
     *
     * @return SubTerm
     */
    public function setDelimiter($delimiter = null)
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    /**
     * Get delimiter.
     *
     * @return string|null
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * Set kana.
     *
     * @param string|null $kana
     *
     * @return SubTerm
     */
    public function setKana($kana = null)
    {
        $this->kana = $kana;

        return $this;
    }

    /**
     * Get kana.
     *
     * @return string|null
     */
    public function getKana()
    {
        return $this->kana;
    }

    /**
     * Set delimiterKana.
     *
     * @param string|null $delimiterKana
     *
     * @return SubTerm
     */
    public function setDelimiterKana($delimiterKana = null)
    {
        $this->delimiterKana = $delimiterKana;

        return $this;
    }

    /**
     * Get delimiterKana.
     *
     * @return string|null
     */
    public function getDelimiterKana()
    {
        return $this->delimiterKana;
    }

    /**
     * Set indexAddLetter.
     *
     * @param string|null $indexAddLetter
     *
     * @return SubTerm
     */
    public function setIndexAddLetter($indexAddLetter = null)
    {
        $this->indexAddLetter = $indexAddLetter;

        return $this;
    }

    /**
     * Get indexAddLetter.
     *
     * @return string|null
     */
    public function getIndexAddLetter()
    {
        return $this->indexAddLetter;
    }

    /**
     * Set indexKana.
     *
     * @param string|null $indexKana
     *
     * @return SubTerm
     */
    public function setIndexKana($indexKana = null)
    {
        $this->indexKana = $indexKana;

        return $this;
    }

    /**
     * Get indexKana.
     *
     * @return string|null
     */
    public function getIndexKana()
    {
        return $this->indexKana;
    }

    /**
     * Set nombre.
     *
     * @param int $nombre
     *
     * @return SubTerm
     */
    public function setNombre($nombre)
    {
        $this->nombre = $nombre;

        return $this;
    }

    /**
     * Get nombre.
     *
     * @return int
     */
    public function getNombre()
    {
        return $this->nombre;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return SubTerm
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
     * @return SubTerm
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
     * @return SubTerm
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
     * @return SubTerm
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
