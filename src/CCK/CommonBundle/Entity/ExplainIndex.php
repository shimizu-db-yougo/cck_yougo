<?php

namespace CCK\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ExplainIndex
 *
 * @ORM\Table(name="ExplainIndex")
 * @ORM\Entity(repositoryClass="CCK\CommonBundle\Repository\ExplainIndexRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ExplainIndex
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
     * @return ExplainIndex
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
     * @return ExplainIndex
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
     * Set indexAddLetter.
     *
     * @param string|null $indexAddLetter
     *
     * @return ExplainIndex
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
     * @return ExplainIndex
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
     * @return ExplainIndex
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
     * @return ExplainIndex
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
     * @return ExplainIndex
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
     * @return ExplainIndex
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
     * @return ExplainIndex
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
