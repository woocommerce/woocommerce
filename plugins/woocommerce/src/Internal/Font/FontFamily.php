<?php
/**
 * FontFamily class file
 */

namespace Automattic\WooCommerce\Internal\Font;

// IMPORTANT: We have to switch to the WordPress API to create the FontFamily post type when they will be implemented: https://github.com/WordPress/gutenberg/issues/58670!

/**
 * Helper class for font family related functionality.
 *
 * @internal Just for internal use.
 */
class FontFamily {

	const POST_TYPE = 'wp_font_family';

	/**
	 * Validates a font family.
	 *
	 * @param array $font_family The font family settings.
	 * @return \WP_Error|null The error if the font family is invalid, null otherwise.
	 */
	private static function validate_font_family( $font_family ) {
		// Validate the font family name.
		if ( empty( $font_family['fontFamily'] ) ) {
			return new \WP_Error(
				'invalid_font_family_name',
				__( 'The font family name is required.', 'woocommerce' ),
			);
		}

		// Validate the font family slug.
		if ( empty( $font_family['preview'] ) ) {
			return new \WP_Error(
				'invalid_font_family_name_preview',
				__( 'The font family preview is required.', 'woocommerce' ),
			);
		}
	}



	/**
	 * Registers the font family post type.
	 *
	 * @param array $font_family_settings The font family settings.
	 */
	public static function insert_font_family( array $font_family_settings ) {
		$font_family = $font_family_settings;
		// Check that the font family slug is unique.
		$query = new \WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'posts_per_page'         => 1,
				'name'                   => $font_family['slug'],
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( ! empty( $query->get_posts() ) ) {
			return new \WP_Error(
				'duplicate_font_family',
				/* translators: %s: Font family slug. */
				sprintf( __( 'A font family with slug "%s" already exists.', 'woocommerce' ), $font_family['slug'] )
			);
		}

		// Validate the font family settings.
		$validation_error = self::validate_font_family( $font_family );
		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
		}

		$post['fontFamily'] = addslashes( \WP_Font_Utils::sanitize_font_family( $font_family['fontFamily'] ) );
		$post['preview']    = $font_family['preview'];

		// Insert the font family.
		return wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_title'   => $font_family['name'],
				'name'         => $font_family['slug'],
				'post_content' => wp_json_encode( $post ),
				'post_status'  => 'publish',
			)
		);

	}

	/**
	 * Gets a font family by name.
	 *
	 * @param string $name The font family slug.
	 * @return \WP_Post|null The font family post or null if not found.
	 */
	public static function get_font_family_by_name( $name ) {
		$query = new \WP_Query(
			array(
				'post_type'              => self::POST_TYPE,
				'posts_per_page'         => 1,
				'title'                  => $name,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( ! empty( $query->get_posts() ) ) {
			return $query->get_posts()[0];
		}
		return null;
	}
}
