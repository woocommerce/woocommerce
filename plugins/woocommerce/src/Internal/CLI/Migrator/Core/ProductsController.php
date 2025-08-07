<?php
/**
 * Products Controller
 *
 * @package Automattic\WooCommerce\Internal\CLI\Migrator\Core
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Core;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\CredentialManager;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry;
use Automattic\WooCommerce\Internal\CLI\Migrator\Lib\ImportSession;
use Exception;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * ProductsController class.
 *
 * Main orchestration engine for product migration that integrates existing components
 * (PlatformRegistry, CredentialManager, ShopifyFetcher/Mapper, ImportSession) to create
 * a cohesive migration system with cursor-based resumption.
 *
 * @internal This class is part of the CLI Migrator feature and should not be used directly.
 */
final class ProductsController {

	/**
	 * The credential manager.
	 *
	 * @var CredentialManager
	 */
	private CredentialManager $credential_manager;

	/**
	 * The platform registry.
	 *
	 * @var PlatformRegistry
	 */
	private PlatformRegistry $platform_registry;

	/**
	 * Current import session.
	 *
	 * @var ImportSession|null
	 */
	private ?ImportSession $session = null;

	/**
	 * Parsed command arguments.
	 *
	 * @var array
	 */
	private array $parsed_args = array();

	/**
	 * Fields to process during migration.
	 *
	 * @var array
	 */
	private array $fields_to_process = array();

	/**
	 * Constructor.
	 *
	 * @param CredentialManager $credential_manager The credential manager.
	 * @param PlatformRegistry  $platform_registry  The platform registry.
	 */
	public function __construct(
		CredentialManager $credential_manager,
		PlatformRegistry $platform_registry
	) {
		$this->credential_manager = $credential_manager;
		$this->platform_registry  = $platform_registry;
	}

	/**
	 * Required for WooCommerce DI pattern compliance.
	 *
	 * @internal
	 */
	final public function init(): void {
		// No additional initialization needed.
	}

	/**
	 * Main entry point for migrating products with complete cursor-based migration loop.
	 *
	 * @param array $assoc_args Command-line arguments.
	 * @return void
	 */
	public function migrate_products( array $assoc_args ): void {
		// Parse and validate arguments.
		$this->parsed_args = $this->parse_and_validate_args( $assoc_args );
		if ( empty( $this->parsed_args ) ) {
			return; // Error already logged in parse method.
		}

		// Initialize or resume session.
		$this->session = $this->manage_session_lifecycle( $this->parsed_args );
		if ( ! $this->session ) {
			return; // Error already logged in session method.
		}

		// Get platform components.
		$fetcher = $this->platform_registry->get_fetcher( $this->parsed_args['platform'] );
		$mapper  = $this->platform_registry->get_mapper( $this->parsed_args['platform'] );

		// Fetch total count and setup progress tracking.
		$total_count = $fetcher->fetch_total_count( $this->parsed_args['filters'] );
		if ( $total_count > 0 ) {
			$this->session->bump_total_number_of_entities( array( 'post' => $total_count ) );
		}

		WP_CLI::line( "Total entities found: {$total_count}" );
		$progress = \WP_CLI\Utils\make_progress_bar(
			'Importing Products from ' . ucfirst( $this->parsed_args['platform'] ),
			$total_count
		);
		$progress->tick( $this->session->count_all_imported_entities(), false );

		// Main cursor-based migration loop.
		$this->execute_migration_loop( $fetcher, $mapper, $progress );

		$progress->finish();
		WP_CLI::success( 'Migration completed successfully.' );
	}

	/**
	 * Execute the main cursor-based migration loop.
	 *
	 * @param object $fetcher  The platform fetcher instance.
	 * @param object $mapper   The platform mapper instance.
	 * @param object $progress The WP_CLI progress bar instance.
	 * @return void
	 */
	private function execute_migration_loop( $fetcher, $mapper, $progress ): void {
		$limit_remaining = $this->parsed_args['limit'];
		$session_cursor  = $this->session->get_reentrancy_cursor();
		$after_cursor    = ! empty( $session_cursor ) ? $session_cursor : null;
		$has_next_page   = true;

		do {
			$batch_limit = min( $this->parsed_args['batch_size'], $limit_remaining );
			if ( $batch_limit <= 0 ) {
				break; // Limit reached.
			}

			// Fetch batch using existing fetcher.
			$fetch_args = array(
				'limit'        => $batch_limit,
				'after_cursor' => $after_cursor,
				// Add query filters from parsed args if needed.
			);

			// Merge in any additional filters.
			if ( ! empty( $this->parsed_args['filters'] ) ) {
				$fetch_args = array_merge( $fetch_args, $this->parsed_args['filters'] );
			}

			try {
				$batch_data = $fetcher->fetch_batch( $fetch_args );
			} catch ( Exception $e ) {
				WP_CLI::warning( "Error fetching batch: {$e->getMessage()}" );
				continue;
			}

			if ( empty( $batch_data['items'] ) ) {
				WP_CLI::line( 'No more products found in this batch.' );
				break;
			}

			// Process batch items (product mapping and import logic will be implemented in the next phase).
			$processed_count = count( $batch_data['items'] ); // Placeholder for now.

			// Update session with progress and cursor.
			$this->session->bump_imported_entities_counts( array( 'post' => $processed_count ) );
			$after_cursor = $batch_data['cursor'];
			$this->session->set_reentrancy_cursor( $after_cursor );

			// Update loop variables.
			$limit_remaining -= count( $batch_data['items'] );
			$has_next_page    = $batch_data['has_next_page'] ?? false;

			$progress->tick( $processed_count );

		} while ( $has_next_page && $limit_remaining > 0 );

		// Mark session as finished if complete.
		if ( ! $has_next_page ) {
			// Session stage management will be implemented in the next phase.
			WP_CLI::log( 'Migration completed - all products processed.' );
		}
	}

