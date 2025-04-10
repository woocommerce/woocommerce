<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

defined( 'ABSPATH' ) || exit;
/**
 * Payments settings utilities class.
 */
class Utils {
	/**
	 * Apply order mappings to a base order map.
	 *
	 * @param array $base_map     The base order map.
	 * @param array $new_mappings The order mappings to apply.
	 *                            This can be a full or partial list of the base one,
	 *                            but it can also contain (only) new IDs and their orders.
	 *
	 * @return array The updated base order map, normalized.
	 */
	public static function order_map_apply_mappings( array $base_map, array $new_mappings ): array {
		// Make sure the base map is sorted ascending by their order values.
		// We don't normalize first because the order values have meaning.
		asort( $base_map );

		$updated_map = $base_map;
		// Apply the new mappings in the order they were given.
		foreach ( $new_mappings as $id => $order ) {
			// If the ID is not in the base map, we ADD it at the desired order. Otherwise, we MOVE it.
			if ( ! isset( $base_map[ $id ] ) ) {
				$updated_map = self::order_map_add_at_order( $updated_map, $id, $order );
				continue;
			}

			$updated_map = self::order_map_move_at_order( $updated_map, $id, $order );
		}

		return self::order_map_normalize( $updated_map );
	}

	/**
	 * Move an id at a specific order in an order map.
	 *
	 * This method is used to simulate the behavior of a drag&drop sorting UI:
	 * - When moving an id down, all the ids with an order equal or lower than the desired order
	 *   but equal or higher than the current order are decreased by 1.
	 * - When moving an id up, all the ids with an order equal or higher than the desired order
	 *   but equal or lower than the current order are increased by 1.
	 *
	 * @param array  $order_map The order map.
	 * @param string $id        The id to place.
	 * @param int    $order     The order at which to place the id.
	 *
	 * @return array The updated order map. This map is not normalized.
	 */
	public static function order_map_move_at_order( array $order_map, string $id, int $order ): array {
		// If the id is not in the order map, return the order map as is.
		if ( ! isset( $order_map[ $id ] ) ) {
			return $order_map;
		}

		// If the id is already at the desired order, return the order map as is.
		if ( $order_map[ $id ] === $order ) {
			return $order_map;
		}

		// If there is no id at the desired order, just place the id there.
		if ( ! in_array( $order, $order_map, true ) ) {
			$order_map[ $id ] = $order;

			return $order_map;
		}

		// We apply the normal behavior of a drag&drop sorting UI.
		$existing_order = $order_map[ $id ];
		if ( $order > $existing_order ) {
			// Moving down.
			foreach ( $order_map as $key => $value ) {
				if ( $value <= $order && $value >= $existing_order ) {
					--$order_map[ $key ];
				}
			}
		} else {
			// Moving up.
			foreach ( $order_map as $key => $value ) {
				if ( $value >= $order && $value <= $existing_order ) {
					++$order_map[ $key ];
				}
			}
		}

		// Place the id at the desired order.
		$order_map[ $id ] = $order;

		return $order_map;
	}

	/**
	 * Place an id at a specific order in an order map.
	 *
	 * @param array  $order_map The order map.
	 * @param string $id        The id to place.
	 * @param int    $order     The order at which to place the id.
	 *
	 * @return array The updated order map.
	 */
	public static function order_map_place_at_order( array $order_map, string $id, int $order ): array {
		// If the id is already at the desired order, return the order map as is.
		if ( isset( $order_map[ $id ] ) && $order_map[ $id ] === $order ) {
			return $order_map;
		}

		// If there is no id at the desired order, just place the id there.
		if ( ! in_array( $order, $order_map, true ) ) {
			$order_map[ $id ] = $order;

			return $order_map;
		}

		// Bump the order of everything with an order equal or higher than the desired order.
		foreach ( $order_map as $key => $value ) {
			if ( $value >= $order ) {
				++$order_map[ $key ];
			}
		}

		// Place the id at the desired order.
		$order_map[ $id ] = $order;

		return $order_map;
	}

	/**
	 * Add an id to a specific order in an order map.
	 *
	 * @param array  $order_map The order map.
	 * @param string $id        The id to move.
	 * @param int    $order     The order to move the id to.
	 *
	 * @return array The updated order map. If the id is already in the order map, the order map is returned as is.
	 */
	public static function order_map_add_at_order( array $order_map, string $id, int $order ): array {
		// If the id is in the order map, return the order map as is.
		if ( isset( $order_map[ $id ] ) ) {
			return $order_map;
		}

		return self::order_map_place_at_order( $order_map, $id, $order );
	}

	/**
	 * Normalize an order map.
	 *
	 * Sort the order map by the order and ensure the order values start from 0 and are consecutive.
	 *
	 * @param array $order_map The order map.
	 *
	 * @return array The normalized order map.
	 */
	public static function order_map_normalize( array $order_map ): array {
		asort( $order_map );

		return array_flip( array_keys( $order_map ) );
	}

