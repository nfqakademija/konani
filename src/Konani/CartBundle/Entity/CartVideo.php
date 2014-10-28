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
}
