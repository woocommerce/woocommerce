<?php

namespace WooCommerceDocs\Manifest;

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
	 * @return string The updated content.
	 */
	public static function replace_relative_links( $content, $link_lookup ) {
		$dom = new \DOMDocument();
		$dom->loadHTML( $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		$anchors = $dom->getElementsByTagName( 'a' );

		foreach ( $anchors as $anchor ) {
			$href = $anchor->getAttribute( 'href' );

			// Ignore full URLs.
			if ( ! filter_var( $href, FILTER_VALIDATE_URL ) ) {
				$relative_path = trim( $href, '/' );

				if ( isset( $link_lookup[ $relative_path ] ) ) {
					$permalink = get_permalink( $link_lookup[ $relative_path ] );

					$anchor->setAttribute( 'href', $permalink );
				}
			}
		}

		$updated_content = $dom->saveHTML();

		// Remove DOCTYPE and HTML wrapper added by DOMDocument.
		$updated_content = preg_replace( '/^<!DOCTYPE.+?>/', '', $updated_content );
		$updated_content = preg_replace( '/^<html><body>/', '', $updated_content );
		$updated_content = preg_replace( '/<\/body><\/html>$/', '', $updated_content );

		return $updated_content;
	}
}
