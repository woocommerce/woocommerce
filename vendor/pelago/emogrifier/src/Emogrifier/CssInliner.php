<?php

namespace Pelago\Emogrifier;

use Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor;
use Pelago\Emogrifier\Utilities\CssConcatenator;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\CssSelector\Exception\SyntaxErrorException;

/**
 * This class provides functions for converting CSS styles into inline style attributes in your HTML code.
 *
 * For Emogrifier 3.0.0, this will be the successor to the \Pelago\Emogrifier class (which then will be deprecated).
 *
 * For more information, please see the README.md file.
 *
 * @author Cameron Brooks
 * @author Jaime Prado
 * @author Oliver Klee <github@oliverklee.de>
 * @author Roman Ožana <ozana@omdesign.cz>
 * @author Sander Kruger <s.kruger@invessel.com>
 * @author Zoli Szabó <zoli.szabo+github@gmail.com>
 */
class CssInliner extends AbstractHtmlProcessor
{
    /**
     * @var int
     */
    const CACHE_KEY_CSS = 0;

    /**
     * @var int
     */
    const CACHE_KEY_SELECTOR = 1;

    /**
     * @var int
     */
    const CACHE_KEY_CSS_DECLARATIONS_BLOCK = 2;

    /**
     * @var int
     */
    const CACHE_KEY_COMBINED_STYLES = 3;

    /**
     * Regular expression component matching a static pseudo class in a selector, without the preceding ":",
     * for which the applicable elements can be determined (by converting the selector to an XPath expression).
     * (Contains alternation without a group and is intended to be placed within a capturing, non-capturing or lookahead
     * group, as appropriate for the usage context.)
     *
     * @var string
     */
    const PSEUDO_CLASS_MATCHER
        = 'empty|(?:first|last|nth(?:-last)?+|only)-child|(?:first|last|nth(?:-last)?+)-of-type|not\\([[:ascii:]]*\\)';

    /**
     * @var bool[]
     */
    private $excludedSelectors = [];

    /**
     * @var bool[]
     */
    private $allowedMediaTypes = ['all' => true, 'screen' => true, 'print' => true];

    /**
     * @var mixed[]
     */
    private $caches = [
        self::CACHE_KEY_CSS => [],
        self::CACHE_KEY_SELECTOR => [],
        self::CACHE_KEY_CSS_DECLARATIONS_BLOCK => [],
        self::CACHE_KEY_COMBINED_STYLES => [],
    ];

    /**
     * @var CssSelectorConverter
     */
    private $cssSelectorConverter = null;

    /**
     * the visited nodes with the XPath paths as array keys
     *
     * @var \DOMElement[]
     */
    private $visitedNodes = [];

    /**
     * the styles to apply to the nodes with the XPath paths as array keys for the outer array
     * and the attribute names/values as key/value pairs for the inner array
     *
     * @var string[][]
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
     * Determines whether the <style> blocks in the HTML passed to this class should be parsed.
     *
     * If set to true, the <style> blocks will be removed from the HTML and their contents will be applied to the HTML
     * via inline styles.
     *
     * If set to false, the <style> blocks will be left as they are in the HTML.
     *
     * @var bool
     */
    private $isStyleBlocksParsingEnabled = true;

    /**
     * For calculating selector precedence order.
     * Keys are a regular expression part to match before a CSS name.
     * Values are a multiplier factor per match to weight specificity.
     *
     * @var int[]
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
     * `parseCssRules`
     *
     * @var string[][]
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
     * @return self fluent interface
     *
     * @throws SyntaxErrorException
     */
    public function inlineCss($css = '')
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

        $cssWithoutComments = $this->removeCssComments($combinedCss);
        list($cssWithoutCommentsCharsetOrImport, $cssImportRules)
            = $this->extractImportAndCharsetRules($cssWithoutComments);

        $excludedNodes = $this->getNodesToExclude();
        $cssRules = $this->parseCssRules($cssWithoutCommentsCharsetOrImport);
        $cssSelectorConverter = $this->getCssSelectorConverter();
        foreach ($cssRules['inlinable'] as $cssRule) {
            try {
                $nodesMatchingCssSelectors = $this->xPath->query($cssSelectorConverter->toXPath($cssRule['selector']));
            } catch (SyntaxErrorException $e) {
                if ($this->debug) {
                    throw $e;
                }
                continue;
            }

            /** @var \DOMElement $node */
            foreach ($nodesMatchingCssSelectors as $node) {
                if (\in_array($node, $excludedNodes, true)) {
                    continue;
                }
                $this->copyInlinableCssToStyleAttribute($node, $cssRule);
            }
        }

