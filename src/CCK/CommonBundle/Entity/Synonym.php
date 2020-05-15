<?php

namespace CCK\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Synonym
 *
 * @ORM\Table(name="Synonym")
 * @ORM\Entity(repositoryClass="CCK\CommonBundle\Repository\SynonymRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Synonym
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
     * @ORM\Column(name="term", type="text", nullable=true)
     */
    private $term;

    /**
     * @var boolean
     *
     * @ORM\Column(name="red_letter", type="boolean", nullable=false, options={"default" = 0})
     */
    private $redLetter;

    /**
     * @var integer
     *
     * @ORM\Column(name="synonym_id", type="integer", nullable=false)
     */
    private $synonymId;

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
     * @ORM\Column(name="index_add_letter", type="text", nullable=true)
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
     * @return Synonym
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
     * Set term.
     *
     * @param string|null $term
     *
     * @return Synonym
     */
    public function setTerm($term = null)
    {
        $this->term = $term;

        return $this;
    }

    /**
     * Get term.
     *
     * @return string|null
     */
    public function getTerm()
    {
        return $this->term;
    }

    /**
     * Set redLetter.
     *
     * @param bool $redLetter
     *
     * @return Synonym
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
     * Set synonymId.
     *
     * @param int $synonymId
     *
     * @return Synonym
     */
    public function setSynonymId($synonymId)
    {
        $this->synonymId = $synonymId;

        return $this;
    }

    /**
     * Get synonymId.
     *
     * @return int
     */
    public function getSynonymId()
    {
        return $this->synonymId;
    }

    /**
     * Set textFrequency.
     *
     * @param int $textFrequency
     *
     * @return Synonym
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
     * @return Synonym
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
     * @return Synonym
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
     * @return Synonym
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
     * @return Synonym
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
     * Set indexAddLetter.
     *
     * @param string|null $indexAddLetter
     *
     * @return Synonym
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
     * @return Synonym
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
     * @return Synonym
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
     * @return Synonym
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
     * @return Synonym
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
     * @return Synonym
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
     * @return Synonym
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
