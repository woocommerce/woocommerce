<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier;

use Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\Css\CssDocument;
use Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor;
use Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\Utilities\CssConcatenator;
use Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\Utilities\DeclarationBlockParser;
use Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\Utilities\Preg;
use Automattic\WooCommerce\EmailEditorVendor\Symfony\Component\CssSelector\CssSelectorConverter;
use Automattic\WooCommerce\EmailEditorVendor\Symfony\Component\CssSelector\Exception\ParseException;

/**
 * This class provides functions for converting CSS styles into inline style attributes in your HTML code.
 */
final class CssInliner extends AbstractHtmlProcessor
{
    /**
     * @var int
     */
    private const CACHE_KEY_SELECTOR = 0;

    /**
     * @var int
     */
    private const CACHE_KEY_COMBINED_STYLES = 1;

    /**
     * Regular expression component matching a static pseudo class in a selector, without the preceding ":",
     * for which the applicable elements can be determined (by converting the selector to an XPath expression).
     * (Contains alternation without a group and is intended to be placed within a capturing, non-capturing or lookahead
     * group, as appropriate for the usage context.)
     *
     * @var string
     */
    private const PSEUDO_CLASS_MATCHER
        = 'empty|(?:first|last|nth(?:-last)?+|only)-(?:child|of-type)|not\\([[:ascii:]]*\\)|root';

    /**
     * This regular expression componenet matches an `...of-type` pseudo class name, without the preceding ":".  These
     * pseudo-classes can currently online be inlined if they have an associated type in the selector expression.
     *
     * @var string
     */
    private const OF_TYPE_PSEUDO_CLASS_MATCHER = '(?:first|last|nth(?:-last)?+|only)-of-type';

    /**
     * regular expression component to match a selector combinator
     *
     * @var string
     */
    private const COMBINATOR_MATCHER = '(?:\\s++|\\s*+[>+~]\\s*+)(?=[[:alpha:]_\\-.#*:\\[])';

    /**
     * options array key for `querySelectorAll`
     *
     * @var string
     */
    private const QSA_ALWAYS_THROW_PARSE_EXCEPTION = 'alwaysThrowParseException';

    /**
     * @var array<string, bool>
     */
    private $excludedSelectors = [];

    /**
     * @var array<non-empty-string, bool>
     */
    private $excludedCssSelectors = [];

    /**
     * @var array<string, bool>
     */
    private $allowedMediaTypes = ['all' => true, 'screen' => true, 'print' => true];

    /**
     * @var array{
     *         0: array<string, int>,
     *         1: array<string, string>
     *      }
     */
    private $caches = [
        self::CACHE_KEY_SELECTOR => [],
        self::CACHE_KEY_COMBINED_STYLES => [],
    ];

    /**
     * @var ?CssSelectorConverter
     */
    private $cssSelectorConverter = null;

    /**
     * the visited nodes with the XPath paths as array keys
     *
     * @var array<string, \DOMElement>
     */
    private $visitedNodes = [];

    /**
     * the styles to apply to the nodes with the XPath paths as array keys for the outer array
     * and the attribute names/values as key/value pairs for the inner array
     *
     * @var array<string, array<string, string>>
     */
    private $styleAttributesForNodes = [];

    /**
     * Determines whether the "style" attributes of tags in the the HTML passed to this class should be preserved.
     * If set to false, the value of the style attributes will be discarded.
     *
     * @var bool
     */
    private $isInlineStyleAttributesParsingEnabled = true;

    /**
     * Determines whether the `<style>` blocks in the HTML passed to this class should be parsed.
     *
     * If set to true, the `<style>` blocks will be removed from the HTML and their contents will be applied to the HTML
     * via inline styles.
     *
     * If set to false, the `<style>` blocks will be left as they are in the HTML.
     *
     * @var bool
     */
    private $isStyleBlocksParsingEnabled = true;

    /**
     * For calculating selector precedence order.
     * Keys are a regular expression part to match before a CSS name.
     * Values are a multiplier factor per match to weight specificity.
     *
     * @var array<string, int>
     */
    private $selectorPrecedenceMatchers = [
        // IDs: worth 10000
        '\\#' => 10000,
        // classes, attributes, pseudo-classes (not pseudo-elements) except `:not`: worth 100
        '(?:\\.|\\[|(?<!:):(?!not\\())' => 100,
        // elements (not attribute values or `:not`), pseudo-elements: worth 1
        '(?:(?<![="\':\\w\\-])|::)' => 1,
    ];

    /**
     * array of data describing CSS rules which apply to the document but cannot be inlined, in the format returned by
     * {@see collateCssRules}
     *
     * @var array<array-key, array{
     *          media: string,
     *          selector: string,
     *          hasUnmatchablePseudo: bool,
     *          declarationsBlock: string,
     *          line: int
     *      }>|null
     */
    private $matchingUninlinableCssRules = null;

    /**
     * Emogrifier will throw Exceptions when it encounters an error instead of silently ignoring them.
     *
     * @var bool
     */
    private $debug = false;

