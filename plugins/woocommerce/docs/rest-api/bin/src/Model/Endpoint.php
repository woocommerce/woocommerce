<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Model;

use WooCommerce\RestApiDocs\Util\RouteFormatter;

/**
 * Represents a single endpoint from the WordPress REST API schema.
 */
final class Endpoint
{
    /**
     * @param string $route The route pattern (e.g., /wc/v3/products/(?P<id>[\d]+))
     * @param string $verb The HTTP verb (GET, POST, PUT, PATCH, DELETE)
     * @param array<string, mixed> $args The endpoint arguments schema
     * @param array<string, mixed>|null $schema The response schema (may be null)
     * @param string|null $description The endpoint description from schema
     */
    public function __construct(
        public readonly string $route,
        public readonly string $verb,
        public readonly array $args = [],
        public readonly ?array $schema = null,
        public readonly ?string $description = null,
    ) {
    }

    /**
     * Get a unique identifier for this endpoint (route + verb).
     */
    public function getIdentifier(): string
    {
        return $this->verb . ' ' . $this->route;
    }

    /**
     * Get the route formatted for display (e.g., /wc/v3/products/{id}).
     */
    public function getDisplayRoute(): string
    {
        return RouteFormatter::formatForDisplay($this->route);
    }

    /**
     * Check if this endpoint requires authentication based on schema.
     */
    public function requiresAuth(): ?bool
    {
        // This information isn't always available in the schema
        // Return null if unknown
        return null;
    }

    /**
     * Get the API version from the route (e.g., "v3" from /wc/v3/products).
     */
    public function getApiVersion(): ?string
    {
        if (preg_match('#^/wc/(v\d+)/#', $this->route, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
