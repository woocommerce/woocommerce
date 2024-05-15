<?php

namespace Automattic\WooCommerce\Blocks\Patterns;

use Automattic\WooCommerce\Admin\Features\Features;
use WP_Upgrader;

/**
 * PTKPatterns class.
 */
class PTKPatternsStore {
	const TRANSIENT_NAME = 'ptk_patterns';

	// Some patterns need to be excluded because they have dependencies which
	// are not installed by default (like Jetpack). Otherwise, the user
	// would see an error when trying to insert them in the editor.
	const EXCLUDED_PATTERNS = array( '13923', '14781', '14779', '13666', '13664', '13660', '13588', '14922', '14880', '13596', '13967', '13958' );

	/**
	 * PatternsToolkit instance.
	 *
	 * @var PTKClient $ptk_client
	 */
	private PTKClient $ptk_client;

	/**
	 * Constructor for the class.
	 *
	 * @param PTKClient $ptk_client An instance of PatternsToolkit.
	 */
	public function __construct( PTKClient $ptk_client ) {
		$this->ptk_client = $ptk_client;

		if ( Features::is_enabled( 'pattern-toolkit-full-composability' ) ) {
			register_activation_hook( WC_PLUGIN_FILE, array( $this, 'reset_cached_patterns' ) );
			add_action( 'upgrader_process_complete', array( $this, 'woocommerce_plugin_update' ), 10, 2 );
		}
	}

	/**
	 * Register block patterns from the Patterns Toolkit.
	 *
	 * @return void
	 */
	public function get_patterns() {
		$patterns = get_transient( self::TRANSIENT_NAME );

		// Only if the transient is not set, we fetch the patterns from the PTK.
		if ( false === $patterns ) {
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

			$patterns = $this->filter_patterns( $patterns, self::EXCLUDED_PATTERNS );

			set_transient( self::TRANSIENT_NAME, $patterns );
		}

		return $patterns;
	}

	/**
	 * Filter patterns to exclude those with the given IDs.
	 *
	 * @param array $patterns The patterns to filter.
	 * @param array $pattern_ids The pattern IDs to exclude.
	 * @return array
	 */
	private function filter_patterns( array $patterns, array $pattern_ids ) {
		return array_filter(
			$patterns,
			function ( $pattern ) use ( $pattern_ids ) {
				if ( ! isset( $pattern['ID'] ) ) {
					return true;
				}

				if ( isset( $pattern['post_type'] ) && 'wp_block' !== $pattern['post_type'] ) {
					return false;
				}

				return ! in_array( (string) $pattern['ID'], $pattern_ids, true );
			}
		);
	}

	/**
	 * Reset the cached patterns to fetch them again from the PTK.
	 *
	 * @return void
	 */
	private function reset_cached_patterns() {
		delete_transient( self::TRANSIENT_NAME );
	}

	/**
	 * Delete the transient when the WooCommerce plugin is updated to fetch the patterns again.
	 *
	 * @param WP_Upgrader $upgrader_object WP_Upgrader instance.
	 * @param array       $options Array of bulk item update data.
	 *
	 * @return void
	 */
	private function woocommerce_plugin_update( $upgrader_object, $options ) {
		if ( 'update' === $options['action'] && 'plugin' === $options['type'] && isset( $options['plugins'] ) ) {
			foreach ( $options['plugins'] as $plugin ) {
				if ( plugin_basename( __FILE__ ) === $plugin ) {
					$this->reset_cached_patterns();
				}
			}
		}
	}
}
