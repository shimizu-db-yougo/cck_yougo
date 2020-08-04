<?php

namespace CCK\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * MainTerm
 *
 * @ORM\Table(name="MainTerm")
 * @ORM\Entity(repositoryClass="CCK\CommonBundle\Repository\MainTermRepository")
 * @ORM\HasLifecycleCallbacks
 */
class MainTerm
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
     * @ORM\Column(name="term_id", type="integer", nullable=false)
     */
    private $termId;

    /**
     * @var integer
     *
     * @ORM\Column(name="curriculum_id", type="integer", nullable=false)
     */
    private $curriculumId;

    /**
     * @var integer
     *
     * @ORM\Column(name="header_id", type="integer", nullable=false)
     */
    private $headerId;

    /**
     * @var integer
     *
     * @ORM\Column(name="print_order", type="integer", nullable=false)
     */
    private $printOrder;

    /**
     * @var string
     *
     * @ORM\Column(name="main_term", type="text", nullable=true)
     */
    private $mainTerm;

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
     * @ORM\Column(name="western_language", type="text", nullable=true)
     */
    private $westernLanguage;

    /**
     * @var string
     *
     * @ORM\Column(name="birth_year", type="text", nullable=true)
     */
    private $birthYear;

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
    private $index_add_letter;

    /**
     * @var string
     *
     * @ORM\Column(name="index_kana", type="text", nullable=true)
     */
    private $indexKana;

    /**
     * @var string
     *
     * @ORM\Column(name="index_original", type="text", nullable=true)
     */
    private $indexOriginal;

    /**
     * @var string
     *
     * @ORM\Column(name="index_original_kana", type="text", nullable=true)
     */
    private $indexOriginalKana;

    /**
     * @var string
     *
     * @ORM\Column(name="index_abbreviation", type="text", nullable=true)
     */
    private $indexAbbreviation;

    /**
     * @var integer
     *
     * @ORM\Column(name="nombre", type="integer", nullable=false)
     */
    private $nombre;

    /**
     * @var string
     *
     * @ORM\Column(name="term_explain", type="text", nullable=true)
     */
    private $termExplain;

    /**
     * @var string
     *
     * @ORM\Column(name="handover", type="text", nullable=true)
     */
    private $handover;

    /**
     * @var string
     *
     * @ORM\Column(name="illust_filename", type="text", nullable=true)
     */
    private $illustFilename;

    /**
     * @var string
     *
     * @ORM\Column(name="illust_caption", type="text", nullable=true)
     */
    private $illustCaption;

    /**
     * @var string
     *
     * @ORM\Column(name="illust_kana", type="text", nullable=true)
     */
    private $illustKana;

    /**
     * @var integer
     *
     * @ORM\Column(name="illust_nombre", type="integer", nullable=false)
     */
    private $illustNombre;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="string", length=100, nullable=true)
     */
    private $user_id;

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
     * @var boolean
     *
     * @ORM\Column(name="kana_exist_flag", type="boolean", nullable=true, options={"default" = 0})
     */
    private $kanaExistFlag;

    /**
     * @var boolean
     *
     * @ORM\Column(name="nombre_bold", type="boolean", nullable=true, options={"default" = 0})
     */
    private $nombreBold;

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
     * Set termId.
     *
     * @param int $termId
     *
     * @return MainTerm
     */
    public function setTermId($termId)
    {
        $this->termId = $termId;

        return $this;
    }

    /**
     * Get termId.
     *
     * @return int
     */
    public function getTermId()
    {
        return $this->termId;
    }

    /**
     * Set curriculumId.
     *
     * @param int $curriculumId
     *
     * @return MainTerm
     */
    public function setCurriculumId($curriculumId)
    {
        $this->curriculumId = $curriculumId;

        return $this;
    }

    /**
     * Get curriculumId.
     *
     * @return int
     */
    public function getCurriculumId()
    {
        return $this->curriculumId;
    }

    /**
     * Set headerId.
     *
     * @param int $headerId
     *
     * @return MainTerm
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
     * Set printOrder.
     *
     * @param int $printOrder
     *
     * @return MainTerm
     */
    public function setPrintOrder($printOrder)
    {
        $this->printOrder = $printOrder;

        return $this;
    }

    /**
     * Get printOrder.
     *
     * @return int
     */
    public function getPrintOrder()
    {
        return $this->printOrder;
    }

    /**
     * Set mainTerm.
     *
     * @param string|null $mainTerm
     *
     * @return MainTerm
     */
    public function setMainTerm($mainTerm = null)
    {
        $this->mainTerm = $mainTerm;

        return $this;
    }

    /**
     * Get mainTerm.
     *
     * @return string|null
     */
    public function getMainTerm()
    {
        return $this->mainTerm;
    }

    /**
     * Set redLetter.
     *
     * @param bool $redLetter
     *
     * @return MainTerm
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
     * @return MainTerm
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
     * @return MainTerm
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
     * @return MainTerm
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
     * @return MainTerm
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
     * Set westernLanguage.
     *
     * @param string|null $westernLanguage
     *
     * @return MainTerm
     */
    public function setWesternLanguage($westernLanguage = null)
    {
        $this->westernLanguage = $westernLanguage;

        return $this;
    }

    /**
     * Get westernLanguage.
     *
     * @return string|null
     */
    public function getWesternLanguage()
    {
        return $this->westernLanguage;
    }

    /**
     * Set birthYear.
     *
     * @param string|null $birthYear
     *
     * @return MainTerm
     */
    public function setBirthYear($birthYear = null)
    {
        $this->birthYear = $birthYear;

        return $this;
    }

    /**
     * Get birthYear.
     *
     * @return string|null
     */
    public function getBirthYear()
    {
        return $this->birthYear;
    }

    /**
     * Set kana.
     *
     * @param string|null $kana
     *
     * @return MainTerm
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
     * @return MainTerm
     */
    public function setIndexAddLetter($indexAddLetter = null)
    {
        $this->index_add_letter = $indexAddLetter;

        return $this;
    }

    /**
     * Get indexAddLetter.
     *
     * @return string|null
     */
    public function getIndexAddLetter()
    {
        return $this->index_add_letter;
    }

    /**
     * Set indexKana.
     *
     * @param string|null $indexKana
     *
     * @return MainTerm
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
     * Set indexOriginal.
     *
     * @param string|null $indexOriginal
     *
     * @return MainTerm
     */
    public function setIndexOriginal($indexOriginal = null)
    {
        $this->indexOriginal = $indexOriginal;

        return $this;
    }

    /**
     * Get indexOriginal.
     *
     * @return string|null
     */
    public function getIndexOriginal()
    {
        return $this->indexOriginal;
    }

    /**
     * Set indexOriginalKana.
     *
     * @param string|null $indexOriginalKana
     *
     * @return MainTerm
     */
    public function setIndexOriginalKana($indexOriginalKana = null)
    {
        $this->indexOriginalKana = $indexOriginalKana;

        return $this;
    }

    /**
     * Get indexOriginalKana.
     *
     * @return string|null
     */
    public function getIndexOriginalKana()
    {
        return $this->indexOriginalKana;
    }

    /**
     * Set indexAbbreviation.
     *
     * @param string|null $indexAbbreviation
     *
     * @return MainTerm
     */
    public function setIndexAbbreviation($indexAbbreviation = null)
    {
        $this->indexAbbreviation = $indexAbbreviation;

        return $this;
    }

    /**
     * Get indexAbbreviation.
     *
     * @return string|null
     */
    public function getIndexAbbreviation()
    {
        return $this->indexAbbreviation;
    }

    /**
     * Set nombre.
     *
     * @param int $nombre
     *
     * @return MainTerm
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
     * Set termExplain.
     *
     * @param string|null $termExplain
     *
     * @return MainTerm
     */
    public function setTermExplain($termExplain = null)
    {
        $this->termExplain = $termExplain;

        return $this;
    }

    /**
     * Get termExplain.
     *
     * @return string|null
     */
    public function getTermExplain()
    {
        return $this->termExplain;
    }

    /**
     * Set handover.
     *
     * @param string|null $handover
     *
     * @return MainTerm
     */
    public function setHandover($handover = null)
    {
        $this->handover = $handover;

        return $this;
    }

    /**
     * Get handover.
     *
     * @return string|null
     */
    public function getHandover()
    {
        return $this->handover;
    }

    /**
     * Set illustFilename.
     *
     * @param string|null $illustFilename
     *
     * @return MainTerm
     */
    public function setIllustFilename($illustFilename = null)
    {
        $this->illustFilename = $illustFilename;

        return $this;
    }

    /**
     * Get illustFilename.
     *
     * @return string|null
     */
    public function getIllustFilename()
    {
        return $this->illustFilename;
    }

    /**
     * Set illustCaption.
     *
     * @param string|null $illustCaption
     *
     * @return MainTerm
     */
    public function setIllustCaption($illustCaption = null)
    {
        $this->illustCaption = $illustCaption;

        return $this;
    }

    /**
     * Get illustCaption.
     *
     * @return string|null
     */
    public function getIllustCaption()
    {
        return $this->illustCaption;
    }

    /**
     * Set illustKana.
     *
     * @param string|null $illustKana
     *
     * @return MainTerm
     */
    public function setIllustKana($illustKana = null)
    {
        $this->illustKana = $illustKana;

        return $this;
    }

    /**
     * Get illustKana.
     *
     * @return string|null
     */
    public function getIllustKana()
    {
        return $this->illustKana;
    }

    /**
     * Set illustNombre.
     *
     * @param int $illustNombre
     *
     * @return MainTerm
     */
    public function setIllustNombre($illustNombre)
    {
        $this->illustNombre = $illustNombre;

        return $this;
    }

    /**
     * Get illustNombre.
     *
     * @return int
     */
    public function getIllustNombre()
    {
        return $this->illustNombre;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return MainTerm
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
     * @return MainTerm
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
     * @return MainTerm
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
     * @return MainTerm
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
     * Set userId.
     *
     * @param string|null $userId
     *
     * @return MainTerm
     */
    public function setUserId($userId = null)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return string|null
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set kanaExistFlag.
     *
     * @param bool|null $kanaExistFlag
     *
     * @return MainTerm
     */
    public function setKanaExistFlag($kanaExistFlag = null)
    {
        $this->kanaExistFlag = $kanaExistFlag;

        return $this;
    }

    /**
     * Get kanaExistFlag.
     *
     * @return bool|null
     */
    public function getKanaExistFlag()
    {
        return $this->kanaExistFlag;
    }

    /**
     * Set nombreBold.
     *
     * @param bool|null $nombreBold
     *
     * @return MainTerm
     */
    public function setNombreBold($nombreBold = null)
    {
        $this->nombreBold = $nombreBold;

        return $this;
    }

    /**
     * Get nombreBold.
     *
     * @return bool|null
     */
    public function getNombreBold()
    {
        return $this->nombreBold;
    }
}
