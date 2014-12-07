<?php

namespace Konani\VideoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Video
 *
 * @ORM\Table(name="video")
 * @ORM\Entity(repositoryClass="Konani\VideoBundle\Entity\VideoRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Video
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="youtube_id", type="string", length=255)
     */
    private $youtubeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * @var float
     *
     * @ORM\Column(name="latitude", type="float")
     */
    private $latitude;

    /**
     * @var float
     *
     * @ORM\Column(name="longitude", type="float")
     */
    private $longitude;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

     /**
     * @ORM\ManyToOne(targetEntity="\Konani\UserBundle\Entity\User", inversedBy="videos")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\OneToMany(targetEntity="\Konani\CartBundle\Entity\CartVideo", mappedBy="video")
     */
    protected $subscriptions;

    public function __construct()
    {
        $this->subscriptions = new ArrayCollection();
    }


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set youtubeId
     *
     * @param string $youtubeId
     * @return Video
     */
    public function setYoutubeId($youtubeId)
    {
        $this->youtubeId = $youtubeId;

        return $this;
    }

    /**
     * Get youtubeId
     *
     * @return string 
     */
    public function getYoutubeId()
    {
        return $this->youtubeId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return Video
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set latitude
     *
     * @param float $latitude
     * @return Video
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param float $longitude
     * @return Video
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set createdAt
     *
     * @ORM\PrePersist
     * @return Video
     */
    public function setCreatedAt()
    {
        $this->createdAt = new \DateTime();

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @ORM\PrePersist
     * @return Video
     */
    public function setUpdatedAt()
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set user
     *
     * @param \Konani\UserBundle\Entity\User $user
     * @return Video
     */
    public function setUser(\Konani\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Konani\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Add subscriptions
     *
     * @param \Konani\CartBundle\Entity\CartVideo $subscriptions
     * @return Video
     */
    public function addSubscription(\Konani\CartBundle\Entity\CartVideo $subscriptions)
    {
        $this->subscriptions[] = $subscriptions;

        return $this;
    }

    /**
     * Remove subscriptions
     *
     * @param \Konani\CartBundle\Entity\CartVideo $subscriptions
     */
    public function removeSubscription(\Konani\CartBundle\Entity\CartVideo $subscriptions)
    {
        $this->subscriptions->removeElement($subscriptions);
    }

    /**
     * Get subscriptions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getSubscriptions()
    {
        return $this->subscriptions;
    }
}