    /**
     * Inlines the given CSS into the existing HTML.
     *
     * @param string $css the CSS to inline, must be UTF-8-encoded
     *
     * @return $this
     *
     * @throws ParseException in debug mode, if an invalid selector is encountered
     * @throws \RuntimeException
     *         in debug mode, if an internal PCRE error occurs
     *         or `CssSelectorConverter::toXPath` returns an invalid XPath expression
     * @throws \UnexpectedValueException
     *         if a selector query result includes a node which is not a `DOMElement`
     */
    public function inlineCss(string $css = ''): self
    {
        $this->clearAllCaches();
        $this->purgeVisitedNodes();

        $this->normalizeStyleAttributesOfAllNodes();

        $combinedCss = $css;
        // grab any existing style blocks from the HTML and append them to the existing CSS
        // (these blocks should be appended so as to have precedence over conflicting styles in the existing CSS)
        if ($this->isStyleBlocksParsingEnabled) {
            $combinedCss .= $this->getCssFromAllStyleNodes();
        }
        $parsedCss = new CssDocument($combinedCss, $this->debug);

        $excludedNodes = $this->getNodesToExclude();
        $cssRules = $this->collateCssRules($parsedCss);
        foreach ($cssRules['inlinable'] as $cssRule) {
            foreach ($this->querySelectorAll($cssRule['selector']) as $node) {
                if (\in_array($node, $excludedNodes, true)) {
                    continue;
                }
                $this->copyInlinableCssToStyleAttribute($this->ensureNodeIsElement($node), $cssRule);
            }
        }

        if ($this->isInlineStyleAttributesParsingEnabled) {
            $this->fillStyleAttributesWithMergedStyles();
        }

        $this->removeImportantAnnotationFromAllInlineStyles();

        $this->determineMatchingUninlinableCssRules($cssRules['uninlinable']);
        $this->copyUninlinableCssToStyleNode($parsedCss);

        return $this;
    }

    /**
     * Disables the parsing of inline styles.
     *
     * @return $this
     */
    public function disableInlineStyleAttributesParsing(): self
    {
        $this->isInlineStyleAttributesParsingEnabled = false;

        return $this;
    }

    /**
     * Disables the parsing of `<style>` blocks.
     *
     * @return $this
     */
    public function disableStyleBlocksParsing(): self
    {
        $this->isStyleBlocksParsingEnabled = false;

        return $this;
    }

    /**
     * Marks a media query type to keep.
     *
     * @param string $mediaName the media type name, e.g., "braille"
     *
     * @return $this
     */
    public function addAllowedMediaType(string $mediaName): self
    {
        $this->allowedMediaTypes[$mediaName] = true;

        return $this;
    }

    /**
     * Drops a media query type from the allowed list.
     *
     * @param string $mediaName the tag name, e.g., "braille"
     *
     * @return $this
     */
    public function removeAllowedMediaType(string $mediaName): self
    {
        if (isset($this->allowedMediaTypes[$mediaName])) {
            unset($this->allowedMediaTypes[$mediaName]);
        }

        return $this;
    }

    /**
     * Adds a selector to exclude nodes from emogrification.
     *
     * Any nodes that match the selector will not have their style altered.
     *
     * @param string $selector the selector to exclude, e.g., ".editor"
     *
     * @return $this
     */
    public function addExcludedSelector(string $selector): self
    {
        $this->excludedSelectors[$selector] = true;

        return $this;
    }

    /**
     * No longer excludes the nodes matching this selector from emogrification.
     *
     * @param string $selector the selector to no longer exclude, e.g., ".editor"
     *
     * @return $this
     */
    public function removeExcludedSelector(string $selector): self
    {
        if (isset($this->excludedSelectors[$selector])) {
            unset($this->excludedSelectors[$selector]);
        }

        return $this;
    }

    /**
     * Adds a selector to exclude CSS selector from emogrification.
     *
     * @param non-empty-string $selector the selector to exclude, e.g., `.editor`
     *
     * @return $this
     */
    public function addExcludedCssSelector(string $selector): self
    {
        $this->excludedCssSelectors[$selector] = true;

        return $this;
    }

    /**
     * No longer excludes the CSS selector from emogrification.
     *
     * @param non-empty-string $selector the selector to no longer exclude, e.g., `.editor`
     *
     * @return $this
     */
    public function removeExcludedCssSelector(string $selector): self
    {
        if (isset($this->excludedCssSelectors[$selector])) {
            unset($this->excludedCssSelectors[$selector]);
        }

        return $this;
    }

    /**
     * Sets the debug mode.
     *
     * @param bool $debug set to true to enable debug mode
     *
     * @return $this
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Gets the array of selectors present in the CSS provided to `inlineCss()` for which the declarations could not be
     * applied as inline styles, but which may affect elements in the HTML.  The relevant CSS will have been placed in a
     * `<style>` element.  The selectors may include those used within `@media` rules or those involving dynamic
     * pseudo-classes (such as `:hover`) or pseudo-elements (such as `::after`).
     *
     * @return array<array-key, string>
     *
     * @throws \BadMethodCallException if `inlineCss` has not been called first
     */
    public function getMatchingUninlinableSelectors(): array
    {
        return \array_column($this->getMatchingUninlinableCssRules(), 'selector');
    }

