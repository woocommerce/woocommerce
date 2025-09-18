<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSList;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Comment\CommentContainer;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSElement;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\SourceException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Positionable;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property\AtRule;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property\Charset;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property\CSSNamespace;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property\Import;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property\Selector;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\RuleSet\AtRuleSet;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\RuleSet\DeclarationBlock;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\RuleSet\RuleSet;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value\CSSString;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value\URL;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value\Value;

use function Safe\preg_match;

/**
 * This is the most generic container available. It can contain `DeclarationBlock`s (rule sets with a selector),
 * `RuleSet`s as well as other `CSSList` objects.
 *
 * It can also contain `Import` and `Charset` objects stemming from at-rules.
 *
 * Note that `CSSListItem` extends both `Commentable` and `Renderable`,
 * so those interfaces must also be implemented by concrete subclasses.
 */
abstract class CSSList implements CSSElement, CSSListItem, Positionable
{
    use CommentContainer;
    use Position;

    /**
     * @var array<int<0, max>, CSSListItem>
     *
     * @internal since 8.8.0
     */
    protected $contents = [];

    /**
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(?int $lineNumber = null)
    {
        $this->setPosition($lineNumber);
    }

    /**
     * @throws UnexpectedTokenException
     * @throws SourceException
     *
     * @internal since V8.8.0
     */
    public static function parseList(ParserState $parserState, CSSList $list): void
    {
        $isRoot = $list instanceof Document;
        $usesLenientParsing = $parserState->getSettings()->usesLenientParsing();
        $comments = [];
        while (!$parserState->isEnd()) {
            $comments = \array_merge($comments, $parserState->consumeWhiteSpace());
            $listItem = null;
            if ($usesLenientParsing) {
                try {
                    $listItem = self::parseListItem($parserState, $list);
                } catch (UnexpectedTokenException $e) {
                    $listItem = false;
                }
            } else {
                $listItem = self::parseListItem($parserState, $list);
            }
            if ($listItem === null) {
                // List parsing finished
                return;
            }
            if ($listItem) {
                $listItem->addComments($comments);
                $list->append($listItem);
            }
            $comments = $parserState->consumeWhiteSpace();
        }
        $list->addComments($comments);
        if (!$isRoot && !$usesLenientParsing) {
            throw new SourceException('Unexpected end of document', $parserState->currentLine());
        }
    }

    /**
     * @return CSSListItem|false|null
     *         If `null` is returned, it means the end of the list has been reached.
     *         If `false` is returned, it means an invalid item has been encountered,
     *         but parsing of the next item should proceed.
     *
     * @throws SourceException
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     */
    private static function parseListItem(ParserState $parserState, CSSList $list)
    {
        $isRoot = $list instanceof Document;
        if ($parserState->comes('@')) {
            $atRule = self::parseAtRule($parserState);
            if ($atRule instanceof Charset) {
                if (!$isRoot) {
                    throw new UnexpectedTokenException(
                        '@charset may only occur in root document',
                        '',
                        'custom',
                        $parserState->currentLine()
                    );
                }
                if (\count($list->getContents()) > 0) {
                    throw new UnexpectedTokenException(
                        '@charset must be the first parseable token in a document',
                        '',
                        'custom',
                        $parserState->currentLine()
                    );
                }
                $parserState->setCharset($atRule->getCharset());
            }
            return $atRule;
        } elseif ($parserState->comes('}')) {
            if ($isRoot) {
                if ($parserState->getSettings()->usesLenientParsing()) {
                    return DeclarationBlock::parse($parserState) ?? false;
                } else {
                    throw new SourceException('Unopened {', $parserState->currentLine());
                }
            } else {
                // End of list
                return null;
            }
        } else {
            return DeclarationBlock::parse($parserState, $list) ?? false;
        }
    }

