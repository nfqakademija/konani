<?php

namespace Konani\CartBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CartVideo
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Konani\CartBundle\Entity\CartVideoRepository")
 */
class CartVideo
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
     * @var integer
     *
     * @ORM\Column(name="cart_id", type="integer")
     */
    private $cartId;

    /**
     * @var integer
     *
     * @ORM\Column(name="video_id", type="integer")
     */
    private $videoId;

    /**
     * @ORM\ManyToOne(targetEntity="\Konani\CartBundle\Entity\Cart", inversedBy="videos")
     * @ORM\JoinColumn(name="cart_id", referencedColumnName="id")
     */
    protected $cart;

    /**
     * @ORM\ManyToOne(targetEntity="\Konani\VideoBundle\Entity\Video", inversedBy="subscriptions")
     * @ORM\JoinColumn(name="video_id", referencedColumnName="id")
     */
    protected $video;

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
     * Set cartId
     *
     * @param integer $cartId
     * @return CartVideo
     */
    public function setCartId($cartId)
    {
        $this->cartId = $cartId;

        return $this;
    }

    /**
     * Get cartId
     *
     * @return integer 
     */
    public function getCartId()
    {
        return $this->cartId;
    }

    /**
     * Set videoId
     *
     * @param integer $videoId
     * @return CartVideo
     */
    public function setVideoId($videoId)
    {
        $this->videoId = $videoId;

        return $this;
    }

    /**
     * Get videoId
     *
     * @return integer 
     */
    public function getVideoId()
    {
        return $this->videoId;
    }

    /**
     * Set cart
     *
     * @param \Konani\CartBundle\Entity\Cart $cart
     * @return CartVideo
     */
    public function setCart(\Konani\CartBundle\Entity\Cart $cart = null)
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * Get cart
     *
     * @return \Konani\CartBundle\Entity\Cart 
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * Set video
     *
     * @param \Konani\VideoBundle\Entity\Video $video
     * @return CartVideo
     */
    public function setVideo(\Konani\VideoBundle\Entity\Video $video = null)
    {
        $this->video = $video;

        return $this;
    }

    /**
     * Get video
     *
     * @return \Konani\VideoBundle\Entity\Video
     */
    public function getVideo()
    {
        return $this->video;
    }
}
