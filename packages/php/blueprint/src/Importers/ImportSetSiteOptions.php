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
	 * Entries must be lowercase so normalised keys can be compared directly before
	 * the database-collation check.
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
		$result                  = StepProcessorResult::success( SetSiteOptions::get_step_name() );
		$restricted_option_names = $this->get_restricted_option_names( array_keys( (array) $schema->options ) );
		foreach ( $schema->options as $key => $value ) {
			// Skip if the option should not be modified.
			if ( isset( $restricted_option_names[ (string) $key ] ) ) {
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
	 * Find restricted option names among imported keys.
	 *
	 * @param array<int|string> $keys Option keys from the blueprint.
	 *
	 * @return array<int|string, bool> Restricted option names as keys.
	 */
	private function get_restricted_option_names( array $keys ): array {
		$restricted_option_names = array();
		$database_candidates     = array();

		foreach ( $keys as $key ) {
			$key = (string) $key;
			if ( in_array( self::normalize_option_name( $key ), self::RESTRICTED_OPTIONS, true ) ) {
				$restricted_option_names[ $key ] = true;
				continue;
			}

			$database_candidates[] = array(
				'original' => $key,
				'trimmed'  => trim( $key ),
			);
		}

		$candidate_names = array_column( $database_candidates, 'trimmed' );
		foreach ( $this->get_collation_restricted_option_indexes( $candidate_names ) as $candidate_index ) {
			if ( ! isset( $database_candidates[ $candidate_index ] ) ) {
				continue;
			}
			$restricted_option_names[ $database_candidates[ $candidate_index ]['original'] ] = true;
		}

		return $restricted_option_names;
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
	private static function normalize_option_name( $key ): string {
		return strtolower( trim( (string) $key ) );
	}

	/**
	 * Match candidates against restricted names using the options table collation.
	 *
	 * The empty option_name branches give both derived tables the real column type and
	 * collation without requiring a restricted option row to exist.
	 *
	 * @param array<string> $candidate_names Trimmed option names from the blueprint.
	 *
	 * @return array<int> Indexes of candidates that match a restricted option.
	 */
	private function get_collation_restricted_option_indexes( array $candidate_names ): array {
		global $wpdb;

		if ( empty( $candidate_names ) || ! isset( $wpdb ) || ! is_object( $wpdb ) || ! isset( $wpdb->options ) || ! method_exists( $wpdb, 'get_col' ) || ! method_exists( $wpdb, 'prepare' ) ) {
			return array();
		}

		$candidate_rows  = implode(
			' UNION ALL ',
			array_fill( 0, count( $candidate_names ), 'SELECT %d AS candidate_index, %s AS option_name' )
		);
		$restricted_rows = implode(
			' UNION ALL ',
			array_fill( 0, count( self::RESTRICTED_OPTIONS ), 'SELECT %s AS option_name' )
		);
		$query_args      = array();

		foreach ( $candidate_names as $candidate_index => $candidate_name ) {
			$query_args[] = $candidate_index;
			$query_args[] = $candidate_name;
		}
		$query_args = array_merge( $query_args, self::RESTRICTED_OPTIONS );

		$query = "
			SELECT DISTINCT candidates.candidate_index
			FROM (
				SELECT 0 AS candidate_index, option_name FROM {$wpdb->options} WHERE 1 = 0
				UNION ALL {$candidate_rows}
			) AS candidates
			INNER JOIN (
				SELECT option_name FROM {$wpdb->options} WHERE 1 = 0
				UNION ALL {$restricted_rows}
			) AS restricted_options USING ( option_name )
		";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- A single prepared comparison applies the options table's collation without an N-query loop.
		$restricted_indexes = $wpdb->get_col( $wpdb->prepare( $query, $query_args ) );

		return array_map( 'intval', $restricted_indexes );
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
