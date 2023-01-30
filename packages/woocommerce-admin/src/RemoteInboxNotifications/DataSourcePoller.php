<?php
/**
 * Handles polling and storage of specs
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

/**
 * Specs data source poller class.
 * This handles polling specs from JSON endpoints, and
 * stores the specs in to the database as an option.
 */
class DataSourcePoller {
	const DATA_SOURCES = array(
		'https://woocommerce.com/wp-json/wccom/inbox-notifications/1.0/notifications.json',
	);

	/**
	 * The logger instance.
	 *
	 * @var WC_Logger|null
	 */
	protected static $logger = null;

	/**
	 * Get the logger instance.
	 *
	 * @return WC_Logger
	 */
	private static function get_logger() {
		if ( is_null( self::$logger ) ) {
			self::$logger = wc_get_logger();
		}

		return self::$logger;
	}

	/**
	 * Reads the data sources for specs and persists those specs.
	 *
	 * @return bool Whether any specs were read.
	 */
	public static function read_specs_from_data_sources() {
		$specs = array();

		// Note that this merges the specs from the data sources based on the
		// slug - last one wins.
		foreach ( self::DATA_SOURCES as $url ) {
			$specs_from_data_source = self::read_data_source( $url, $specs );
			self::merge_specs( $specs_from_data_source, $specs, $url );
		}

		// Persist the specs as an option.
		update_option( RemoteInboxNotificationsEngine::SPECS_OPTION_NAME, $specs );

		return 0 !== count( $specs );
	}

	/**
	 * Read a single data source and return the read specs
	 *
	 * @param string $url The URL to read the specs from.
	 *
	 * @return array The specs that have been read from the data source.
	 */
	private static function read_data_source( $url ) {
		$logger_context = array( 'source' => $url );
		$logger         = self::get_logger();
		$response       = wp_remote_get( $url );

		if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
			$logger->error(
				'Error getting remote inbox notification data feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $response, true ), $logger_context );

			return [];
		}

		$body  = $response['body'];
		$specs = json_decode( $body );

		if ( null === $specs ) {
			$logger->error(
				'Empty response in remote inbox notification data feed',
				$logger_context
			);

			return [];
		}

		if ( ! is_array( $specs ) ) {
			$logger->error(
				'Remote inbox notification data feed is not an array',
				$logger_context
			);

			return [];
		}

		return $specs;
	}

	/**
	 * Merge the specs.
	 *
	 * @param Array  $specs_to_merge_in The specs to merge in to $specs.
	 * @param Array  $specs             The list of specs being merged into.
	 * @param string $url               The url of the feed being merged in (for error reporting).
	 */
	private static function merge_specs( $specs_to_merge_in, &$specs, $url ) {
		foreach ( $specs_to_merge_in as $spec ) {
			if ( ! self::validate_spec( $spec, $url ) ) {
				continue;
			}

			$slug           = $spec->slug;
			$specs[ $slug ] = $spec;
		}
	}

	/**
	 * Validate the spec.
	 *
	 * @param object $spec The spec to validate.
	 * @param string $url  The url of the feed that provided the spec.
	 *
	 * @return bool The result of the validation.
	 */
	private static function validate_spec( $spec, $url ) {
		$logger         = self::get_logger();
		$logger_context = array( 'source' => $url );

		if ( ! isset( $spec->slug ) ) {
			$logger->error(
				'Spec is invalid because the slug is missing in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $spec, true ), $logger_context );

			return false;
		}

		if ( ! isset( $spec->status ) ) {
			$logger->error(
				'Spec is invalid because the status is missing in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $spec, true ), $logger_context );

			return false;
		}

		if ( ! isset( $spec->locales ) || ! is_array( $spec->locales ) ) {
			$logger->error(
				'Spec is invalid because the status is missing or empty in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $spec, true ), $logger_context );

			return false;
		}

		if ( null === SpecRunner::get_locale( $spec->locales ) ) {
			$logger->error(
				'Spec is invalid because the locale could not be retrieved in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $spec, true ), $logger_context );

			return false;
		}

		if ( ! isset( $spec->type ) ) {
			$logger->error(
				'Spec is invalid because the type is missing in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $spec, true ), $logger_context );

			return false;
		}

		if ( isset( $spec->actions ) && is_array( $spec->actions ) ) {
			foreach ( $spec->actions as $action ) {
				if ( ! self::validate_action( $action, $url ) ) {
					$logger->error(
						'Spec is invalid because an action is invalid in feed',
						$logger_context
					);
					// phpcs:ignore
					$logger->error( print_r( $spec, true ), $logger_context );

					return false;
				}
			}
		}

		if ( isset( $spec->rules ) && is_array( $spec->rules ) ) {
			foreach ( $spec->rules as $rule ) {
				if ( ! isset( $rule->type ) ) {
					$logger->error(
						'Spec is invalid because a rule type is empty in feed',
						$logger_context
					);
					// phpcs:ignore
					$logger->error( print_r( $rule, true ), $logger_context );
					// phpcs:ignore
					$logger->error( print_r( $spec, true ), $logger_context );

					return false;
				}

				$processor = GetRuleProcessor::get_processor( $rule->type );

				if ( ! $processor->validate( $rule ) ) {
					$logger->error(
						'Spec is invalid because a rule is invalid in feed',
						$logger_context
					);
					// phpcs:ignore
					$logger->error( print_r( $rule, true ), $logger_context );
					// phpcs:ignore
					$logger->error( print_r( $spec, true ), $logger_context );

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Validate the action.
	 *
	 * @param object $action The action to validate.
	 * @param string $url    The url of the feed containing the action (for error reporting).
	 *
	 * @return bool The result of the validation.
	 */
	private static function validate_action( $action, $url ) {
		$logger         = self::get_logger();
		$logger_context = array( 'source' => $url );

		if ( ! isset( $action->locales ) || ! is_array( $action->locales ) ) {
			$logger->error(
				'Action is invalid because it has empty or missing locales in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $action, true ), $logger_context );

			return false;
		}

		if ( null === SpecRunner::get_action_locale( $action->locales ) ) {
			$logger->error(
				'Action is invalid because the locale could not be retrieved in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $action, true ), $logger_context );

			return false;
		}

		if ( ! isset( $action->name ) ) {
			$logger->error(
				'Action is invalid because the name is missing in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $action, true ), $logger_context );

			return false;
		}

		if ( ! isset( $action->status ) ) {
			$logger->error(
				'Action is invalid because the status is missing in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $action, true ), $logger_context );

			return false;
		}

		return true;
	}
}