	/**
	 * Parse and validate command-line arguments.
	 *
	 * @param array $assoc_args Raw associative arguments.
	 * @return array Parsed and validated arguments or empty array on error.
	 */
	private function parse_and_validate_args( array $assoc_args ): array {
		$parsed = array();

		// Platform validation.
		$platform = $this->platform_registry->resolve_platform( $assoc_args );
		if ( ! $platform ) {
			return array(); // Error already logged in resolve_platform.
		}
		$parsed['platform'] = $platform;

		// Field selection and validation.
		$this->fields_to_process = $this->parse_field_selection( $assoc_args );
		$parsed['fields']        = $this->fields_to_process;

		// Migration parameters.
		$parsed['limit']         = isset( $assoc_args['limit'] ) ? max( 1, (int) $assoc_args['limit'] ) : PHP_INT_MAX;
		$parsed['batch_size']    = isset( $assoc_args['batch-size'] ) ? max( 1, min( 250, (int) $assoc_args['batch-size'] ) ) : 20;
		$parsed['skip_existing'] = isset( $assoc_args['skip-existing'] );
		$parsed['dry_run']       = isset( $assoc_args['dry-run'] );
		$parsed['resume']        = isset( $assoc_args['resume'] );

		// Query filters (inspired by reference implementation).
		$parsed['filters'] = $this->parse_query_filters( $assoc_args );

		// Validate credentials exist for platform.
		if ( ! $this->credential_manager->has_credentials( $platform ) ) {
			WP_CLI::error(
				sprintf(
					"No credentials found for platform '%s'. Please run: wp wc migrate setup --platform=%s",
					$platform,
					$platform
				)
			);
			return array();
		}

		WP_CLI::log( sprintf( 'Validated arguments for %s platform migration.', $platform ) );
		return $parsed;
	}

	/**
	 * Parse field selection from command arguments.
	 *
	 * @param array $assoc_args Command arguments.
	 * @return array Selected fields to process.
	 */
	private function parse_field_selection( array $assoc_args ): array {
		$default_fields = array(
			'name',
			'slug',
			'description',
			'status',
			'date_created',
			'catalog_visibility',
			'categories',
			'tags',
			'price',
			'sku',
			'stock',
			'weight',
			'brand',
			'images',
			'attributes',
			'metafields',
		);

		// Handle --fields argument.
		if ( isset( $assoc_args['fields'] ) ) {
			$selected_fields = array_map( 'trim', explode( ',', $assoc_args['fields'] ) );
			$selected_fields = array_filter( $selected_fields ); // Remove empty values.

			// Validate field names.
			$invalid_fields = array_diff( $selected_fields, $default_fields );
			if ( ! empty( $invalid_fields ) ) {
				WP_CLI::warning(
					sprintf(
						'Invalid field names: %s. Valid fields: %s',
						implode( ', ', $invalid_fields ),
						implode( ', ', $default_fields )
					)
				);
			}

			$fields = array_intersect( $selected_fields, $default_fields );
		} else {
			$fields = $default_fields;
		}

		// Handle --exclude-fields argument.
		if ( isset( $assoc_args['exclude-fields'] ) ) {
			$exclude_fields = array_map( 'trim', explode( ',', $assoc_args['exclude-fields'] ) );
			$fields         = array_diff( $fields, $exclude_fields );

			WP_CLI::log(
				sprintf( 'Excluding fields: %s', implode( ', ', $exclude_fields ) )
			);
		}

		if ( empty( $fields ) ) {
			WP_CLI::error( 'No valid fields selected for migration.' );
			return array();
		}

		WP_CLI::log(
			sprintf( 'Selected fields for migration: %s', implode( ', ', $fields ) )
		);

		return $fields;
	}

