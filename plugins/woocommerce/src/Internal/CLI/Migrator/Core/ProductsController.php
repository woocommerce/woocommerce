<?php
/**
 * Products Controller
 *
 * @package Automattic\WooCommerce\Internal\CLI\Migrator\Core
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Core;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\CredentialManager;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\MigratorTracker;
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
	 * Migration tracker instance.
	 *
	 * @var MigratorTracker
	 */
	private MigratorTracker $tracker;

	/**
	 * Run start time for this CLI invocation (used for timing metrics).
	 *
	 * @var int
	 */
	private int $session_start_time = 0;

	/**
	 * Initialize the controller with its dependencies.
	 * Called automatically by the WooCommerce DI container.
	 *
	 * @internal
	 *
	 * @param CredentialManager          $credential_manager The credential manager.
	 * @param PlatformRegistry           $platform_registry  The platform registry.
	 * @param WooCommerceProductImporter $product_importer   The product importer.
	 * @param MigratorTracker            $tracker            The migration tracker.
	 */
	final public function init(
		CredentialManager $credential_manager,
		PlatformRegistry $platform_registry,
		WooCommerceProductImporter $product_importer,
		MigratorTracker $tracker
	): void {
		$this->credential_manager = $credential_manager;
		$this->platform_registry  = $platform_registry;
		$this->product_importer   = $product_importer;
		$this->tracker            = $tracker;
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

		$this->session_start_time = time();

		if ( $this->parsed_args['dry_run'] ) {
			WP_CLI::line( WP_CLI::colorize( '%Y--- DRY RUN MODE ENABLED ---%n' ) );
			WP_CLI::line( 'No products will be created or modified. This is a simulation only.' );
			WP_CLI::line( '' );
		}

		if ( ! $this->parsed_args['dry_run'] ) {
			$this->session = $this->manage_session_lifecycle( $this->parsed_args );
			if ( ! $this->session ) {
				return;
			}

			/**
			 * Fires when a migration session starts.
			 *
			 * @since 10.3.0
			 *
			 * @param string $platform The platform being migrated from.
			 * @param array  $metadata Session metadata including session_id, filters, and fields.
			 */
			do_action(
				'wc_migrator_session_started',
				$this->parsed_args['platform'],
				array(
					'session_id' => $this->session->get_id(),
					'filters'    => $this->parsed_args['filters'],
					'fields'     => $this->fields_to_process,
					'is_dry_run' => $this->parsed_args['dry_run'],
					'resume'     => $this->parsed_args['resume'],
				)
			);
		}

		$fetcher = $this->platform_registry->get_fetcher( $this->parsed_args['platform'] );
		$mapper  = $this->platform_registry->get_mapper( $this->parsed_args['platform'], array( 'fields' => $this->fields_to_process ) );

		$total_count = $fetcher->fetch_total_count( $this->parsed_args['filters'] );

		if ( ! $this->parsed_args['dry_run'] ) {
			$existing_total = $this->session->count_all_total_entities();
			if ( 0 < $total_count && 0 === $existing_total ) {
				$this->session->bump_total_number_of_entities( array( 'post' => $total_count ) );
			}
		}

		WP_CLI::line( "Total entities found: {$total_count}" );
		$progress_label = $this->parsed_args['dry_run']
			? 'Simulating Products from ' . ucfirst( $this->parsed_args['platform'] )
			: 'Importing Products from ' . ucfirst( $this->parsed_args['platform'] );
		$progress       = \WP_CLI\Utils\make_progress_bar( $progress_label, $total_count );

		if ( ! $this->parsed_args['dry_run'] ) {
			$progress->tick( $this->session->count_all_imported_entities() );
		}

		$this->configure_product_importer();

		$this->execute_migration_loop( $fetcher, $mapper, $progress );

		$progress->finish();

		$this->display_migration_summary();

		$this->display_feedback_survey();

		if ( ! $this->parsed_args['dry_run'] ) {
			$final_stats = array(
				'total_found'    => $total_count,
				'total_imported' => $this->session->count_all_imported_entities(),
			);
			/**
			 * Fires when a migration session completes.
			 *
			 * @since 10.3.0
			 *
			 * @param string $platform    The platform being migrated from.
			 * @param array  $final_stats Final migration statistics.
			 */
			do_action( 'wc_migrator_session_completed', $this->parsed_args['platform'], $final_stats );

			$this->log_session_time_metrics( $final_stats );
		}

		if ( $this->parsed_args['dry_run'] ) {
			WP_CLI::success( 'Dry-run completed successfully. No products were actually created or modified.' );
		} else {
			WP_CLI::success( 'Migration completed successfully.' );
		}
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
		$session_cursor             = $this->parsed_args['dry_run'] ? null : $this->session->get_reentrancy_cursor();
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
				/**
				 * Fires when an error occurs during migration.
				 *
				 * @since 10.3.0
				 *
				 * @param string $error_type The type of error (fetch, mapping, import).
				 * @param string $message    The error message.
				 * @param array  $context    Additional error context.
				 */
				do_action(
					'wc_migrator_error_occurred',
					'fetch',
					$e->getMessage(),
					array(
						'batch_args' => $batch_args,
						'platform'   => $this->parsed_args['platform'],
					)
				);

				WP_CLI::warning( "Error fetching batch: {$e->getMessage()}" );
				break;
			}

			if ( empty( $batch_data['items'] ) ) {
				break;
			}

			$processed_count = $this->process_batch( $batch_data['items'], $mapper );

			$total_processed_in_session += $processed_count;

			if ( ! $this->parsed_args['dry_run'] ) {
				$this->session->bump_imported_entities_counts( array( 'post' => $processed_count ) );
				$after_cursor = $batch_data['cursor'];
				$this->session->set_reentrancy_cursor( $after_cursor );
			} else {
				$after_cursor = $batch_data['cursor'];
			}

			$limit_remaining -= count( $batch_data['items'] );
			$has_next_page    = $batch_data['has_next_page'] ?? false;

			$progress->tick( $processed_count, sprintf( 'Processed %d products', $total_processed_in_session ) );
		} while ( $has_next_page && $limit_remaining > 0 );

		if ( ! $has_next_page && ! $this->parsed_args['dry_run'] ) {
			$this->session->set_stage( ImportSession::STAGE_FINISHED );
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
			$platform_display_name = $this->platform_registry->get_platform_display_name( $platform );
			WP_CLI::error(
				sprintf(
					"No credentials found for platform '%s'. Please run: wp wc migrate setup --platform=%s",
					$platform_display_name,
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
				/**
				 * Fires when an error occurs during migration.
				 *
				 * @since 10.3.0
				 *
				 * @param string $error_type The type of error (fetch, mapping, import).
				 * @param string $message    The error message.
				 * @param array  $context    Additional error context.
				 */
				do_action(
					'wc_migrator_error_occurred',
					'mapping',
					$e->getMessage(),
					array(
						'product_data' => $product_data,
						'platform'     => $this->parsed_args['platform'],
					)
				);

				WP_CLI::warning( sprintf( 'Error mapping product: %s', $e->getMessage() ) );
				continue;
			}
		}

		if ( ! empty( $mapped_products ) ) {
			if ( $this->parsed_args['dry_run'] ) {
				$batch_results = $this->simulate_import_batch( $mapped_products );
			} else {
				$batch_results = $this->product_importer->import_batch( $mapped_products, $source_data_batch );
			}

			/**
			 * Fires when a batch has been processed during migration.
			 *
			 * @since 10.3.0
			 *
			 * @param array $batch_results   Results from the batch import.
			 * @param array $source_data     Source platform data for the batch.
			 * @param array $mapped_products Mapped WooCommerce data for the batch.
			 */
			do_action( 'wc_migrator_batch_processed', $batch_results, $source_data_batch, $mapped_products );

			$this->log_batch_results( $batch_results );
			$processed_count = $batch_results['stats']['successful'];

			if ( $processed_count > 0 && ! $this->parsed_args['dry_run'] ) {
				$current_count = get_option( 'wc_migrator_products_count', 0 );
				update_option( 'wc_migrator_products_count', $current_count + $processed_count );
			}
		}

		return $processed_count;
	}

	/**
	 * Simulate the import process for dry-run mode.
	 *
	 * @param array $mapped_products Array of mapped product data.
	 * @return array Simulated batch results matching real import format.
	 */
	private function simulate_import_batch( array $mapped_products ): array {
		$results = array();
		$stats   = array(
			'successful' => 0,
			'failed'     => 0,
			'skipped'    => 0,
		);

		foreach ( $mapped_products as $product_data ) {
			$product_name = $product_data['name'] ?? 'Unknown Product';

			if ( empty( $product_data['name'] ) ) {
				$results[] = array(
					'status'  => 'error',
					'message' => 'Product name is required',
					'data'    => $product_data,
				);
				++$stats['failed'];
				$this->simulate_stats_increment( 'errors_encountered' );
				continue;
			}

			$existing_product_id = null;
			if ( ! empty( $product_data['sku'] ) ) {
				$existing_product_id = wc_get_product_id_by_sku( $product_data['sku'] );
			}

			$would_skip = false;
			if ( $existing_product_id && $this->parsed_args['skip_existing'] ) {
				$would_skip = true;
			}

			if ( $would_skip ) {
				$results[] = array(
					'status'  => 'skipped',
					'message' => "Product '{$product_name}' would be skipped (already exists)",
					'data'    => $product_data,
				);
				++$stats['skipped'];
				$this->simulate_stats_increment( 'products_skipped' );
			} else {
				$results[] = array(
					'status'  => 'success',
					'message' => "Product '{$product_name}' would be imported",
					'data'    => $product_data,
				);
				++$stats['successful'];

				if ( $existing_product_id ) {
					$this->simulate_stats_increment( 'products_updated' );
				} else {
					$this->simulate_stats_increment( 'products_created' );
				}

				if ( in_array( 'images', $this->fields_to_process, true ) && ! empty( $product_data['images'] ) ) {
					$image_count = is_array( $product_data['images'] ) ? count( $product_data['images'] ) : 1;
					for ( $i = 0; $i < $image_count; $i++ ) {
						$this->simulate_stats_increment( 'images_processed' );
					}
				}
			}

			wc_get_logger()->info( "DRY RUN: Would import product '{$product_name}'", array( 'source' => 'wc-migrator' ) );
		}

		return array(
			'results' => $results,
			'stats'   => $stats,
		);
	}

	/**
	 * Simulate incrementing stats by using reflection to access private properties.
	 * This ensures dry-run stats match what the real import would show.
	 *
	 * @param string $stat_key The stat key to increment.
	 */
	private function simulate_stats_increment( string $stat_key ): void {
		try {
			$reflection     = new \ReflectionClass( $this->product_importer );
			$stats_property = $reflection->getProperty( 'import_stats' );
			$stats_property->setAccessible( true );

			$current_stats = $stats_property->getValue( $this->product_importer );
			if ( isset( $current_stats[ $stat_key ] ) ) {
				++$current_stats[ $stat_key ];
				$stats_property->setValue( $this->product_importer, $current_stats );
			}
		} catch ( \ReflectionException $e ) {
			wc_get_logger()->warning(
				"DRY RUN: Could not update import stats for '{$stat_key}': " . $e->getMessage(),
				array( 'source' => 'wc-migrator' )
			);
		}
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
			'verbose'                 => $this->parsed_args['verbose'] ?? false,
		);

		$this->product_importer->configure( $import_options );

		if ( $this->parsed_args['verbose'] ?? false ) {
			$this->product_importer->set_progress_callback( array( $this, 'display_product_progress' ) );
		}
	}

	/**
	 * Display progress indicator for individual product imports.
	 *
	 * @param int        $current_index Current product index (1-based).
	 * @param int        $total_count   Total number of products in batch.
	 * @param string     $product_name  Name of the product being processed.
	 * @param array|null $result        Import result (null when starting, array when finished).
	 */
	public function display_product_progress( int $current_index, int $total_count, string $product_name, ?array $result ): void {
		if ( null === $result ) {
			return;
		}

		$display_name = strlen( $product_name ) > 40 ? substr( $product_name, 0, 37 ) . '...' : $product_name;

		$status_char  = '✓';
		$status_color = '%G';

		if ( 'error' === $result['status'] ) {
			$status_char  = '✗';
			$status_color = '%R';
		} elseif ( 'success' === $result['status'] && 'skipped' === $result['action'] ) {
			$status_char  = '−';
			$status_color = '%Y';
		}

		$progress = sprintf( '[%d/%d]', $current_index, $total_count );

		if ( 1 === $current_index ) {
			WP_CLI::line( '' );
		}

		WP_CLI::line(
			WP_CLI::colorize(
				sprintf( '%s%s%s %s %s', $status_color, $status_char, '%n', $progress, $display_name )
			)
		);
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
		if ( $this->parsed_args['dry_run'] ) {
			WP_CLI::line( WP_CLI::colorize( '%YDry-Run Summary:%n' ) );
			WP_CLI::line( sprintf( '  Products Would Be Created: %d', $stats['products_created'] ) );
			WP_CLI::line( sprintf( '  Products Would Be Updated: %d', $stats['products_updated'] ) );
			WP_CLI::line( sprintf( '  Products Would Be Skipped: %d', $stats['products_skipped'] ) );
			WP_CLI::line( sprintf( '  Images Would Be Processed: %d', $stats['images_processed'] ) );
		} else {
			WP_CLI::line( WP_CLI::colorize( '%YMigration Summary:%n' ) );
			WP_CLI::line( sprintf( '  Products Created: %d', $stats['products_created'] ) );
			WP_CLI::line( sprintf( '  Products Updated: %d', $stats['products_updated'] ) );
			WP_CLI::line( sprintf( '  Products Skipped: %d', $stats['products_skipped'] ) );
			WP_CLI::line( sprintf( '  Images Processed: %d', $stats['images_processed'] ) );
		}

		if ( $stats['errors_encountered'] > 0 ) {
			if ( $this->parsed_args['dry_run'] ) {
				WP_CLI::line( WP_CLI::colorize( sprintf( '  %%RValidation Errors Found: %d%%n', $stats['errors_encountered'] ) ) );
			} else {
				WP_CLI::line( WP_CLI::colorize( sprintf( '  %%RErrors Encountered: %d%%n', $stats['errors_encountered'] ) ) );
			}
		}

		WP_CLI::line( '' );
	}

	/**
	 * Log session time metrics using session-specific data.
	 *
	 * @param array $final_stats Final migration statistics.
	 */
	private function log_session_time_metrics( array $final_stats ): void {
		$session_products = $final_stats['total_imported'] ?? 0;

		if ( empty( $session_products ) ) {
			return;
		}

		if ( empty( $this->session_start_time ) ) {
			return;
		}

		$session_duration_seconds = time() - $this->session_start_time;
		$platform                 = $this->parsed_args['platform'];

		$avg_time_per_product   = $session_duration_seconds / $session_products;
		$session_time_formatted = human_time_diff( 0, $session_duration_seconds );
		$avg_time_formatted     = number_format( $avg_time_per_product, 2 );

		$platform_display_name = $this->platform_registry->get_platform_display_name( $platform );
		$metrics_message       = sprintf(
			'Session completed for %s: %d products in %s (avg: %s seconds per product)',
			$platform_display_name,
			$session_products,
			$session_time_formatted,
			$avg_time_formatted
		);

		wc_get_logger()->info( $metrics_message, array( 'source' => 'wc-migrator' ) );
	}

	/**
	 * Display feedback survey link to collect user feedback.
	 */
	private function display_feedback_survey(): void {
		WP_CLI::line( '' );
		WP_CLI::line( WP_CLI::colorize( '%GHelp us improve the WooCommerce Migrator!%n' ) );
		WP_CLI::line( 'Please share your feedback about this migration experience:' );
		WP_CLI::line( WP_CLI::colorize( '%Chttps://developer.woocommerce.com/migrator-feedback/%n' ) );
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
