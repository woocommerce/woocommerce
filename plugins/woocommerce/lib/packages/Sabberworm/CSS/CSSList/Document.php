<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSList;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\SourceException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property\Selector;

/**
 * This class represents the root of a parsed CSS file. It contains all top-level CSS contents: mostly declaration
 * blocks, but also any at-rules encountered (`Import` and `Charset`).
 */
class Document extends CSSBlockList
{
    /**
     * @throws SourceException
     *
     * @internal since V8.8.0
     */
    public static function parse(ParserState $parserState): Document
    {
        $document = new Document($parserState->currentLine());
        CSSList::parseList($parserState, $document);

        return $document;
    }

    /**
     * Returns all `Selector` objects with the requested specificity found recursively in the tree.
     *
     * Note that this does not yield the full `DeclarationBlock` that the selector belongs to
     * (and, currently, there is no way to get to that).
     *
     * @param string|null $specificitySearch
     *        An optional filter by specificity.
     *        May contain a comparison operator and a number or just a number (defaults to "==").
     *
     * @return list<Selector>
     *
     * @example `getSelectorsBySpecificity('>= 100')`
     */
    public function getSelectorsBySpecificity(?string $specificitySearch = null): array
    {
        return $this->getAllSelectors($specificitySearch);
    }

    /**
     * Overrides `render()` to make format argument optional.
     */
    public function render(?OutputFormat $outputFormat = null): string
    {
        if ($outputFormat === null) {
            $outputFormat = new OutputFormat();
        }
        return $outputFormat->getFormatter()->comments($this) . $this->renderListContents($outputFormat);
    }

    public function isRootList(): bool
    {
        return true;
    }
}
