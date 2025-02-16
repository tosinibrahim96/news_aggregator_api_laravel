<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\Carbon;

/**
 * Data Transfer Object for news articles
 */
readonly class ArticleDTO
{
    public function __construct(
        public string $title,
        public string $description,
        public ?string $content,
        public string $url,
        public ?string $imageUrl,
        public ?string $author,
        public Carbon $publishedAt,
        public string $externalId,
        public string $category,
        public string $sourceIdentifier,
    ) {}

    /**
     * Create a DTO from an array of data
     *
     * @param array<string, mixed> $data
     * @param string $sourceIdentifier
     * @return static
     */
    public static function fromArray(array $data, string $sourceIdentifier): self
    {
        return new self(
            title: $data['title'],
            description: $data['description'] ?? '',
            content: $data['content'] ?? null,
            url: $data['url'],
            imageUrl: $data['image_url'] ?? null,
            author: $data['author'] ?? null,
            publishedAt: Carbon::parse($data['published_at']),
            externalId: $data['external_id'],
            category: $data['category'],
            sourceIdentifier: $sourceIdentifier
        );
    }
}