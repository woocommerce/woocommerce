<?php
namespace Automattic\WooCommerce\Blocks\Patterns;

/**
 * PatternRegistry class.
 *
 * @internal
 */
class PatternRegistry {
	const SLUG_REGEX            = '/^[A-z0-9\/_-]+$/';
	const COMMA_SEPARATED_REGEX = '/[\s,]+/';

	/**
	 * Returns pattern slugs with their localized labels for categorization.
	 *
	 * Each key represents a unique pattern slug, while the value is the localized label.
	 *
	 * @return array<string, string>
	 */
	private function get_category_labels() {
		return [
			'woo-commerce'     => __( 'WooCommerce', 'woocommerce' ),
			'intro'            => __( 'Intro', 'woocommerce' ),
			'featured-selling' => __( 'Featured Selling', 'woocommerce' ),
			'about'            => __( 'About', 'woocommerce' ),
			'social-media'     => __( 'Social Media', 'woocommerce' ),
			'services'         => __( 'Services', 'woocommerce' ),
			'reviews'          => __( 'Reviews', 'woocommerce' ),
		];
	}

	/**
	 * Register a block pattern.
	 *
	 * @param string $source The pattern source.
	 * @param array  $pattern_data The pattern data.
	 *
	 * @return void
	 */
	public function register_block_pattern( $source, $pattern_data ) {
		if ( empty( $pattern_data['slug'] ) ) {
			_doing_it_wrong(
				'register_block_patterns',
				esc_html(
					sprintf(
					/* translators: %s: file name. */
						__( 'Could not register pattern "%s" as a block pattern ("Slug" field missing)', 'woocommerce' ),
						$source
					)
				),
				'6.0.0'
			);
			return;
		}

		if ( ! preg_match( self::SLUG_REGEX, $pattern_data['slug'] ) ) {
			_doing_it_wrong(
				'register_block_patterns',
				esc_html(
					sprintf(
					/* translators: %1s: file name; %2s: slug value found. */
						__( 'Could not register pattern "%1$s" as a block pattern (invalid slug "%2$s")', 'woocommerce' ),
						$source,
						$pattern_data['slug']
					)
				),
				'6.0.0'
			);
			return;
		}

		if ( \WP_Block_Patterns_Registry::get_instance()->is_registered( $pattern_data['slug'] ) ) {
			return;
		}

		// Title is a required property.
		if ( ! isset( $pattern_data['title'] ) || ! $pattern_data['title'] ) {
			_doing_it_wrong(
				'register_block_patterns',
				esc_html(
					sprintf(
					/* translators: %1s: file name; %2s: slug value found. */
						__( 'Could not register pattern "%s" as a block pattern ("Title" field missing)', 'woocommerce' ),
						$source
					)
				),
				'6.0.0'
			);
			return;
		}

		// For properties of type array, parse data as comma-separated.
		foreach ( array( 'categories', 'keywords', 'blockTypes' ) as $property ) {
			if ( ! empty( $pattern_data[ $property ] ) ) {
				if ( is_array( $pattern_data[ $property ] ) ) {
					$pattern_data[ $property ] = array_values(
						array_map(
							function ( $property ) {
								return $property['title'];
							},
							$pattern_data[ $property ]
						)
					);
				} else {
					$pattern_data[ $property ] = array_filter(
						preg_split(
							self::COMMA_SEPARATED_REGEX,
							(string) $pattern_data[ $property ]
						)
					);
				}
			} else {
				unset( $pattern_data[ $property ] );
			}
		}

		// Parse properties of type int.
		foreach ( array( 'viewportWidth' ) as $property ) {
			if ( ! empty( $pattern_data[ $property ] ) ) {
				$pattern_data[ $property ] = (int) $pattern_data[ $property ];
			} else {
				unset( $pattern_data[ $property ] );
			}
		}

		// Parse properties of type bool.
		foreach ( array( 'inserter' ) as $property ) {
			if ( ! empty( $pattern_data[ $property ] ) ) {
				$pattern_data[ $property ] = in_array(
					strtolower( $pattern_data[ $property ] ),
					array( 'yes', 'true' ),
					true
				);
			} else {
				unset( $pattern_data[ $property ] );
			}
		}

		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.LowLevelTranslationFunction
		$pattern_data['title'] = translate_with_gettext_context( wp_strip_all_tags( html_entity_decode( (string) $pattern_data['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ), 'Pattern title', 'woocommerce' );
		if ( ! empty( $pattern_data['description'] ) ) {
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.LowLevelTranslationFunction
			$pattern_data['description'] = translate_with_gettext_context( $pattern_data['description'], 'Pattern description', 'woocommerce' );
		}

		// A pattern is registrable as long as it provides either inline content
		// or a `filePath` that core can load lazily (WP 6.5+). Bail only when
		// neither is available.
		if ( empty( $pattern_data['content'] ) && empty( $pattern_data['filePath'] ) ) {
			return;
		}

		// When a `filePath` is provided, let core load the content lazily on
		// demand. Drop any empty `content` so core falls back to `filePath`
		// instead of registering an empty pattern.
		if ( ! empty( $pattern_data['filePath'] ) && empty( $pattern_data['content'] ) ) {
			unset( $pattern_data['content'] );
		}

		$category_labels = $this->get_category_labels();

		if ( ! empty( $pattern_data['categories'] ) ) {
			foreach ( $pattern_data['categories'] as $key => $category ) {
				$category_slug = _wp_to_kebab_case( $category );

				$pattern_data['categories'][ $key ] = $category_slug;

				$label = $category_labels[ $category_slug ] ?? self::kebab_to_capital_case( $category_slug );

				register_block_pattern_category(
					$category_slug,
					array(
						'label' => $label,
					),
				);
			}
		}

		register_block_pattern( $pattern_data['slug'], $pattern_data );
	}

	/**
	 * Convert a kebab-case string to capital case.
	 *
	 * @param string $value The kebab-case string.
	 *
	 * @return string
	 */
	private static function kebab_to_capital_case( $value ) {
		$string = str_replace( '-', ' ', $value );
		$string = ucwords( $string );

		return $string;
	}
}