    /**
     * @return array<array-key, array{
     *             media: string,
     *             selector: string,
     *             hasUnmatchablePseudo: bool,
     *             declarationsBlock: string,
     *             line: int
     *         }>
     *
     * @throws \BadMethodCallException if `inlineCss` has not been called first
     */
    private function getMatchingUninlinableCssRules(): array
    {
        if (!\is_array($this->matchingUninlinableCssRules)) {
            throw new \BadMethodCallException('inlineCss must be called first', 1568385221);
        }

        return $this->matchingUninlinableCssRules;
    }

    /**
     * Clears all caches.
     */
    private function clearAllCaches(): void
    {
        $this->caches = [
            self::CACHE_KEY_SELECTOR => [],
            self::CACHE_KEY_COMBINED_STYLES => [],
        ];
    }

    /**
     * Purges the visited nodes.
     */
    private function purgeVisitedNodes(): void
    {
        $this->visitedNodes = [];
        $this->styleAttributesForNodes = [];
    }

    /**
     * Parses the document and normalizes all existing CSS attributes.
     * This changes 'DISPLAY: none' to 'display: none'.
     * We wouldn't have to do this if DOMXPath supported XPath 2.0.
     * Also stores a reference of nodes with existing inline styles so we don't overwrite them.
     */
    private function normalizeStyleAttributesOfAllNodes(): void
    {
        /** @var \DOMElement $node */
        foreach ($this->getAllNodesWithStyleAttribute() as $node) {
            if ($this->isInlineStyleAttributesParsingEnabled) {
                $this->normalizeStyleAttributes($node);
            }
            // Remove style attribute in every case, so we can add them back (if inline style attributes
            // parsing is enabled) to the end of the style list, thus keeping the right priority of CSS rules;
            // else original inline style rules may remain at the beginning of the final inline style definition
            // of a node, which may give not the desired results
            $node->removeAttribute('style');
        }
    }

    /**
     * Returns a list with all DOM nodes that have a style attribute.
     *
     * @return \DOMNodeList
     *
     * @throws \RuntimeException
     */
    private function getAllNodesWithStyleAttribute(): \DOMNodeList
    {
        $query = '//*[@style]';
        $matches = $this->getXPath()->query($query);
        if (!$matches instanceof \DOMNodeList) {
            throw new \RuntimeException('XPatch query failed: ' . $query, 1618577797);
        }

        return $matches;
    }

    /**
     * Normalizes the value of the "style" attribute and saves it.
     *
     * @param \DOMElement $node
     */
    private function normalizeStyleAttributes(\DOMElement $node): void
    {
        $declarationBlockParser = new DeclarationBlockParser();

        $normalizedOriginalStyle = (new Preg())->throwExceptions($this->debug)->replaceCallback(
            '/-{0,2}+[_a-zA-Z][\\w\\-]*+(?=:)/S',
            /** @param array<array-key, string> $propertyNameMatches */
            static function (array $propertyNameMatches) use ($declarationBlockParser): string {
                return $declarationBlockParser->normalizePropertyName($propertyNameMatches[0]);
            },
            $node->getAttribute('style')
        );

        // In order to not overwrite existing style attributes in the HTML, we have to save the original HTML styles.
        $nodePath = $node->getNodePath();
        if (\is_string($nodePath) && !isset($this->styleAttributesForNodes[$nodePath])) {
            $this->styleAttributesForNodes[$nodePath] = $declarationBlockParser->parse($normalizedOriginalStyle);
            $this->visitedNodes[$nodePath] = $node;
        }

        $node->setAttribute('style', $normalizedOriginalStyle);
    }

    /**
     * Returns CSS content.
     *
     * @return string
     */
    private function getCssFromAllStyleNodes(): string
    {
        $styleNodes = $this->getXPath()->query('//style');
        if ($styleNodes === false) {
            return '';
        }

        $css = '';
        foreach ($styleNodes as $styleNode) {
            if (\is_string($styleNode->nodeValue)) {
                $css .= "\n\n" . $styleNode->nodeValue;
            }
            $parentNode = $styleNode->parentNode;
            if ($parentNode instanceof \DOMNode) {
                $parentNode->removeChild($styleNode);
            }
        }

        return $css;
    }

    /**
     * Find the nodes that are not to be emogrified.
     *
     * @return list<\DOMElement>
     *
     * @throws ParseException in debug mode, if an invalid selector is encountered
     * @throws \RuntimeException in debug mode, if `CssSelectorConverter::toXPath` returns an invalid XPath expression
     * @throws \UnexpectedValueException if the selector query result includes a node which is not a `DOMElement`
     */
    private function getNodesToExclude(): array
    {
        $excludedNodes = [];
        foreach (\array_keys($this->excludedSelectors) as $selectorToExclude) {
            foreach ($this->querySelectorAll($selectorToExclude) as $node) {
                $excludedNodes[] = $this->ensureNodeIsElement($node);
            }
        }

        return $excludedNodes;
    }

