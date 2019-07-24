<?php

namespace CCK\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Refer
 *
 * @ORM\Table(name="Refer")
 * @ORM\Entity(repositoryClass="CCK\CommonBundle\Repository\ReferRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Refer
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
     * @ORM\Column(name="refer_term_id", type="integer", nullable=false)
     */
    private $referTermId;

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
     * @return Refer
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
     * Set referTermId.
     *
     * @param int $referTermId
     *
     * @return Refer
     */
    public function setReferTermId($referTermId)
    {
        $this->referTermId = $referTermId;

        return $this;
    }

    /**
     * Get referTermId.
     *
     * @return int
     */
    public function getReferTermId()
    {
        return $this->referTermId;
    }

    /**
     * Set nombre.
     *
     * @param int $nombre
     *
     * @return Refer
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
     * @return Refer
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
     * @return Refer
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
     * @return Refer
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
     * @return Refer
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
