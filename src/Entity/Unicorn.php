<?php

namespace App\Entity;

use App\Repository\UnicornRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=UnicornRepository::class)
 * @ORM\Table(name="unicorns")
 *
 */
class Unicorn
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"allUnicorns", "singleUnicorn", "singlePost", "allPosts"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"allUnicorns", "singleUnicorn", "singlePost", "allPosts"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"allUnicorns", "singleUnicorn"})
     */
    private $color;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"allUnicorns", "singleUnicorn"})
     */
    private $height;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"allUnicorns", "singleUnicorn"})
     */
    protected $price;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":"0"})
     * @Groups({"allUnicorns", "singleUnicorn",  "singlePost", "allPosts"})
     */
    private $sold;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Post", mappedBy="unicorn_id")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     * @Groups({"singleUnicorn"})
     */
    private $posts;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function isSold(): ?bool
    {
        return $this->sold;
    }

    public function setSold(bool $sold): self
    {
        $this->sold = $sold;

        return $this;
    }

    public function getPosts(): ?Collection
    {
        return $this->posts;
    }

    /**
     * @param Collection $posts
     */
    public function setPosts(Collection $posts): self
    {
        $this->posts = $posts;

        return $this;
    }


}