    /**
     * @param array{}|array{alwaysThrowParseException: bool} $options
     *        This is an array of option values to control behaviour:
     *        - `QSA_ALWAYS_THROW_PARSE_EXCEPTION` - `bool` - throw any `ParseException` regardless of debug setting.
     *
     * @return \DOMNodeList<\DOMNode> the HTML elements that match the provided CSS `$selectors`
     *
     * @throws ParseException
     *         in debug mode (or with `QSA_ALWAYS_THROW_PARSE_EXCEPTION` option), if an invalid selector is encountered
     * @throws \RuntimeException in debug mode, if `CssSelectorConverter::toXPath` returns an invalid XPath expression
     */
    private function querySelectorAll(string $selectors, array $options = []): \DOMNodeList
    {
        try {
            $result = $this->getXPath()->query($this->getCssSelectorConverter()->toXPath($selectors));

            if ($result === false) {
                throw new \RuntimeException('query failed with selector \'' . $selectors . '\'', 1726533051);
            }

            return $result;
        } catch (ParseException $exception) {
            $alwaysThrowParseException = $options[self::QSA_ALWAYS_THROW_PARSE_EXCEPTION] ?? false;
            if ($this->debug || $alwaysThrowParseException) {
                throw $exception;
            }
            return new \DOMNodeList();
        } catch (\RuntimeException $exception) {
            if (
                $this->debug
            ) {
                throw $exception;
            }
            // `RuntimeException` indicates a bug in CssSelector so pass the message to the error handler.
            \trigger_error($exception->getMessage());
            return new \DOMNodeList();
        }
    }

    /**
     * @throws \UnexpectedValueException if `$node` is not a `DOMElement`
     */
    private function ensureNodeIsElement(\DOMNode $node): \DOMElement
    {
        if (!$node instanceof \DOMElement) {
            $path = $node->getNodePath() ?? '$node';
            throw new \UnexpectedValueException($path . ' is not a DOMElement.', 1617975914);
        }

        return $node;
    }

    /**
     * @return CssSelectorConverter
     */
    private function getCssSelectorConverter(): CssSelectorConverter
    {
        if (!$this->cssSelectorConverter instanceof CssSelectorConverter) {
            $this->cssSelectorConverter = new CssSelectorConverter();
        }

        return $this->cssSelectorConverter;
    }

    /**
     * Collates the individual rules from a `CssDocument` object.
     *
     * @param CssDocument $parsedCss
     *
     * @return array<string, array<array-key, array{
     *           media: string,
     *           selector: string,
     *           hasUnmatchablePseudo: bool,
     *           declarationsBlock: string,
     *           line: int
     *         }>>
     *         This 2-entry array has the key "inlinable" containing rules which can be inlined as `style` attributes
     *         and the key "uninlinable" containing rules which cannot.  Each value is an array of sub-arrays with the
     *         following keys:
     *         - "media" (the media query string, e.g. "@media screen and (max-width: 480px)",
     *           or an empty string if not from a `@media` rule);
     *         - "selector" (the CSS selector, e.g., "*" or "header h1");
     *         - "hasUnmatchablePseudo" (`true` if that selector contains pseudo-elements or dynamic pseudo-classes such
     *           that the declarations cannot be applied inline);
     *         - "declarationsBlock" (the semicolon-separated CSS declarations for that selector,
     *           e.g., `color: red; height: 4px;`);
     *         - "line" (the line number, e.g. 42).
     */
    private function collateCssRules(CssDocument $parsedCss): array
    {
        $matches = $parsedCss->getStyleRulesData(\array_keys($this->allowedMediaTypes));

        $preg = (new Preg())->throwExceptions($this->debug);
        $cssRules = [
            'inlinable' => [],
            'uninlinable' => [],
        ];
        foreach ($matches as $key => $cssRule) {
            if (!$cssRule->hasAtLeastOneDeclaration()) {
                continue;
            }

            $mediaQuery = $cssRule->getContainingAtRule();
            $declarationsBlock = $cssRule->getDeclarationAsText();
            $selectors = $cssRule->getSelectors();

            // Maybe exclude CSS selectors
            if (\count($this->excludedCssSelectors) > 0) {
                // Normalize spaces, line breaks & tabs
                $selectorsNormalized = \array_map(static function (string $selector) use ($preg): string {
                    return $preg->replace('@\\s++@u', ' ', $selector);
                }, $selectors);

                $selectors = \array_filter($selectorsNormalized, function (string $selector): bool {
                    return !isset($this->excludedCssSelectors[$selector]);
                });
            }

            foreach ($selectors as $selector) {
                // don't process pseudo-elements and behavioral (dynamic) pseudo-classes;
                // only allow structural pseudo-classes
                $hasPseudoElement = \strpos($selector, '::') !== false;
                $hasUnmatchablePseudo = $hasPseudoElement || $this->hasUnsupportedPseudoClass($selector);

                $parsedCssRule = [
                    'media' => $mediaQuery,
                    'selector' => $selector,
                    'hasUnmatchablePseudo' => $hasUnmatchablePseudo,
                    'declarationsBlock' => $declarationsBlock,
                    // keep track of where it appears in the file, since order is important
                    'line' => $key,
                ];
                $ruleType = (!$cssRule->hasContainingAtRule() && !$hasUnmatchablePseudo) ? 'inlinable' : 'uninlinable';
                $cssRules[$ruleType][] = $parsedCssRule;
            }
        }

        \usort(
            $cssRules['inlinable'],
            /**
             * @param array{selector: string, line: int, ...} $first
             * @param array{selector: string, line: int, ...} $second
             */
            function (array $first, array $second): int {
                return $this->sortBySelectorPrecedence($first, $second);
            }
        );

        return $cssRules;
    }

