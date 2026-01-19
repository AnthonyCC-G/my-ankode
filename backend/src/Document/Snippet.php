<?php

namespace App\Document;

use App\Repository\SnippetRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use DateTimeImmutable;

#[MongoDB\Document(collection: 'snippets', repositoryClass: SnippetRepository::class)]
class Snippet
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $title = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $language = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $code = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $description = null;

    #[MongoDB\Field(type: 'collection')]
    private array $tags = [];

    #[MongoDB\Field(type: 'date_immutable')]
    private ?DateTimeImmutable $createdAt = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $userId = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    // Getters et Setters

    public function getId(): ?string
    {
        return $this->id;
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

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUserId(): ?string  
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }
}