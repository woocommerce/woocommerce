<?php
namespace Automattic\WooCommerce\Blocks\Patterns;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * PatternRegistry class.
 */
class PatternRegistry {
	const SLUG_REGEX            = '/^[A-z0-9\/_-]+$/';
	const COMMA_SEPARATED_REGEX = '/[\s,]+/';

	/**
	 * Register a block pattern.
	 *
	 * @param string $source The pattern source.
	 * @param array  $pattern_data The pattern data.
	 * @param array  $dictionary The patterns' dictionary.
	 *
	 * @return void
	 */
    public function register_block_pattern( $source, $pattern_data, $dictionary ) {
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

		if ( isset( $pattern_data['featureFlag'] ) && '' !== $pattern_data['featureFlag'] && ! Features::is_enabled( $pattern_data['featureFlag'] ) ) {
			return;
		}

		// Title is a required property.
		if ( ! $pattern_data['title'] ) {
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
		$pattern_data['title'] = translate_with_gettext_context( $pattern_data['title'], 'Pattern title', 'woocommerce' );
		if ( ! empty( $pattern_data['description'] ) ) {
            // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.LowLevelTranslationFunction
			$pattern_data['description'] = translate_with_gettext_context( $pattern_data['description'], 'Pattern description', 'woocommerce' );
		}

		$pattern_data_from_dictionary = $this->get_pattern_from_dictionary( $dictionary, $pattern_data['slug'] );

		if ( file_exists( $source ) ) {
			// The actual pattern content is the output of the file.
			ob_start();

			/*
				For patterns that can have AI-generated content, we need to get its content from the dictionary and pass
				it to the pattern file through the "$content" and "$images" variables.
				This is to avoid having to access the dictionary for each pattern when it's registered or inserted.
				Before the "$content" and "$images" variables were populated in each pattern. Since the pattern
				registration happens in the init hook, the dictionary was being access one for each pattern and
				for each page load. This way we only do it once on registration.
				For more context: https://github.com/woocommerce/woocommerce-blocks/pull/11733
			*/

			$content = array();
			$images  = array();
			if ( ! is_null( $pattern_data_from_dictionary ) ) {
				$content = $pattern_data_from_dictionary['content'];
				$images  = $pattern_data_from_dictionary['images'] ?? array();
			}

			include $source;
			$pattern_data['content'] = ob_get_clean();

			if ( ! $pattern_data['content'] ) {
				return;
			}
		}

		if ( ! empty( $pattern_data['categories'] ) ) {
			foreach ( $pattern_data['categories'] as $key => $category ) {
				$category_slug = _wp_to_kebab_case( $category );

				$pattern_data['categories'][ $key ] = $category_slug;

				register_block_pattern_category(
					$category_slug,
                    // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
					array( 'label' => __( $category, 'woocommerce' ) )
				);
			}
		}

		register_block_pattern( $pattern_data['slug'], $pattern_data );
	}

	/**
	 * Filter the patterns dictionary to get the pattern data corresponding to the pattern slug.
	 *
	 * @param array  $dictionary The patterns' dictionary.
	 * @param string $slug The pattern slug.
	 *
	 * @return array|null
	 */
	private function get_pattern_from_dictionary( $dictionary, $slug ) {
		foreach ( $dictionary as $pattern_dictionary ) {
			if ( isset( $pattern_dictionary['slug'] ) && $pattern_dictionary['slug'] === $slug ) {
				return $pattern_dictionary;
			}
		}

		return null;
	}
}
