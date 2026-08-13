<?php

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\CSSList;

use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\SourceException;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property\Selector;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\RuleSet\DeclarationBlock;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\RuleSet\RuleSet;

/**
 * This class represents the root of a parsed CSS file. It contains all top-level CSS contents: mostly declaration
 * blocks, but also any at-rules encountered (`Import` and `Charset`).
 */
class Document extends CSSBlockList
{
    /**
     * @param int $iLineNo
     */
    public function __construct($iLineNo = 0)
    {
        parent::__construct($iLineNo);
    }

    /**
     * @return Document
     *
     * @throws SourceException
     *
     * @internal since V8.8.0
     */
    public static function parse(ParserState $oParserState)
    {
        $oDocument = new Document($oParserState->currentLine());
        CSSList::parseList($oParserState, $oDocument);
        return $oDocument;
    }

    /**
     * Gets all `DeclarationBlock` objects recursively, no matter how deeply nested the selectors are.
     * Aliased as `getAllSelectors()`.
     *
     * @return array<int, DeclarationBlock>
     */
    public function getAllDeclarationBlocks()
    {
        /** @var array<int, DeclarationBlock> $aResult */
        $aResult = [];
        $this->allDeclarationBlocks($aResult);
        return $aResult;
    }

    /**
     * Gets all `DeclarationBlock` objects recursively.
     *
     * @return array<int, DeclarationBlock>
     *
     * @deprecated will be removed in version 9.0; use `getAllDeclarationBlocks()` instead
     */
    public function getAllSelectors()
    {
        return $this->getAllDeclarationBlocks();
    }

    /**
     * Returns all `RuleSet` objects recursively found in the tree, no matter how deeply nested the rule sets are.
     *
     * @return array<int, RuleSet>
     */
    public function getAllRuleSets()
    {
        /** @var array<int, RuleSet> $aResult */
        $aResult = [];
        $this->allRuleSets($aResult);
        return $aResult;
    }

    /**
     * Returns all `Selector` objects with the requested specificity found recursively in the tree.
     *
     * Note that this does not yield the full `DeclarationBlock` that the selector belongs to
     * (and, currently, there is no way to get to that).
     *
     * @param string|null $sSpecificitySearch
     *        An optional filter by specificity.
     *        May contain a comparison operator and a number or just a number (defaults to "==").
     *
     * @return array<int, Selector>
     * @example `getSelectorsBySpecificity('>= 100')`
     *
     */
    public function getSelectorsBySpecificity($sSpecificitySearch = null)
    {
        /** @var array<int, Selector> $aResult */
        $aResult = [];
        $this->allSelectors($aResult, $sSpecificitySearch);
        return $aResult;
    }

    /**
     * Expands all shorthand properties to their long value.
     *
     * @return void
     *
     * @deprecated since 8.7.0, will be removed without substitution in version 9.0 in #511
     */
    public function expandShorthands()
    {
        foreach ($this->getAllDeclarationBlocks() as $oDeclaration) {
            $oDeclaration->expandShorthands();
        }
    }

    /**
     * Create shorthands properties whenever possible.
     *
     * @return void
     *
     * @deprecated since 8.7.0, will be removed without substitution in version 9.0 in #511
     */
    public function createShorthands()
    {
        foreach ($this->getAllDeclarationBlocks() as $oDeclaration) {
            $oDeclaration->createShorthands();
        }
    }

    /**
     * Overrides `render()` to make format argument optional.
     *
     * @param OutputFormat|null $oOutputFormat
     *
     * @return string
     */
    public function render($oOutputFormat = null)
    {
        if ($oOutputFormat === null) {
            $oOutputFormat = new OutputFormat();
        }
        return $oOutputFormat->comments($this) . $this->renderListContents($oOutputFormat);
    }

    /**
     * @return bool
     */
    public function isRootList()
    {
        return true;
    }
}
