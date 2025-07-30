<?php
/**
 * HTML to Text Converter class
 *
 * This file was extracted from the `soundasleep/html2text` package.
 * Copyright (c) 2019 Jevon Wright
 * MIT License
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer;

/**
 * Converts HTML into plain text format suitable for email display
 *
 * Features:
 * - Maintains links with href copied over
 * - Information in the <head> is lost
 * - Handles various HTML elements appropriately for text conversion
 */
class Html2Text {

	/**
	 * Default options for HTML to text conversion
	 *
	 * @return array<string, bool|string> Default options array.
	 */
	public static function default_options(): array {
		return array(
			'ignore_errors' => false,
			'drop_links'    => false,
			'char_set'      => 'auto',
		);
	}

	/**
	 * Converts HTML into plain text format
	 *
	 * @param string                             $html    The input HTML.
	 * @param boolean|array<string, bool|string> $options Conversion options.
	 * @return string The HTML converted to text.
	 * @throws Html2Text_Exception|\InvalidArgumentException If the HTML could not be loaded or invalid options are provided.
	 */
	public static function convert( string $html, $options = array() ): string {

		if ( false === $options || true === $options ) {
			// Using old style (< 1.0) of passing in options.
			$options = array( 'ignore_errors' => $options );
		}

		$options = array_merge( static::default_options(), $options );

		// Check all options are valid.
		foreach ( array_keys( $options ) as $key ) {
			if ( ! in_array( $key, array_keys( static::default_options() ), true ) ) {
				// Log invalid option for debugging purposes without exposing in exception.
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Security: Logging sensitive data separately from user-facing exception messages.
				error_log( 'Html2Text: Invalid option provided: ' . htmlspecialchars( (string) $key, ENT_QUOTES, 'UTF-8' ) . '. Valid options are: ' . htmlspecialchars( implode( ',', array_keys( static::default_options() ) ), ENT_QUOTES, 'UTF-8' ) );
				// Throw generic error message to avoid exposing user input.
				throw new \InvalidArgumentException( 'Invalid option provided for html2text conversion.' );
			}
		}

		$is_office_document = self::is_office_document( $html );

		if ( $is_office_document ) {
			// Remove office namespace.
			$html = str_replace( array( '<o:p>', '</o:p>' ), '', $html );
		}

		$html = self::fix_newlines( $html );

		// Use mb_convert_encoding for legacy versions of php.
		if ( PHP_MAJOR_VERSION * 10 + PHP_MINOR_VERSION < 81 && mb_detect_encoding( $html, 'UTF-8', true ) ) {
			$converted = mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' );
			$html      = false !== $converted ? $converted : $html;
		}

		// Ensure $html is always a string before passing to get_document.
		if ( ! is_string( $html ) ) {
			$html = (string) $html;
		}

		$doc = self::get_document( $html, $options );

		$output = self::iterate_over_node( $doc, null, false, $is_office_document, $options );

		// Process output for whitespace/newlines.
		$output = self::process_whitespace_newlines( $output );

		return $output;
	}

	/**
	 * Unify newlines
	 *
	 * Converts \r\n to \n, and \r to \n. This means that all newlines
	 * (Unix, Windows, Mac) all become \ns.
	 *
	 * @param string $text Text with any number of \r, \r\n and \n combinations.
	 * @return string The fixed text.
	 */
	public static function fix_newlines( string $text ): string {
		// Replace \r\n to \n.
		$text = str_replace( "\r\n", "\n", $text );
		// Remove \rs.
		$text = str_replace( "\r", "\n", $text );

		return $text;
	}

	/**
	 * Get non-breaking space character codes
	 *
	 * @return array<string> Array of nbsp codes.
	 */
	public static function nbsp_codes(): array {
		return array(
			"\xc2\xa0",
			"\u00a0",
		);
	}

	/**
	 * Get zero-width non-joiner character codes
	 *
	 * @return array<string> Array of zwnj codes.
	 */
	public static function zwnj_codes(): array {
		return array(
			"\xe2\x80\x8c",
			"\u200c",
		);
	}

