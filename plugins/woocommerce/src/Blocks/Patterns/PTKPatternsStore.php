<?php

namespace Automattic\WooCommerce\Blocks\Patterns;

use WP_Upgrader;

/**
 * PTKPatterns class.
 *
 * @internal
 */
class PTKPatternsStore {
	const OPTION_NAME = 'ptk_patterns';

	/**
	 * Hook and action name used to trigger fetching patterns.
	 */
	const FETCH_PATTERNS_ACTION = 'fetch_patterns';

	const CATEGORY_MAPPING = array(
		'testimonials' => 'reviews',
	);

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

		// We want to flush the cached patterns when:
		// - The WooCommerce plugin is deactivated.
		// - The `woocommerce_allow_tracking` option is disabled.
		//
		// Cancel any old fetch jobs when tracking is enabled or the plugin is updated.
		add_action( 'woocommerce_activated_plugin', array( $this, 'flush_or_fetch_patterns' ), 10, 2 );
		add_action( 'update_option_woocommerce_allow_tracking', array( $this, 'flush_or_fetch_patterns' ), 10, 2 );
		add_action( 'deactivated_plugin', array( $this, 'flush_cached_patterns' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( $this, 'fetch_patterns_on_plugin_update' ), 10, 2 );
		add_action( 'action_scheduler_ensure_recurring_actions', array( $this, 'ensure_recurring_fetch_patterns_if_enabled' ) );

		// Keep the callback for fetch jobs that were already scheduled.
		add_action( self::FETCH_PATTERNS_ACTION, array( $this, 'fetch_patterns' ) );
	}

	/**
	 * Resets the cached patterns when tracking is disabled and cancels old fetch jobs otherwise.
	 *
	 * @return void
	 */
	public function flush_or_fetch_patterns() {
		if ( $this->allowed_tracking_is_enabled() ) {
			$this->cancel_fetch_patterns_when_ready();
			return;
		}

		$this->flush_cached_patterns();
	}

	/**
	 * Cancel old pattern fetch actions when Action Scheduler is initialized.
	 *
	 * @return void
	 */
	private function cancel_fetch_patterns_when_ready() {
		if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
			return;
		}

		if ( did_action( 'action_scheduler_init' ) ) {
			as_unschedule_all_actions( self::FETCH_PATTERNS_ACTION, array(), 'woocommerce' );
		} else {
			add_action(
				'action_scheduler_init',
				function () {
					as_unschedule_all_actions( self::FETCH_PATTERNS_ACTION, array(), 'woocommerce' );
				}
			);
		}
	}

	/**
	 * Cancel old fetch pattern actions.
	 * This is called by the `action_scheduler_ensure_recurring_actions` hook.
	 *
	 * @return void
	 */
	public function ensure_recurring_fetch_patterns_if_enabled() {
		$this->cancel_fetch_patterns_when_ready();
	}

	/**
	 * Get the patterns from the Patterns Toolkit cache.
	 *
	 * @return array
	 */
	public function get_patterns() {
		$patterns = get_option( self::OPTION_NAME );

		// Return no patterns when the cache is missing or invalid.
		if ( false === $patterns || ! $this->ptk_client->is_valid_schema( $patterns ) ) {
			return array();
		}

		return $patterns;
	}

	/**
	 * Filter the patterns that have external dependencies.
	 *
	 * @param array $patterns The patterns to filter.
	 * @return array
	 */
	private function filter_patterns( array $patterns ) {
		return array_values(
			array_filter(
				$patterns,
				function ( $pattern ) {
					if ( ! isset( $pattern['ID'] ) ) {
						return true;
					}

					if ( isset( $pattern['post_type'] ) && 'wp_block' !== $pattern['post_type'] ) {
						return false;
					}

					if ( $this->has_external_dependencies( $pattern ) ) {
						return false;
					}

					return true;
				}
			)
		);
	}

	/**
	 * Cancel old fetch jobs when the WooCommerce plugin is updated.
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
					$this->cancel_fetch_patterns_when_ready();
				}
			}
		}
	}

	/**
	 * Reset the cached patterns to fetch them again from the PTK.
	 *
	 * @since 10.4.1 Unscheduling is deferred if Action Scheduler hasn't initialized yet.
	 * @return void
	 */
	public function flush_cached_patterns() {
		delete_option( self::OPTION_NAME );

		$this->cancel_fetch_patterns_when_ready();
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

		$patterns = $this->ptk_client->fetch_patterns(
			array(
				// This is the site where the patterns are stored. Despite the 'wpcomstaging.com' domain suggesting a staging environment, this URL points to the production environment where stable versions of the patterns are maintained.
				'site'       => 'wooblockpatterns.wpcomstaging.com',
				'categories' => array(
					'_woo_intro',
					'_woo_featured_selling',
					'_woo_about',
					'_woo_reviews',
					'_woo_social_media',
					'_woo_woocommerce',
					'_dotcom_imported_intro',
					'_dotcom_imported_about',
					'_dotcom_imported_services',
					'_dotcom_imported_reviews',
				),
			)
		);

		if ( is_wp_error( $patterns ) ) {
			wc_get_logger()->warning(
				sprintf(
				// translators: %s is a generated error message.
					__( 'Failed to get WooCommerce patterns from the PTK: "%s"', 'woocommerce' ),
					$patterns->get_error_message()
				),
			);
			return;
		}

		$patterns = $this->filter_patterns( $patterns );
		$patterns = $this->map_categories( $patterns );

		update_option( self::OPTION_NAME, $patterns, false );
	}

	/**
	 * Check if the user allowed tracking.
	 *
	 * @return bool
	 */
	private function allowed_tracking_is_enabled(): bool {
		return 'yes' === get_option( 'woocommerce_allow_tracking' );
	}

	/**
	 * Change the categories of the patterns to match the ones used in the CYS flow
	 *
	 * @param array $patterns The patterns to map categories for.
	 * @return array The patterns with the categories mapped.
	 */
	private function map_categories( array $patterns ) {
		return array_map(
			function ( $pattern ) {
				if ( isset( $pattern['categories'] ) ) {
					foreach ( $pattern['categories'] as $key => $category ) {
						if ( isset( $category['slug'] ) && isset( self::CATEGORY_MAPPING[ $key ] ) ) {
							$new_category = self::CATEGORY_MAPPING[ $key ];
							unset( $pattern['categories'][ $key ] );
							$pattern['categories'][ $new_category ]['slug']  = $new_category;
							$pattern['categories'][ $new_category ]['title'] = ucfirst( $new_category );
						}
					}
				}

				return $pattern;
			},
			$patterns
		);
	}

	/**
	 * Check if the pattern has external dependencies.
	 *
	 * @param array $pattern The pattern to check.
	 *
	 * @return bool
	 */
	private function has_external_dependencies( $pattern ) {
		if ( ! isset( $pattern['dependencies'] ) || ! is_array( $pattern['dependencies'] ) ) {
			return false;
		}

		foreach ( $pattern['dependencies'] as $dependency ) {
			if ( 'woocommerce' !== $dependency ) {
				return true;
			}
		}

		return false;
	}
}
