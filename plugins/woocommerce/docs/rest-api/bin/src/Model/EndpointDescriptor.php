<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Model;

/**
 * Represents a parsed endpoint descriptor file.
 */
final class EndpointDescriptor
{
    /**
     * @param string $filePath The path to the descriptor file
     * @param string $category The category path (e.g., v3/Products)
     * @param string $route The exact route pattern
     * @param string $name The human-readable name
     * @param array<string> $verbs The HTTP verb(s)
     * @param string $description The markdown description
     * @param bool $ignore Whether to ignore this descriptor
     * @param bool $public Whether the endpoint is public (no auth required)
     */
    public function __construct(
        public readonly string $filePath,
        public readonly string $category,
        public readonly string $route,
        public readonly string $name,
        public readonly array $verbs,
        public readonly string $description,
        public readonly bool $ignore = false,
        public readonly bool $public = false,
    ) {
    }

    /**
     * Get unique identifiers for all verb combinations of this endpoint.
     *
     * @return array<string>
     */
    public function getIdentifiers(): array
    {
        return array_map(
            fn(string $verb) => $verb . ' ' . $this->route,
            $this->verbs
        );
    }

    /**
     * Check if this descriptor matches an endpoint identifier.
     */
    public function matchesIdentifier(string $identifier): bool
    {
        return in_array($identifier, $this->getIdentifiers(), true);
    }

    /**
     * Get the category as an array of parts.
     *
     * @return array<string>
     */
    public function getCategoryParts(): array
    {
        return array_filter(explode('/', $this->category));
    }

    /**
     * Get the category depth.
     */
    public function getCategoryDepth(): int
    {
        return count($this->getCategoryParts());
    }

    /**
     * Check if authentication is required.
     * All endpoints require auth by default, unless marked as public.
     */
    public function requiresAuth(): bool
    {
        return !$this->public;
    }

    /**
     * Check if this descriptor has a meaningful description.
     * Returns false if description is empty or contains only whitespace.
     */
    public function hasDescription(): bool
    {
        return trim($this->description) !== '';
    }
}