    /**
     * Tests if a selector contains a pseudo-class which would mean it cannot be converted to an XPath expression for
     * inlining CSS declarations.
     *
     * Any pseudo class that does not match {@see PSEUDO_CLASS_MATCHER} cannot be converted.  Additionally, `...of-type`
     * pseudo-classes cannot be converted if they are not associated with a type selector.
     *
     * @param string $selector
     *
     * @return bool
     */
    private function hasUnsupportedPseudoClass(string $selector): bool
    {
        $preg = (new Preg())->throwExceptions($this->debug);

        if ($preg->match('/:(?!' . self::PSEUDO_CLASS_MATCHER . ')[\\w\\-]/i', $selector) !== 0) {
            return true;
        }

        if ($preg->match('/:(?:' . self::OF_TYPE_PSEUDO_CLASS_MATCHER . ')/i', $selector) === 0) {
            return false;
        }

        foreach ($preg->split('/' . self::COMBINATOR_MATCHER . '/', $selector) as $selectorPart) {
            if ($this->selectorPartHasUnsupportedOfTypePseudoClass($selectorPart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tests if part of a selector contains an `...of-type` pseudo-class such that it cannot be converted to an XPath
     * expression.
     *
     * @param string $selectorPart part of a selector which has been split up at combinators
     *
     * @return bool `true` if the selector part does not have a type but does have an `...of-type` pseudo-class
     */
    private function selectorPartHasUnsupportedOfTypePseudoClass(string $selectorPart): bool
    {
        $preg = (new Preg())->throwExceptions($this->debug);

        if ($preg->match('/^[\\w\\-]/', $selectorPart) !== 0) {
            return false;
        }

        return $preg->match('/:(?:' . self::OF_TYPE_PSEUDO_CLASS_MATCHER . ')/i', $selectorPart) !== 0;
    }

    /**
     * @param array{selector: string, line: int, ...} $first
     * @param array{selector: string, line: int, ...} $second
     *
     * @return int
     */
    private function sortBySelectorPrecedence(array $first, array $second): int
    {
        $precedenceOfFirst = $this->getCssSelectorPrecedence($first['selector']);
        $precedenceOfSecond = $this->getCssSelectorPrecedence($second['selector']);

        // We want these sorted in ascending order so selectors with lesser precedence get processed first and
        // selectors with greater precedence get sorted last.
        $precedenceForEquals = $first['line'] < $second['line'] ? -1 : 1;
        $precedenceForNotEquals = $precedenceOfFirst < $precedenceOfSecond ? -1 : 1;
        return ($precedenceOfFirst === $precedenceOfSecond) ? $precedenceForEquals : $precedenceForNotEquals;
    }

    /**
     * @param string $selector
     *
     * @return int
     */
    private function getCssSelectorPrecedence(string $selector): int
    {
        $selectorKey = $selector;
        if (isset($this->caches[self::CACHE_KEY_SELECTOR][$selectorKey])) {
            return $this->caches[self::CACHE_KEY_SELECTOR][$selectorKey];
        }

        $preg = (new Preg())->throwExceptions($this->debug);
        $precedence = 0;
        foreach ($this->selectorPrecedenceMatchers as $matcher => $value) {
            if (\trim($selector) === '') {
                break;
            }
            $count = 0;
            $selector = $preg->replace('/' . $matcher . '\\w+/', '', $selector, -1, $count);
            $precedence += ($value * $count);
        }
        $this->caches[self::CACHE_KEY_SELECTOR][$selectorKey] = $precedence;

        return $precedence;
    }

    /**
     * Copies $cssRule into the style attribute of $node.
     *
     * Note: This method does not check whether $cssRule matches $node.
     *
     * @param \DOMElement $node
     * @param array{
     *            media: string,
     *            selector: string,
     *            hasUnmatchablePseudo: bool,
     *            declarationsBlock: string,
     *            line: int
     *        } $cssRule
     */
    private function copyInlinableCssToStyleAttribute(\DOMElement $node, array $cssRule): void
    {
        $declarationsBlock = $cssRule['declarationsBlock'];
        $declarationBlockParser = new DeclarationBlockParser();
        $newStyleDeclarations = $declarationBlockParser->parse($declarationsBlock);
        if ($newStyleDeclarations === []) {
            return;
        }

        // if it has a style attribute, get it, process it, and append (overwrite) new stuff
        if ($node->hasAttribute('style')) {
            // break it up into an associative array
            $oldStyleDeclarations = $declarationBlockParser->parse($node->getAttribute('style'));
        } else {
            $oldStyleDeclarations = [];
        }
        $node->setAttribute(
            'style',
            $this->generateStyleStringFromDeclarationsArrays($oldStyleDeclarations, $newStyleDeclarations)
        );
    }

    /**
     * This method merges old or existing name/value array with new name/value array
     * and then generates a string of the combined style suitable for placing inline.
     * This becomes the single point for CSS string generation allowing for consistent
     * CSS output no matter where the CSS originally came from.
     *
     * @param array<string, string> $oldStyles
     * @param array<string, string> $newStyles
     *
     * @return string
     *
     * @throws \UnexpectedValueException if an empty property name is encountered (which should not happen)
     */
    private function generateStyleStringFromDeclarationsArrays(array $oldStyles, array $newStyles): string
    {
        $cacheKey = \serialize([$oldStyles, $newStyles]);
        if (isset($this->caches[self::CACHE_KEY_COMBINED_STYLES][$cacheKey])) {
            return $this->caches[self::CACHE_KEY_COMBINED_STYLES][$cacheKey];
        }

        // Unset the overridden styles to preserve order, important if shorthand and individual properties are mixed
        foreach ($oldStyles as $attributeName => $attributeValue) {
            if (!isset($newStyles[$attributeName])) {
                continue;
            }

            $newAttributeValue = $newStyles[$attributeName];
            if (
                $this->attributeValueIsImportant($attributeValue)
                && !$this->attributeValueIsImportant($newAttributeValue)
            ) {
                unset($newStyles[$attributeName]);
            } else {
                unset($oldStyles[$attributeName]);
            }
        }

        $combinedStyles = \array_merge($oldStyles, $newStyles);

        $declarationBlockParser = new DeclarationBlockParser();
        $style = '';
        foreach ($combinedStyles as $attributeName => $attributeValue) {
            $trimmedAttributeName = \trim($attributeName);
            if ($trimmedAttributeName === '') {
                throw new \UnexpectedValueException('An empty property name was encountered.', 1727046078);
            }
            $propertyName = $declarationBlockParser->normalizePropertyName($trimmedAttributeName);
            $propertyValue = \trim($attributeValue);
            $style .= $propertyName . ': ' . $propertyValue . '; ';
        }
        $trimmedStyle = \rtrim($style);

        $this->caches[self::CACHE_KEY_COMBINED_STYLES][$cacheKey] = $trimmedStyle;

        return $trimmedStyle;
    }

    /**
     * Checks whether $attributeValue is marked as !important.
     *
     * @param string $attributeValue
     *
     * @return bool
     */
    private function attributeValueIsImportant(string $attributeValue): bool
    {
        return (new Preg())->throwExceptions($this->debug)->match('/!\\s*+important$/i', $attributeValue) !== 0;
    }

    /**
     * Merges styles from styles attributes and style nodes and applies them to the attribute nodes
     */
    private function fillStyleAttributesWithMergedStyles(): void
    {
        $declarationBlockParser = new DeclarationBlockParser();
        foreach ($this->styleAttributesForNodes as $nodePath => $styleAttributesForNode) {
            $node = $this->visitedNodes[$nodePath];
            $currentStyleAttributes = $declarationBlockParser->parse($node->getAttribute('style'));
            $node->setAttribute(
                'style',
                $this->generateStyleStringFromDeclarationsArrays(
                    $currentStyleAttributes,
                    $styleAttributesForNode
                )
            );
        }
    }

    /**
     * Searches for all nodes with a style attribute and removes the "!important" annotations out of
     * the inline style declarations, eventually by rearranging declarations.
     *
     * @throws \RuntimeException
     */
    private function removeImportantAnnotationFromAllInlineStyles(): void
    {
        /** @var \DOMElement $node */
        foreach ($this->getAllNodesWithStyleAttribute() as $node) {
            $this->removeImportantAnnotationFromNodeInlineStyle($node);
        }
    }

    /**
     * Removes the "!important" annotations out of the inline style declarations,
     * eventually by rearranging declarations.
     * Rearranging needed when !important shorthand properties are followed by some of their
     * not !important expanded-version properties.
     * For example "font: 12px serif !important; font-size: 13px;" must be reordered
     * to "font-size: 13px; font: 12px serif;" in order to remain correct.
     *
     * @param \DOMElement $node
     *
     * @throws \RuntimeException
     */
    private function removeImportantAnnotationFromNodeInlineStyle(\DOMElement $node): void
    {
        $style = $node->getAttribute('style');
        $inlineStyleDeclarations = (new DeclarationBlockParser())->parse((bool) $style ? $style : '');
        /** @var array<string, string> $regularStyleDeclarations */
        $regularStyleDeclarations = [];
        /** @var array<string, string> $importantStyleDeclarations */
        $importantStyleDeclarations = [];
        foreach ($inlineStyleDeclarations as $property => $value) {
            if ($this->attributeValueIsImportant($value)) {
                $importantStyleDeclarations[$property]
                    = (new Preg())->throwExceptions($this->debug)->replace('/\\s*+!\\s*+important$/i', '', $value);
            } else {
                $regularStyleDeclarations[$property] = $value;
            }
        }
        $inlineStyleDeclarationsInNewOrder = \array_merge($regularStyleDeclarations, $importantStyleDeclarations);
        $node->setAttribute(
            'style',
            $this->generateStyleStringFromSingleDeclarationsArray($inlineStyleDeclarationsInNewOrder)
        );
    }

    /**
     * Generates a CSS style string suitable to be used inline from the $styleDeclarations property => value array.
     *
     * @param array<string, string> $styleDeclarations
     *
     * @return string
     */
    private function generateStyleStringFromSingleDeclarationsArray(array $styleDeclarations): string
    {
        return $this->generateStyleStringFromDeclarationsArrays([], $styleDeclarations);
    }

    /**
     * Determines which of `$cssRules` actually apply to `$this->domDocument`, and sets them in
     * `$this->matchingUninlinableCssRules`.
     *
     * @param array<array-key, array{
     *            media: string,
     *            selector: string,
     *            hasUnmatchablePseudo: bool,
     *            declarationsBlock: string,
     *            line: int
     *        }> $cssRules
     *        the "uninlinable" array of CSS rules returned by `collateCssRules`
     */
    private function determineMatchingUninlinableCssRules(array $cssRules): void
    {
        $this->matchingUninlinableCssRules = \array_filter(
            $cssRules,
            function (array $cssRule): bool {
                return $this->existsMatchForSelectorInCssRule($cssRule);
            }
        );
    }

    /**
     * Checks whether there is at least one matching element for the CSS selector contained in the `selector` element
     * of the provided CSS rule.
     *
     * Any dynamic pseudo-classes will be assumed to apply. If the selector matches a pseudo-element,
     * it will test for a match with its originating element.
     *
     * @param array{
     *            media: string,
     *            selector: string,
     *            hasUnmatchablePseudo: bool,
     *            declarationsBlock: string,
     *            line: int
     *        } $cssRule
     *
     * @return bool
     *
     * @throws ParseException
     */
    private function existsMatchForSelectorInCssRule(array $cssRule): bool
    {
        $selector = $cssRule['selector'];
        if ($cssRule['hasUnmatchablePseudo']) {
            $selector = $this->removeUnmatchablePseudoComponents($selector);
        }
        return $this->existsMatchForCssSelector($selector);
    }

    /**
     * Checks whether there is at least one matching element for $cssSelector.
     * When not in debug mode, it returns true also for invalid selectors (because they may be valid,
     * just not implemented/recognized yet by Emogrifier).
     *
     * @param string $cssSelector
     *
     * @return bool
     *
     * @throws ParseException in debug mode, if an invalid selector is encountered
     * @throws \RuntimeException in debug mode, if `CssSelectorConverter::toXPath` returns an invalid XPath expression
     */
    private function existsMatchForCssSelector(string $cssSelector): bool
    {
        try {
            $nodesMatchingSelector
                = $this->querySelectorAll($cssSelector, [self::QSA_ALWAYS_THROW_PARSE_EXCEPTION => true]);
        } catch (ParseException $e) {
            if ($this->debug) {
                throw $e;
            }
            return true;
        }

        return $nodesMatchingSelector->length !== 0;
    }

    /**
     * Removes pseudo-elements and dynamic pseudo-classes from a CSS selector, replacing them with "*" if necessary.
     * If such a pseudo-component is within the argument of `:not`, the entire `:not` component is removed or replaced.
     *
     * @param string $selector
     *
     * @return string
     *         selector which will match the relevant DOM elements if the pseudo-classes are assumed to apply, or in the
     *         case of pseudo-elements will match their originating element
     */
    private function removeUnmatchablePseudoComponents(string $selector): string
    {
        $preg = (new Preg())->throwExceptions($this->debug);

        // The regex allows nested brackets via `(?2)`.
        // A space is temporarily prepended because the callback can't determine if the match was at the very start.
        $selectorWithoutNots = \ltrim((new Preg())->throwExceptions($this->debug)->replaceCallback(
            '/([\\s>+~]?+):not(\\([^()]*+(?:(?2)[^()]*+)*+\\))/i',
            /** @param array<array-key, string> $matches */
            function (array $matches): string {
                return $this->replaceUnmatchableNotComponent($matches);
            },
            ' ' . $selector
        ));

        $selectorWithoutUnmatchablePseudoComponents = $this->removeSelectorComponents(
            ':(?!' . self::PSEUDO_CLASS_MATCHER . '):?+[\\w\\-]++(?:\\([^\\)]*+\\))?+',
            $selectorWithoutNots
        );

        if (
            $preg->match(
                '/:(?:' . self::OF_TYPE_PSEUDO_CLASS_MATCHER . ')/i',
                $selectorWithoutUnmatchablePseudoComponents
            )
            === 0
        ) {
            return $selectorWithoutUnmatchablePseudoComponents;
        }
        return \implode('', \array_map(
            function (string $selectorPart): string {
                return $this->removeUnsupportedOfTypePseudoClasses($selectorPart);
            },
            $preg->split(
                '/(' . self::COMBINATOR_MATCHER . ')/',
                $selectorWithoutUnmatchablePseudoComponents,
                -1,
                PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
            )
        ));
    }

    /**
     * Helps `removeUnmatchablePseudoComponents()` replace or remove a selector `:not(...)` component if its argument
     * contains pseudo-elements or dynamic pseudo-classes.
     *
     * @param array<array-key, string> $matches array of elements matched by the regular expression
     *
     * @return string
     *         the full match if there were no unmatchable pseudo components within; otherwise, any preceding combinator
     *         followed by "*", or an empty string if there was no preceding combinator
     */
    private function replaceUnmatchableNotComponent(array $matches): string
    {
        [$notComponentWithAnyPrecedingCombinator, $anyPrecedingCombinator, $notArgumentInBrackets] = $matches;

        if ($this->hasUnsupportedPseudoClass($notArgumentInBrackets)) {
            return $anyPrecedingCombinator !== '' ? $anyPrecedingCombinator . '*' : '';
        }
        return $notComponentWithAnyPrecedingCombinator;
    }

    /**
     * Removes components from a CSS selector, replacing them with "*" if necessary.
     *
     * @param string $matcher regular expression part to match the components to remove
     * @param string $selector
     *
     * @return string
     *         selector which will match the relevant DOM elements if the removed components are assumed to apply (or in
     *         the case of pseudo-elements will match their originating element)
     */
    private function removeSelectorComponents(string $matcher, string $selector): string
    {
        return (new Preg())->throwExceptions($this->debug)->replace(
            ['/([\\s>+~]|^)' . $matcher . '/i', '/' . $matcher . '/i'],
            ['$1*', ''],
            $selector
        );
    }

    /**
     * Removes any `...-of-type` pseudo-classes from part of a CSS selector, if it does not have a type, replacing them
     * with "*" if necessary.
     *
     * @param string $selectorPart part of a selector which has been split up at combinators
     *
     * @return string
     *         selector part which will match the relevant DOM elements if the pseudo-classes are assumed to apply
     */
    private function removeUnsupportedOfTypePseudoClasses(string $selectorPart): string
    {
        if (!$this->selectorPartHasUnsupportedOfTypePseudoClass($selectorPart)) {
            return $selectorPart;
        }

        return $this->removeSelectorComponents(
            ':(?:' . self::OF_TYPE_PSEUDO_CLASS_MATCHER . ')(?:\\([^\\)]*+\\))?+',
            $selectorPart
        );
    }

    /**
     * Applies `$this->matchingUninlinableCssRules` to `$this->domDocument` by placing them as CSS in a `<style>`
     * element.
     * If there are no uninlinable CSS rules to copy there, a `<style>` element will be created containing only the
     * applicable at-rules from `$parsedCss`.
     * If there are none of either, an empty `<style>` element will not be created.
     *
     * @param CssDocument $parsedCss
     *        This may contain various at-rules whose content `CssInliner` does not currently attempt to inline or
     *        process in any other way, such as `@import`, `@font-face`, `@keyframes`, etc., and which should precede
     *        the processed but found-to-be-uninlinable CSS placed in the `<style>` element.
     *        Note that `CssInliner` processes `@media` rules so that they can be ordered correctly with respect to
     *        other uninlinable rules; these will not be duplicated from `$parsedCss`.
     */
    private function copyUninlinableCssToStyleNode(CssDocument $parsedCss): void
    {
        $css = $parsedCss->renderNonConditionalAtRules();

        // avoid including unneeded class dependency if there are no rules
        if ($this->getMatchingUninlinableCssRules() !== []) {
            $cssConcatenator = new CssConcatenator();
            foreach ($this->getMatchingUninlinableCssRules() as $cssRule) {
                $cssConcatenator->append([$cssRule['selector']], $cssRule['declarationsBlock'], $cssRule['media']);
            }
            $css .= $cssConcatenator->getCss();
        }

        // avoid adding empty style element
        if ($css !== '') {
            $this->addStyleElementToDocument($css);
        }
    }

    /**
     * Adds a style element with $css to $this->domDocument.
     *
     * This method is protected to allow overriding.
     *
     * @see https://github.com/MyIntervals/emogrifier/issues/103
     *
     * @param string $css
     */
    protected function addStyleElementToDocument(string $css): void
    {
        $domDocument = $this->getDomDocument();
        $styleElement = $domDocument->createElement('style', $css);
        $styleAttribute = $domDocument->createAttribute('type');
        $styleAttribute->value = 'text/css';
        $styleElement->appendChild($styleAttribute);

        $headElement = $this->getHeadElement();
        $headElement->appendChild($styleElement);
    }

    /**
     * Returns the HEAD element.
     *
     * This method assumes that there always is a HEAD element.
     *
     * @return \DOMElement
     *
     * @throws \UnexpectedValueException
     */
    private function getHeadElement(): \DOMElement
    {
        $node = $this->getDomDocument()->getElementsByTagName('head')->item(0);
        if (!$node instanceof \DOMElement) {
            throw new \UnexpectedValueException('There is no HEAD element. This should never happen.', 1617923227);
        }

        return $node;
    }
}
