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
	const EXCLUDED_PATTERNS = array( '13923', '14781', '14779', '13666', '13664', '13660', '13588', '14922', '14880', '13596', '13967', '13958', '15050', '15027' );

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
			// We want to flush the cached patterns when:
			// - The WooCommerce plugin is deactivated.
			// - The `woocommerce_allow_tracking` option is disabled.
			//
			// We also want to re-fetch the patterns and update the cache when:
			// - The `woocommerce_allow_tracking` option changes to enabled.
			// - The WooCommerce plugin is activated (if `woocommerce_allow_tracking` is enabled).
			// - The WooCommerce plugin is updated.

			add_action( 'woocommerce_activated_plugin', array( $this, 'flush_or_fetch_patterns' ), 10, 2 );
			add_action( 'update_option_woocommerce_allow_tracking', array( $this, 'flush_or_fetch_patterns' ), 10, 2 );
			add_action( 'deactivated_plugin', array( $this, 'flush_cached_patterns' ), 10, 2 );
			add_action( 'upgrader_process_complete', array( $this, 'fetch_patterns_on_plugin_update' ), 10, 2 );

			// This is the scheduled action that takes care of flushing and re-fetching the patterns from the PTK API.
			add_action( 'fetch_patterns', array( $this, 'fetch_patterns' ) );
		}
	}

	/**
	 * Resets the cached patterns when the `woocommerce_allow_tracking` option is disabled.
	 * Resets and fetch the patterns from the PTK when it is enabled (if the scheduler
	 * is initialized, it's done asynchronously via a scheduled action).
	 *
	 * @return void
	 */
	public function flush_or_fetch_patterns() {
		if ( $this->allowed_tracking_is_enabled() ) {
			$this->schedule_fetch_patterns();
			return;
		}

		$this->flush_cached_patterns();
	}

	/**
	 * Schedule an async action to fetch the PTK patterns when the scheduler is initialized.
	 *
	 * @return void
	 */
	private function schedule_fetch_patterns() {
		if ( did_action( 'action_scheduler_init' ) ) {
			$this->schedule_action_if_not_pending( 'fetch_patterns' );
		} else {
			add_action(
				'action_scheduler_init',
				function () {
					$this->schedule_action_if_not_pending( 'fetch_patterns' );
				}
			);
		}
	}

	/**
	 * Schedule an action if it's not already pending.
	 *
	 * @param string $action The action name to schedule.
	 * @return void
	 */
	private function schedule_action_if_not_pending( $action ) {
		if ( as_has_scheduled_action( $action ) ) {
			return;
		}

		as_schedule_single_action( time(), $action );
	}

	/**
	 * Get the patterns from the Patterns Toolkit cache.
	 *
	 * @return array
	 */
	public function get_patterns() {
		$patterns = get_transient( self::TRANSIENT_NAME );

		// Only if the transient is not set, we schedule fetching the patterns from the PTK.
		if ( false === $patterns ) {
			$this->schedule_fetch_patterns();
			return array();
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
	 * Re-fetch the patterns when the WooCommerce plugin is updated.
	 *
	 * @param WP_Upgrader $upgrader_object WP_Upgrader instance.
	 * @param array       $options Array of bulk item update data.
	 *
	 * @return void
	 */
	public function fetch_patterns_on_plugin_update( $upgrader_object, $options ) {
		if ( 'update' === $options['action'] && 'plugin' === $options['type'] && isset( $options['plugins'] ) ) {
			foreach ( $options['plugins'] as $plugin ) {
				if ( str_contains( $plugin, 'woocommerce.php' ) ) {
					$this->schedule_fetch_patterns();
				}
			}
		}
	}

	/**
	 * Reset the cached patterns to fetch them again from the PTK.
	 *
	 * @return void
	 */
	public function flush_cached_patterns() {
		delete_transient( self::TRANSIENT_NAME );
	}

	/**
	 * Reset the cached patterns and fetch them again from the PTK API.
	 *
	 * @return void
	 */
	public function fetch_patterns() {
		if ( ! $this->allowed_tracking_is_enabled() ) {
			return;
		}

		$this->flush_cached_patterns();

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

	/**
	 * Check if the user allowed tracking.
	 *
	 * @return bool
	 */
	private function allowed_tracking_is_enabled(): bool {
		return 'yes' === get_option( 'woocommerce_allow_tracking' );
	}
}
