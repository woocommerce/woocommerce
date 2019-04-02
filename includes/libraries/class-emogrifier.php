<?php

namespace Pelago;

/**
 * This class provides functions for converting CSS styles into inline style attributes in your HTML code.
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
class Emogrifier
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
    const CACHE_KEY_XPATH = 2;

    /**
     * @var int
     */
    const CACHE_KEY_CSS_DECLARATIONS_BLOCK = 3;

    /**
     * @var int
     */
    const CACHE_KEY_COMBINED_STYLES = 4;

    /**
     * for calculating nth-of-type and nth-child selectors
     *
     * @var int
     */
    const INDEX = 0;

    /**
     * for calculating nth-of-type and nth-child selectors
     *
     * @var int
     */
    const MULTIPLIER = 1;

    /**
     * @var string
     */
    const ID_ATTRIBUTE_MATCHER = '/(\\w+)?\\#([\\w\\-]+)/';

    /**
     * @var string
     */
    const CLASS_ATTRIBUTE_MATCHER = '/(\\w+|[\\*\\]])?((\\.[\\w\\-]+)+)/';

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
     * @var string
     */
    const CONTENT_TYPE_META_TAG = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';

    /**
     * @var string
     */
    const DEFAULT_DOCUMENT_TYPE = '<!DOCTYPE html>';

    /**
     * @var \DOMDocument
     */
    protected $domDocument = null;

    /**
     * @var string
     */
    private $css = '';

    /**
     * @var bool[]
     */
    private $excludedSelectors = [];

    /**
     * @var string[]
     */
    private $unprocessableHtmlTags = ['wbr'];

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
        self::CACHE_KEY_XPATH => [],
        self::CACHE_KEY_CSS_DECLARATIONS_BLOCK => [],
        self::CACHE_KEY_COMBINED_STYLES => [],
    ];

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
     * @var string[]
     */
    private $xPathRules = [
        // attribute presence
        '/^\\[(\\w+|\\w+\\=[\'"]?\\w+[\'"]?)\\]/' => '*[@\\1]',
        // type and attribute exact value
        '/(\\w)\\[(\\w+)\\=[\'"]?([\\w\\s]+)[\'"]?\\]/' => '\\1[@\\2="\\3"]',
        // type and attribute value with ~ (one word within a whitespace-separated list of words)
        '/([\\w\\*]+)\\[(\\w+)[\\s]*\\~\\=[\\s]*[\'"]?([\\w\\-_\\/]+)[\'"]?\\]/'
        => '\\1[contains(concat(" ", @\\2, " "), concat(" ", "\\3", " "))]',
        // type and attribute value with | (either exact value match or prefix followed by a hyphen)
        '/([\\w\\*]+)\\[(\\w+)[\\s]*\\|\\=[\\s]*[\'"]?([\\w\\-_\\s\\/]+)[\'"]?\\]/'
        => '\\1[@\\2="\\3" or starts-with(@\\2, concat("\\3", "-"))]',
        // type and attribute value with ^ (prefix match)
        '/([\\w\\*]+)\\[(\\w+)[\\s]*\\^\\=[\\s]*[\'"]?([\\w\\-_\\/]+)[\'"]?\\]/' => '\\1[starts-with(@\\2, "\\3")]',
        // type and attribute value with * (substring match)
        '/([\\w\\*]+)\\[(\\w+)[\\s]*\\*\\=[\\s]*[\'"]?([\\w\\-_\\s\\/:;]+)[\'"]?\\]/' => '\\1[contains(@\\2, "\\3")]',
        // adjacent sibling
        '/\\s*\\+\\s*/' => '/following-sibling::*[1]/self::',
        // child
        '/\\s*>\\s*/' => '/',
        // descendant (don't match spaces within already translated XPath predicates)
        '/\\s+(?![^\\[\\]]*+\\])/' => '//',
        // type and :first-child
        '/([^\\/]+):first-child/i' => '*[1]/self::\\1',
        // type and :last-child
        '/([^\\/]+):last-child/i' => '*[last()]/self::\\1',

        // The following matcher will break things if it is placed before the adjacent matcher.
        // So one of the matchers matches either too much or not enough.
        // type and attribute value with $ (suffix match)
        '/([\\w\\*]+)\\[(\\w+)[\\s]*\\$\\=[\\s]*[\'"]?([\\w\\-_\\s\\/]+)[\'"]?\\]/'
        => '\\1[substring(@\\2, string-length(@\\2) - string-length("\\3") + 1) = "\\3"]',
    ];

    /**
     * Determines whether CSS styles that have an equivalent HTML attribute
     * should be mapped and attached to those elements.
     *
     * @var bool
     */
    private $shouldMapCssToHtml = false;

    /**
     * This multi-level array contains simple mappings of CSS properties to
     * HTML attributes. If a mapping only applies to certain HTML nodes or
     * only for certain values, the mapping is an object with a whitelist
     * of nodes and values.
     *
     * @var mixed[][]
     */
    private $cssToHtmlMap = [
        'background-color' => [
            'attribute' => 'bgcolor',
        ],
        'text-align' => [
            'attribute' => 'align',
            'nodes' => ['p', 'div', 'td'],
            'values' => ['left', 'right', 'center', 'justify'],
        ],
        'float' => [
            'attribute' => 'align',
            'nodes' => ['table', 'img'],
            'values' => ['left', 'right'],
        ],
        'border-spacing' => [
            'attribute' => 'cellspacing',
            'nodes' => ['table'],
        ],
    ];

    /**
     * Emogrifier will throw Exceptions when it encounters an error instead of silently ignoring them.
     *
     * @var bool
     */
    private $debug = false;

    /**
     * @param string $unprocessedHtml the HTML to process, must be UTF-8-encoded
     * @param string $css the CSS to merge, must be UTF-8-encoded
     */
    public function __construct($unprocessedHtml = '', $css = '')
    {
        if ($unprocessedHtml !== '') {
            $this->setHtml($unprocessedHtml);
        }
        $this->setCss($css);
    }

    /**
     * Sets the HTML to process.
     *
     * @param string $html the HTML to process, must be UTF-encoded, must not be empty
     *
     * @return void
     *
     * @throws \InvalidArgumentException if $unprocessedHtml is anything other than a non-empty string
     */
    public function setHtml($html)
    {
        if (!\is_string($html)) {
            throw new \InvalidArgumentException('The provided HTML must be a string.', 1540403913);
        }
        if ($html === '') {
            throw new \InvalidArgumentException('The provided HTML must not be empty.', 1540403910);
        }

        $this->createUnifiedDomDocument($html);
    }

    /**
     * Provides access to the internal DOMDocument representation of the HTML in its current state.
     *
     * @return \DOMDocument
     */
    public function getDomDocument()
    {
        return $this->domDocument;
    }

    /**
     * Sets the CSS to merge with the HTML.
     *
     * @param string $css the CSS to merge, must be UTF-8-encoded
     *
     * @return void
     */
    public function setCss($css)
    {
        $this->css = $css;
    }

    /**
     * Renders the normalized and processed HTML.
     *
     * @return string
     */
    protected function render()
    {
        return $this->domDocument->saveHTML();
    }

    /**
     * Renders the content of the BODY element of the normalized and processed HTML.
     *
     * @return string
     */
    protected function renderBodyContent()
    {
        $bodyNodeHtml = $this->domDocument->saveHTML($this->getBodyElement());

        return \str_replace(['<body>', '</body>'], '', $bodyNodeHtml);
    }

    /**
     * Returns the BODY element.
     *
     * This method assumes that there always is a BODY element.
     *
     * @return \DOMElement
     */
    private function getBodyElement()
    {
        return $this->domDocument->getElementsByTagName('body')->item(0);
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
     * Applies $this->css to the given HTML and returns the HTML with the CSS
     * applied.
     *
     * This method places the CSS inline.
     *
     * @return string
     *
     * @throws \BadMethodCallException
     */
    public function emogrify()
    {
        $this->assertExistenceOfHtml();

        $this->process();

        return $this->render();
    }

    /**
     * Applies $this->css to the given HTML and returns only the HTML content
     * within the <body> tag.
     *
     * This method places the CSS inline.
     *
     * @return string
     *
     * @throws \BadMethodCallException
     */
    public function emogrifyBodyContent()
    {
        $this->assertExistenceOfHtml();

        $this->process();

        return $this->renderBodyContent();
    }

    /**
     * Checks that some HTML has been set, and throws an exception otherwise.
     *
     * @return void
     *
     * @throws \BadMethodCallException
     */
    private function assertExistenceOfHtml()
    {
        if ($this->domDocument === null) {
            throw new \BadMethodCallException('Please set some HTML first.', 1390393096);
        }
    }

    /**
     * Creates a DOM document from the given HTML and stores it in $this->domDocument.
     *
     * The DOM document will always have a BODY element.
     *
     * @param string $html
     *
     * @return void
     */
    private function createUnifiedDomDocument($html)
    {
        $this->createRawDomDocument($html);
        $this->ensureExistenceOfBodyElement();
    }

    /**
     * Creates a DOMDocument instance from the given HTML and stores it in $this->domDocument.
     *
     * @param string $html
     *
     * @return void
     */
    private function createRawDomDocument($html)
    {
        $domDocument = new \DOMDocument();
        $domDocument->encoding = 'UTF-8';
        $domDocument->strictErrorChecking = false;
        $domDocument->formatOutput = true;
        $libXmlState = \libxml_use_internal_errors(true);
        $domDocument->loadHTML($this->prepareHtmlForDomConversion($html));
        \libxml_clear_errors();
        \libxml_use_internal_errors($libXmlState);
        $domDocument->normalizeDocument();

        $this->domDocument = $domDocument;
    }

    /**
     * Returns the HTML with added document type and Content-Type meta tag if needed,
     * ensuring that the HTML will be good for creating a DOM document from it.
     *
     * @param string $html
     *
     * @return string the unified HTML
     */
    private function prepareHtmlForDomConversion($html)
    {
        $htmlWithDocumentType = $this->ensureDocumentType($html);

        return $this->addContentTypeMetaTag($htmlWithDocumentType);
    }

    /**
     * Applies $this->css to $this->domDocument.
     *
     * This method places the CSS inline.
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function process()
    {
        $this->clearAllCaches();
        $this->purgeVisitedNodes();

        $xPath = new \DOMXPath($this->domDocument);
        \set_error_handler([$this, 'handleXpathQueryWarnings'], E_WARNING);
        $this->removeUnprocessableTags();
        $this->normalizeStyleAttributesOfAllNodes($xPath);

        // grab any existing style blocks from the html and append them to the existing CSS
        // (these blocks should be appended so as to have precedence over conflicting styles in the existing CSS)
        $allCss = $this->css;
        if ($this->isStyleBlocksParsingEnabled) {
            $allCss .= $this->getCssFromAllStyleNodes($xPath);
        }

        $excludedNodes = $this->getNodesToExclude($xPath);
        $cssRules = $this->parseCssRules($allCss);
        foreach ($cssRules['inlineable'] as $cssRule) {
            // There's no real way to test "PHP Warning" output generated by the following XPath query unless PHPUnit
            // converts it to an exception. Unfortunately, this would only apply to tests and not work for production
            // executions, which can still flood logs/output unnecessarily. Instead, Emogrifier's error handler should
            // always throw an exception and it must be caught here and only rethrown if in debug mode.
            try {
                // \DOMXPath::query will always return a DOMNodeList or throw an exception when errors are caught.
                $nodesMatchingCssSelectors = $xPath->query($this->translateCssToXpath($cssRule['selector']));
            } catch (\InvalidArgumentException $e) {
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

        \restore_error_handler();
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
        if ($this->shouldMapCssToHtml) {
            $this->mapAllInlineStylesToHtmlAttributes($xPath);
        }
        if ($this->shouldRemoveInvisibleNodes) {
            $this->removeInvisibleNodes($xPath);
        }
    }

    /**
     * Searches for all nodes with a style attribute, transforms the CSS found
     * to HTML attributes and adds those attributes to each node.
     *
     * @param \DOMXPath $xPath
     *
     * @return void
     */
    private function mapAllInlineStylesToHtmlAttributes(\DOMXPath $xPath)
    {
        /** @var \DOMElement $node */
        foreach ($this->getAllNodesWithStyleAttribute($xPath) as $node) {
            $inlineStyleDeclarations = $this->parseCssDeclarationsBlock($node->getAttribute('style'));
            $this->mapCssToHtmlAttributes($inlineStyleDeclarations, $node);
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
     * Applies $styles to $node.
     *
     * This method maps CSS styles to HTML attributes and adds those to the
     * node.
     *
     * @param string[] $styles the new CSS styles taken from the global styles to be applied to this node
     * @param \DOMElement $node node to apply styles to
     *
     * @return void
     */
    private function mapCssToHtmlAttributes(array $styles, \DOMElement $node)
    {
        foreach ($styles as $property => $value) {
            // Strip !important indicator
            $value = \trim(\str_replace('!important', '', $value));
            $this->mapCssToHtmlAttribute($property, $value, $node);
        }
    }

    /**
     * Tries to apply the CSS style to $node as an attribute.
     *
     * This method maps a CSS rule to HTML attributes and adds those to the node.
     *
     * @param string $property the name of the CSS property to map
     * @param string $value the value of the style rule to map
     * @param \DOMElement $node node to apply styles to
     *
     * @return void
     */
    private function mapCssToHtmlAttribute($property, $value, \DOMElement $node)
    {
        if (!$this->mapSimpleCssProperty($property, $value, $node)) {
            $this->mapComplexCssProperty($property, $value, $node);
        }
    }

    /**
     * Looks up the CSS property in the mapping table and maps it if it matches the conditions.
     *
     * @param string $property the name of the CSS property to map
     * @param string $value the value of the style rule to map
     * @param \DOMElement $node node to apply styles to
     *
     * @return bool true if the property can be mapped using the simple mapping table
     */
    private function mapSimpleCssProperty($property, $value, \DOMElement $node)
    {
        if (!isset($this->cssToHtmlMap[$property])) {
            return false;
        }

        $mapping = $this->cssToHtmlMap[$property];
        $nodesMatch = !isset($mapping['nodes']) || \in_array($node->nodeName, $mapping['nodes'], true);
        $valuesMatch = !isset($mapping['values']) || \in_array($value, $mapping['values'], true);
        if (!$nodesMatch || !$valuesMatch) {
            return false;
        }

        $node->setAttribute($mapping['attribute'], $value);

        return true;
    }

    /**
     * Maps CSS properties that need special transformation to an HTML attribute.
     *
     * @param string $property the name of the CSS property to map
     * @param string $value the value of the style rule to map
     * @param \DOMElement $node node to apply styles to
     *
     * @return void
     */
    private function mapComplexCssProperty($property, $value, \DOMElement $node)
    {
        switch ($property) {
            case 'background':
                $this->mapBackgroundProperty($node, $value);
                break;
            case 'width':
                // intentional fall-through
            case 'height':
                $this->mapWidthOrHeightProperty($node, $value, $property);
                break;
            case 'margin':
                $this->mapMarginProperty($node, $value);
                break;
            case 'border':
                $this->mapBorderProperty($node, $value);
                break;
            default:
        }
    }

    /**
     * Maps the "background" CSS property to visual HTML attributes.
     *
     * @param \DOMElement $node node to apply styles to
     * @param string $value the value of the style rule to map
     *
     * @return void
     */
    private function mapBackgroundProperty(\DOMElement $node, $value)
    {
        // parse out the color, if any
        $styles = \explode(' ', $value);
        $first = $styles[0];
        if (!\is_numeric($first[0]) && \strpos($first, 'url') !== 0) {
            // as this is not a position or image, assume it's a color
            $node->setAttribute('bgcolor', $first);
        }
    }

    /**
     * Maps the "width" or "height" CSS properties to visual HTML attributes.
     *
     * @param \DOMElement $node node to apply styles to
     * @param string $value the value of the style rule to map
     * @param string $property the name of the CSS property to map
     *
     * @return void
     */
    private function mapWidthOrHeightProperty(\DOMElement $node, $value, $property)
    {
        // only parse values in px and %, but not values like "auto"
        if (\preg_match('/^\\d+(px|%)$/', $value)) {
            // Remove 'px'. This regex only conserves numbers and %.
            $number = \preg_replace('/[^0-9.%]/', '', $value);
            $node->setAttribute($property, $number);
        }
    }

    /**
     * Maps the "margin" CSS property to visual HTML attributes.
     *
     * @param \DOMElement $node node to apply styles to
     * @param string $value the value of the style rule to map
     *
     * @return void
     */
    private function mapMarginProperty(\DOMElement $node, $value)
    {
        if (!$this->isTableOrImageNode($node)) {
            return;
        }

        $margins = $this->parseCssShorthandValue($value);
        if ($margins['left'] === 'auto' && $margins['right'] === 'auto') {
            $node->setAttribute('align', 'center');
        }
    }

    /**
     * Maps the "border" CSS property to visual HTML attributes.
     *
     * @param \DOMElement $node node to apply styles to
     * @param string $value the value of the style rule to map
     *
     * @return void
     */
    private function mapBorderProperty(\DOMElement $node, $value)
    {
        if (!$this->isTableOrImageNode($node)) {
            return;
        }

        if ($value === 'none' || $value === '0') {
            $node->setAttribute('border', '0');
        }
    }

    /**
     * Checks whether $node is a table or img element.
     *
     * @param \DOMElement $node
     *
     * @return bool
     */
    private function isTableOrImageNode(\DOMElement $node)
    {
        return $node->nodeName === 'table' || $node->nodeName === 'img';
    }

    /**
     * Parses a shorthand CSS value and splits it into individual values
     *
     * @param string $value a string of CSS value with 1, 2, 3 or 4 sizes
     *                      For example: padding: 0 auto;
     *                      '0 auto' is split into top: 0, left: auto, bottom: 0,
     *                      right: auto.
     *
     * @return string[] an array of values for top, right, bottom and left (using these as associative array keys)
     */
    private function parseCssShorthandValue($value)
    {
        $values = \preg_split('/\\s+/', $value);

        $css = [];
        $css['top'] = $values[0];
        $css['right'] = (\count($values) > 1) ? $values[1] : $css['top'];
        $css['bottom'] = (\count($values) > 2) ? $values[2] : $css['top'];
        $css['left'] = (\count($values) > 3) ? $values[3] : $css['right'];

        return $css;
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
     * @deprecated will be removed in Emogrifier 3.0
     *
     * @return void
     */
    public function disableInvisibleNodeRemoval()
    {
        $this->shouldRemoveInvisibleNodes = false;
    }

    /**
     * Enables the attachment/override of HTML attributes for which a
     * corresponding CSS property has been set.
     *
     * @deprecated will be removed in Emogrifier 3.0, use the CssToAttributeConverter instead
     *
     * @return void
     */
    public function enableCssToHtmlMapping()
    {
        $this->shouldMapCssToHtml = true;
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
            static::CACHE_KEY_XPATH => [],
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

        // support use without autoload
        if (!\class_exists('Pelago\\Emogrifier\\CssConcatenator')) {
            require_once __DIR__ . '/Emogrifier/CssConcatenator.php';
        }

        $cssConcatenator = new Emogrifier\CssConcatenator();
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
     * Checks whether there is at least one matching element for $cssSelector.
     * When not in debug mode, it returns true also for invalid selectors (because they may be valid,
     * just not implemented/recognized yet by Emogrifier).
     *
     * @param \DOMXPath $xPath
     * @param string $cssSelector
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    private function existsMatchForCssSelector(\DOMXPath $xPath, $cssSelector)
    {
        try {
            $nodesMatchingSelector = $xPath->query($this->translateCssToXpath($cssSelector));
        } catch (\InvalidArgumentException $e) {
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
     * @see https://github.com/jjriv/emogrifier/issues/103
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
     * Checks that $this->domDocument has a BODY element and adds it if it is missing.
     *
     * @return void
     */
    private function ensureExistenceOfBodyElement()
    {
        if ($this->domDocument->getElementsByTagName('body')->item(0) !== null) {
            return;
        }

        $htmlElement = $this->domDocument->getElementsByTagName('html')->item(0);
        $htmlElement->appendChild($this->domDocument->createElement('body'));
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
            $nodes = $this->domDocument->getElementsByTagName($tagName);
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
     * Makes sure that the passed HTML has a document type.
     *
     * @param string $html
     *
     * @return string HTML with document type
     */
    private function ensureDocumentType($html)
    {
        $hasDocumentType = \stripos($html, '<!DOCTYPE') !== false;
        if ($hasDocumentType) {
            return $html;
        }

        return static::DEFAULT_DOCUMENT_TYPE . $html;
    }

    /**
     * Adds a Content-Type meta tag for the charset.
     *
     * This method also ensures that there is a HEAD element.
     *
     * @param string $html
     *
     * @return string the HTML with the meta tag added
     */
    private function addContentTypeMetaTag($html)
    {
        $hasContentTypeMetaTag = \stripos($html, 'Content-Type') !== false;
        if ($hasContentTypeMetaTag) {
            return $html;
        }

        // We are trying to insert the meta tag to the right spot in the DOM.
        // If we just prepended it to the HTML, we would lose attributes set to the HTML tag.
        $hasHeadTag = \stripos($html, '<head') !== false;
        $hasHtmlTag = \stripos($html, '<html') !== false;

        if ($hasHeadTag) {
            $reworkedHtml = \preg_replace('/<head(.*?)>/i', '<head$1>' . static::CONTENT_TYPE_META_TAG, $html);
        } elseif ($hasHtmlTag) {
            $reworkedHtml = \preg_replace(
                '/<html(.*?)>/i',
                '<html$1><head>' . static::CONTENT_TYPE_META_TAG . '</head>',
                $html
            );
        } else {
            $reworkedHtml = static::CONTENT_TYPE_META_TAG . $html;
        }

        return $reworkedHtml;
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
     * Maps a CSS selector to an XPath query string.
     *
     * @see http://plasmasturm.org/log/444/
     *
     * @param string $cssSelector a CSS selector
     *
     * @return string the corresponding XPath selector
     */
    private function translateCssToXpath($cssSelector)
    {
        $paddedSelector = ' ' . $cssSelector . ' ';
        $lowercasePaddedSelector = \preg_replace_callback(
            '/\\s+\\w+\\s+/',
            function (array $matches) {
                return \strtolower($matches[0]);
            },
            $paddedSelector
        );
        $trimmedLowercaseSelector = \trim($lowercasePaddedSelector);
        $xPathKey = \md5($trimmedLowercaseSelector);
        if (isset($this->caches[static::CACHE_KEY_XPATH][$xPathKey])) {
            return $this->caches[static::CACHE_KEY_SELECTOR][$xPathKey];
        }

        $hasNotSelector = (bool)\preg_match(
            '/^([^:]+):not\\(\\s*([[:ascii:]]+)\\s*\\)$/',
            $trimmedLowercaseSelector,
            $matches
        );
        if (!$hasNotSelector) {
            $xPath = '//' . $this->translateCssToXpathPass($trimmedLowercaseSelector);
        } else {
            /** @var string[] $matches */
            list(, $partBeforeNot, $notContents) = $matches;
            $xPath = '//' . $this->translateCssToXpathPass($partBeforeNot) .
                '[not(' . $this->translateCssToXpathPassInline($notContents) . ')]';
        }
        $this->caches[static::CACHE_KEY_SELECTOR][$xPathKey] = $xPath;

        return $this->caches[static::CACHE_KEY_SELECTOR][$xPathKey];
    }

    /**
     * Flexibly translates the CSS selector $trimmedLowercaseSelector to an xPath selector.
     *
     * @param string $trimmedLowercaseSelector
     *
     * @return string
     */
    private function translateCssToXpathPass($trimmedLowercaseSelector)
    {
        return $this->translateCssToXpathPassWithMatchClassAttributesCallback(
            $trimmedLowercaseSelector,
            [$this, 'matchClassAttributes']
        );
    }

    /**
     * Flexibly translates the CSS selector $trimmedLowercaseSelector to an xPath selector for inline usage.
     *
     * @param string $trimmedLowercaseSelector
     *
     * @return string
     */
    private function translateCssToXpathPassInline($trimmedLowercaseSelector)
    {
        return $this->translateCssToXpathPassWithMatchClassAttributesCallback(
            $trimmedLowercaseSelector,
            [$this, 'matchClassAttributesInline']
        );
    }

    /**
     * Flexibly translates the CSS selector $trimmedLowercaseSelector to an xPath selector while using
     * $matchClassAttributesCallback as to match the class attributes.
     *
     * @param string $trimmedLowercaseSelector
     * @param callable $matchClassAttributesCallback
     *
     * @return string
     */
    private function translateCssToXpathPassWithMatchClassAttributesCallback(
        $trimmedLowercaseSelector,
        callable $matchClassAttributesCallback
    ) {
        $roughXpath = \preg_replace(\array_keys($this->xPathRules), $this->xPathRules, $trimmedLowercaseSelector);
        $xPathWithIdAttributeMatchers = \preg_replace_callback(
            static::ID_ATTRIBUTE_MATCHER,
            [$this, 'matchIdAttributes'],
            $roughXpath
        );
        $xPathWithIdAttributeAndClassMatchers = \preg_replace_callback(
            static::CLASS_ATTRIBUTE_MATCHER,
            $matchClassAttributesCallback,
            $xPathWithIdAttributeMatchers
        );

        // Advanced selectors are going to require a bit more advanced emogrification.
        $xPathWithIdAttributeAndClassMatchers = \preg_replace_callback(
            '/([^\\/]+):nth-child\\(\\s*(odd|even|[+\\-]?\\d|[+\\-]?\\d?n(\\s*[+\\-]\\s*\\d)?)\\s*\\)/i',
            [$this, 'translateNthChild'],
            $xPathWithIdAttributeAndClassMatchers
        );
        $finalXpath = \preg_replace_callback(
            '/([^\\/]+):nth-of-type\\(\\s*(odd|even|[+\\-]?\\d|[+\\-]?\\d?n(\\s*[+\\-]\\s*\\d)?)\\s*\\)/i',
            [$this, 'translateNthOfType'],
            $xPathWithIdAttributeAndClassMatchers
        );

        return $finalXpath;
    }

    /**
     * @param string[] $match
     *
     * @return string
     */
    private function matchIdAttributes(array $match)
    {
        return ($match[1] !== '' ? $match[1] : '*') . '[@id="' . $match[2] . '"]';
    }

    /**
     * @param string[] $match
     *
     * @return string xPath class attribute query wrapped in element selector
     */
    private function matchClassAttributes(array $match)
    {
        return ($match[1] !== '' ? $match[1] : '*') . '[' . $this->matchClassAttributesInline($match) . ']';
    }

    /**
     * @param string[] $match
     *
     * @return string xPath class attribute query
     */
    private function matchClassAttributesInline(array $match)
    {
        return 'contains(concat(" ",@class," "),concat(" ","' .
            \implode(
                '"," "))][contains(concat(" ",@class," "),concat(" ","',
                \explode('.', \substr($match[2], 1))
            ) . '"," "))';
    }

    /**
     * @param string[] $match
     *
     * @return string
     */
    private function translateNthChild(array $match)
    {
        $parseResult = $this->parseNth($match);

        if (isset($parseResult[static::MULTIPLIER])) {
            if ($parseResult[static::MULTIPLIER] < 0) {
                $parseResult[static::MULTIPLIER] = \abs($parseResult[static::MULTIPLIER]);
                $xPathExpression = \sprintf(
                    '*[(last() - position()) mod %1%u = %2$u]/static::%3$s',
                    $parseResult[static::MULTIPLIER],
                    $parseResult[static::INDEX],
                    $match[1]
                );
            } else {
                $xPathExpression = \sprintf(
                    '*[position() mod %1$u = %2$u]/static::%3$s',
                    $parseResult[static::MULTIPLIER],
                    $parseResult[static::INDEX],
                    $match[1]
                );
            }
        } else {
            $xPathExpression = \sprintf('*[%1$u]/static::%2$s', $parseResult[static::INDEX], $match[1]);
        }

        return $xPathExpression;
    }

    /**
     * @param string[] $match
     *
     * @return string
     */
    private function translateNthOfType(array $match)
    {
        $parseResult = $this->parseNth($match);

        if (isset($parseResult[static::MULTIPLIER])) {
            if ($parseResult[static::MULTIPLIER] < 0) {
                $parseResult[static::MULTIPLIER] = \abs($parseResult[static::MULTIPLIER]);
                $xPathExpression = \sprintf(
                    '%1$s[(last() - position()) mod %2$u = %3$u]',
                    $match[1],
                    $parseResult[static::MULTIPLIER],
                    $parseResult[static::INDEX]
                );
            } else {
                $xPathExpression = \sprintf(
                    '%1$s[position() mod %2$u = %3$u]',
                    $match[1],
                    $parseResult[static::MULTIPLIER],
                    $parseResult[static::INDEX]
                );
            }
        } else {
            $xPathExpression = \sprintf('%1$s[%2$u]', $match[1], $parseResult[static::INDEX]);
        }

        return $xPathExpression;
    }

    /**
     * @param string[] $match
     *
     * @return int[]
     */
    private function parseNth(array $match)
    {
        if (\in_array(\strtolower($match[2]), ['even', 'odd'], true)) {
            // we have "even" or "odd"
            $index = \strtolower($match[2]) === 'even' ? 0 : 1;
            return [static::MULTIPLIER => 2, static::INDEX => $index];
        }
        if (\stripos($match[2], 'n') === false) {
            // if there is a multiplier
            $index = (int)\str_replace(' ', '', $match[2]);
            return [static::INDEX => $index];
        }

        if (isset($match[3])) {
            $multipleTerm = \str_replace($match[3], '', $match[2]);
            $index = (int)\str_replace(' ', '', $match[3]);
        } else {
            $multipleTerm = $match[2];
            $index = 0;
        }

        $multiplier = \str_ireplace('n', '', $multipleTerm);

        if ($multiplier === '') {
            $multiplier = 1;
        } elseif ($multiplier === '0') {
            return [static::INDEX => $index];
        } else {
            $multiplier = (int)$multiplier;
        }

        while ($index < 0) {
            $index += \abs($multiplier);
        }

        return [static::MULTIPLIER => $multiplier, static::INDEX => $index];
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
     * @throws \InvalidArgumentException
     */
    private function getNodesToExclude(\DOMXPath $xPath)
    {
        $excludedNodes = [];
        foreach (\array_keys($this->excludedSelectors) as $selectorToExclude) {
            try {
                $matchingNodes = $xPath->query($this->translateCssToXpath($selectorToExclude));
            } catch (\InvalidArgumentException $e) {
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
     * Handles invalid xPath expression warnings, generated during the process() method,
     * during querying \DOMDocument and trigger an \InvalidArgumentException with an invalid selector
     * or \RuntimeException, depending on the source of the warning.
     *
     * @param int $type
     * @param string $message
     * @param string $file
     * @param int $line
     * @param array $context
     *
     * @return bool always false
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function handleXpathQueryWarnings(// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
        $type,
        $message,
        $file,
        $line,
        array $context
    ) {
        $selector = '';
        if (isset($context['cssRule']['selector'])) {
            // warnings generated by invalid/unrecognized selectors in method process()
            $selector = $context['cssRule']['selector'];
        } elseif (isset($context['selectorToExclude'])) {
            // warnings generated by invalid/unrecognized selectors in method getNodesToExclude()
            $selector = $context['selectorToExclude'];
        } elseif (isset($context['cssSelector'])) {
            // warnings generated by invalid/unrecognized selectors in method existsMatchForCssSelector()
            $selector = $context['cssSelector'];
        }

        if ($selector !== '') {
            throw new \InvalidArgumentException(
                \sprintf('%1$s in selector >> %2$s << in %3$s on line %4$u', $message, $selector, $file, $line),
                1509279985
            );
        }

        // Catches eventual warnings generated by method getAllNodesWithStyleAttribute()
        if (isset($context['xPath'])) {
            throw new \RuntimeException(
                \sprintf('%1$s in %2$s on line %3$u', $message, $file, $line),
                1509280067
            );
        }

        // the normal error handling continues when handler return false
        return false;
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
