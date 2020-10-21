<?php

namespace App\Entity;

use App\Repository\NewsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=NewsRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class News
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $publishdate;

    /**
     * @Assert\File(maxSize="20480k", mimeTypes={"image/jpeg", "image/png"})
     * @Assert\Image(mimeTypesMessage="invalid_image")
     */
    private $imgFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $imgPath;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     */
    private $body;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublishdate(): ?\DateTimeInterface
    {
        return $this->publishdate;
    }

    public function setPublishdate(\DateTimeInterface $publishdate): self
    {
        $this->publishdate = $publishdate;

        return $this;
    }

    public function getImgFile()
    {
        return $this->imgFile;
    }

    public function setImgFile(UploadedFile $file = null)
    {
        $this->imgFile = $file;

        // если новый файл то обнуляем 
        if ($this->imgPath) {
            $this->imgPath     = null;
        }

        return $this;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preUploadImg()
    {

        if (null !== $this->getImgFile()) {
            // a file was uploaded
            // generate a unique filename
            $count = 0;
            $rootDir = __DIR__.'/../../public/upload/';
            do {
                $random       = \random_bytes(16);
                $randomString = \bin2hex($random);
                ++$count;
            } while (\file_exists($rootDir.'/'.$randomString.'.'.$this->getImgFile()->guessExtension()) && $count < 50);

            $newpath = $randomString.'.'.$this->getImgFile()->guessExtension(); // здесь отвратительный вариант зависимости логики от порядка вызова
            $this->getImgFile()->move($rootDir, $newpath);
            $this->setImgFile(null);
            $this->setImgPath($newpath);
        }
    }

    public function getImgPath(): ?string
    {
        return $this->imgPath;
    }

    public function setImgPath(?string $imgPath): self
    {
        $this->imgPath = $imgPath;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }
}
