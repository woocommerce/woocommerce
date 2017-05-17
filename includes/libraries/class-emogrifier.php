<?php
/**
 * This class provides functions for converting CSS styles into inline style attributes in your HTML code.
 *
 * For more information, please see the README.md file.
 *
 * @version 1.2.0
 *
 * @author Cameron Brooks
 * @author Jaime Prado
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Roman Ožana <ozana@omdesign.cz>
 * @author Sander Kruger <s.kruger@invessel.com>
 */
// @codingStandardsIgnoreFile
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
	 * @var string
	 */
	const CONTENT_TYPE_META_TAG = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';

	/**
	 * @var string
	 */
	const DEFAULT_DOCUMENT_TYPE = '<!DOCTYPE html>';

	/**
	 * @var string
	 */
	private $html = '';

	/**
	 * @var string
	 */
	private $css = '';

	/**
	 * @var bool[]
	 */
	private $excludedSelectors = array();

	/**
	 * @var string[]
	 */
	private $unprocessableHtmlTags = array( 'wbr' );

	/**
	 * @var bool[]
	 */
	private $allowedMediaTypes = array( 'all' => true, 'screen' => true, 'print' => true );

	/**
	 * @var mixed[]
	 */
	private $caches = array(
		self::CACHE_KEY_CSS => array(),
		self::CACHE_KEY_SELECTOR => array(),
		self::CACHE_KEY_XPATH => array(),
		self::CACHE_KEY_CSS_DECLARATIONS_BLOCK => array(),
		self::CACHE_KEY_COMBINED_STYLES => array(),
	);

	/**
	 * the visited nodes with the XPath paths as array keys
	 *
	 * @var \DOMElement[]
	 */
	private $visitedNodes = array();

	/**
	 * the styles to apply to the nodes with the XPath paths as array keys for the outer array
	 * and the attribute names/values as key/value pairs for the inner array
	 *
	 * @var string[][]
	 */
	private $styleAttributesForNodes = array();

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
	private $shouldKeepInvisibleNodes = true;

	/**
	 * @var string[]
	 */
	private $xPathRules = array(
		// child
		'/\\s*>\\s*/'                              => '/',
		// adjacent sibling
		'/\\s+\\+\\s+/'                            => '/following-sibling::*[1]/self::',
		// descendant
		'/\\s+(?=.*[^\\]]{1}$)/'                   => '//',
		// :first-child
		'/([^\\/]+):first-child/i'                 => '*[1]/self::\\1',
		// :last-child
		'/([^\\/]+):last-child/i'                  => '*[last()]/self::\\1',
		// attribute only
		'/^\\[(\\w+|\\w+\\=[\'"]?\\w+[\'"]?)\\]/'  => '*[@\\1]',
		// attribute
		'/(\\w)\\[(\\w+)\\]/'                      => '\\1[@\\2]',
		// exact attribute
		'/(\\w)\\[(\\w+)\\=[\'"]?([\\w\\s]+)[\'"]?\\]/' => '\\1[@\\2="\\3"]',
		// element attribute~=
		'/([\\w\\*]+)\\[(\\w+)[\\s]*\\~\\=[\\s]*[\'"]?([\\w-_\\/]+)[\'"]?\\]/'
			=> '\\1[contains(concat(" ", @\\2, " "), concat(" ", "\\3", " "))]',
		// element attribute^=
		'/([\\w\\*]+)\\[(\\w+)[\\s]*\\^\\=[\\s]*[\'"]?([\\w-_\\/]+)[\'"]?\\]/' => '\\1[starts-with(@\\2, "\\3")]',
		// element attribute*=
		'/([\\w\\*]+)\\[(\\w+)[\\s]*\\*\\=[\\s]*[\'"]?([\\w-_\\s\\/:;]+)[\'"]?\\]/' => '\\1[contains(@\\2, "\\3")]',
		// element attribute$=
		'/([\\w\\*]+)\\[(\\w+)[\\s]*\\$\\=[\\s]*[\'"]?([\\w-_\\s\\/]+)[\'"]?\\]/'
			=> '\\1[substring(@\\2, string-length(@\\2) - string-length("\\3") + 1) = "\\3"]',
		// element attribute|=
		'/([\\w\\*]+)\\[(\\w+)[\\s]*\\|\\=[\\s]*[\'"]?([\\w-_\\s\\/]+)[\'"]?\\]/'
			=> '\\1[@\\2="\\3" or starts-with(@\\2, concat("\\3", "-"))]',
	);

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
	private $cssToHtmlMap = array(
		'background-color' => array(
			'attribute' => 'bgcolor',
		),
		'text-align' => array(
			'attribute' => 'align',
			'nodes' => array('p', 'div', 'td'),
			'values' => array('left', 'right', 'center', 'justify'),
		),
		'float' => array(
			'attribute' => 'align',
			'nodes' => array('table', 'img'),
			'values' => array('left', 'right'),
		),
		'border-spacing' => array(
			'attribute' => 'cellspacing',
			'nodes' => array('table'),
		),
	);

	public static $_media = '';

	/**
	 * The constructor.
	 *
	 * @param string $html the HTML to emogrify, must be UTF-8-encoded
	 * @param string $css the CSS to merge, must be UTF-8-encoded
	 */
	public function __construct($html = '', $css = '')
	{
		$this->setHtml($html);
		$this->setCss($css);
	}

	/**
	 * The destructor.
	 */
	public function __destruct()
	{
		$this->purgeVisitedNodes();
	}

	/**
	 * Sets the HTML to emogrify.
	 *
	 * @param string $html the HTML to emogrify, must be UTF-8-encoded
	 *
	 * @return void
	 */
	public function setHtml($html)
	{
		$this->html = $html;
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
	 * Applies $this->css to $this->html and returns the HTML with the CSS
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
		if ($this->html === '') {
			throw new BadMethodCallException('Please set some HTML first before calling emogrify.', 1390393096);
		}

		self::$_media = ''; // reset.

		$xmlDocument = $this->createXmlDocument();
		$this->process($xmlDocument);

		return $xmlDocument->saveHTML();
	}

	/**
	 * Applies $this->css to $this->html and returns only the HTML content
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
		if ($this->html === '') {
			throw new BadMethodCallException('Please set some HTML first before calling emogrify.', 1390393096);
		}

		$xmlDocument = $this->createXmlDocument();
		$this->process($xmlDocument);

		$innerDocument = new DOMDocument();
		foreach ($xmlDocument->documentElement->getElementsByTagName('body')->item(0)->childNodes as $childNode) {
			$innerDocument->appendChild($innerDocument->importNode($childNode, true));
		}

		return html_entity_decode($innerDocument->saveHTML());
	}

	/**
	 * Applies $this->css to $xmlDocument.
	 *
	 * This method places the CSS inline.
	 *
	 * @param \DOMDocument $xmlDocument
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function process(DOMDocument $xmlDocument)
	{
		$xPath = new DOMXPath($xmlDocument);
		$this->clearAllCaches();

		// Before be begin processing the CSS file, parse the document and normalize all existing CSS attributes.
		// This changes 'DISPLAY: none' to 'display: none'.
		// We wouldn't have to do this if DOMXPath supported XPath 2.0.
		// Also store a reference of nodes with existing inline styles so we don't overwrite them.
		$this->purgeVisitedNodes();

		set_error_handler(array($this, 'handleXpathError'), E_WARNING);

		$nodesWithStyleAttributes = $xPath->query('//*[@style]');
		if ($nodesWithStyleAttributes !== false) {
			/** @var \DOMElement $node */
			foreach ($nodesWithStyleAttributes as $node) {
				if ($this->isInlineStyleAttributesParsingEnabled) {
					$this->normalizeStyleAttributes($node);
				} else {
					$node->removeAttribute('style');
				}
			}
		}

		// grab any existing style blocks from the html and append them to the existing CSS
		// (these blocks should be appended so as to have precedence over conflicting styles in the existing CSS)
		$allCss = $this->css;

		if ($this->isStyleBlocksParsingEnabled) {
			$allCss .= $this->getCssFromAllStyleNodes($xPath);
		}

		$cssParts = $this->splitCssAndMediaQuery($allCss);
		$excludedNodes = $this->getNodesToExclude($xPath);
		$cssRules = $this->parseCssRules($cssParts['css']);
		foreach ($cssRules as $cssRule) {
			// query the body for the xpath selector
			$nodesMatchingCssSelectors = $xPath->query($this->translateCssToXpath($cssRule['selector']));
			// ignore invalid selectors
			if ($nodesMatchingCssSelectors === false) {
				continue;
			}

			/** @var \DOMElement $node */
			foreach ($nodesMatchingCssSelectors as $node) {
				if (in_array($node, $excludedNodes, true)) {
					continue;
				}

				// if it has a style attribute, get it, process it, and append (overwrite) new stuff
				if ($node->hasAttribute('style')) {
					// break it up into an associative array
					$oldStyleDeclarations = $this->parseCssDeclarationsBlock($node->getAttribute('style'));
				} else {
					$oldStyleDeclarations = array();
				}
				$newStyleDeclarations = $this->parseCssDeclarationsBlock($cssRule['declarationsBlock']);
				if ($this->shouldMapCssToHtml) {
					$this->mapCssToHtmlAttributes($newStyleDeclarations, $node);
				}
				$node->setAttribute(
					'style',
					$this->generateStyleStringFromDeclarationsArrays($oldStyleDeclarations, $newStyleDeclarations)
				);
			}
		}

		restore_error_handler();

		if ($this->isInlineStyleAttributesParsingEnabled) {
			$this->fillStyleAttributesWithMergedStyles();
		}

		if ($this->shouldKeepInvisibleNodes) {
			$this->removeInvisibleNodes($xPath);
		}

		$this->copyCssWithMediaToStyleNode($xmlDocument, $xPath, $cssParts['media']);
	}

	/**
	 * Applies $styles to $node.
	 *
	 * This method maps CSS styles to HTML attributes and adds those to the
	 * node.
	 *
	 * @param string[] $styles the new CSS styles taken from the global styles to be applied to this node
	 * @param \DOMNode $node   node to apply styles to
	 *
	 * @return void
	 */
	private function mapCssToHtmlAttributes(array $styles, DOMNode $node)
	{
		foreach ($styles as $property => $value) {
			// Strip !important indicator
			$value = trim(str_replace('!important', '', $value));
			$this->mapCssToHtmlAttribute($property, $value, $node);
		}
	}

	/**
	 * Tries to apply the CSS style to $node as an attribute.
	 *
	 * This method maps a CSS rule to HTML attributes and adds those to the node.
	 *
	 * @param string $property the name of the CSS property to map
	 * @param string $value    the value of the style rule to map
	 * @param \DOMNode $node   node to apply styles to
	 *
	 * @return void
	 */
	private function mapCssToHtmlAttribute($property, $value, DOMNode $node)
	{
		if (!$this->mapSimpleCssProperty($property, $value, $node)) {
			$this->mapComplexCssProperty($property, $value, $node);
		}
	}

	/**
	 * Looks up the CSS property in the mapping table and maps it if it matches the conditions.
	 *
	 * @param string $property the name of the CSS property to map
	 * @param string $value    the value of the style rule to map
	 * @param \DOMNode $node   node to apply styles to
	 *
	 * @return bool true if the property cab be mapped using the simple mapping table
	 */
	private function mapSimpleCssProperty($property, $value, DOMNode $node)
	{
		if (!isset($this->cssToHtmlMap[$property])) {
			return false;
		}

		$mapping = $this->cssToHtmlMap[$property];
		$nodesMatch = !isset($mapping['nodes']) || in_array($node->nodeName, $mapping['nodes'], true);
		$valuesMatch = !isset($mapping['values']) || in_array($value, $mapping['values'], true);
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
	 * @param string $value    the value of the style rule to map
	 * @param \DOMNode $node   node to apply styles to
	 *
	 * @return void
	 */
	private function mapComplexCssProperty($property, $value, DOMNode $node)
	{
		$nodeName = $node->nodeName;
		$isTable = $nodeName === 'table';
		$isImage = $nodeName === 'img';
		$isTableOrImage = $isTable || $isImage;

		switch ($property) {
			case 'background':
				// Parse out the color, if any
				$styles = explode(' ', $value);
				$first = $styles[0];
				if (!is_numeric(substr($first, 0, 1)) && substr($first, 0, 3) !== 'url') {
					// This is not a position or image, assume it's a color
					$node->setAttribute('bgcolor', $first);
				}
				break;
			case 'width':
				// intentional fall-through
			case 'height':
				// Only parse values in px and %, but not values like "auto".
				if (preg_match('/^\d+(px|%)$/', $value)) {
					// Remove 'px'. This regex only conserves numbers and %
					$number = preg_replace('/[^0-9.%]/', '', $value);
					$node->setAttribute($property, $number);
				}
				break;
			case 'margin':
				if ($isTableOrImage) {
					$margins = $this->parseCssShorthandValue($value);
					if ($margins['left'] === 'auto' && $margins['right'] === 'auto') {
						$node->setAttribute('align', 'center');
					}
				}
				break;
			case 'border':
				if ($isTableOrImage) {
					if ($value === 'none' || $value === '0') {
						$node->setAttribute('border', '0');
					}
				}
				break;
			default:
		}
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
		$values = preg_split('/\\s+/', $value);

		$css = array();
		$css['top'] = $values[0];
		$css['right'] = (count($values) > 1) ? $values[1] : $css['top'];
		$css['bottom'] = (count($values) > 2) ? $values[2] : $css['top'];
		$css['left'] = (count($values) > 3) ? $values[3] : $css['right'];

		return $css;
	}

	/**
	 * Extracts and parses the individual rules from a CSS string.
	 *
	 * @param string $css a string of raw CSS code
	 *
	 * @return string[][] an array of string sub-arrays with the keys
	 *         "selector" (the CSS selector(s), e.g., "*" or "h1"),
	 *         "declarationsBLock" (the semicolon-separated CSS declarations for that selector(s),
	 *         e.g., "color: red; height: 4px;"),
	 *         and "line" (the line number e.g. 42)
	 */
	private function parseCssRules($css)
	{
		$cssKey = md5($css);
		if (!isset($this->caches[self::CACHE_KEY_CSS][$cssKey])) {
			// process the CSS file for selectors and definitions
			preg_match_all('/(?:^|[\\s^{}]*)([^{]+){([^}]*)}/mis', $css, $matches, PREG_SET_ORDER);

			$cssRules = array();
			/** @var string[] $cssRule */
			foreach ($matches as $key => $cssRule) {
				$cssDeclaration = trim($cssRule[2]);
				if ($cssDeclaration === '') {
					continue;
				}

				$selectors = explode(',', $cssRule[1]);
				foreach ($selectors as $selector) {
					// don't process pseudo-elements and behavioral (dynamic) pseudo-classes;
					// only allow structural pseudo-classes
					$hasPseudoElement = strpos($selector, '::') !== false;
					$hasAnyPseudoClass = (bool) preg_match('/:[a-zA-Z]/', $selector);
					$hasSupportedPseudoClass = (bool) preg_match('/:\\S+\\-(child|type\\()/i', $selector);
					if ($hasPseudoElement || ($hasAnyPseudoClass && !$hasSupportedPseudoClass)) {
						continue;
					}

					$cssRules[] = array(
						'selector' => trim($selector),
						'declarationsBlock' => $cssDeclaration,
						// keep track of where it appears in the file, since order is important
						'line' => $key,
					);
				}
			}

			usort($cssRules, array($this, 'sortBySelectorPrecedence'));

			$this->caches[self::CACHE_KEY_CSS][$cssKey] = $cssRules;
		}

		return $this->caches[self::CACHE_KEY_CSS][$cssKey];
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
	 */
	public function disableInvisibleNodeRemoval()
	{
		$this->shouldKeepInvisibleNodes = false;
	}

	/**
	 * Enables the attachment/override of HTML attributes for which a
	 * corresponding CSS property has been set.
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
		$this->clearCache(self::CACHE_KEY_CSS);
		$this->clearCache(self::CACHE_KEY_SELECTOR);
		$this->clearCache(self::CACHE_KEY_XPATH);
		$this->clearCache(self::CACHE_KEY_CSS_DECLARATIONS_BLOCK);
		$this->clearCache(self::CACHE_KEY_COMBINED_STYLES);
	}

	/**
	 * Clears a single cache by key.
	 *
	 * @param int $key the cache key, must be CACHE_KEY_CSS, CACHE_KEY_SELECTOR, CACHE_KEY_XPATH
	 *                 or CACHE_KEY_CSS_DECLARATION_BLOCK
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException
	 */
	private function clearCache($key)
	{
		$allowedCacheKeys = array(
			self::CACHE_KEY_CSS,
			self::CACHE_KEY_SELECTOR,
			self::CACHE_KEY_XPATH,
			self::CACHE_KEY_CSS_DECLARATIONS_BLOCK,
			self::CACHE_KEY_COMBINED_STYLES,
		);
		if (!in_array($key, $allowedCacheKeys, true)) {
			throw new InvalidArgumentException('Invalid cache key: ' . $key, 1391822035);
		}

		$this->caches[$key] = array();
	}

	/**
	 * Purges the visited nodes.
	 *
	 * @return void
	 */
	private function purgeVisitedNodes()
	{
		$this->visitedNodes = array();
		$this->styleAttributesForNodes = array();
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
		$key = array_search($tagName, $this->unprocessableHtmlTags, true);
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
	private function removeInvisibleNodes(DOMXPath $xPath)
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
			if ($node->parentNode && is_callable(array($node->parentNode, 'removeChild'))) {
				$node->parentNode->removeChild($node);
			}
		}
	}

	private function normalizeStyleAttributes_callback( $m ) {
		return strtolower( $m[0] );
	}

	/**
	 * Normalizes the value of the "style" attribute and saves it.
	 *
	 * @param \DOMElement $node
	 *
	 * @return void
	 */
	private function normalizeStyleAttributes(DOMElement $node)
	{
		$normalizedOriginalStyle = preg_replace_callback(
			'/[A-z\\-]+(?=\\:)/S',
			array( $this, 'normalizeStyleAttributes_callback' ),
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
		$combinedStyles = array_merge($oldStyles, $newStyles);
		$cacheKey = serialize($combinedStyles);
		if (isset($this->caches[self::CACHE_KEY_COMBINED_STYLES][$cacheKey])) {
			return $this->caches[self::CACHE_KEY_COMBINED_STYLES][$cacheKey];
		}

		foreach ($oldStyles as $attributeName => $attributeValue) {
			if (!isset($newStyles[$attributeName])) {
				continue;
			}

			$newAttributeValue = $newStyles[$attributeName];
			if ($this->attributeValueIsImportant($attributeValue)
				&& !$this->attributeValueIsImportant($newAttributeValue)
			) {
				$combinedStyles[$attributeName] = $attributeValue;
			}
		}

		$style = '';
		foreach ($combinedStyles as $attributeName => $attributeValue) {
			$style .= strtolower(trim($attributeName)) . ': ' . trim($attributeValue) . '; ';
		}
		$trimmedStyle = rtrim($style);

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
		return strtolower(substr(trim($attributeValue), -10)) === '!important';
	}

	/**
	 * Applies $css to $xmlDocument, limited to the media queries that actually apply to the document.
	 *
	 * @param \DOMDocument $xmlDocument the document to match against
	 * @param \DOMXPath $xPath
	 * @param string $css a string of CSS
	 *
	 * @return void
	 */
	private function copyCssWithMediaToStyleNode(DOMDocument $xmlDocument, DOMXPath $xPath, $css)
	{
		if ($css === '') {
			return;
		}

		$mediaQueriesRelevantForDocument = array();

		foreach ($this->extractMediaQueriesFromCss($css) as $mediaQuery) {
			foreach ($this->parseCssRules($mediaQuery['css']) as $selector) {
				if ($this->existsMatchForCssSelector($xPath, $selector['selector'])) {
					$mediaQueriesRelevantForDocument[] = $mediaQuery['query'];
					break;
				}
			}
		}

		$this->addStyleElementToDocument($xmlDocument, implode($mediaQueriesRelevantForDocument));
	}

	/**
	 * Extracts the media queries from $css while skipping empty media queries.
	 *
	 * @param string $css
	 *
	 * @return string[][] numeric array with string sub-arrays with the keys "css" and "query"
	 */
	private function extractMediaQueriesFromCss($css)
	{
		preg_match_all('/@media\\b[^{]*({((?:[^{}]+|(?1))*)})/', $css, $rawMediaQueries, PREG_SET_ORDER);
		$parsedQueries = array();

		foreach ($rawMediaQueries as $mediaQuery) {
			if ($mediaQuery[2] !== '') {
				$parsedQueries[] = array(
					'css'   => $mediaQuery[2],
					'query' => $mediaQuery[0],
				);
			}
		}

		return $parsedQueries;
	}

	/**
	 * Checks whether there is at least one matching element for $cssSelector.
	 *
	 * @param \DOMXPath $xPath
	 * @param string $cssSelector
	 *
	 * @return bool
	 */
	private function existsMatchForCssSelector(DOMXPath $xPath, $cssSelector)
	{
		$nodesMatchingSelector = $xPath->query($this->translateCssToXpath($cssSelector));

		return $nodesMatchingSelector !== false && $nodesMatchingSelector->length !== 0;
	}

	/**
	 * Returns CSS content.
	 *
	 * @param \DOMXPath $xPath
	 *
	 * @return string
	 */
	private function getCssFromAllStyleNodes(DOMXPath $xPath)
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
	 * Adds a style element with $css to $document.
	 *
	 * This method is protected to allow overriding.
	 *
	 * @see https://github.com/jjriv/emogrifier/issues/103
	 *
	 * @param \DOMDocument $document
	 * @param string $css
	 *
	 * @return void
	 */
	protected function addStyleElementToDocument(DOMDocument $document, $css)
	{
		$styleElement = $document->createElement('style', $css);
		$styleAttribute = $document->createAttribute('type');
		$styleAttribute->value = 'text/css';
		$styleElement->appendChild($styleAttribute);

		$head = $this->getOrCreateHeadElement($document);
		$head->appendChild($styleElement);
	}

	/**
	 * Returns the existing or creates a new head element in $document.
	 *
	 * @param \DOMDocument $document
	 *
	 * @return \DOMNode the head element
	 */
	private function getOrCreateHeadElement(DOMDocument $document)
	{
		$head = $document->getElementsByTagName('head')->item(0);

		if ($head === null) {
			$head = $document->createElement('head');
			$html = $document->getElementsByTagName('html')->item(0);
			$html->insertBefore($head, $document->getElementsByTagName('body')->item(0));
		}

		return $head;
	}

	/**
	 * Splits input CSS code to an array where:
	 *
	 * - key "css" will be contains clean CSS code
	 * - key "media" will be contains all valuable media queries
	 *
	 * Example:
	 *
	 * The CSS code
	 *
	 *   "@import "file.css"; h1 { color:red; } @media { h1 {}} @media tv { h1 {}}"
	 *
	 * will be parsed into the following array:
	 *
	 *   "css" => "h1 { color:red; }"
	 *   "media" => "@media { h1 {}}"
	 *
	 * @param string $css
	 *
	 * @return string[]
	 */
	private function splitCssAndMediaQuery($css)
	{
		$cssWithoutComments = preg_replace('/\\/\\*.*\\*\\//sU', '', $css);

		$mediaTypesExpression = '';
		if (!empty($this->allowedMediaTypes)) {
			$mediaTypesExpression = '|' . implode('|', array_keys($this->allowedMediaTypes));
		}

		$cssForAllowedMediaTypes = preg_replace_callback(
			'#@media\\s+(?:only\\s)?(?:[\\s{\\(]' . $mediaTypesExpression . ')\\s?[^{]+{.*}\\s*}\\s*#misU',
			array( $this, '_media_concat' ),
			$cssWithoutComments
		);

		// filter the CSS
		$search = array(
			'import directives' => '/^\\s*@import\\s[^;]+;/misU',
			'remaining media enclosures' => '/^\\s*@media\\s[^{]+{(.*)}\\s*}\\s/misU',
		);

		$cleanedCss = preg_replace($search, '', $cssForAllowedMediaTypes);

		return array('css' => $cleanedCss, 'media' => self::$_media);
	}

	private function _media_concat( $matches ) {
		self::$_media .= $matches[0];
	}

	/**
	 * Creates a DOMDocument instance with the current HTML.
	 *
	 * @return \DOMDocument
	 */
	private function createXmlDocument()
	{
		$xmlDocument = new DOMDocument;
		$xmlDocument->encoding = 'UTF-8';
		$xmlDocument->strictErrorChecking = false;
		$xmlDocument->formatOutput = true;
		$libXmlState = libxml_use_internal_errors(true);
		$xmlDocument->loadHTML($this->getUnifiedHtml());
		libxml_clear_errors();
		libxml_use_internal_errors($libXmlState);
		$xmlDocument->normalizeDocument();

		return $xmlDocument;
	}

	/**
	 * Returns the HTML with the unprocessable HTML tags removed and
	 * with added document type and Content-Type meta tag if needed.
	 *
	 * @return string the unified HTML
	 *
	 * @throws \BadMethodCallException
	 */
	private function getUnifiedHtml()
	{
		$htmlWithoutUnprocessableTags = $this->removeUnprocessableTags($this->html);
		$htmlWithDocumentType = $this->ensureDocumentType($htmlWithoutUnprocessableTags);

		return $this->addContentTypeMetaTag($htmlWithDocumentType);
	}

	/**
	 * Removes the unprocessable tags from $html (if this feature is enabled).
	 *
	 * @param string $html
	 *
	 * @return string the reworked HTML with the unprocessable tags removed
	 */
	private function removeUnprocessableTags($html)
	{
		if (empty($this->unprocessableHtmlTags)) {
			return $html;
		}

		$unprocessableHtmlTags = implode('|', $this->unprocessableHtmlTags);

		return preg_replace(
			'/<\\/?(' . $unprocessableHtmlTags . ')[^>]*>/i',
			'',
			$html
		);
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
		$hasDocumentType = stripos($html, '<!DOCTYPE') !== false;
		if ($hasDocumentType) {
			return $html;
		}

		return self::DEFAULT_DOCUMENT_TYPE . $html;
	}

	/**
	 * Adds a Content-Type meta tag for the charset.
	 *
	 * @param string $html
	 *
	 * @return string the HTML with the meta tag added
	 */
	private function addContentTypeMetaTag($html)
	{
		$hasContentTypeMetaTag = stristr($html, 'Content-Type') !== false;
		if ($hasContentTypeMetaTag) {
			return $html;
		}

		// We are trying to insert the meta tag to the right spot in the DOM.
		// If we just prepended it to the HTML, we would lose attributes set to the HTML tag.
		$hasHeadTag = stripos($html, '<head') !== false;
		$hasHtmlTag = stripos($html, '<html') !== false;

		if ($hasHeadTag) {
			$reworkedHtml = preg_replace('/<head(.*?)>/i', '<head$1>' . self::CONTENT_TYPE_META_TAG, $html);
		} elseif ($hasHtmlTag) {
			$reworkedHtml = preg_replace(
				'/<html(.*?)>/i',
				'<html$1><head>' . self::CONTENT_TYPE_META_TAG . '</head>',
				$html
			);
		} else {
			$reworkedHtml = self::CONTENT_TYPE_META_TAG . $html;
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
		$selectorKey = md5($selector);
		if (!isset($this->caches[self::CACHE_KEY_SELECTOR][$selectorKey])) {
			$precedence = 0;
			$value = 100;
			// ids: worth 100, classes: worth 10, elements: worth 1
			$search = array('\\#','\\.','');

			foreach ($search as $s) {
				if (trim($selector) === '') {
					break;
				}
				$number = 0;
				$selector = preg_replace('/' . $s . '\\w+/', '', $selector, -1, $number);
				$precedence += ($value * $number);
				$value /= 10;
			}
			$this->caches[self::CACHE_KEY_SELECTOR][$selectorKey] = $precedence;
		}

		return $this->caches[self::CACHE_KEY_SELECTOR][$selectorKey];
	}

	private function translateCssToXpath_callback( $matches ) {
		return strtolower($matches[0]);
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
		$lowercasePaddedSelector = preg_replace_callback(
			'/\\s+\\w+\\s+/',
			array( $this, 'translateCssToXpath_callback' ),
			$paddedSelector
		);

		$trimmedLowercaseSelector = trim($lowercasePaddedSelector);
		$xPathKey = md5($trimmedLowercaseSelector);
		if (!isset($this->caches[self::CACHE_KEY_XPATH][$xPathKey])) {
			$roughXpath = '//' . preg_replace(
				array_keys($this->xPathRules),
				$this->xPathRules,
				$trimmedLowercaseSelector
			);
			$xPathWithIdAttributeMatchers = preg_replace_callback(
				self::ID_ATTRIBUTE_MATCHER,
				array($this, 'matchIdAttributes'),
				$roughXpath
			);
			$xPathWithIdAttributeAndClassMatchers = preg_replace_callback(
				self::CLASS_ATTRIBUTE_MATCHER,
				array($this, 'matchClassAttributes'),
				$xPathWithIdAttributeMatchers
			);

			// Advanced selectors are going to require a bit more advanced emogrification.
			// When we required PHP 5.3, we could do this with closures.
			$xPathWithIdAttributeAndClassMatchers = preg_replace_callback(
				'/([^\\/]+):nth-child\\(\\s*(odd|even|[+\\-]?\\d|[+\\-]?\\d?n(\\s*[+\\-]\\s*\\d)?)\\s*\\)/i',
				array($this, 'translateNthChild'),
				$xPathWithIdAttributeAndClassMatchers
			);
			$finalXpath = preg_replace_callback(
				'/([^\\/]+):nth-of-type\\(\s*(odd|even|[+\\-]?\\d|[+\\-]?\\d?n(\\s*[+\\-]\\s*\\d)?)\\s*\\)/i',
				array($this, 'translateNthOfType'),
				$xPathWithIdAttributeAndClassMatchers
			);

			$this->caches[self::CACHE_KEY_SELECTOR][$xPathKey] = $finalXpath;
		}
		return $this->caches[self::CACHE_KEY_SELECTOR][$xPathKey];
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
	 * @return string
	 */
	private function matchClassAttributes(array $match)
	{
		return ($match[1] !== '' ? $match[1] : '*') . '[contains(concat(" ",@class," "),concat(" ","' .
			implode(
				'"," "))][contains(concat(" ",@class," "),concat(" ","',
				explode('.', substr($match[2], 1))
			) . '"," "))]';
	}

	/**
	 * @param string[] $match
	 *
	 * @return string
	 */
	private function translateNthChild(array $match)
	{
		$parseResult = $this->parseNth($match);

		if (isset($parseResult[self::MULTIPLIER])) {
			if ($parseResult[self::MULTIPLIER] < 0) {
				$parseResult[self::MULTIPLIER] = abs($parseResult[self::MULTIPLIER]);
				$xPathExpression = sprintf(
					'*[(last() - position()) mod %u = %u]/self::%s',
					$parseResult[self::MULTIPLIER],
					$parseResult[self::INDEX],
					$match[1]
				);
			} else {
				$xPathExpression = sprintf(
					'*[position() mod %u = %u]/self::%s',
					$parseResult[self::MULTIPLIER],
					$parseResult[self::INDEX],
					$match[1]
				);
			}
		} else {
			$xPathExpression = sprintf('*[%u]/self::%s', $parseResult[self::INDEX], $match[1]);
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

		if (isset($parseResult[self::MULTIPLIER])) {
			if ($parseResult[self::MULTIPLIER] < 0) {
				$parseResult[self::MULTIPLIER] = abs($parseResult[self::MULTIPLIER]);
				$xPathExpression = sprintf(
					'%s[(last() - position()) mod %u = %u]',
					$match[1],
					$parseResult[self::MULTIPLIER],
					$parseResult[self::INDEX]
				);
			} else {
				$xPathExpression = sprintf(
					'%s[position() mod %u = %u]',
					$match[1],
					$parseResult[self::MULTIPLIER],
					$parseResult[self::INDEX]
				);
			}
		} else {
			$xPathExpression = sprintf('%s[%u]', $match[1], $parseResult[self::INDEX]);
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
		if (in_array(strtolower($match[2]), array('even', 'odd'), true)) {
			// we have "even" or "odd"
			$index = strtolower($match[2]) === 'even' ? 0 : 1;
			return array(self::MULTIPLIER => 2, self::INDEX => $index);
		}
		if (stripos($match[2], 'n') === false) {
			// if there is a multiplier
			$index = (int) str_replace(' ', '', $match[2]);
			return array(self::INDEX => $index);
		}

		if (isset($match[3])) {
			$multipleTerm = str_replace($match[3], '', $match[2]);
			$index = (int) str_replace(' ', '', $match[3]);
		} else {
			$multipleTerm = $match[2];
			$index = 0;
		}

		$multiplier = str_ireplace('n', '', $multipleTerm);

		if ($multiplier === '') {
			$multiplier = 1;
		} elseif ($multiplier === '0') {
			return array(self::INDEX => $index);
		} else {
			$multiplier = (int) $multiplier;
		}

		while ($index < 0) {
			$index += abs($multiplier);
		}

		return array(self::MULTIPLIER => $multiplier, self::INDEX => $index);
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

		$properties = array();
		$declarations = preg_split('/;(?!base64|charset)/', $cssDeclarationsBlock);

		foreach ($declarations as $declaration) {
			$matches = array();
			if (!preg_match('/^([A-Za-z\\-]+)\\s*:\\s*(.+)$/', trim($declaration), $matches)) {
				continue;
			}

			$propertyName = strtolower($matches[1]);
			$propertyValue = $matches[2];
			$properties[$propertyName] = $propertyValue;
		}
		$this->caches[self::CACHE_KEY_CSS_DECLARATIONS_BLOCK][$cssDeclarationsBlock] = $properties;

		return $properties;
	}

	/**
	 * Find the nodes that are not to be emogrified.
	 *
	 * @param \DOMXPath $xPath
	 *
	 * @return \DOMElement[]
	 */
	private function getNodesToExclude(DOMXPath $xPath)
	{
		$excludedNodes = array();
		foreach (array_keys($this->excludedSelectors) as $selectorToExclude) {
			foreach ($xPath->query($this->translateCssToXpath($selectorToExclude)) as $node) {
				$excludedNodes[] = $node;
			}
		}

		return $excludedNodes;
	}

	/**
	 * Handles invalid xPath expression warnings, generated by process() method,
	 * during querying \DOMDocument and trigger \InvalidArgumentException
	 * with invalid selector.
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
	 */
	public function handleXpathError($type, $message, $file, $line, array $context)
	{
		if ($type === E_WARNING && isset($context['cssRule']['selector'])) {
			throw new InvalidArgumentException(
				sprintf(
					'%s in selector >> %s << in %s on line %s',
					$message,
					$context['cssRule']['selector'],
					$file,
					$line
				)
			);
		}

		// the normal error handling continues when handler return false
		return false;
	}
}
