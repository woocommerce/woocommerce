<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Utils;

/**
 * This class should guarantee that our work with the DOMDocument is unified and safe.
 */
class Dom_Document_Helper {
	/**
	 * Instance of the DOMDocument.
	 *
	 * @var \DOMDocument
	 */
	private \DOMDocument $dom;

	/**
	 * Constructor.
	 *
	 * @param string $html_content The HTML content to load.
	 */
	public function __construct( string $html_content ) {
		$this->load_html( $html_content );
	}

	/**
	 * Loads the given HTML content into the DOMDocument.
	 *
	 * @param string $html_content The HTML content to load.
	 */
	private function load_html( string $html_content ): void {
		libxml_use_internal_errors( true );
		$this->dom = new \DOMDocument();
		if ( ! empty( $html_content ) ) {
			// prefixing the content with the XML declaration to force the input encoding to UTF-8.
			$this->dom->loadHTML( '<?xml encoding="UTF-8">' . $html_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		}
		libxml_clear_errors();
	}

	/**
	 * Searches for the first appearance of the given tag name.
	 *
	 * @param string $tag_name The tag name to search for.
	 */
	public function find_element( string $tag_name ): ?\DOMElement {
		$elements = $this->dom->getElementsByTagName( $tag_name );
		return $elements->item( 0 ) ? $elements->item( 0 ) : null;
	}

	/**
	 * Returns every element matching the given tag name, in document order.
	 *
	 * @param string $tag_name The tag name to search for.
	 * @return array<int, \DOMElement>
	 */
	public function find_elements( string $tag_name ): array {
		$elements = array();
		foreach ( $this->dom->getElementsByTagName( $tag_name ) as $element ) {
			if ( $element instanceof \DOMElement ) {
				$elements[] = $element;
			}
		}
		return $elements;
	}

	/**
	 * Returns the value of the given attribute from the given element.
	 *
	 * @param \DOMElement $element The element to get the attribute value from.
	 * @param string      $attribute The attribute to get the value from.
	 */
	public function get_attribute_value( \DOMElement $element, string $attribute ): string {
		return $element->hasAttribute( $attribute ) ? $element->getAttribute( $attribute ) : '';
	}

	/**
	 * Searches for the first appearance of the given tag name and returns the value of specified attribute.
	 *
	 * @param string $tag_name The tag name to search for.
	 * @param string $attribute The attribute to get the value from.
	 */
	public function get_attribute_value_by_tag_name( string $tag_name, string $attribute ): ?string {
		$element = $this->find_element( $tag_name );
		if ( ! $element ) {
			return null;
		}
		return $this->get_attribute_value( $element, $attribute );
	}

	/**
	 * Returns the outer HTML of the given element.
	 *
	 * @param \DOMElement $element The element to get the outer HTML from.
	 */
	public function get_outer_html( \DOMElement $element ): string {
		return (string) $this->dom->saveHTML( $element );
	}

	/**
	 * Removes the given element from the document.
	 *
	 * A no-op when the element has already been detached (its parent is null), so removing the same
	 * node twice — e.g. two images sharing one wrapper — is safe.
	 *
	 * @param \DOMElement $element The element to remove.
	 */
	public function remove_element( \DOMElement $element ): void {
		$parent = $element->parentNode; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( $parent instanceof \DOMNode ) {
			$parent->removeChild( $element );
		}
	}

	/**
	 * Serializes every top-level node of the loaded fragment back to HTML.
	 *
	 * The document is loaded with LIBXML_HTML_NOIMPLIED (no implicit html/body wrapper), so the
	 * top-level nodes are the fragment's own roots. Useful for reading back what remains after
	 * elements have been removed.
	 *
	 * @return string
	 */
	public function get_root_html(): string {
		$html = '';
		foreach ( $this->dom->childNodes as $child ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			// Skip the `<?xml encoding="UTF-8">` processing instruction that load_html() prepends to
			// force UTF-8; serializing it back would corrupt callers (e.g. strip_tags treats the
			// unterminated `<?` as a tag and swallows the rest of the string).
			if ( XML_PI_NODE === $child->nodeType ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				continue;
			}
			$html .= (string) $this->dom->saveHTML( $child );
		}
		return $html;
	}

	/**
	 * Returns the inner HTML of the given element.
	 *
	 * @param \DOMElement $element The element to get the inner HTML from.
	 */
	public function get_element_inner_html( \DOMElement $element ): string {
		$inner_html = '';
		$children   = $element->childNodes; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		foreach ( $children as $child ) {
			$inner_html .= $this->dom->saveHTML( $child );
		}
		return $inner_html;
	}
}