	/**
	 * Remove leading or trailing spaces and excess empty lines from provided multiline text
	 *
	 * @param string $text Multiline text with any number of leading or trailing spaces or excess lines.
	 * @return string The fixed text.
	 */
	public static function process_whitespace_newlines( string $text ): string {

		// Remove excess spaces around tabs.
		$result = preg_replace( '/ *\t */im', "\t", $text );
		$text   = null !== $result ? $result : $text;

		// Remove leading whitespace.
		$text = ltrim( $text );

		// Remove leading spaces on each line.
		$result = preg_replace( "/\n[ \t]*/im", "\n", $text );
		$text   = null !== $result ? $result : $text;

		// Convert non-breaking spaces to regular spaces to prevent output issues,
		// do it here so they do NOT get removed with other leading spaces, as they
		// are sometimes used for indentation.
		$text = self::render_text( $text );

		// Remove trailing whitespace.
		$text = rtrim( $text );

		// Remove trailing spaces on each line.
		$result = preg_replace( "/[ \t]*\n/im", "\n", $text );
		$text   = null !== $result ? $result : $text;

		// Unarmor pre blocks.
		$text = self::fix_newlines( $text );

		// Remove unnecessary empty lines.
		$result = preg_replace( "/\n\n\n*/im", "\n\n", $text );

		return null !== $result ? $result : $text;
	}

	/**
	 * Can we guess that this HTML is generated by Microsoft Office?
	 *
	 * @param string $html The HTML content.
	 * @return bool True if this appears to be an Office document.
	 */
	public static function is_office_document( string $html ): bool {
		return strpos( $html, 'urn:schemas-microsoft-com:office' ) !== false;
	}

	/**
	 * Check if text is whitespace
	 *
	 * @param string $text The text to check.
	 * @return bool True if the text is whitespace.
	 */
	public static function is_whitespace( string $text ): bool {
		return 0 === strlen( trim( self::render_text( $text ), "\n\r\t " ) );
	}

