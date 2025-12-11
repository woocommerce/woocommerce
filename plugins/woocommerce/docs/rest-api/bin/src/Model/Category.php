<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Model;

/**
 * Represents a category in the documentation tree.
 */
final class Category
{
    /** @var array<string, Category> */
    private array $children = [];

    /** @var array<EndpointDescriptor> */
    private array $endpoints = [];

    /**
     * @param string $name The category name
     * @param string $path The full category path (e.g., v3/Products)
     * @param Category|null $parent The parent category
     */
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly ?Category $parent = null,
    ) {
    }

    /**
     * Add a child category.
     */
    public function addChild(Category $child): void
    {
        $this->children[$child->name] = $child;
    }

    /**
     * Get a child category by name.
     */
    public function getChild(string $name): ?Category
    {
        return $this->children[$name] ?? null;
    }

    /**
     * Check if a child category exists.
     */
    public function hasChild(string $name): bool
    {
        return isset($this->children[$name]);
    }

    /**
     * Get all child categories.
     *
     * @return array<string, Category>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Add an endpoint to this category.
     */
    public function addEndpoint(EndpointDescriptor $endpoint): void
    {
        $this->endpoints[] = $endpoint;
    }

    /**
     * Get all endpoints in this category.
     *
     * @return array<EndpointDescriptor>
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }

    /**
     * Get the depth of this category in the tree.
     */
    public function getDepth(): int
    {
        return count(array_filter(explode('/', $this->path)));
    }

    /**
     * Check if this category has any endpoints or children with endpoints.
     */
    public function hasContent(): bool
    {
        if (count($this->endpoints) > 0) {
            return true;
        }

        foreach ($this->children as $child) {
            if ($child->hasContent()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get total endpoint count including all descendants.
     */
    public function getTotalEndpointCount(): int
    {
        $count = count($this->endpoints);

        foreach ($this->children as $child) {
            $count += $child->getTotalEndpointCount();
        }

        return $count;
    }

    /**
     * Sort children alphabetically.
     */
    public function sortChildren(): void
    {
        ksort($this->children);

        foreach ($this->children as $child) {
            $child->sortChildren();
        }
    }
}
