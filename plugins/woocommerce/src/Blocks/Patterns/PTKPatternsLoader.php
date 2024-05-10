<?php

namespace Automattic\WooCommerce\Blocks\Patterns;

/**
 * PTKPatterns class.
 */
class PTKPatternsLoader {
	const EXCLUDED_PATTERNS = array( '13923', '14781', '14779', '13666', '13664', '13660', '13588' );

	/**
	 * PatternsToolkit instance.
	 *
	 * @var PTKClient $ptk_client
	 */
	private $ptk_client;

	/**
	 * PatternRegistry instance.
	 *
	 * @var PatternRegistry $pattern_registry
	 */
	private PatternRegistry $pattern_registry;

	/**
	 * Constructor for the class.
	 *
	 * @param PTKClient $ptk_client An instance of PatternsToolkit.
	 * @param PatternRegistry       $pattern_registry An instance of PatternRegistry.
	 */
	public function __construct( $ptk_client, $pattern_registry ) {
		$this->ptk_client       = $ptk_client;
		$this->pattern_registry = $pattern_registry;
	}

	/**
	 * Register block patterns from the Patterns Toolkit.
	 *
	 * @param array $dictionary The patterns' dictionary.
	 *
	 * @return void
	 */
	public function register_patterns_from_ptk( array $dictionary ) {
		$patterns = $this->ptk_client->fetch_patterns(
			array(
				'categories' => array( 'intro', 'about', 'services', 'testimonials' ),
			)
		);

		if ( is_wp_error( $patterns ) ) {
			wc_get_logger()->warning(
				sprintf(
				// translators: %s is a generated error message.
					__( 'Failed to get the patterns from the PTK: "%s"', 'woocommerce' ),
					$patterns->get_error_message()
				),
			);
			return;
		}

		$filtered_patterns = $this->filter_patterns( $patterns, self::EXCLUDED_PATTERNS );

		foreach ( $filtered_patterns as $pattern ) {
			$pattern['slug']    = $pattern['name'];
			$pattern['content'] = $pattern['html'];

			$this->pattern_registry->register_block_pattern( $pattern['ID'], $pattern, $dictionary );
		}
	}

	/**
	 * Filter patterns to only include those with the given IDs.
	 *
	 * @param array $patterns The patterns to filter.
	 * @param array $pattern_ids The pattern IDs to exclude.
	 * @return array
	 */
	private function filter_patterns( array $patterns, array $pattern_ids ) {
		return array_filter(
			$patterns,
			function ( $pattern ) use ( $pattern_ids ) {
				if ( 'wp_block' !== $pattern['post_type'] ) {
					return false;
				}

				return ! in_array( (string) $pattern['ID'], $pattern_ids, true );
			}
		);
	}
}
