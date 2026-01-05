<?php

namespace App\Document;

use App\Repository\ArticleRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use DateTimeImmutable;


#[MongoDB\Document(collection: 'articles', repositoryClass: ArticleRepository::class)]
class Article
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $title = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $url = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $description = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $source = null;

    #[MongoDB\Field(type: 'collection')]
    private array $tags = [];

    #[MongoDB\Field(type: 'date_immutable')]
    private ?DateTimeImmutable $publishedAt = null;

    #[MongoDB\Field(type: 'date_immutable')]
    private ?DateTimeImmutable $createdAt = null;

    #[MongoDB\Field(type: 'bool')]
    private bool $isRead = false;

    #[MongoDB\Field(type: 'string')]
    private $user = null;

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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
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

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
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

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

}