	/**
	 * Parse query filters for Shopify-specific filtering.
	 *
	 * @param array $assoc_args Command arguments.
	 * @return array Parsed query filters.
	 */
	private function parse_query_filters( array $assoc_args ): array {
		$filters = array();

		// Status filter.
		if ( isset( $assoc_args['status'] ) ) {
			$valid_statuses = array( 'active', 'archived', 'draft' );
			$status         = strtolower( $assoc_args['status'] );
			if ( in_array( $status, $valid_statuses, true ) ) {
				$filters['status'] = strtoupper( $status );
			} else {
				WP_CLI::warning(
					sprintf(
						'Invalid status "%s". Valid options: %s',
						$status,
						implode( ', ', $valid_statuses )
					)
				);
			}
		}

		// Date range filters.
		if ( isset( $assoc_args['created-after'] ) ) {
			$date = $this->validate_date_filter( $assoc_args['created-after'], 'created-after' );
			if ( $date ) {
				$filters['created_after'] = $date;
			}
		}

		if ( isset( $assoc_args['created-before'] ) ) {
			$date = $this->validate_date_filter( $assoc_args['created-before'], 'created-before' );
			if ( $date ) {
				$filters['created_before'] = $date;
			}
		}

		// Product type filter.
		if ( isset( $assoc_args['product-type'] ) && 'all' !== $assoc_args['product-type'] ) {
			$filters['product_type'] = $assoc_args['product-type'];
		}

		// Handle filter.
		if ( isset( $assoc_args['handle'] ) ) {
			$filters['handle'] = sanitize_title( $assoc_args['handle'] );
		}

		return $filters;
	}

	/**
	 * Validate date filter input.
	 *
	 * @param string $date_input  The date input string.
	 * @param string $filter_name The filter name for error messages.
	 * @return string|null Formatted date string or null on error.
	 */
	private function validate_date_filter( string $date_input, string $filter_name ): ?string {
		$timestamp = strtotime( $date_input );
		if ( false === $timestamp ) {
			WP_CLI::warning(
				sprintf( 'Invalid date format for --%s: %s', $filter_name, $date_input )
			);
			return null;
		}

		return gmdate( 'Y-m-d\\TH:i:s\\Z', $timestamp );
	}

	/**
	 * Manage the session lifecycle - create new or resume existing.
	 *
	 * @param array $parsed_args Parsed command arguments.
	 * @return ImportSession|null Import session instance or null on error.
	 */
	private function manage_session_lifecycle( array $parsed_args ): ?ImportSession {
		// Check for existing active session.
		$active_session = ImportSession::get_active();

		if ( $active_session && ! $active_session->is_finished() ) {
			return $this->handle_existing_session( $active_session, $parsed_args );
		}

		// Create new session.
		return $this->create_new_session( $parsed_args );
	}

	/**
	 * Handle existing session with user prompt for resume decision.
	 *
	 * @param ImportSession $session     The existing session.
	 * @param array         $parsed_args Parsed command arguments.
	 * @return ImportSession|null Session to use or null on error.
	 */
	private function handle_existing_session( ImportSession $session, array $parsed_args ): ?ImportSession {
		// Display session information.
		$metadata = $session->get_metadata();

		$total_imported = $session->count_all_imported_entities();
		$total_entities = $session->count_all_total_entities();
		$started_at     = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $session->get_started_at() ) );

		WP_CLI::line( '' );
		WP_CLI::line( WP_CLI::colorize( '%YExisting Migration Session Found:%n' ) );
		WP_CLI::line( sprintf( '  Session ID: %d', $session->get_id() ) );
		WP_CLI::line( sprintf( '  Platform: %s', $metadata['data_source'] ) );
		WP_CLI::line( sprintf( '  Started: %s', $started_at ) );
		WP_CLI::line( sprintf( '  Progress: %d / %d products imported', $total_imported, $total_entities ) );

		if ( $session->get_reentrancy_cursor() ) {
			WP_CLI::line( sprintf( '  Last Cursor: %s', substr( $session->get_reentrancy_cursor(), 0, 50 ) . '...' ) );
		}

		WP_CLI::line( '' );

		// Handle resume decision.
		$should_resume = $parsed_args['resume'] ?? false;

		if ( ! $should_resume ) {
			WP_CLI::confirm( 'Do you want to resume this migration session?' );
			$should_resume = true; // If we get here, user confirmed.
		}

		if ( $should_resume ) {
			WP_CLI::success( sprintf( 'Resuming migration session %d', $session->get_id() ) );
			return $session;
		} else {
			// Archive old session and create new one.
			$session->archive();
			WP_CLI::line( 'Previous session archived. Creating new migration session.' );
			return $this->create_new_session( $parsed_args );
		}
	}

	/**
	 * Create a new import session.
	 *
	 * @param array $parsed_args Parsed command arguments.
	 * @return ImportSession|null New session instance or null on error.
	 */
	private function create_new_session( array $parsed_args ): ?ImportSession {
		try {
			$session = ImportSession::create(
				array(
					'data_source' => $parsed_args['platform'],
					'file_name'   => sprintf(
						'%s Migration - %s',
						ucfirst( $parsed_args['platform'] ),
						current_time( 'mysql' )
					),
				)
			);

			WP_CLI::success( sprintf( 'Created new migration session %d', $session->get_id() ) );
			return $session;

		} catch ( Exception $e ) {
			WP_CLI::error( sprintf( 'Failed to create migration session: %s', $e->getMessage() ) );
			return null;
		}
	}
}
