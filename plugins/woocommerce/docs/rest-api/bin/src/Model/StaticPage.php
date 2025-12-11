<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Model;

/**
 * Represents a static page parsed from a markdown file.
 */
final class StaticPage
{
    /**
     * @param string $filePath The path to the source markdown file
     * @param string $slug The URL slug (filename without extension)
     * @param string $title The page title
     * @param int $order The display order in navigation
     * @param string $content The markdown content (without header)
     * @param bool $isIndex Whether this is the index/home page
     */
    public function __construct(
        public readonly string $filePath,
        public readonly string $slug,
        public readonly string $title,
        public readonly int $order,
        public readonly string $content,
        public readonly bool $isIndex = false,
    ) {
    }

    /**
     * Get the output filename.
     */
    public function getOutputFilename(): string
    {
        return $this->slug . '.html';
    }
}
