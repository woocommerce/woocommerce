<?php
/**
 * Handles polling and storage of specs
 *
 * @package WooCommerce Admin/Classes
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
		'https://woocommerce.com/wp-json/wccom//inbox-notifications/1.0/notifications.json',
	);

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
			self::merge_specs( $specs_from_data_source, $specs );
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
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
			return [];
		}

		$body  = $response['body'];
		$specs = json_decode( $body );

		if ( null === $specs ) {
			return [];
		}

		if ( ! is_array( $specs ) ) {
			return [];
		}

		return $specs;
	}

	/**
	 * Merge the specs.
	 *
	 * @param Array $specs_to_merge_in The specs to merge in to $specs.
	 * @param Array $specs             The master list of specs.
	 */
	private static function merge_specs( $specs_to_merge_in, &$specs ) {
		foreach ( $specs_to_merge_in as $spec ) {
			if ( ! self::validate_spec( $spec ) ) {
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
	 *
	 * @return bool The result of the validation.
	 */
	private static function validate_spec( $spec ) {
		if ( ! isset( $spec->slug ) ) {
			return false;
		}

		if ( ! isset( $spec->status ) ) {
			return false;
		}

		if ( ! isset( $spec->locales ) || ! is_array( $spec->locales ) ) {
			return false;
		}

		if ( null === SpecRunner::get_locale( $spec->locales ) ) {
			return false;
		}

		if ( ! isset( $spec->type ) ) {
			return false;
		}

		if ( ! isset( $spec->slug ) ) {
			return false;
		}

		if ( isset( $spec->actions ) && is_array( $spec->actions ) ) {
			foreach ( $spec->actions as $action ) {
				if ( ! self::validate_action( $action ) ) {
					return false;
				}
			}
		}

		if ( isset( $spec->rules ) && is_array( $spec->rules ) ) {
			foreach ( $spec->rules as $rule ) {
				if ( ! isset( $rule->type ) ) {
					return false;
				}

				$processor = GetRuleProcessor::get_processor( $rule->type );

				if ( ! $processor->validate( $rule ) ) {
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
	 *
	 * @return bool The result of the validation.
	 */
	private static function validate_action( $action ) {
		if ( ! isset( $action->locales ) || ! is_array( $action->locales ) ) {
			return false;
		}

		if ( null === SpecRunner::get_action_locale( $action->locales ) ) {
			return false;
		}

		if ( ! isset( $action->name ) ) {
			return false;
		}

		if ( ! isset( $action->status ) ) {
			return false;
		}

		return true;
	}
}
