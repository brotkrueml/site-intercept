<?php

namespace App\Entity;

use App\Exception\InvalidStatusException;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RedirectRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Redirect
{
    public const STATUS_CODE_301 = 301; // Moved Permanently
    public const STATUS_CODE_302 = 302; // Found
    public const STATUS_CODE_303 = 303; // See Other
    public const STATUS_CODE_307 = 307; // Temporary Redirect

    public static $allowedStatusCodes = [
        self::STATUS_CODE_301,
        self::STATUS_CODE_302,
        self::STATUS_CODE_303,
        self::STATUS_CODE_307,
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=2000)
     */
    private $source;

    /**
     * @ORM\Column(type="string", length=2000)
     */
    private $target;

    /**
     * @ORM\Column(type="integer")
     */
    private $statusCode = self::STATUS_CODE_303;

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * @throws \Exception
     */
    public function updatedTimestamps(): void
    {
        $this->setUpdatedAt(new \DateTime(date('Y-m-d H:i:s')));
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt(new \DateTime(date('Y-m-d H:i:s')));
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget(string $target): self
    {
        $this->target = $target;
        return $this;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        if (!in_array($statusCode, $this->allowedStatusCodes, true)) {
            throw new InvalidStatusException('The HTTP status code is invalid for a redirect', 1553001673);
        }
        $this->statusCode = $statusCode;
        return $this;
    }
}
