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
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\WooCommerceProductImporter;
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
class ProductsController {

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
	 * WooCommerce Product Importer instance.
	 *
	 * @var WooCommerceProductImporter
	 */
	private WooCommerceProductImporter $product_importer;

	/**
	 * Initialize the controller with its dependencies.
	 * Called automatically by the WooCommerce DI container.
	 *
	 * @internal
	 *
	 * @param CredentialManager          $credential_manager The credential manager.
	 * @param PlatformRegistry           $platform_registry  The platform registry.
	 * @param WooCommerceProductImporter $product_importer   The product importer.
	 */
	final public function init(
		CredentialManager $credential_manager,
		PlatformRegistry $platform_registry,
		WooCommerceProductImporter $product_importer
	): void {
		$this->credential_manager = $credential_manager;
		$this->platform_registry  = $platform_registry;
		$this->product_importer   = $product_importer;
	}

	/**
	 * Main entry point for migrating products.
	 *
	 * @param array  $assoc_args Command-line arguments.
	 * @param string $platform   Optional pre-resolved platform (to avoid duplicate resolution).
	 * @return void
	 */
	public function migrate_products( array $assoc_args, string $platform = '' ): void {
		$this->parsed_args = $this->parse_and_validate_args( $assoc_args, $platform );
		if ( empty( $this->parsed_args ) ) {
			return;
		}

		$this->session = $this->manage_session_lifecycle( $this->parsed_args );
		if ( ! $this->session ) {
			return;
		}

		// Get platform components.
		$fetcher = $this->platform_registry->get_fetcher( $this->parsed_args['platform'] );
		$mapper  = $this->platform_registry->get_mapper( $this->parsed_args['platform'], array( 'fields' => $this->fields_to_process ) );

		// Fetch total count and setup progress tracking.
		$total_count = $fetcher->fetch_total_count( $this->parsed_args['filters'] );

		// Only set total count if it hasn't been set yet (new session or first time).
		$existing_total = $this->session->count_all_total_entities();
		if ( 0 < $total_count && 0 === $existing_total ) {
			$this->session->bump_total_number_of_entities( array( 'post' => $total_count ) );
		}

		WP_CLI::line( "Total entities found: {$total_count}" );
		$progress = \WP_CLI\Utils\make_progress_bar(
			'Importing Products from ' . ucfirst( $this->parsed_args['platform'] ),
			$total_count
		);
		$progress->tick( $this->session->count_all_imported_entities(), false );

		$this->configure_product_importer();

		$this->execute_migration_loop( $fetcher, $mapper, $progress );

		$progress->finish();

		$this->display_migration_summary();

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
		$limit_remaining            = $this->parsed_args['limit'];
		$session_cursor             = $this->session->get_reentrancy_cursor();
		$after_cursor               = ! empty( $session_cursor ) ? $session_cursor : null;
		$has_next_page              = true;
		$total_processed_in_session = 0;

		do {
			$batch_limit = min( $this->parsed_args['batch_size'], $limit_remaining );
			if ( $batch_limit <= 0 ) {
				break;
			}

			$batch_args = array(
				'limit'        => $batch_limit,
				'after_cursor' => $after_cursor,
			);

			if ( ! empty( $this->parsed_args['filters'] ) ) {
				$batch_args = array_merge( $batch_args, $this->parsed_args['filters'] );
			}

			try {
				$batch_data = $fetcher->fetch_batch( $batch_args );
			} catch ( Exception $e ) {
				WP_CLI::warning( "Error fetching batch: {$e->getMessage()}" );
				break;
			}

			if ( empty( $batch_data['items'] ) ) {
				WP_CLI::line( 'No more products found in this batch.' );
				break;
			}

			$processed_count = $this->process_batch( $batch_data['items'], $mapper );

			$total_processed_in_session += $processed_count;

			$this->session->bump_imported_entities_counts( array( 'post' => $processed_count ) );
			$after_cursor = $batch_data['cursor'];
			$this->session->set_reentrancy_cursor( $after_cursor );

			$limit_remaining -= count( $batch_data['items'] );
			$has_next_page    = $batch_data['has_next_page'] ?? false;

			$progress->tick( $processed_count );

		} while ( $has_next_page && $limit_remaining > 0 );

		if ( $total_processed_in_session > 0 ) {
			WP_CLI::success( sprintf( 'Processed %d products in this session', $total_processed_in_session ) );
		}

		if ( ! $has_next_page ) {
			$this->session->set_stage( ImportSession::STAGE_FINISHED );
			WP_CLI::log( 'Migration completed - all products processed.' );
		}
	}

