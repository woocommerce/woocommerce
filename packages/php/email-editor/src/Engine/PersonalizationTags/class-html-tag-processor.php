<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags;

use WP_HTML_Tag_Processor;
use WP_HTML_Text_Replacement;

/**
 * Class based on WP_HTML_Tag_Processor which is extended to replace
 * tokens with their values in the email content.
 *
 * This class was inspired by a concept from the WordPress core,
 * which could help us to avoid refactoring in the future.
 */
class HTML_Tag_Processor extends WP_HTML_Tag_Processor {
	/**
	 * List of deferred updates which will be replaced after calling flush_updates().
	 *
	 * @var WP_HTML_Text_Replacement[]
	 */
	private $deferred_updates = array();

	/**
	 * Replaces the token with the new content.
	 *
	 * @param string $new_content The new content to replace the token.
	 */
	public function replace_token( string $new_content ): void {
		$this->set_bookmark( 'here' );
		$here                     = $this->bookmarks['here'];
		$this->deferred_updates[] = new WP_HTML_Text_Replacement(
			$here->start,
			$here->length,
			$new_content
		);
	}

	/**
	 * Flushes the deferred updates to the lexical updates.
	 */
	public function flush_updates(): void {
		foreach ( $this->deferred_updates as $key => $update ) {
			$this->lexical_updates[] = $update;
			unset( $this->deferred_updates[ $key ] );
		}
	}
}
