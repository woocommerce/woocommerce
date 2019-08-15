<?php

namespace Pelago\Emogrifier;

use Pelago\Emogrifier\HtmlProcessor\AbstractHtmlProcessor;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\CssSelector\Exception\SyntaxErrorException;

/**
 * This class provides functions for converting CSS styles into inline style attributes in your HTML code.
 *
 * For Emogrifier 3.0.0, this will be the successor to the \Pelago\Emogrifier class (which then will be deprecated).
 *
 * For more information, please see the README.md file.
 *
 * @internal This class currently is a new technology preview, and its API is still in flux. Don't use it in production.
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
    const PSEUDO_CLASS_MATCHER = '\\S+\\-(?:child|type\\()|not\\([[:ascii:]]*\\)';

    /**
     * @var bool[]
     */
    private $excludedSelectors = [];

    /**
     * @var string[]
     */
    private $unprocessableHtmlTags = [];

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
     * Determines whether elements with the `display: none` property are
     * removed from the DOM.
     *
     * @var bool
     */
    private $shouldRemoveInvisibleNodes = true;

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
     * Emogrifier will throw Exceptions when it encounters an error instead of silently ignoring them.
     *
     * @var bool
     */
    private $debug = false;

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

    /**
     * Inlines the given CSS into the existing HTML.
     *
     * @param string $css the CSS to inline, must be UTF-8-encoded
     *
     * @return CssInliner fluent interface
     *
     * @throws SyntaxErrorException
     */
    public function inlineCss($css)
    {
        $this->clearAllCaches();
        $this->purgeVisitedNodes();

        $xPath = new \DOMXPath($this->domDocument);
        $this->removeUnprocessableTags();
        $this->normalizeStyleAttributesOfAllNodes($xPath);

        $combinedCss = $css;
        // grab any existing style blocks from the HTML and append them to the existing CSS
        // (these blocks should be appended so as to have precedence over conflicting styles in the existing CSS)
        if ($this->isStyleBlocksParsingEnabled) {
            $combinedCss .= $this->getCssFromAllStyleNodes($xPath);
        }

        $excludedNodes = $this->getNodesToExclude($xPath);
        $cssRules = $this->parseCssRules($combinedCss);
        $cssSelectorConverter = $this->getCssSelectorConverter();
        foreach ($cssRules['inlineable'] as $cssRule) {
            try {
                $nodesMatchingCssSelectors = $xPath->query($cssSelectorConverter->toXPath($cssRule['selector']));
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
                $this->copyInlineableCssToStyleAttribute($node, $cssRule);
            }
        }

        if ($this->isInlineStyleAttributesParsingEnabled) {
            $this->fillStyleAttributesWithMergedStyles();
        }
        $this->postProcess($xPath);

        $this->removeImportantAnnotationFromAllInlineStyles($xPath);

        $this->copyUninlineableCssToStyleNode($xPath, $cssRules['uninlineable']);

        return $this;
    }

    /**
     * Applies some optional post-processing to the HTML in the DOM document.
     *
     * @param \DOMXPath $xPath
     *
     * @return void
     */
    private function postProcess(\DOMXPath $xPath)
    {
        if ($this->shouldRemoveInvisibleNodes) {
            $this->removeInvisibleNodes($xPath);
        }
    }

    /**
     * Searches for all nodes with a style attribute and removes the "!important" annotations out of
     * the inline style declarations, eventually by rearranging declarations.
     *
     * @param \DOMXPath $xPath
     *
     * @return void
     */
    private function removeImportantAnnotationFromAllInlineStyles(\DOMXPath $xPath)
    {
        foreach ($this->getAllNodesWithStyleAttribute($xPath) as $node) {
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
     * Returns a list with all DOM nodes that have a style attribute.
     *
     * @param \DOMXPath $xPath
     *
     * @return \DOMNodeList
     */
    private function getAllNodesWithStyleAttribute(\DOMXPath $xPath)
    {
        return $xPath->query('//*[@style]');
    }

    /**
     * Extracts and parses the individual rules from a CSS string.
     *
     * @param string $css a string of raw CSS code
     *
     * @return string[][][] A 2-entry array with the key "inlineable" containing rules which can be inlined as `style`
     *         attributes and the key "uninlineable" containing rules which cannot.  Each value is an array of string
     *         sub-arrays with the keys
     *         "media" (the media query string, e.g. "@media screen and (max-width: 480px)",
     *         or an empty string if not from a `@media` rule),
     *         "selector" (the CSS selector, e.g., "*" or "header h1"),
     *         "hasUnmatchablePseudo" (true if that selector contains psuedo-elements or dynamic pseudo-classes
     *         such that the declarations cannot be applied inline),
     *         "declarationsBlock" (the semicolon-separated CSS declarations for that selector,
     *         e.g., "color: red; height: 4px;"),
     *         and "line" (the line number e.g. 42)
     */
    private function parseCssRules($css)
    {
        $cssKey = \md5($css);
        if (!isset($this->caches[static::CACHE_KEY_CSS][$cssKey])) {
            $matches = $this->getCssRuleMatches($css);

            $cssRules = [
                'inlineable' => [],
                'uninlineable' => [],
            ];
            /** @var string[][] $matches */
            /** @var string[] $cssRule */
            foreach ($matches as $key => $cssRule) {
                $cssDeclaration = \trim($cssRule['declarations']);
                if ($cssDeclaration === '') {
                    continue;
                }

                $selectors = \explode(',', $cssRule['selectors']);
                foreach ($selectors as $selector) {
                    // don't process pseudo-elements and behavioral (dynamic) pseudo-classes;
                    // only allow structural pseudo-classes
                    $hasPseudoElement = \strpos($selector, '::') !== false;
                    $hasUnsupportedPseudoClass = (bool)\preg_match(
                        '/:(?!' . static::PSEUDO_CLASS_MATCHER . ')[\\w\\-]/i',
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
                    $ruleType = ($cssRule['media'] === '' && !$hasUnmatchablePseudo) ? 'inlineable' : 'uninlineable';
                    $cssRules[$ruleType][] = $parsedCssRule;
                }
            }

            \usort($cssRules['inlineable'], [$this, 'sortBySelectorPrecedence']);

            $this->caches[static::CACHE_KEY_CSS][$cssKey] = $cssRules;
        }

        return $this->caches[static::CACHE_KEY_CSS][$cssKey];
    }

    /**
     * Parses a string of CSS into the media query, selectors and declarations for each ruleset in order.
     *
     * @param string $css
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
        $ruleMatches = [];

        $splitCss = $this->splitCssAndMediaQuery($css);
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
     * Disables the removal of elements with `display: none` properties.
     *
     * @return void
     *
     * @deprecated will be removed in Emogrifier 3.0
     */
    public function disableInvisibleNodeRemoval()
    {
        $this->shouldRemoveInvisibleNodes = false;
    }

    /**
     * Clears all caches.
     *
     * @return void
     */
    private function clearAllCaches()
    {
        $this->caches = [
            static::CACHE_KEY_CSS => [],
            static::CACHE_KEY_SELECTOR => [],
            static::CACHE_KEY_CSS_DECLARATIONS_BLOCK => [],
            static::CACHE_KEY_COMBINED_STYLES => [],
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
     * Marks a tag for removal.
     *
     * There are some HTML tags that DOMDocument cannot process, and it will throw an error if it encounters them.
     * In particular, DOMDocument will complain if you try to use HTML5 tags in an XHTML document.
     *
     * Note: The tags will not be removed if they have any content.
     *
     * @param string $tagName the tag name, e.g., "p"
     *
     * @return void
     */
    public function addUnprocessableHtmlTag($tagName)
    {
        $this->unprocessableHtmlTags[] = $tagName;
    }

    /**
     * Drops a tag from the removal list.
     *
     * @param string $tagName the tag name, e.g., "p"
     *
     * @return void
     */
    public function removeUnprocessableHtmlTag($tagName)
    {
        $key = \array_search($tagName, $this->unprocessableHtmlTags, true);
        if ($key !== false) {
            unset($this->unprocessableHtmlTags[$key]);
        }
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
     * This removes styles from your email that contain display:none.
     * We need to look for display:none, but we need to do a case-insensitive search. Since DOMDocument only
     * supports XPath 1.0, lower-case() isn't available to us. We've thus far only set attributes to lowercase,
     * not attribute values. Consequently, we need to translate() the letters that would be in 'NONE' ("NOE")
     * to lowercase.
     *
     * @param \DOMXPath $xPath
     *
     * @return void
     */
    private function removeInvisibleNodes(\DOMXPath $xPath)
    {
        $nodesWithStyleDisplayNone = $xPath->query(
            '//*[contains(translate(translate(@style," ",""),"NOE","noe"),"display:none")]'
        );
        if ($nodesWithStyleDisplayNone->length === 0) {
            return;
        }

        // The checks on parentNode and is_callable below ensure that if we've deleted the parent node,
        // we don't try to call removeChild on a nonexistent child node
        /** @var \DOMNode $node */
        foreach ($nodesWithStyleDisplayNone as $node) {
            if ($node->parentNode && \is_callable([$node->parentNode, 'removeChild'])) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    /**
     * Parses the document and normalizes all existing CSS attributes.
     * This changes 'DISPLAY: none' to 'display: none'.
     * We wouldn't have to do this if DOMXPath supported XPath 2.0.
     * Also stores a reference of nodes with existing inline styles so we don't overwrite them.
     *
     * @param \DOMXPath $xPath
     *
     * @return void
     */
    private function normalizeStyleAttributesOfAllNodes(\DOMXPath $xPath)
    {
        /** @var \DOMElement $node */
        foreach ($this->getAllNodesWithStyleAttribute($xPath) as $node) {
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
     * Normalizes the value of the "style" attribute and saves it.
     *
     * @param \DOMElement $node
     *
     * @return void
     */
    private function normalizeStyleAttributes(\DOMElement $node)
    {
        $normalizedOriginalStyle = \preg_replace_callback(
            '/[A-z\\-]+(?=\\:)/S',
            function (array $m) {
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
        if (isset($this->caches[static::CACHE_KEY_COMBINED_STYLES][$cacheKey])) {
            return $this->caches[static::CACHE_KEY_COMBINED_STYLES][$cacheKey];
        }

        // Unset the overridden styles to preserve order, important if shorthand and individual properties are mixed
        foreach ($oldStyles as $attributeName => $attributeValue) {
            if (!isset($newStyles[$attributeName])) {
                continue;
            }

            $newAttributeValue = $newStyles[$attributeName];
            if ($this->attributeValueIsImportant($attributeValue)
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

        $this->caches[static::CACHE_KEY_COMBINED_STYLES][$cacheKey] = $trimmedStyle;

        return $trimmedStyle;
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
     * Applies $cssRules to $this->domDocument, limited to the rules that actually apply to the document.
     *
     * @param \DOMXPath $xPath
     * @param string[][] $cssRules The "uninlineable" array of CSS rules returned by `parseCssRules`
     *
     * @return void
     */
    private function copyUninlineableCssToStyleNode(\DOMXPath $xPath, array $cssRules)
    {
        $cssRulesRelevantForDocument = \array_filter(
            $cssRules,
            function (array $cssRule) use ($xPath) {
                $selector = $cssRule['selector'];
                if ($cssRule['hasUnmatchablePseudo']) {
                    $selector = $this->removeUnmatchablePseudoComponents($selector);
                }
                return $this->existsMatchForCssSelector($xPath, $selector);
            }
        );

        if ($cssRulesRelevantForDocument === []) {
            // avoid adding empty style element (or including unneeded class dependency)
            return;
        }

        $cssConcatenator = new CssConcatenator();
        foreach ($cssRulesRelevantForDocument as $cssRule) {
            $cssConcatenator->append([$cssRule['selector']], $cssRule['declarationsBlock'], $cssRule['media']);
        }

        $this->addStyleElementToDocument($cssConcatenator->getCss());
    }

    /**
     * Removes pseudo-elements and dynamic pseudo-classes from a CSS selector, replacing them with "*" if necessary.
     *
     * @param string $selector
     *
     * @return string Selector which will match the relevant DOM elements if the pseudo-classes are assumed to apply,
     *                or in the case of pseudo-elements will match their originating element.
     */
    private function removeUnmatchablePseudoComponents($selector)
    {
        $pseudoComponentMatcher = ':(?!' . static::PSEUDO_CLASS_MATCHER . '):?+[\\w\\-]++(?:\\([^\\)]*+\\))?+';
        return \preg_replace(
            ['/(\\s|^)' . $pseudoComponentMatcher . '/i', '/' . $pseudoComponentMatcher . '/i'],
            ['$1*', ''],
            $selector
        );
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
    private function copyInlineableCssToStyleAttribute(\DOMElement $node, array $cssRule)
    {
        // if it has a style attribute, get it, process it, and append (overwrite) new stuff
        if ($node->hasAttribute('style')) {
            // break it up into an associative array
            $oldStyleDeclarations = $this->parseCssDeclarationsBlock($node->getAttribute('style'));
        } else {
            $oldStyleDeclarations = [];
        }
        $newStyleDeclarations = $this->parseCssDeclarationsBlock($cssRule['declarationsBlock']);
        $node->setAttribute(
            'style',
            $this->generateStyleStringFromDeclarationsArrays($oldStyleDeclarations, $newStyleDeclarations)
        );
    }

    /**
     * Checks whether there is at least one matching element for $cssSelector.
     * When not in debug mode, it returns true also for invalid selectors (because they may be valid,
     * just not implemented/recognized yet by Emogrifier).
     *
     * @param \DOMXPath $xPath
     * @param string $cssSelector
     *
     * @return bool
     *
     * @throws SyntaxErrorException
     */
    private function existsMatchForCssSelector(\DOMXPath $xPath, $cssSelector)
    {
        try {
            $nodesMatchingSelector = $xPath->query($this->getCssSelectorConverter()->toXPath($cssSelector));
        } catch (SyntaxErrorException $e) {
            if ($this->debug) {
                throw $e;
            }
            return true;
        }

        return $nodesMatchingSelector !== false && $nodesMatchingSelector->length !== 0;
    }

    /**
     * Returns CSS content.
     *
     * @param \DOMXPath $xPath
     *
     * @return string
     */
    private function getCssFromAllStyleNodes(\DOMXPath $xPath)
    {
        $styleNodes = $xPath->query('//style');

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
     * Splits input CSS code into an array of parts for different media querues, in order.
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
        $cssWithoutComments = \preg_replace('/\\/\\*.*\\*\\//sU', '', $css);

        $mediaTypesExpression = '';
        if (!empty($this->allowedMediaTypes)) {
            $mediaTypesExpression = '|' . \implode('|', \array_keys($this->allowedMediaTypes));
        }

        $mediaRuleBodyMatcher = '[^{]*+{(?:[^{}]*+{.*})?\\s*+}\\s*+';

        $cssSplitForAllowedMediaTypes = \preg_split(
            '#(@media\\s++(?:only\\s++)?+(?:(?=[{\\(])' . $mediaTypesExpression . ')' . $mediaRuleBodyMatcher
            . ')#misU',
            $cssWithoutComments,
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
     * Removes empty unprocessable tags from the DOM document.
     *
     * @return void
     */
    private function removeUnprocessableTags()
    {
        foreach ($this->unprocessableHtmlTags as $tagName) {
            // Deleting nodes from a 'live' NodeList invalidates iteration on it, so a copy must be made to iterate.
            $nodes = [];
            foreach ($this->domDocument->getElementsByTagName($tagName) as $node) {
                $nodes[] = $node;
            }
            /** @var \DOMNode $node */
            foreach ($nodes as $node) {
                $hasContent = $node->hasChildNodes() || $node->hasChildNodes();
                if (!$hasContent) {
                    $node->parentNode->removeChild($node);
                }
            }
        }
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
        if (!isset($this->caches[static::CACHE_KEY_SELECTOR][$selectorKey])) {
            $precedence = 0;
            foreach ($this->selectorPrecedenceMatchers as $matcher => $value) {
                if (\trim($selector) === '') {
                    break;
                }
                $number = 0;
                $selector = \preg_replace('/' . $matcher . '\\w+/', '', $selector, -1, $number);
                $precedence += ($value * $number);
            }
            $this->caches[static::CACHE_KEY_SELECTOR][$selectorKey] = $precedence;
        }

        return $this->caches[static::CACHE_KEY_SELECTOR][$selectorKey];
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
        if (isset($this->caches[static::CACHE_KEY_CSS_DECLARATIONS_BLOCK][$cssDeclarationsBlock])) {
            return $this->caches[static::CACHE_KEY_CSS_DECLARATIONS_BLOCK][$cssDeclarationsBlock];
        }

        $properties = [];
        $declarations = \preg_split('/;(?!base64|charset)/', $cssDeclarationsBlock);

        foreach ($declarations as $declaration) {
            $matches = [];
            if (!\preg_match('/^([A-Za-z\\-]+)\\s*:\\s*(.+)$/s', \trim($declaration), $matches)) {
                continue;
            }

            $propertyName = \strtolower($matches[1]);
            $propertyValue = $matches[2];
            $properties[$propertyName] = $propertyValue;
        }
        $this->caches[static::CACHE_KEY_CSS_DECLARATIONS_BLOCK][$cssDeclarationsBlock] = $properties;

        return $properties;
    }

    /**
     * Find the nodes that are not to be emogrified.
     *
     * @param \DOMXPath $xPath
     *
     * @return \DOMElement[]
     *
     * @throws SyntaxErrorException
     */
    private function getNodesToExclude(\DOMXPath $xPath)
    {
        $excludedNodes = [];
        foreach (\array_keys($this->excludedSelectors) as $selectorToExclude) {
            try {
                $matchingNodes = $xPath->query($this->getCssSelectorConverter()->toXPath($selectorToExclude));
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
}