    /**
     * @throws SourceException
     * @throws UnexpectedTokenException
     * @throws UnexpectedEOFException
     */
    private static function parseAtRule(ParserState $parserState): ?CSSListItem
    {
        $parserState->consume('@');
        $identifier = $parserState->parseIdentifier();
        $identifierLineNumber = $parserState->currentLine();
        $parserState->consumeWhiteSpace();
        if ($identifier === 'import') {
            $location = URL::parse($parserState);
            $parserState->consumeWhiteSpace();
            $mediaQuery = null;
            if (!$parserState->comes(';')) {
                $mediaQuery = \trim($parserState->consumeUntil([';', ParserState::EOF]));
                if ($mediaQuery === '') {
                    $mediaQuery = null;
                }
            }
            $parserState->consumeUntil([';', ParserState::EOF], true, true);
            return new Import($location, $mediaQuery, $identifierLineNumber);
        } elseif ($identifier === 'charset') {
            $charsetString = CSSString::parse($parserState);
            $parserState->consumeWhiteSpace();
            $parserState->consumeUntil([';', ParserState::EOF], true, true);
            return new Charset($charsetString, $identifierLineNumber);
        } elseif (self::identifierIs($identifier, 'keyframes')) {
            $result = new KeyFrame($identifierLineNumber);
            $result->setVendorKeyFrame($identifier);
            $result->setAnimationName(\trim($parserState->consumeUntil('{', false, true)));
            CSSList::parseList($parserState, $result);
            if ($parserState->comes('}')) {
                $parserState->consume('}');
            }
            return $result;
        } elseif ($identifier === 'namespace') {
            $prefix = null;
            $url = Value::parsePrimitiveValue($parserState);
            if (!$parserState->comes(';')) {
                $prefix = $url;
                $url = Value::parsePrimitiveValue($parserState);
            }
            $parserState->consumeUntil([';', ParserState::EOF], true, true);
            if ($prefix !== null && !\is_string($prefix)) {
                throw new UnexpectedTokenException('Wrong namespace prefix', $prefix, 'custom', $identifierLineNumber);
            }
            if (!($url instanceof CSSString || $url instanceof URL)) {
                throw new UnexpectedTokenException(
                    'Wrong namespace url of invalid type',
                    $url,
                    'custom',
                    $identifierLineNumber
                );
            }
            return new CSSNamespace($url, $prefix, $identifierLineNumber);
        } else {
            // Unknown other at rule (font-face or such)
            $arguments = \trim($parserState->consumeUntil('{', false, true));
            if (\substr_count($arguments, '(') !== \substr_count($arguments, ')')) {
                if ($parserState->getSettings()->usesLenientParsing()) {
                    return null;
                } else {
                    throw new SourceException('Unmatched brace count in media query', $parserState->currentLine());
                }
            }
            $useRuleSet = true;
            foreach (\explode('/', AtRule::BLOCK_RULES) as $blockRuleName) {
                if (self::identifierIs($identifier, $blockRuleName)) {
                    $useRuleSet = false;
                    break;
                }
            }
            if ($useRuleSet) {
                $atRule = new AtRuleSet($identifier, $arguments, $identifierLineNumber);
                RuleSet::parseRuleSet($parserState, $atRule);
            } else {
                $atRule = new AtRuleBlockList($identifier, $arguments, $identifierLineNumber);
                CSSList::parseList($parserState, $atRule);
                if ($parserState->comes('}')) {
                    $parserState->consume('}');
                }
            }
            return $atRule;
        }
    }

    /**
     * Tests an identifier for a given value. Since identifiers are all keywords, they can be vendor-prefixed.
     * We need to check for these versions too.
     */
    private static function identifierIs(string $identifier, string $match): bool
    {
        if (\strcasecmp($identifier, $match) === 0) {
            return true;
        }

        return preg_match("/^(-\\w+-)?$match$/i", $identifier) === 1;
    }

    /**
     * Prepends an item to the list of contents.
     */
    public function prepend(CSSListItem $item): void
    {
        \array_unshift($this->contents, $item);
    }

    /**
     * Appends an item to the list of contents.
     */
    public function append(CSSListItem $item): void
    {
        $this->contents[] = $item;
    }

    /**
     * Splices the list of contents.
     *
     * @param array<int, CSSListItem> $replacement
     */
    public function splice(int $offset, ?int $length = null, ?array $replacement = null): void
    {
        \array_splice($this->contents, $offset, $length, $replacement);
    }

    /**
     * Inserts an item in the CSS list before its sibling. If the desired sibling cannot be found,
     * the item is appended at the end.
     */
    public function insertBefore(CSSListItem $item, CSSListItem $sibling): void
    {
        if (\in_array($sibling, $this->contents, true)) {
            $this->replace($sibling, [$item, $sibling]);
        } else {
            $this->append($item);
        }
    }

