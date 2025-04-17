<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\RunSql;
use Automattic\WooCommerce\Blueprint\UsePluginHelpers;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Processes SQL execution steps in the Blueprint.
 *
 * Handles the execution of SQL queries with safety checks to prevent
 * unauthorized modifications to sensitive WordPress data.
 *
 * @package Automattic\WooCommerce\Blueprint\Importers
 */
class ImportRunSql implements StepProcessor {
	use UsePluginHelpers;
	use UseWPFunctions;

	/**
	 * List of allowed SQL query types.
	 *
	 * @var array
	 */
	private const ALLOWED_QUERY_TYPES = array(
		'INSERT',
		'UPDATE',
		'REPLACE INTO',
	);


	/**
	 * Process the SQL execution step.
	 *
	 * Validates and executes the SQL query while ensuring:
	 * 1. Only allowed query types are executed
	 * 2. No modifications to admin users or roles
	 * 3. No unauthorized changes to user capabilities
	 *
	 * @param object $schema The schema containing the SQL query to execute.
	 * @return StepProcessorResult The result of the SQL execution.
	 */
	public function process( $schema ): StepProcessorResult {
		global $wpdb;
		$result = StepProcessorResult::success( RunSql::get_step_name() );

		// Normalize whitespace to prevent obfuscation techniques.
		$normalized_sql = $this->normalize_sql( $schema->sql->contents );

		// Check if the query type is allowed.
		if ( ! $this->is_allowed_query_type( $normalized_sql ) ) {
			$result->add_error(
				sprintf(
					'Only %s queries are allowed.',
					implode( ', ', self::ALLOWED_QUERY_TYPES )
				)
			);
			return $result;
		}

		// Detect SQL injection patterns.
		if ( $this->contains_sql_injection_patterns( $normalized_sql ) ) {
			$result->add_error( 'SQL query contains potential injection patterns.' );
			return $result;
		}

		// Check if the query affects protected tables.
		if ( $this->affects_protected_tables( $normalized_sql ) ) {
			$result->add_error( 'Modifications to admin users or roles are not allowed.' );
			return $result;
		}

		// Check if the query affects user capabilities in wp_options.
		if ( $this->affects_user_capabilities( $normalized_sql ) ) {
			$result->add_error( 'Modifications to user roles or capabilities are not allowed.' );
			return $result;
		}

		$wpdb->suppress_errors();
		$wpdb->query( 'START TRANSACTION' );

		try {
			$query_result = $wpdb->query( $normalized_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( $wpdb->last_error ) {
				$wpdb->query( 'ROLLBACK' );
				$result->add_error( "Error executing SQL: {$wpdb->last_error}" );
			} else {
				$wpdb->query( 'COMMIT' );
				$result->add_debug( "Executed SQL ({$schema->sql->name}): Affected {$query_result} rows" );
			}
		} catch ( \Throwable $e ) {
			$wpdb->query( 'ROLLBACK' );
			$result->add_error( "Exception executing SQL: {$e->getMessage()}" );
		}

		return $result;
	}

	/**
	 * Returns the class name of the step this processor handles.
	 *
	 * @return string The class name of the step this processor handles.
	 */
	public function get_step_class(): string {
		return RunSql::class;
	}

	/**
	 * Check if the current user has the required capabilities for this step.
	 *
	 * @param object $schema The schema to process.
	 *
	 * @return bool True if the user has the required capabilities. False otherwise.
	 */
	public function check_step_capabilities( $schema ): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		if ( ! current_user_can( 'edit_users' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Normalize SQL by removing excessive whitespace and standardizing syntax.
	 *
	 * This method processes the SQL query to remove unnecessary whitespace characters,
	 * ensuring a consistent and readable format. It also standardizes the syntax
	 * around common tokens like operators and punctuation.
	 *
	 * @param string $sql_content The SQL query to normalize.
	 * @return string Normalized SQL query.
	 */
	public function normalize_sql( string $sql_content ): string {
		// Simplify by combining whitespace removal and comment removal.
		$sql = preg_replace( '/\s+/', ' ', $sql_content );
		$sql = preg_replace( '/--.*$/m', '', $sql ); // Remove single-line comments (-- style).
		$sql = preg_replace( '/#.*$/m', '', $sql ); // Remove hash-style comments (# style).

		$pattern = '/\/\*.*?\*\//s'; // Remove multi-line comments (/* style */).
		while ( preg_match( $pattern, $sql ) ) {
			$sql = preg_replace( $pattern, '', $sql );
		}

		// Standardize whitespace around common tokens.
		$sql = preg_replace( '/\s*(=|<|>|\(|\)|\+|-|\*|\/|,)\s*/', ' $1 ', $sql );

		return trim( $sql );
	}

	/**
	 * Check if the SQL query type is allowed.
	 *
	 * @param string $sql_content The SQL query to check.
	 * @return bool True if the query type is allowed, false otherwise.
	 */
	private function is_allowed_query_type( string $sql_content ): bool {
		foreach ( self::ALLOWED_QUERY_TYPES as $query_type ) {
			if ( 0 === stripos( trim( $sql_content ), $query_type ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check for common SQL injection patterns.
	 *
	 * @param string $sql_content The SQL query to check.
	 * @return bool True if potential injection patterns found, false otherwise.
	 */
	private function contains_sql_injection_patterns( string $sql_content ): bool {
		$patterns = array(
			'/UNION\s+(?:ALL\s+)?SELECT/i',  // UNION-based injections.
			'/OR\s+1\s*=\s*1/i',             // OR 1=1 condition.
			'/AND\s+0\s*=\s*0/i',            // AND 0=0 condition.
			'/;\s*--/i',                     // Inline comment terminations.
			'/SLEEP\s*\(/i',                 // Time-based injections.
			'/BENCHMARK\s*\(/i',             // Benchmark-based injections.
			'/LOAD_FILE\s*\(/i',             // File access.
			'/INTO\s+OUTFILE/i',             // File write.
			'/INTO\s+DUMPFILE/i',            // File dump.
			'/CREATE\s+(?:TEMPORARY\s+)?TABLE/i',  // Table creation.
			'/DROP\s+TABLE/i',               // Table deletion.
			'/ALTER\s+TABLE/i',              // Table alteration.
			'/INFORMATION_SCHEMA/i',         // Database metadata access.
			'/EXEC\s*\(/i',                  // Stored procedure execution.
			'/SCHEMA_NAME/i',                // Schema access.
			'/DATABASE\(\)/i',               // Current database name.
			'/CHR\s*\(/i',                   // Character function for evasion.
			'/CHAR\s*\(/i',                  // Character function for evasion.
			'/FROM\s+mysql\./i',             // Direct MySQL system table access.
			'/FROM\s+information_schema\./i', // Direct information schema access.
		);
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $sql_content ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Check if the SQL query affects protected user tables.
	 *
	 * @param string $sql_content The SQL query to check.
	 * @return bool True if the query affects protected tables, false otherwise.
	 */
	private function affects_protected_tables( string $sql_content ): bool {
		global $wpdb;

		$protected_tables = array(
			$wpdb->prefix . 'users',
			$wpdb->prefix . 'usermeta',
			$wpdb->usermeta,
			$wpdb->users,
		);

		foreach ( $protected_tables as $table ) {
			if ( preg_match( '/\b' . preg_quote( $table, '/' ) . '\b/i', $sql_content ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the SQL query affects user capabilities in wp_options.
	 *
	 * @param string $sql_content The SQL query to check.
	 * @return bool True if the query affects user capabilities, false otherwise.
	 */
	private function affects_user_capabilities( string $sql_content ): bool {
		global $wpdb;

		// Check if the query affects user capabilities in wp_options.
		if ( stripos( $sql_content, $wpdb->prefix . 'options' ) !== false ) {
			$option_patterns = array(
				'user_roles',
				'capabilities',
				'wp_user_',
				'role_',
				'administrator',
			);

			foreach ( $option_patterns as $pattern ) {
				if ( stripos( $sql_content, $pattern ) !== false ) {
					return true;
				}
			}
		}
		return false;
	}
}