	/**
	 * Change the minimum order of an order map.
	 *
	 * @param array $order_map     The order map.
	 * @param int   $new_min_order The new minimum order.
	 *
	 * @return array The updated order map.
	 */
	public static function order_map_change_min_order( array $order_map, int $new_min_order ): array {
		// Sanity checks.
		if ( empty( $order_map ) ) {
			return array();
		}

		$updated_map = array();
		$bump        = $new_min_order - min( $order_map );
		foreach ( $order_map as $id => $order ) {
			$updated_map[ $id ] = $order + $bump;
		}

		asort( $updated_map );

		return $updated_map;
	}

	/**
	 * Get the list of plugin slug suffixes used for handling non-standard testing slugs.
	 *
	 * @return string[] The list of plugin slug suffixes used for handling non-standard testing slugs.
	 */
	public static function get_testing_plugin_slug_suffixes(): array {
		return array( '-dev', '-rc', '-test', '-beta', '-alpha' );
	}

	/**
	 * Generate a list of testing plugin slugs from a standard/official plugin slug.
	 *
	 * @param string $slug             The standard/official plugin slug. Most likely the WPORG slug.
	 * @param bool   $include_original Optional. Whether to include the original slug in the list.
	 *                                 If true, the original slug will be the first item in the list.
	 *
	 * @return string[] The list of testing plugin slugs generated from the standard/official plugin slug.
	 */
	public static function generate_testing_plugin_slugs( string $slug, bool $include_original = false ): array {
		$slugs = array();
		if ( $include_original ) {
			$slugs[] = $slug;
		}

		foreach ( self::get_testing_plugin_slug_suffixes() as $suffix ) {
			$slugs[] = $slug . $suffix;
		}

		return $slugs;
	}

	/**
	 * Normalize a plugin slug to a standard/official slug.
	 *
	 * This is a best-effort approach.
	 * It will remove beta testing suffixes and lowercase the slug.
	 * It will NOT convert plugin titles to slugs or sanitize the slug like sanitize_title() does.
	 *
	 * @param string $slug The plugin slug.
	 *
	 * @return string The normalized plugin slug.
	 */
	public static function normalize_plugin_slug( string $slug ): string {
		// If the slug is empty or contains anything other than alphanumeric and dash characters, it will be left as is.
		if ( empty( $slug ) || ! preg_match( '/^[\w-]+$/', $slug, $matches ) ) {
			return $slug;
		}

		// Lowercase the slug.
		$slug = strtolower( $slug );
		// Remove testing suffixes.
		foreach ( self::get_testing_plugin_slug_suffixes() as $suffix ) {
			$slug = str_ends_with( $slug, $suffix ) ? substr( $slug, 0, -strlen( $suffix ) ) : $slug;
		}

		return $slug;
	}

	/**
	 * Truncate a text to a target character length while preserving whole words.
	 *
	 * We take a greedy approach: if some characters of a word fit in the target length, the whole word is included.
	 * This means we might exceed the target length by a few characters.
	 * The append string length is not included in the character count.
	 *
	 * @param string      $text          The text to truncate.
	 *                                   It will not be sanitized, stripped of HTML tags, or modified in any way before truncation.
	 * @param int         $target_length The target character length of the truncated text.
	 * @param string|null $append        Optional. The string to append to the truncated text, if there is any truncation.
	 *
	 * @return string The truncated text.
	 */
	public static function truncate_with_words( string $text, int $target_length, string $append = null ): string {
		// First, deal with locale that doesn't have words separated by spaces, but instead deals with characters.
		// Borrowed from wp_trim_words().
		if ( str_starts_with( wp_get_word_count_type(), 'characters' ) && preg_match( '/^utf\-?8$/i', get_option( 'blog_charset' ) ) ) {
			$text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $text ), ' ' );
			preg_match_all( '/./u', $text, $words_array );

			// Nothing to do if the text is already short enough.
			if ( count( $words_array[0] ) <= $target_length ) {
				return $text;
			}

			$words_array = array_slice( $words_array[0], 0, $target_length );
			$truncated   = implode( '', $words_array );
			if ( null !== $append ) {
				$truncated .= $append;
			}

			return $truncated;
		}

		// Deal with locale that has words separated by spaces.
		if ( strlen( $text ) <= $target_length ) {
			return $text;
		}

		$words_array = preg_split( "/[\n\r\t ]+/", $text, - 1, PREG_SPLIT_NO_EMPTY );
		$sep         = ' ';

		// Include words until the target length is reached.
		$truncated        = '';
		$remaining_length = $target_length;
		while ( $remaining_length > 0 && ! empty( $words_array ) ) {
			$word              = array_shift( $words_array );
			$truncated        .= $word . $sep;
			$remaining_length -= strlen( $word . $sep );
		}

		// Remove the last separator.
		$truncated = rtrim( $truncated, $sep );

		if ( null !== $append ) {
			$truncated .= $append;
		}

		return $truncated;
	}
}