	/**
	 * Parse and validate command-line arguments.
	 *
	 * @param array  $assoc_args Raw associative arguments.
	 * @param string $platform   Optional pre-resolved platform.
	 * @return array Parsed and validated arguments or empty array on error.
	 */
	private function parse_and_validate_args( array $assoc_args, string $platform = '' ): array {
		$parsed = array();

		// Platform validation - use pre-resolved platform if provided, otherwise resolve.
		if ( empty( $platform ) ) {
			$platform = $this->platform_registry->resolve_platform( $assoc_args );
			if ( empty( $platform ) ) {
				return array();
			}
		}
		$parsed['platform'] = $platform;

		$this->fields_to_process = $this->parse_field_selection( $assoc_args );

		$parsed['fields']                  = $this->fields_to_process;
		$parsed['limit']                   = isset( $assoc_args['limit'] ) ? max( 1, (int) $assoc_args['limit'] ) : PHP_INT_MAX;
		$parsed['batch_size']              = isset( $assoc_args['batch-size'] ) ? max( 1, min( 250, (int) $assoc_args['batch-size'] ) ) : 20;
		$parsed['skip_existing']           = isset( $assoc_args['skip-existing'] );
		$parsed['dry_run']                 = isset( $assoc_args['dry-run'] );
		$parsed['resume']                  = isset( $assoc_args['resume'] );
		$parsed['verbose']                 = isset( $assoc_args['verbose'] );
		$parsed['assign_default_category'] = isset( $assoc_args['assign-default-category'] );

		$parsed['filters'] = $this->parse_query_filters( $assoc_args );

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

		$excluded_fields     = array();
		$explicitly_selected = false;

		if ( isset( $assoc_args['fields'] ) ) {
			$explicitly_selected = true;
			$selected_fields     = array_map( 'trim', explode( ',', $assoc_args['fields'] ) );
			$selected_fields     = array_filter( $selected_fields );

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

			$fields          = array_intersect( $selected_fields, $default_fields );
			$excluded_fields = array_diff( $default_fields, $fields );
		} else {
			$fields = $default_fields;
		}

		// Handle --exclude-fields argument.
		if ( isset( $assoc_args['exclude-fields'] ) ) {
			$exclude_fields_input = array_map( 'trim', explode( ',', $assoc_args['exclude-fields'] ) );
			$excluded_fields      = array_merge( $excluded_fields, $exclude_fields_input );
			$fields               = array_diff( $fields, $exclude_fields_input );
		}

		if ( empty( $fields ) ) {
			WP_CLI::error( 'No valid fields selected for migration.' );
			return array();
		}

		// Log field selection information.
		if ( $explicitly_selected || isset( $assoc_args['exclude-fields'] ) || ! empty( $assoc_args['verbose'] ) ) {
			$include_message = sprintf( 'Including fields: %s', implode( ', ', $fields ) );
			WP_CLI::log( $include_message );
			wc_get_logger()->info( $include_message, array( 'source' => 'wc-migrator' ) );

			if ( ! empty( $excluded_fields ) ) {
				$exclude_message = sprintf( 'Excluding fields: %s', implode( ', ', array_unique( $excluded_fields ) ) );
				WP_CLI::log( $exclude_message );
				wc_get_logger()->info( $exclude_message, array( 'source' => 'wc-migrator' ) );
			}
		}

		return $fields;
	}