        if ($this->isInlineStyleAttributesParsingEnabled) {
            $this->fillStyleAttributesWithMergedStyles();
        }

        $this->removeImportantAnnotationFromAllInlineStyles();

        $this->determineMatchingUninlinableCssRules($cssRules['uninlinable']);
        $this->copyUninlinableCssToStyleNode($cssImportRules);

        return $this;
    }

    /**
     * Disables the parsing of inline styles.
     *
     * @return void
     */
    public function disableInlineStyleAttributesParsing()
    {
        $this->isInlineStyleAttributesParsingEnabled = false;
    }

    /**
     * Disables the parsing of <style> blocks.
     *
     * @return void
     */
    public function disableStyleBlocksParsing()
    {
        $this->isStyleBlocksParsingEnabled = false;
    }

    /**
     * Marks a media query type to keep.
     *
     * @param string $mediaName the media type name, e.g., "braille"
     *
     * @return void
     */
    public function addAllowedMediaType($mediaName)
    {
        $this->allowedMediaTypes[$mediaName] = true;
    }

    /**
     * Drops a media query type from the allowed list.
     *
     * @param string $mediaName the tag name, e.g., "braille"
     *
     * @return void
     */
    public function removeAllowedMediaType($mediaName)
    {
        if (isset($this->allowedMediaTypes[$mediaName])) {
            unset($this->allowedMediaTypes[$mediaName]);
        }
    }

    /**
     * Adds a selector to exclude nodes from emogrification.
     *
     * Any nodes that match the selector will not have their style altered.
     *
     * @param string $selector the selector to exclude, e.g., ".editor"
     *
     * @return void
     */
    public function addExcludedSelector($selector)
    {
        $this->excludedSelectors[$selector] = true;
    }

    /**
     * No longer excludes the nodes matching this selector from emogrification.
     *
     * @param string $selector the selector to no longer exclude, e.g., ".editor"
     *
     * @return void
     */
    public function removeExcludedSelector($selector)
    {
        if (isset($this->excludedSelectors[$selector])) {
            unset($this->excludedSelectors[$selector]);
        }
    }

    /**
     * Sets the debug mode.
     *
     * @param bool $debug set to true to enable debug mode
     *
     * @return void
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * Gets the array of selectors present in the CSS provided to `inlineCss()` for which the declarations could not be
     * applied as inline styles, but which may affect elements in the HTML.  The relevant CSS will have been placed in a
     * `<style>` element.  The selectors may include those used within `@media` rules or those involving dynamic
     * pseudo-classes (such as `:hover`) or pseudo-elements (such as `::after`).
     *
     * @return string[]
     *
     * @throws \BadMethodCallException if `inlineCss` has not been called first
     */
    public function getMatchingUninlinableSelectors()
    {
        if ($this->matchingUninlinableCssRules === null) {
            throw new \BadMethodCallException('inlineCss must be called first', 1568385221);
        }

        return \array_column($this->matchingUninlinableCssRules, 'selector');
    }

    /**
     * Clears all caches.
     *
     * @return void
     */
    private function clearAllCaches()
    {
        $this->caches = [
            self::CACHE_KEY_CSS => [],
            self::CACHE_KEY_SELECTOR => [],
            self::CACHE_KEY_CSS_DECLARATIONS_BLOCK => [],
            self::CACHE_KEY_COMBINED_STYLES => [],
        ];
    }

    /**
     * Purges the visited nodes.
     *
     * @return void
     */
    private function purgeVisitedNodes()
    {
        $this->visitedNodes = [];
        $this->styleAttributesForNodes = [];
    }

    /**
     * Parses the document and normalizes all existing CSS attributes.
     * This changes 'DISPLAY: none' to 'display: none'.
     * We wouldn't have to do this if DOMXPath supported XPath 2.0.
     * Also stores a reference of nodes with existing inline styles so we don't overwrite them.
     *
     * @return void
     */
    private function normalizeStyleAttributesOfAllNodes()
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
     */
    private function getAllNodesWithStyleAttribute()
    {
        return $this->xPath->query('//*[@style]');
    }

    /**
     * Normalizes the value of the "style" attribute and saves it.
     *
     * @param \DOMElement $node
     *
     * @return void
     */
    private function normalizeStyleAttributes(\DOMElement $node)
    {
        $normalizedOriginalStyle = \preg_replace_callback(
            '/-?+[_a-zA-Z][\\w\\-]*+(?=:)/S',
            static function (array $m) {
                return \strtolower($m[0]);
            },
            $node->getAttribute('style')
        );

        // in order to not overwrite existing style attributes in the HTML, we
        // have to save the original HTML styles
        $nodePath = $node->getNodePath();
        if (!isset($this->styleAttributesForNodes[$nodePath])) {
            $this->styleAttributesForNodes[$nodePath] = $this->parseCssDeclarationsBlock($normalizedOriginalStyle);
            $this->visitedNodes[$nodePath] = $node;
        }

        $node->setAttribute('style', $normalizedOriginalStyle);
    }

    /**
     * Parses a CSS declaration block into property name/value pairs.
     *
     * Example:
     *
     * The declaration block
     *
     *   "color: #000; font-weight: bold;"
     *
     * will be parsed into the following array:
     *
     *   "color" => "#000"
     *   "font-weight" => "bold"
     *
     * @param string $cssDeclarationsBlock the CSS declarations block without the curly braces, may be empty
     *
     * @return string[]
     *         the CSS declarations with the property names as array keys and the property values as array values
     */
    private function parseCssDeclarationsBlock($cssDeclarationsBlock)
    {
        if (isset($this->caches[self::CACHE_KEY_CSS_DECLARATIONS_BLOCK][$cssDeclarationsBlock])) {
            return $this->caches[self::CACHE_KEY_CSS_DECLARATIONS_BLOCK][$cssDeclarationsBlock];
        }

        $properties = [];
        foreach (\preg_split('/;(?!base64|charset)/', $cssDeclarationsBlock) as $declaration) {
            $matches = [];
            if (!\preg_match('/^([A-Za-z\\-]+)\\s*:\\s*(.+)$/s', \trim($declaration), $matches)) {
                continue;
            }

            $propertyName = \strtolower($matches[1]);
            $propertyValue = $matches[2];
            $properties[$propertyName] = $propertyValue;
        }
        $this->caches[self::CACHE_KEY_CSS_DECLARATIONS_BLOCK][$cssDeclarationsBlock] = $properties;

        return $properties;
    }

    /**
     * Returns CSS content.
     *
     * @return string
     */
    private function getCssFromAllStyleNodes()
    {
        $styleNodes = $this->xPath->query('//style');
        if ($styleNodes === false) {
            return '';
        }

        $css = '';
        /** @var \DOMNode $styleNode */
        foreach ($styleNodes as $styleNode) {
            $css .= "\n\n" . $styleNode->nodeValue;
            $styleNode->parentNode->removeChild($styleNode);
        }

        return $css;
    }

    /**
     * Removes comments from the supplied CSS.
     *
     * @param string $css
     *
     * @return string CSS with the comments removed
     */
    private function removeCssComments($css)
    {
        return \preg_replace('%/\\*[^*]*+(?:\\*(?!/)[^*]*+)*+\\*/%', '', $css);
    }

    /**
     * Extracts `@import` and `@charset` rules from the supplied CSS.  These rules must not be preceded by any other
     * rules, or they will be ignored.  (From the CSS 2.1 specification: "CSS 2.1 user agents must ignore any '@import'
     * rule that occurs inside a block or after any non-ignored statement other than an @charset or an @import rule."
     * Note also that `@charset` is case sensitive whereas `@import` is not.)
     *
     * @param string $css CSS with comments removed
     *
     * @return string[] The first element is the CSS with the valid `@import` and `@charset` rules removed.  The second
     * element contains a concatenation of the valid `@import` rules, each followed by whatever whitespace followed it
     * in the original CSS (so that either unminified or minified formatting is preserved); if there were no `@import`
     * rules, it will be an empty string.  The (valid) `@charset` rules are discarded.
     */
    private function extractImportAndCharsetRules($css)
    {
        $possiblyModifiedCss = $css;
        $importRules = '';

        while (
            \preg_match(
                '/^\\s*+(@((?i)import(?-i)|charset)\\s[^;]++;\\s*+)/',
                $possiblyModifiedCss,
                $matches
            )
        ) {
            list($fullMatch, $atRuleAndFollowingWhitespace, $atRuleName) = $matches;

            if (\strtolower($atRuleName) === 'import') {
                $importRules .= $atRuleAndFollowingWhitespace;
            }

            $possiblyModifiedCss = \substr($possiblyModifiedCss, \strlen($fullMatch));
        }

        return [$possiblyModifiedCss, $importRules];
    }

    /**
     * Find the nodes that are not to be emogrified.
     *
     * @return \DOMElement[]
     *
     * @throws SyntaxErrorException
     */
    private function getNodesToExclude()
    {
        $excludedNodes = [];
        foreach (\array_keys($this->excludedSelectors) as $selectorToExclude) {
            try {
                $matchingNodes = $this->xPath->query($this->getCssSelectorConverter()->toXPath($selectorToExclude));
            } catch (SyntaxErrorException $e) {
                if ($this->debug) {
                    throw $e;
                }
                continue;
            }
            foreach ($matchingNodes as $node) {
                $excludedNodes[] = $node;
            }
        }

        return $excludedNodes;
    }

    /**
     * @return CssSelectorConverter
     */
    private function getCssSelectorConverter()
    {
        if ($this->cssSelectorConverter === null) {
            $this->cssSelectorConverter = new CssSelectorConverter();
        }

        return $this->cssSelectorConverter;
    }

    /**
     * Extracts and parses the individual rules from a CSS string.
     *
     * @param string $css a string of raw CSS code with comments removed
     *
     * @return string[][][] A 2-entry array with the key "inlinable" containing rules which can be inlined as `style`
     *         attributes and the key "uninlinable" containing rules which cannot.  Each value is an array of string
     *         sub-arrays with the keys
     *         "media" (the media query string, e.g. "@media screen and (max-width: 480px)",
     *         or an empty string if not from a `@media` rule),
     *         "selector" (the CSS selector, e.g., "*" or "header h1"),
     *         "hasUnmatchablePseudo" (true if that selector contains pseudo-elements or dynamic pseudo-classes
     *         such that the declarations cannot be applied inline),
     *         "declarationsBlock" (the semicolon-separated CSS declarations for that selector,
     *         e.g., "color: red; height: 4px;"),
     *         and "line" (the line number e.g. 42)
     */
    private function parseCssRules($css)
    {
        $cssKey = \md5($css);
        if (isset($this->caches[self::CACHE_KEY_CSS][$cssKey])) {
            return $this->caches[self::CACHE_KEY_CSS][$cssKey];
        }

        $matches = $this->getCssRuleMatches($css);

        $cssRules = [
            'inlinable' => [],
            'uninlinable' => [],
        ];
        /** @var string[][] $matches */
        /** @var string[] $cssRule */
        foreach ($matches as $key => $cssRule) {
            $cssDeclaration = \trim($cssRule['declarations']);
            if ($cssDeclaration === '') {
                continue;
            }

            foreach (\explode(',', $cssRule['selectors']) as $selector) {
                // don't process pseudo-elements and behavioral (dynamic) pseudo-classes;
                // only allow structural pseudo-classes
                $hasPseudoElement = \strpos($selector, '::') !== false;
                $hasUnsupportedPseudoClass = (bool)\preg_match(
                    '/:(?!' . self::PSEUDO_CLASS_MATCHER . ')[\\w\\-]/i',
                    $selector
                );
                $hasUnmatchablePseudo = $hasPseudoElement || $hasUnsupportedPseudoClass;

                $parsedCssRule = [
                    'media' => $cssRule['media'],
                    'selector' => \trim($selector),
                    'hasUnmatchablePseudo' => $hasUnmatchablePseudo,
                    'declarationsBlock' => $cssDeclaration,
                    // keep track of where it appears in the file, since order is important
                    'line' => $key,
                ];
                $ruleType = ($cssRule['media'] === '' && !$hasUnmatchablePseudo) ? 'inlinable' : 'uninlinable';
                $cssRules[$ruleType][] = $parsedCssRule;
            }
        }

        \usort($cssRules['inlinable'], [$this, 'sortBySelectorPrecedence']);

        $this->caches[self::CACHE_KEY_CSS][$cssKey] = $cssRules;

        return $cssRules;
    }

    /**
     * @param string[] $a
     * @param string[] $b
     *
     * @return int
     */
    private function sortBySelectorPrecedence(array $a, array $b)
    {
        $precedenceA = $this->getCssSelectorPrecedence($a['selector']);
        $precedenceB = $this->getCssSelectorPrecedence($b['selector']);

        // We want these sorted in ascending order so selectors with lesser precedence get processed first and
        // selectors with greater precedence get sorted last.
        $precedenceForEquals = ($a['line'] < $b['line'] ? -1 : 1);
        $precedenceForNotEquals = ($precedenceA < $precedenceB ? -1 : 1);
        return ($precedenceA === $precedenceB) ? $precedenceForEquals : $precedenceForNotEquals;
    }

    /**
     * @param string $selector
     *
     * @return int
     */
    private function getCssSelectorPrecedence($selector)
    {
        $selectorKey = \md5($selector);
        if (isset($this->caches[self::CACHE_KEY_SELECTOR][$selectorKey])) {
            return $this->caches[self::CACHE_KEY_SELECTOR][$selectorKey];
        }

        $precedence = 0;
        foreach ($this->selectorPrecedenceMatchers as $matcher => $value) {
            if (\trim($selector) === '') {
                break;
            }
            $number = 0;
            $selector = \preg_replace('/' . $matcher . '\\w+/', '', $selector, -1, $number);
            $precedence += ($value * $number);
        }
        $this->caches[self::CACHE_KEY_SELECTOR][$selectorKey] = $precedence;

        return $precedence;
    }

    /**
     * Parses a string of CSS into the media query, selectors and declarations for each ruleset in order.
     *
     * @param string $css CSS with comments removed
     *
     * @return string[][] Array of string sub-arrays with the keys
     *         "media" (the media query string, e.g. "@media screen and (max-width: 480px)",
     *         or an empty string if not from an `@media` rule),
     *         "selectors" (the CSS selector(s), e.g., "*" or "h1, h2"),
     *         "declarations" (the semicolon-separated CSS declarations for that/those selector(s),
     *         e.g., "color: red; height: 4px;"),
     */
    private function getCssRuleMatches($css)
    {
        $splitCss = $this->splitCssAndMediaQuery($css);

        $ruleMatches = [];
        foreach ($splitCss as $cssPart) {
            // process each part for selectors and definitions
            \preg_match_all('/(?:^|[\\s^{}]*)([^{]+){([^}]*)}/mi', $cssPart['css'], $matches, PREG_SET_ORDER);

            /** @var string[][] $matches */
            foreach ($matches as $cssRule) {
                $ruleMatches[] = [
                    'media' => $cssPart['media'],
                    'selectors' => $cssRule[1],
                    'declarations' => $cssRule[2],
                ];
            }
        }

        return $ruleMatches;
    }

    /**
     * Splits input CSS code into an array of parts for different media queries, in order.
     * Each part is an array where:
     *
     * - key "css" will contain clean CSS code (for @media rules this will be the group rule body within "{...}")
     * - key "media" will contain "@media " followed by the media query list, for all allowed media queries,
     *   or an empty string for CSS not within a media query
     *
     * Example:
     *
     * The CSS code
     *
     *   "@import "file.css"; h1 { color:red; } @media { h1 {}} @media tv { h1 {}}"
     *
     * will be parsed into the following array:
     *
     *   0 => [
     *     "css" => "h1 { color:red; }",
     *     "media" => ""
     *   ],
     *   1 => [
     *     "css" => " h1 {}",
     *     "media" => "@media "
     *   ]
     *
     * @param string $css
     *
     * @return string[][]
     */
    private function splitCssAndMediaQuery($css)
    {
        $mediaTypesExpression = '';
        if (!empty($this->allowedMediaTypes)) {
            $mediaTypesExpression = '|' . \implode('|', \array_keys($this->allowedMediaTypes));
        }

        $mediaRuleBodyMatcher = '[^{]*+{(?:[^{}]*+{.*})?\\s*+}\\s*+';

        $cssSplitForAllowedMediaTypes = \preg_split(
            '#(@media\\s++(?:only\\s++)?+(?:(?=[{(])' . $mediaTypesExpression . ')' . $mediaRuleBodyMatcher
            . ')#misU',
            $css,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        // filter the CSS outside/between allowed @media rules
        $cssCleaningMatchers = [
            'import/charset directives' => '/\\s*+@(?:import|charset)\\s[^;]++;/i',
            'remaining media enclosures' => '/\\s*+@media\\s' . $mediaRuleBodyMatcher . '/isU',
        ];

        $splitCss = [];
        foreach ($cssSplitForAllowedMediaTypes as $index => $cssPart) {
            $isMediaRule = $index % 2 !== 0;
            if ($isMediaRule) {
                \preg_match('/^([^{]*+){(.*)}[^}]*+$/s', $cssPart, $matches);
                $splitCss[] = [
                    'css' => $matches[2],
                    'media' => $matches[1],
                ];
            } else {
                $cleanedCss = \trim(\preg_replace($cssCleaningMatchers, '', $cssPart));
                if ($cleanedCss !== '') {
                    $splitCss[] = [
                        'css' => $cleanedCss,
                        'media' => '',
                    ];
                }
            }
        }
        return $splitCss;
    }

    /**
     * Copies $cssRule into the style attribute of $node.
     *
     * Note: This method does not check whether $cssRule matches $node.
     *
     * @param \DOMElement $node
     * @param string[][] $cssRule
     *
     * @return void
     */
    private function copyInlinableCssToStyleAttribute(\DOMElement $node, array $cssRule)
    {
        $newStyleDeclarations = $this->parseCssDeclarationsBlock($cssRule['declarationsBlock']);
        if ($newStyleDeclarations === []) {
            return;
        }

        // if it has a style attribute, get it, process it, and append (overwrite) new stuff
        if ($node->hasAttribute('style')) {
            // break it up into an associative array
            $oldStyleDeclarations = $this->parseCssDeclarationsBlock($node->getAttribute('style'));
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
     * @param string[] $oldStyles
     * @param string[] $newStyles
     *
     * @return string
     */
    private function generateStyleStringFromDeclarationsArrays(array $oldStyles, array $newStyles)
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

        $style = '';
        foreach ($combinedStyles as $attributeName => $attributeValue) {
            $style .= \strtolower(\trim($attributeName)) . ': ' . \trim($attributeValue) . '; ';
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
    private function attributeValueIsImportant($attributeValue)
    {
        return \strtolower(\substr(\trim($attributeValue), -10)) === '!important';
    }

    /**
     * Merges styles from styles attributes and style nodes and applies them to the attribute nodes
     *
     * @return void
     */
    private function fillStyleAttributesWithMergedStyles()
    {
        foreach ($this->styleAttributesForNodes as $nodePath => $styleAttributesForNode) {
            $node = $this->visitedNodes[$nodePath];
            $currentStyleAttributes = $this->parseCssDeclarationsBlock($node->getAttribute('style'));
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
     * @return void
     */
    private function removeImportantAnnotationFromAllInlineStyles()
    {
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
     * @return void
     */
    private function removeImportantAnnotationFromNodeInlineStyle(\DOMElement $node)
    {
        $inlineStyleDeclarations = $this->parseCssDeclarationsBlock($node->getAttribute('style'));
        $regularStyleDeclarations = [];
        $importantStyleDeclarations = [];
        foreach ($inlineStyleDeclarations as $property => $value) {
            if ($this->attributeValueIsImportant($value)) {
                $importantStyleDeclarations[$property] = \trim(\str_replace('!important', '', $value));
            } else {
                $regularStyleDeclarations[$property] = $value;
            }
        }
        $inlineStyleDeclarationsInNewOrder = \array_merge(
            $regularStyleDeclarations,
            $importantStyleDeclarations
        );
        $node->setAttribute(
            'style',
            $this->generateStyleStringFromSingleDeclarationsArray($inlineStyleDeclarationsInNewOrder)
        );
    }

    /**
     * Generates a CSS style string suitable to be used inline from the $styleDeclarations property => value array.
     *
     * @param string[] $styleDeclarations
     *
     * @return string
     */
    private function generateStyleStringFromSingleDeclarationsArray(array $styleDeclarations)
    {
        return $this->generateStyleStringFromDeclarationsArrays([], $styleDeclarations);
    }

    /**
     * Determines which of `$cssRules` actually apply to `$this->domDocument`, and sets them in
     * `$this->matchingUninlinableCssRules`.
     *
     * @param string[][] $cssRules the "uninlinable" array of CSS rules returned by `parseCssRules`
     *
     * @return void
     */
    private function determineMatchingUninlinableCssRules(array $cssRules)
    {
        $this->matchingUninlinableCssRules = \array_filter($cssRules, [$this, 'existsMatchForSelectorInCssRule']);
    }

    /**
     * Checks whether there is at least one matching element for the CSS selector contained in the `selector` element
     * of the provided CSS rule.
     *
     * Any dynamic pseudo-classes will be assumed to apply. If the selector matches a pseudo-element,
     * it will test for a match with its originating element.
     *
     * @param string[] $cssRule
     *
     * @return bool
     *
     * @throws SyntaxErrorException
     */
    private function existsMatchForSelectorInCssRule(array $cssRule)
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
     * @throws SyntaxErrorException
     */
    private function existsMatchForCssSelector($cssSelector)
    {
        try {
            $nodesMatchingSelector = $this->xPath->query($this->getCssSelectorConverter()->toXPath($cssSelector));
        } catch (SyntaxErrorException $e) {
            if ($this->debug) {
                throw $e;
            }
            return true;
        }

        return $nodesMatchingSelector !== false && $nodesMatchingSelector->length !== 0;
    }

    /**
     * Removes pseudo-elements and dynamic pseudo-classes from a CSS selector, replacing them with "*" if necessary.
     * If such a pseudo-component is within the argument of `:not`, the entire `:not` component is removed or replaced.
     *
     * @param string $selector
     *
     * @return string Selector which will match the relevant DOM elements if the pseudo-classes are assumed to apply,
     *                or in the case of pseudo-elements will match their originating element.
     */
    private function removeUnmatchablePseudoComponents($selector)
    {
        // The regex allows nested brackets via `(?2)`.
        // A space is temporarily prepended because the callback can't determine if the match was at the very start.
        $selectorWithoutNots = \ltrim(\preg_replace_callback(
            '/(\\s?+):not(\\([^()]*+(?:(?2)[^()]*+)*+\\))/i',
            [$this, 'replaceUnmatchableNotComponent'],
            ' ' . $selector
        ));

        $pseudoComponentMatcher = ':(?!' . self::PSEUDO_CLASS_MATCHER . '):?+[\\w\\-]++(?:\\([^\\)]*+\\))?+';
        return \preg_replace(
            ['/(\\s|^)' . $pseudoComponentMatcher . '/i', '/' . $pseudoComponentMatcher . '/i'],
            ['$1*', ''],
            $selectorWithoutNots
        );
    }

    /**
     * Helps `removeUnmatchablePseudoComponents()` replace or remove a selector `:not(...)` component if its argument
     * contains pseudo-elements or dynamic pseudo-classes.
     *
     * @param string[] $matches array of elements matched by the regular expression
     *
     * @return string the full match if there were no unmatchable pseudo components within; otherwise, any preceding
     *         whitespace followed by "*", or an empty string if there was no preceding whitespace
     */
    private function replaceUnmatchableNotComponent(array $matches)
    {
        list($notComponentWithAnyPrecedingWhitespace, $anyPrecedingWhitespace, $notArgumentInBrackets) = $matches;

        $hasUnmatchablePseudo = \preg_match(
            '/:(?!' . self::PSEUDO_CLASS_MATCHER . ')[\\w\\-:]/i',
            $notArgumentInBrackets
        );

        if ($hasUnmatchablePseudo) {
            return $anyPrecedingWhitespace !== '' ? $anyPrecedingWhitespace . '*' : '';
        }
        return $notComponentWithAnyPrecedingWhitespace;
    }

    /**
     * Applies `$this->matchingUninlinableCssRules` to `$this->domDocument` by placing them as CSS in a `<style>`
     * element.
     *
     * @param string $cssImportRules This may contain any `@import` rules that should precede the CSS placed in the
     *        `<style>` element.  If there are no unlinlinable CSS rules to copy there, a `<style>` element will be
     *        created containing just `$cssImportRules`.  `$cssImportRules` may be an empty string; if it is, and there
     *        are no unlinlinable CSS rules, an empty `<style>` element will not be created.
     *
     * @return void
     */
    private function copyUninlinableCssToStyleNode($cssImportRules)
    {
        $css = $cssImportRules;

        // avoid including unneeded class dependency if there are no rules
        if ($this->matchingUninlinableCssRules !== []) {
            $cssConcatenator = new CssConcatenator();
            foreach ($this->matchingUninlinableCssRules as $cssRule) {
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
     *
     * @return void
     */
    protected function addStyleElementToDocument($css)
    {
        $styleElement = $this->domDocument->createElement('style', $css);
        $styleAttribute = $this->domDocument->createAttribute('type');
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
     */
    private function getHeadElement()
    {
        return $this->domDocument->getElementsByTagName('head')->item(0);
    }
}