	/**
	 * Parse HTML into a DOMDocument
	 *
	 * @param string                     $html    The input HTML.
	 * @param array<string, bool|string> $options Parsing options.
	 * @return \DOMDocument The parsed document tree.
	 * @throws Html2Text_Exception If the HTML could not be loaded.
	 */
	private static function get_document( string $html, array $options ): \DOMDocument {

		$doc = new \DOMDocument();

		$html = trim( $html );

		if ( ! $html ) {
			// DOMDocument doesn't support empty value and throws an error.
			// Return empty document instead.
			return $doc;
		}

		if ( '<' !== $html[0] ) {
			// If HTML does not begin with a tag, we put a body tag around it.
			// If we do not do this, PHP will insert a paragraph tag around
			// the first block of text for some reason which can mess up
			// the newlines. See pre.html test for an example.
			$html = '<body>' . $html . '</body>';
		}

		$header = '';
		// Use char sets for modern versions of php.
		if ( PHP_MAJOR_VERSION * 10 + PHP_MINOR_VERSION >= 81 ) {
			// Use specified char_set, or auto detect if not set.
			$char_set = ! empty( $options['char_set'] ) && is_string( $options['char_set'] ) ? $options['char_set'] : 'auto';
			if ( 'auto' === $char_set ) {
				$detected = mb_detect_encoding( $html );
				$char_set = false !== $detected ? $detected : 'UTF-8';
			} elseif ( strpos( $char_set, ',' ) !== false ) {
				$encoding_list = explode( ',', $char_set );
				$encoding_list = array_map( 'trim', $encoding_list );
				$encoding_list = array_filter(
					$encoding_list,
					function ( $encoding ) {
						return ! empty( $encoding );
					}
				);
				if ( ! empty( $encoding_list ) ) {
					// Ensure we have a proper list with consecutive integer keys.
					$encoding_list = array_values( $encoding_list );
					mb_detect_order( $encoding_list );
					$detected = mb_detect_encoding( $html );
					$char_set = false !== $detected ? $detected : 'UTF-8';
				}
			}
			// Turn off error detection for Windows-1252 legacy html.
			if ( strpos( $char_set, '1252' ) !== false ) {
				$options['ignore_errors'] = true;
			}
			$header = '<?xml version="1.0" encoding="' . $char_set . '">';
		}

		if ( ! empty( $options['ignore_errors'] ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$doc->strictErrorChecking = false;
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$doc->recover = true;
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$doc->xmlStandalone  = true;
			$old_internal_errors = libxml_use_internal_errors( true );
			$load_result         = $doc->loadHTML( $header . $html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET | LIBXML_PARSEHUGE );
			libxml_use_internal_errors( $old_internal_errors );
		} else {
			$load_result = $doc->loadHTML( $header . $html );
		}

		if ( ! $load_result ) {
			// Log truncated HTML content for debugging purposes (limit to 500 chars to prevent log bloat).
			$html_preview = strlen( $html ) > 500 ? substr( $html, 0, 500 ) . '...[truncated]' : $html;
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Security: Logging sensitive data separately from user-facing exception messages.
			error_log( 'Html2Text: Failed to load HTML content: ' . htmlspecialchars( $html_preview, ENT_QUOTES, 'UTF-8' ) );
			// Throw a generic error message to avoid exposing sensitive data.
			throw new Html2Text_Exception( 'Could not load HTML - the content may be malformed.' );
		}

		return $doc;
	}

	/**
	 * Replace any special characters with simple text versions
	 *
	 * This prevents output issues:
	 * - Convert non-breaking spaces to regular spaces; and
	 * - Convert zero-width non-joiners to '' (nothing).
	 *
	 * This is to match our goal of rendering documents as they would be rendered
	 * by a browser.
	 *
	 * @param string $text The text to process.
	 * @return string The processed text.
	 */
	private static function render_text( string $text ): string {
		$text = str_replace( self::nbsp_codes(), ' ', $text );
		$text = str_replace( self::zwnj_codes(), '', $text );
		return $text;
	}

	/**
	 * Get the next child name
	 *
	 * @param \DOMNode|null $node The node to check.
	 * @return string|null The next child name.
	 */
	private static function next_child_name( ?\DOMNode $node ): ?string {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( null === $node || null === $node->nextSibling ) {
			return null;
		}

		// Get the next child.
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$next_node = $node->nextSibling;
		while ( null !== $next_node ) {
			if ( $next_node instanceof \DOMText ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( ! self::is_whitespace( $next_node->wholeText ) ) {
					break;
				}
			}

			if ( $next_node instanceof \DOMElement ) {
				break;
			}

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$next_node = $next_node->nextSibling;
		}

		$next_name = null;
		if ( $next_node instanceof \DOMElement || $next_node instanceof \DOMText ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$next_name = strtolower( $next_node->nodeName );
		}

		return $next_name;
	}

	/**
	 * Iterate over a DOM node and convert to text
	 *
	 * @param \DOMNode                   $node                The DOM node.
	 * @param string|null                $prev_name           Previous node name.
	 * @param bool                       $in_pre              Whether we're in a pre block.
	 * @param bool                       $is_office_document  Whether this is an Office document.
	 * @param array<string, bool|string> $options             Conversion options.
	 * @return string The converted text.
	 */
	private static function iterate_over_node( \DOMNode $node, ?string $prev_name, bool $in_pre, bool $is_office_document, array $options ): string {
		if ( $node instanceof \DOMText ) {
			// Replace whitespace characters with a space (equivalent to \s).
			if ( $in_pre ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$text = "\n" . trim( self::render_text( $node->wholeText ), "\n\r\t " ) . "\n";

				// Remove trailing whitespace only.
				$result = preg_replace( "/[ \t]*\n/im", "\n", $text );
				$text   = null !== $result ? $result : $text;

				// Armor newlines with \r.
				return str_replace( "\n", "\r", $text );
			}
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$text   = self::render_text( $node->wholeText );
			$result = preg_replace( "/[\\t\\n\\f\\r ]+/im", ' ', $text );
			$text   = null !== $result ? $result : $text;

			if ( ! self::is_whitespace( $text ) && ( 'p' === $prev_name || 'div' === $prev_name ) ) {
				return "\n" . $text;
			}
			return $text;
		}

		if ( $node instanceof \DOMDocumentType || $node instanceof \DOMProcessingInstruction ) {
			// Ignore.
			return '';
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$name      = strtolower( $node->nodeName );
		$next_name = self::next_child_name( $node );

		// Start whitespace.
		switch ( $name ) {
			case 'hr':
				$prefix = '';
				if ( null !== $prev_name ) {
					$prefix = "\n";
				}
				return $prefix . "---------------------------------------------------------------\n";

			case 'style':
			case 'head':
			case 'title':
			case 'meta':
			case 'script':
				// Ignore these tags.
				return '';

			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
			case 'ol':
			case 'ul':
			case 'pre':
				// Add two newlines.
				$output = "\n\n";
				break;

			case 'td':
			case 'th':
				// Add tab char to separate table fields.
				$output = "\t";
				break;

			case 'p':
				// Microsoft exchange emails often include HTML which, when passed through
				// html2text, results in lots of double line returns everywhere.
				//
				// To fix this, for any p element with a className of `MsoNormal` (the standard
				// classname in any Microsoft export or outlook for a paragraph that behaves
				// like a line return) we skip the first line returns and set the name to br.
				if ( $is_office_document && $node instanceof \DOMElement && 'MsoNormal' === $node->getAttribute( 'class' ) ) {
					$output = '';
					$name   = 'br';
					break;
				}

				// Add two lines.
				$output = "\n\n";
				break;

			case 'tr':
				// Add one line.
				$output = "\n";
				break;

			case 'div':
				$output = '';
				if ( null !== $prev_name ) {
					// Add one line.
					$output .= "\n";
				}
				break;

			case 'li':
				$output = '- ';
				break;

			default:
				// Print out contents of unknown tags.
				$output = '';
				break;
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( $node->childNodes->length > 0 ) {

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$n                      = $node->childNodes->item( 0 );
			$previous_sibling_names = array();
			$previous_sibling_name  = null;
			$parts                  = array();
			$trailing_whitespace    = 0;

			while ( null !== $n ) {

				$text = self::iterate_over_node( $n, $previous_sibling_name, $in_pre || 'pre' === $name, $is_office_document, $options );

				// Pass current node name to next child, as previousSibling does not appear to get populated.
				if ( $n instanceof \DOMDocumentType
					|| $n instanceof \DOMProcessingInstruction
					|| ( $n instanceof \DOMText && self::is_whitespace( $text ) ) ) {
					// Keep current previousSiblingName, these are invisible.
					++$trailing_whitespace;
				} else {
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$previous_sibling_name    = strtolower( $n->nodeName );
					$previous_sibling_names[] = $previous_sibling_name;
					$trailing_whitespace      = 0;
				}

				$node->removeChild( $n );
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$n = $node->childNodes->item( 0 );

				$parts[] = $text;
			}

			// Remove trailing whitespace, important for the br check below.
			while ( $trailing_whitespace-- > 0 ) {
				array_pop( $parts );
			}

			// Suppress last br tag inside a node list if follows text.
			$last_name = array_pop( $previous_sibling_names );
			if ( 'br' === $last_name ) {
				$last_name = array_pop( $previous_sibling_names );
				if ( '#text' === $last_name ) {
					array_pop( $parts );
				}
			}

			$output .= implode( '', $parts );
		}

		// End whitespace.
		switch ( $name ) {
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
			case 'pre':
			case 'p':
				// Add two lines.
				$output .= "\n\n";
				break;

			case 'br':
				// Add one line.
				$output .= "\n";
				break;

			case 'div':
				break;

			case 'a':
				// Links are returned in [text](link) format.
				$href = $node instanceof \DOMElement ? $node->getAttribute( 'href' ) : '';

				$output = trim( $output );

				// Remove double [[ ]] s from linking images.
				if ( '[' === substr( $output, 0, 1 ) && ']' === substr( $output, -1 ) ) {
					$output = substr( $output, 1, strlen( $output ) - 2 );

					// For linking images, the title of the <a> overrides the title of the <img>.
					if ( $node instanceof \DOMElement && $node->getAttribute( 'title' ) ) {
						$output = $node->getAttribute( 'title' );
					}
				}

				// If there is no link text, but a title attr.
				if ( ! $output && $node instanceof \DOMElement && $node->getAttribute( 'title' ) ) {
					$output = $node->getAttribute( 'title' );
				}

				if ( ! $href ) {
					// It doesn't link anywhere.
					if ( $node instanceof \DOMElement && $node->getAttribute( 'name' ) ) {
						if ( $options['drop_links'] ) {
							$output = "$output";
						} else {
							$output = "[$output]";
						}
					}
				} elseif ( $href === $output || "mailto:$output" === $href || "http://$output" === $href || "https://$output" === $href ) {
					// Link to the same address: just use link.
					$output = "$output";
				} elseif ( $output ) {
					// Replace it.
					if ( $options['drop_links'] ) {
						$output = "$output";
					} else {
						$output = "[$output]($href)";
					}
				} else {
					// Empty string.
					$output = "$href";
				}

				// Does the next node require additional whitespace?
				switch ( $next_name ) {
					case 'h1':
					case 'h2':
					case 'h3':
					case 'h4':
					case 'h5':
					case 'h6':
						$output .= "\n";
						break;
				}
				break;

			case 'img':
				if ( $node instanceof \DOMElement && $node->getAttribute( 'title' ) ) {
					$output = '[' . $node->getAttribute( 'title' ) . ']';
				} elseif ( $node instanceof \DOMElement && $node->getAttribute( 'alt' ) ) {
					$output = '[' . $node->getAttribute( 'alt' ) . ']';
				} else {
					$output = '';
				}
				break;

			case 'li':
				$output .= "\n";
				break;

			case 'blockquote':
				// Process quoted text for whitespace/newlines.
				$output = self::process_whitespace_newlines( $output );

				// Add leading newline.
				$output = "\n" . $output;

				// Prepend '> ' at the beginning of all lines.
				$result = preg_replace( "/\n/im", "\n> ", $output );
				$output = null !== $result ? $result : $output;

				// Replace leading '> >' with '>>'.
				$result = preg_replace( "/\n> >/im", "\n>>", $output );
				$output = null !== $result ? $result : $output;

				// Add another leading newline and trailing newlines.
				$output = "\n" . $output . "\n\n";
				break;
			default:
				// Do nothing.
		}

		return $output;
	}
}
