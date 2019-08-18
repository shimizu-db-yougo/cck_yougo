<?php

namespace CCK\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Explain
 *
 * @ORM\Table(name="Explain")
 * @ORM\Entity(repositoryClass="CCK\CommonBundle\Repository\ExplainRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Explain
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
     * @return Explain
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
     * @return Explain
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
     * @return Explain
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
     * @return Explain
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
     * @return Explain
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
     * @return Explain
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
     * @return Explain
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
     * @return Explain
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
     * @return Explain
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