	/**
	 * Parse query filters for platform-agnostic filtering.
	 *
	 * @param array $assoc_args Command arguments.
	 * @return array Parsed query filters.
	 */
	private function parse_query_filters( array $assoc_args ): array {
		$filters = array();

		if ( isset( $assoc_args['status'] ) ) {
			$valid_statuses = array( 'active', 'archived', 'draft' );
			$status         = strtolower( $assoc_args['status'] );
			if ( in_array( $status, $valid_statuses, true ) ) {
				$filters['status'] = $status;
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

		if ( isset( $assoc_args['product-type'] ) && 'all' !== $assoc_args['product-type'] ) {
			$filters['product_type'] = $assoc_args['product-type'];
		}

		if ( isset( $assoc_args['handle'] ) ) {
			$filters['handle'] = sanitize_title( $assoc_args['handle'] );
		}

		if ( isset( $assoc_args['vendor'] ) ) {
			$filters['vendor'] = $assoc_args['vendor'];
		}

		if ( isset( $assoc_args['ids'] ) ) {
			$filters['ids'] = $assoc_args['ids'];
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
		$active_session = ImportSession::get_active();

		if ( $active_session && ! $active_session->is_finished() ) {
			return $this->handle_existing_session( $active_session, $parsed_args );
		}

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

		$total_imported    = $session->count_all_imported_entities();
		$total_entities    = $session->count_all_total_entities();
		$started_timestamp = $session->get_started_at();
		$started_at        = is_numeric( $started_timestamp ) ?
			get_date_from_gmt( gmdate( 'Y-m-d H:i:s', (int) $started_timestamp ) ) :
			$started_timestamp;

		WP_CLI::line( '' );
		WP_CLI::line( WP_CLI::colorize( '%YExisting Migration Session Found:%n' ) );
		WP_CLI::line( sprintf( '  Session ID: %d', $session->get_id() ) );
		WP_CLI::line( sprintf( '  Platform: %s', $metadata['data_source'] ) );
		WP_CLI::line( sprintf( '  Started: %s', $started_at ) );
		WP_CLI::line( sprintf( '  Progress: %d / %d products imported', $total_imported, $total_entities ) );

		if ( ( $parsed_args['verbose'] ?? false ) && $session->get_reentrancy_cursor() ) {
			WP_CLI::line( sprintf( '  Last Cursor: %s', substr( $session->get_reentrancy_cursor(), 0, 50 ) . '...' ) );
		}

		WP_CLI::line( '' );

		$should_resume = $parsed_args['resume'] ?? false;

		if ( ! $should_resume ) {
			WP_CLI::out( 'Do you want to resume this migration session? [y/n] ' );
			$answer = $this->get_user_input();
			if ( 'y' === $answer ) {
				$should_resume = true;
			} else {
				$should_resume = false;
			}
		}

		if ( $should_resume ) {
			WP_CLI::success( sprintf( 'Resuming migration session %d', $session->get_id() ) );
			return $session;
		} else {
			$session->archive();
			WP_CLI::line( 'Previous session archived. Starting a new import session.' );

			$new_session = $this->create_new_session( $parsed_args );

			if ( $new_session ) {
				WP_CLI::success( sprintf( 'Starting fresh migration from the beginning (Session %d)', $new_session->get_id() ) );
			}

			return $new_session;
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

			return $session;

		} catch ( Exception $e ) {
			WP_CLI::error( sprintf( 'Failed to create migration session: %s', $e->getMessage() ) );
			return null;
		}
	}

	/**
	 * Process a batch of items using the mapper and importer.
	 *
	 * @param array  $batch_items Array of source platform items.
	 * @param object $mapper      Platform mapper instance.
	 * @return int Number of successfully processed items.
	 */
	private function process_batch( array $batch_items, $mapper ): int {
		$processed_count   = 0;
		$mapped_products   = array();
		$source_data_batch = array();

		foreach ( $batch_items as $item ) {
			try {
				// Extract the actual product node from GraphQL response structure.
				// Handle both object and array GraphQL shapes.
				if ( is_object( $item ) && isset( $item->node ) ) {
					$product_data = $item->node;
				} elseif ( is_array( $item ) && isset( $item['node'] ) ) {
					$product_data = $item['node'];
				} else {
					$product_data = $item;
				}

				$mapped_product = $mapper->map_product_data( $product_data );
				if ( ! empty( $mapped_product ) ) {
					$mapped_products[]   = $mapped_product;
					$source_data_batch[] = is_object( $product_data ) ? (array) $product_data : $product_data;
				}
			} catch ( Exception $e ) {
				WP_CLI::warning( sprintf( 'Error mapping product: %s', $e->getMessage() ) );
				continue;
			}
		}

		if ( ! empty( $mapped_products ) ) {
			$batch_results = $this->product_importer->import_batch( $mapped_products, $source_data_batch );

			$this->log_batch_results( $batch_results );
			$processed_count = $batch_results['stats']['successful'];
		}

		return $processed_count;
	}

	/**
	 * Configure the injected product importer with options based on parsed arguments.
	 */
	private function configure_product_importer(): void {
		$import_options = array(
			'skip_existing'           => $this->parsed_args['skip_existing'] ?? false,
			'update_existing'         => ! ( $this->parsed_args['skip_existing'] ?? false ),
			'import_images'           => in_array( 'images', $this->fields_to_process, true ),
			'skip_duplicate_images'   => true,
			'create_categories'       => in_array( 'categories', $this->fields_to_process, true ),
			'create_tags'             => in_array( 'tags', $this->fields_to_process, true ),
			'handle_variations'       => in_array( 'attributes', $this->fields_to_process, true ),
			'assign_default_category' => $this->parsed_args['assign_default_category'] ?? false,
			'dry_run'                 => $this->parsed_args['dry_run'] ?? false,
			'verbose'                 => $this->parsed_args['verbose'] ?? false,
		);

		$this->product_importer->configure( $import_options );
	}

	/**
	 * Log batch import results.
	 *
	 * @param array $batch_results Results from batch import.
	 */
	private function log_batch_results( array $batch_results ): void {
		$stats = $batch_results['stats'];

		// Only log failures and errors when verbose flag is set.
		if ( $this->parsed_args['verbose'] && $stats['failed'] > 0 ) {
			WP_CLI::warning( sprintf( '%d products failed to import', $stats['failed'] ) );

			// Log first few errors for debugging.
			$error_count = 0;
			foreach ( $batch_results['results'] as $result ) {
				if ( 'error' === $result['status'] && $error_count < 3 ) {
					WP_CLI::warning( sprintf( 'Import error: %s', $result['message'] ) );
					++$error_count;
				}
			}
		}

		// Only log skipped products if there are many and verbose is enabled.
		if ( $this->parsed_args['verbose'] && $stats['skipped'] > 5 ) {
			WP_CLI::log( sprintf( 'Skipped %d existing products', $stats['skipped'] ) );
		}
	}

	/**
	 * Display final migration summary statistics.
	 */
	private function display_migration_summary(): void {
		if ( null === $this->product_importer ) {
			return;
		}

		$stats = $this->product_importer->get_import_stats();

		WP_CLI::line( '' );
		WP_CLI::line( WP_CLI::colorize( '%YMigration Summary:%n' ) );
		WP_CLI::line( sprintf( '  Products Created: %d', $stats['products_created'] ) );
		WP_CLI::line( sprintf( '  Products Updated: %d', $stats['products_updated'] ) );
		WP_CLI::line( sprintf( '  Products Skipped: %d', $stats['products_skipped'] ) );
		WP_CLI::line( sprintf( '  Images Processed: %d', $stats['images_processed'] ) );

		if ( $stats['errors_encountered'] > 0 ) {
			WP_CLI::line( WP_CLI::colorize( sprintf( '  %%RErrors Encountered: %d%%n', $stats['errors_encountered'] ) ) );
		}

		WP_CLI::line( '' );
	}

	/**
	 * Get user input from STDIN. Separate method for easier testing.
	 *
	 * @return string User input, trimmed and lowercased.
	 */
	protected function get_user_input(): string {
		return strtolower( trim( fgets( STDIN ) ) );
	}
}
