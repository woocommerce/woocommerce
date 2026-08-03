<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class ImportSetSiteOptions
 *
 * Importer for the SetSiteOptions step.
 *
 * @package Automattic\WooCommerce\Blueprint\Importers
 */
class ImportSetSiteOptions implements StepProcessor {
	use UseWPFunctions;

	/**
	 * List of WordPress options that should not be modified.
	 *
	 * Entries must be lowercase — keys are normalised to lowercase before being
	 * compared against this list. See is_restricted_option().
	 *
	 * @var array<string>
	 */
	private const RESTRICTED_OPTIONS = array(
		'siteurl',
		'home',
		'active_plugins',
		'template',
		'stylesheet',
		'admin_email',
		'unfiltered_html',
		'users_can_register',
		'default_role',
		'db_version',
		'cron',
		'rewrite_rules',
		'wp_user_roles',
	);

	/**
	 * Process the step.
	 *
	 * @param object $schema The schema to process.
	 *
	 * @return StepProcessorResult
	 */
	public function process( $schema ): StepProcessorResult {
		$result = StepProcessorResult::success( SetSiteOptions::get_step_name() );
		foreach ( $schema->options as $key => $value ) {
			// Skip if the option should not be modified.
			if ( $this->is_restricted_option( $key ) ) {
				$result->add_warn( "Cannot modify '{$key}' option: Modifying is restricted for this key." );
				continue;
			}

			$value         = json_decode( wp_json_encode( $value ), true );
			$updated       = $this->wp_update_option( $key, $value );
			$current_value = $this->wp_get_option( $key );

			if ( $current_value !== $value ) {
				$result->add_warn( "{$key} was intended to be set, but the stored value may have been overridden by a hook." );
				continue;
			}

			if ( $updated ) {
				$result->add_info( "{$key} has been updated." );
				continue;
			}

			if ( $current_value === $value ) {
				$result->add_info( "{$key} has not been updated because the current value is already up to date." );
			}
		}

		return $result;
	}

	/**
	 * Check whether a blueprint is allowed to write to the given option key.
	 *
	 * A plain, case-sensitive comparison against RESTRICTED_OPTIONS is not enough,
	 * because the key a blueprint supplies and the row WordPress ultimately writes to
	 * are matched by two different sets of rules:
	 *
	 * - WordPress trims option names before reading or writing them, so ' wp_user_roles'
	 *   and 'wp_user_roles' are the same option.
	 * - Option lookups are resolved by MySQL using the table's collation, which is
	 *   case-insensitive (and, for the utf8mb4 collations WordPress uses, also
	 *   accent-insensitive) by default. 'wp_USER_roles' therefore reads and writes the
	 *   existing 'wp_user_roles' row.
	 *
	 * This is checked in two layers. The first normalises the key and compares it against
	 * the list, which covers case and surrounding whitespace and works even when the
	 * restricted option does not exist yet. The second asks the database which row the key
	 * actually resolves to and restricts the write when that row is a restricted option,
	 * which covers equivalences string normalisation cannot model on its own.
	 *
	 * @param string $key The option key from the blueprint.
	 *
	 * @return bool True if the option must not be modified.
	 */
	protected function is_restricted_option( $key ): bool {
		if ( in_array( $this->normalize_option_name( $key ), self::RESTRICTED_OPTIONS, true ) ) {
			return true;
		}

		$stored_name = $this->get_stored_option_name( $key );

		return null !== $stored_name && in_array( $this->normalize_option_name( $stored_name ), self::RESTRICTED_OPTIONS, true );
	}

	/**
	 * Normalise an option name for comparison against RESTRICTED_OPTIONS.
	 *
	 * Mirrors the trimming WordPress applies to option names, and lowercases so the
	 * comparison matches the case-insensitive collation the database uses.
	 *
	 * @param string $key The option key to normalise.
	 *
	 * @return string
	 */
	private function normalize_option_name( $key ): string {
		return strtolower( trim( (string) $key ) );
	}

	/**
	 * Resolve an option key to the option name as it is actually stored.
	 *
	 * Runs the lookup through the database so the key is matched using the same collation
	 * WordPress uses when it reads and writes the option. Returns null when the key does
	 * not resolve to an existing row, or when there is no database to ask.
	 *
	 * @param string $key The option key from the blueprint.
	 *
	 * @return string|null The stored option name, or null if the key matches no row.
	 */
	protected function get_stored_option_name( $key ) {
		global $wpdb;

		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || ! method_exists( $wpdb, 'get_var' ) ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The point of this query is to let the database resolve the key with its own collation, which a cached lookup by the supplied key cannot do.
		$stored_name = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				trim( (string) $key )
			)
		);

		return is_string( $stored_name ) ? $stored_name : null;
	}

	/**
	 * Get the step class.
	 *
	 * @return string
	 */
	public function get_step_class(): string {
		return SetSiteOptions::class;
	}

	/**
	 * Check if the current user has the required capabilities for this step.
	 *
	 * @param object $schema The schema to process.
	 *
	 * @return bool True if the user has the required capabilities. False otherwise.
	 */
	public function check_step_capabilities( $schema ): bool {
		return current_user_can( 'manage_options' );
	}
}
