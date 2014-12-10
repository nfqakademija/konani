<?php

namespace Konani\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * User
 *
 * @ORM\Table(name="fos_user")
 * @ORM\Entity(repositoryClass="Konani\UserBundle\Entity\UserRepository")
 */
class User extends BaseUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="surename", type="string", length=64, nullable=true)
     */
    private $surename;

    /**
     * @ORM\OneToMany(targetEntity="\Konani\VideoBundle\Entity\Video", mappedBy="user")
     */
    protected $videos;

    /**
     * @ORM\OneToMany(targetEntity="\Konani\VideoBundle\Entity\File", mappedBy="user")
     */
    protected $files;


    public function __construct()
    {
        parent::__construct();
        $this->videos = new ArrayCollection();
        $this->files = new ArrayCollection();
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
     * Set name
     *
     * @param string $name
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set surename
     *
     * @param string $surename
     * @return User
     */
    public function setSurename($surename)
    {
        $this->surename = $surename;

        return $this;
    }

    /**
     * Get surename
     *
     * @return string
     */
    public function getSurename()
    {
        return $this->surename;
    }

    /**
     * Add videos
     *
     * @param \Konani\VideoBundle\Entity\Video $videos
     * @return User
     */
    public function addVideo(\Konani\VideoBundle\Entity\Video $videos)
    {
        $this->videos[] = $videos;

        return $this;
    }

    /**
     * Remove videos
     *
     * @param \Konani\VideoBundle\Entity\Video $videos
     */
    public function removeVideo(\Konani\VideoBundle\Entity\Video $videos)
    {
        $this->videos->removeElement($videos);
    }

    /**
     * Get videos
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVideos()
    {
        return $this->videos;
    }

    /**
     * Add Files
     *
     * @param \Konani\VideoBundle\Entity\File $file
     * @return User
     */
    public function addFile(\Konani\VideoBundle\Entity\File $file)
    {
        $this->files[] = $file;

        return $this;
    }

    /**
     * Remove files
     *
     * @param \Konani\VideoBundle\Entity\File $file
     */
    public function removeFile(\Konani\VideoBundle\Entity\File $file)
    {
        $this->files->removeElement($file);
    }

    /**
     * Get files
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFiles()
    {
        return $this->files;
    }
}
