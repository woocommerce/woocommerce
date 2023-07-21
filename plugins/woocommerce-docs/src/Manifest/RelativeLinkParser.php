<?php

namespace WooCommerceDocs\Manifest;

use WooCommerceDocs\Data\DocsStore;

/**
 * Class RelativeLinkParser
 */
class RelativeLinkParser {
	/**
	 * Extract links from the given manifest and create a lookup object.
	 *
	 * @param array $manifest The manifest array.
	 * @return array The lookup object containing the links.
	 */
	public static function extract_links_from_manifest( $manifest ) {
		$links = array();

		self::process_categories( $manifest['categories'], $links );

		return $links;
	}

	/**
	 * Process the categories recursively and extract links.
	 *
	 * @param array $categories The categories array.
	 * @param array $links The array to store the extracted links.
	 */
	private static function process_categories( $categories, &$links ) {
		foreach ( $categories as $category ) {
			if ( isset( $category['links'] ) ) {
				$links = array_merge( $links, $category['links'] );
			}

			if ( isset( $category['categories'] ) ) {
				self::process_categories( $category['categories'], $links );
			}

			if ( isset( $category['posts'] ) ) {
				self::process_posts( $category['posts'], $links );
			}
		}
	}

	/**
	 * Process the posts and extract links.
	 *
	 * @param array $posts The posts array.
	 * @param array $links The array to store the extracted links.
	 */
	private static function process_posts( $posts, &$links ) {
		foreach ( $posts as $post ) {
			if ( isset( $post['links'] ) ) {
				$links = array_merge( $links, $post['links'] );
			}
		}
	}

	/**
	 * Replace relative links with permalinks in the given content using the lookup object.
	 *
	 * @param string $content The content to update.
	 * @param array  $link_lookup The lookup object containing the links.
	 * @param int    $action_id The action id used for logging.
	 * @return string The updated content.
	 */
	public static function replace_relative_links( $content, $link_lookup, $action_id ) {
		$dom = new \DOMDocument();
		$dom->loadHTML( $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		$anchors = $dom->getElementsByTagName( 'a' );

		foreach ( $anchors as $anchor ) {
			$href = $anchor->getAttribute( 'href' );

			// Check if its a url or relative path.
			if ( ! preg_match( '/^https?:\/\//', $href ) ) {
				// Check if the link exists in the lookup object.
				if ( isset( $link_lookup[ $href ] ) ) {
					$post = DocsStore::get_post( $link_lookup[ $href ] );

					if ( $post ) {
						$post_id = $post->ID;
						// get permalink from lookup object.
						$permalink = get_permalink( $post_id );
						$anchor->setAttribute( 'href', $permalink );
					} else {
						\ActionScheduler_Logger::instance()->log( $action_id, "Could not replace file link with post link, post with ID: $post_id not found." );
					}
				}
			}
		}

		$updated_content = $dom->saveHTML();

		return $updated_content;
	}
}