    /**
     * Removes an item from the CSS list.
     *
     * @param CSSListItem $itemToRemove
     *        May be a `RuleSet` (most likely a `DeclarationBlock`), an `Import`,
     *        a `Charset` or another `CSSList` (most likely a `MediaQuery`)
     *
     * @return bool whether the item was removed
     */
    public function remove(CSSListItem $itemToRemove): bool
    {
        $key = \array_search($itemToRemove, $this->contents, true);
        if ($key !== false) {
            unset($this->contents[$key]);
            return true;
        }

        return false;
    }

    /**
     * Replaces an item from the CSS list.
     *
     * @param CSSListItem $oldItem
     *        May be a `RuleSet` (most likely a `DeclarationBlock`), an `Import`, a `Charset`
     *        or another `CSSList` (most likely a `MediaQuery`)
     * @param CSSListItem|array<CSSListItem> $newItem
     */
    public function replace(CSSListItem $oldItem, $newItem): bool
    {
        $key = \array_search($oldItem, $this->contents, true);
        if ($key !== false) {
            if (\is_array($newItem)) {
                \array_splice($this->contents, $key, 1, $newItem);
            } else {
                \array_splice($this->contents, $key, 1, [$newItem]);
            }
            return true;
        }

        return false;
    }

    /**
     * @param array<int, CSSListItem> $contents
     */
    public function setContents(array $contents): void
    {
        $this->contents = [];
        foreach ($contents as $content) {
            $this->append($content);
        }
    }

    /**
     * Removes a declaration block from the CSS list if it matches all given selectors.
     *
     * @param DeclarationBlock|array<Selector>|string $selectors the selectors to match
     * @param bool $removeAll whether to stop at the first declaration block found or remove all blocks
     */
    public function removeDeclarationBlockBySelector($selectors, bool $removeAll = false): void
    {
        if ($selectors instanceof DeclarationBlock) {
            $selectors = $selectors->getSelectors();
        }
        if (!\is_array($selectors)) {
            $selectors = \explode(',', $selectors);
        }
        foreach ($selectors as $key => &$selector) {
            if (!($selector instanceof Selector)) {
                if (!Selector::isValid($selector)) {
                    throw new UnexpectedTokenException(
                        "Selector did not match '" . Selector::SELECTOR_VALIDATION_RX . "'.",
                        $selector,
                        'custom'
                    );
                }
                $selector = new Selector($selector);
            }
        }
        foreach ($this->contents as $key => $item) {
            if (!($item instanceof DeclarationBlock)) {
                continue;
            }
            if ($item->getSelectors() == $selectors) {
                unset($this->contents[$key]);
                if (!$removeAll) {
                    return;
                }
            }
        }
    }

    protected function renderListContents(OutputFormat $outputFormat): string
    {
        $result = '';
        $isFirst = true;
        $nextLevelFormat = $outputFormat;
        if (!$this->isRootList()) {
            $nextLevelFormat = $outputFormat->nextLevel();
        }
        $nextLevelFormatter = $nextLevelFormat->getFormatter();
        $formatter = $outputFormat->getFormatter();
        foreach ($this->contents as $listItem) {
            $renderedCss = $formatter->safely(static function () use ($nextLevelFormat, $listItem): string {
                return $listItem->render($nextLevelFormat);
            });
            if ($renderedCss === null) {
                continue;
            }
            if ($isFirst) {
                $isFirst = false;
                $result .= $nextLevelFormatter->spaceBeforeBlocks();
            } else {
                $result .= $nextLevelFormatter->spaceBetweenBlocks();
            }
            $result .= $renderedCss;
        }

        if (!$isFirst) {
            // Had some output
            $result .= $formatter->spaceAfterBlocks();
        }

        return $result;
    }

    /**
     * Return true if the list can not be further outdented. Only important when rendering.
     */
    abstract public function isRootList(): bool;

    /**
     * Returns the stored items.
     *
     * @return array<int<0, max>, CSSListItem>
     */
    public function getContents(): array
    {
        return $this->contents;
    }
}
