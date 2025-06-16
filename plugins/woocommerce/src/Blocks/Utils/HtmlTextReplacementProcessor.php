<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * HTML processor for replacing text content with raw HTML.
 *
 * Extends WP_HTML_Processor to provide unsafe text replacement functionality
 * for use in block rendering contexts.
 *
 * @since 9.8.0
 * @internal This class is not intended for public use.
 */
class HtmlTextReplacementProcessor extends \WP_HTML_Processor {
	/**
	 * Replace the contents of a text node with arbitrary HTML.
	 *
	 * This method does not perform any safety checking on the provided HTML.
	 *
	 * @param string $raw_html The raw HTML to replace the text content with.
	 * @return bool True if the text was successfully replaced, false otherwise.
	 */
	public function unsafe_replace_text_with_raw_html( string $raw_html ): bool {
		if ( '#text' === $this->get_token_type() ) {
			$this->set_bookmark( '_wc_html_raw_html_replace_' );
			// The bookmark names are prefixed with `_` so the key below has an extra `_`.
			$bm                      = $this->bookmarks['__wc_html_raw_html_replace_'];
			$this->lexical_updates[] = new \WP_HTML_Text_Replacement(
				$bm->start,
				$bm->length,
				$raw_html
			);
			$this->release_bookmark( '_wc_html_raw_html_replace_' );

			return true;
		}

		return false;
	}
}
