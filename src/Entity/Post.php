<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity(repositoryClass=PostRepository::class)
 * @ORM\Table(name="posts")
 */
class Post
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"singleUnicorn", "singlePost", "allPosts"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Groups({"singleUnicorn", "singlePost", "allPosts"})
     */
    private $creator;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"singleUnicorn", "singlePost", "allPosts"})
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"singleUnicorn", "singlePost", "allPosts"})
     */
    private $comment;


    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Unicorn", inversedBy="posts")
     * @ORM\JoinColumn(name="unicorn_id", referencedColumnName="id", nullable=false )
     * @Groups({"singleUnicorn", "singlePost", "allPosts"})
     * @SerializedName("unicorn")
     */
    private $unicorn_id;

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }

    public function setCreator(string $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getUnicornId(): ?Unicorn
    {
        return $this->unicorn_id;
    }

    public function setUnicorn(Unicorn $unicorn): self
    {
        $this->unicorn_id = $unicorn;

        return $this;
    }

}
