<?php

namespace Automattic\WooCommerce\Internal\ProductAttributesLookup;

use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;
use WP_CLI;

/**
 * Command line tools to handle the regeneration of the product aatributes lookup table.
 */
class CLIRunner {

	use AccessiblePrivateMethods;

	/**
	 * The instance of DataRegenerator to use.
	 *
	 * @var DataRegenerator
	 */
	private DataRegenerator $data_regenerator;

	/**
	 * The instance of DataRegenerator to use.
	 *
	 * @var LookupDataStore
	 */
	private LookupDataStore $lookup_data_store;

	/**
	 * Creates a new instance of the class.
	 */
	public function __construct() {
		self::mark_method_as_accessible( 'init' );
	}

	// phpcs:disable WooCommerce.Functions.InternalInjectionMethod

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * This method is normally defined as public, we define it as private here
	 * (and "publicize" it in the constructor) to prevent WP_CLI from
	 * creating a dummy command for it.
	 *
	 * @param DataRegenerator $data_regenerator The instance of DataRegenerator to use.
	 * @param LookupDataStore $lookup_data_store The instance of DataRegenerator to use.
	 */
	private function init( DataRegenerator $data_regenerator, LookupDataStore $lookup_data_store ) {
		$this->data_regenerator  = $data_regenerator;
		$this->lookup_data_store = $lookup_data_store;
	}

	// phpcs:enable WooCommerce.Functions.InternalInjectionMethod

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

	/**
	 * Enable the usage of the product attributes lookup table.
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function enable( array $args = array(), array $assoc_args = array() ) {
		return $this->invoke( 'enable_core', $args, $assoc_args );
	}

	/**
	 * Core method for the "enable" command.
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	private function enable_core( array $args, array $assoc_args ) {
		$table_name = $this->lookup_data_store->get_lookup_table_name();
		if ( 'yes' === get_option( 'woocommerce_attribute_lookup_enabled' ) ) {
			$this->warning( "The usage of the of the %W{$table_name}%n table is already enabled." );
			return;
		}

		if ( ! array_key_exists( 'force', $assoc_args ) ) {
			$must_confirm = true;
			if ( $this->lookup_data_store->regeneration_is_in_progress() ) {
				$this->warning( "The regeneration of the %W{$table_name}%n table is currently in process." );
			} elseif ( $this->lookup_data_store->regeneration_was_aborted() ) {
				$this->warning( "The regeneration of the %W{$table_name}%n table was aborted." );
			} elseif ( 0 === $this->get_lookup_table_info()['total_rows'] ) {
				$this->warning( "The %W{$table_name}%n table is empty." );
			} else {
				$must_confirm = false;
			}

			if ( $must_confirm ) {
				WP_CLI::confirm( 'Are you sure that you want to enable the table usage?' );
			}
		}

		update_option( 'woocommerce_attribute_lookup_enabled', 'yes' );
		$table_name = $this->lookup_data_store->get_lookup_table_name();
		$this->success( "The usage of the %W{$table_name}%n table for product attribute lookup has been enabled." );
	}

	/**
	 * Disable the usage of the product attributes lookup table.
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function disable( array $args = array(), array $assoc_args = array() ) {
		return $this->invoke( 'disable_core', $args, $assoc_args );
	}

	/**
	 * Core method for the "disable" command.
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	private function disable_core( array $args, array $assoc_args ) {
		if ( 'yes' !== get_option( 'woocommerce_attribute_lookup_enabled' ) ) {
			$table_name = $this->lookup_data_store->get_lookup_table_name();
			$this->warning( "The usage of the of the %W{$table_name}%n table is already disabled." );
			return;
		}
		update_option( 'woocommerce_attribute_lookup_enabled', 'no' );
		$table_name = $this->lookup_data_store->get_lookup_table_name();
		$this->success( "The usage of the %W{$table_name}%n table for product attribute lookup has been disabled." );
	}

	/**
	 * Regenerate the product attributes lookup table data for one single product.
	 *
	 * ## OPTIONS
	 *
	 * <product-id>
	 * : The id of the product for which the data will be regenerated.
	 *
	 * [--disable-db-optimization]
	 * : Don't use optimized database access even if products are stored as custom post types.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc palt regenerate_for_product 34 --disable-db-optimization
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function regenerate_for_product( array $args = array(), array $assoc_args = array() ) {
		return $this->invoke( 'regenerate_for_product_core', $args, $assoc_args );
	}

	/**
	 * Core method for the "regenerate_for_product" command.
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	private function regenerate_for_product_core( array $args = array(), array $assoc_args = array() ) {
		$product_id = current( $args );
		$this->data_regenerator->check_can_do_lookup_table_regeneration( $product_id );
		$use_db_optimization = ! array_key_exists( 'disable-db-optimization', $assoc_args );
		$this->check_can_use_db_optimization( $use_db_optimization );
		$start_time = microtime( true );
		$this->lookup_data_store->create_data_for_product( $product_id, $use_db_optimization );

		if ( $this->lookup_data_store->get_last_create_operation_failed() ) {
			$this->error( "Lookup data regeneration failed.\nSee the WooCommerce logs (source is %9palt-updates%n) for details." );
		} else {
			$total_time = microtime( true ) - $start_time;
			WP_CLI::success( sprintf( 'Attributes lookup data for product %d regenerated in %f seconds.', $product_id, $total_time ) );
		}
	}

	/**
	 * If database access optimization is requested but can't be used, show a warning.
	 *
	 * @param bool $use_db_optimization True if database access optimization is requested.
	 */
	private function check_can_use_db_optimization( bool $use_db_optimization ) {
		if ( $use_db_optimization && ! $this->lookup_data_store->can_use_optimized_db_access() ) {
			$this->warning( "Optimized database access can't be used (products aren't stored as custom post types)." );
		}
	}

	/**
	 * Obtain information about the product attributes lookup table.
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function info( array $args = array(), array $assoc_args = array() ) {
		return $this->invoke( 'info_core', $args, $assoc_args );
	}

	/**
	 * Core method for the "info" command.
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	private function info_core( array $args, array $assoc_args ) {
		global $wpdb;

		$enabled = 'yes' === get_option( 'woocommerce_attribute_lookup_enabled' );

		$table_name = $this->lookup_data_store->get_lookup_table_name();
		$info       = $this->get_lookup_table_info();

		$this->log( "Table name: %W{$table_name}%n" );
		$this->log( 'Table usage is ' . ( $enabled ? '%Genabled%n' : '%Ydisabled%n' ) );
		$this->log( "The table contains %C{$info['total_rows']}%n rows corresponding to %G{$info['products_count']}%n products." );

		if ( $info['total_rows'] > 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$highest_product_id_in_table = $wpdb->get_var( 'select max(product_or_parent_id) from ' . $table_name );
			$this->log( "The highest product id in the table is %B{$highest_product_id_in_table}%n." );
		}

		if ( $this->lookup_data_store->regeneration_is_in_progress() ) {
			$max_product_id_to_process = get_option( 'woocommerce_attribute_lookup_last_product_id_to_process', '???' );
			WP_CLI::log( '' );
			$this->warning( 'Full regeneration of the table is currently %Gin progress.%n' );
			if ( ! $this->data_regenerator->has_scheduled_action_for_regeneration_step() ) {
				$this->log( 'However, there are %9NO%n actions scheduled to run the regeneration steps (a %9wp cli palt regenerate%n command was aborted?).' );
			}
			$this->log( "The last product id that will be processed is %Y{$max_product_id_to_process}%n." );
			$this->log( "\nRun %9wp cli palt abort_regeneration%n to abort the regeneration process," );
			$this->log( "then you'll be able to run %9wp cli palt resume_regeneration%n to resume the regeneration process," );
		} elseif ( $this->lookup_data_store->regeneration_was_aborted() ) {
			$max_product_id_to_process = get_option( 'woocommerce_attribute_lookup_last_product_id_to_process', '???' );
			WP_CLI::log( '' );
			$this->warning( "Full regeneration of the table has been %Raborted.%n\nThe last product id that will be processed is %Y{$max_product_id_to_process}%n." );
			$this->log( "\nRun %9wp cli palt resume_regeneration%n to resume the regeneration process." );
		}
	}

	/**
	 * Abort the background regeneration of the product attributes lookup table that is happening in the background.
	 *
	 * [--cleanup]
	 * : Also cleanup temporary data (so regeneration can't be resumed, but it can be restarted).
	 *
	 *  ## EXAMPLES
	 *
	 *      wp wc palt abort_regeneration --cleanup
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function abort_regeneration( array $args = array(), array $assoc_args = array() ) {
		return $this->invoke( 'abort_regeneration_core', $args, $assoc_args );
	}

	/**
	 * Core method for the "abort_regeneration" command.
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	private function abort_regeneration_core( array $args, array $assoc_args ) {
		$this->data_regenerator->abort_regeneration( false );
		$table_name = $this->lookup_data_store->get_lookup_table_name();
		$this->success( "The regeneration of the data in the %W{$table_name}%n table has been aborted." );
		if ( array_key_exists( 'cleanup', $assoc_args ) ) {
			$this->cleanup_regeneration_progress( array(), array() );
		}
	}

	/**
	 * Resume the background regeneration of the product attributes lookup table after it has been aborted.
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function resume_regeneration( array $args = array(), array $assoc_args = array() ) {
		return $this->invoke( 'resume_regeneration_core', $args, $assoc_args );
	}

	/**
	 * Core method for the "resume_regeneration" command.
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	private function resume_regeneration_core( array $args, array $assoc_args ) {
		$this->data_regenerator->resume_regeneration( false );
		$table_name = $this->lookup_data_store->get_lookup_table_name();
		$this->success( "The regeneration of the data in the %W{$table_name}%n table has been resumed." );
	}

	/**
	 * Delete the temporary data used during the regeneration of the product attributes lookup table. This data is normally deleted automatically after the regeneration process finishes.
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function cleanup_regeneration_progress( array $args = array(), array $assoc_args = array() ) {
		return $this->invoke( 'cleanup_regeneration_progress_core', $args, $assoc_args );
	}

	/**
	 * Core method for the "cleanup_regeneration_progress" command.
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	private function cleanup_regeneration_progress_core( array $args, array $assoc_args ) {
		$this->data_regenerator->finalize_regeneration( false );
		$table_name = $this->lookup_data_store->get_lookup_table_name();
		$this->success( "The temporary data used for regeneration of the data in the %W{$table_name}%n table has been deleted." );
	}

	/**
	 * Initiate the background regeneration of the product attributes lookup table. The regeneration will happen in the background, using scheduled actions.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Don't prompt for confirmation if the product attributes lookup table isn't empty.
	 *
	 *   ## EXAMPLES
	 *
	 *       wp wc palt initiate_regeneration --force
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function initiate_regeneration( array $args = array(), array $assoc_args = array() ) {
		return $this->invoke( 'initiate_regeneration_core', $args, $assoc_args );
	}

	/**
	 * Core method for the "initiate_regeneration" command.
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	private function initiate_regeneration_core( array $args, array $assoc_args ) {
		$this->data_regenerator->check_can_do_lookup_table_regeneration();
		$info = $this->get_lookup_table_info();
		if ( $info['total_rows'] > 0 && ! array_key_exists( 'force', $assoc_args ) ) {
			$table_name = $this->lookup_data_store->get_lookup_table_name();
			$this->warning( "The %W{$table_name}%n table contains %C{$info['total_rows']}%n rows corresponding to %G{$info['products_count']}%n products." );
			WP_CLI::confirm( 'Initiating the regeneration will first delete the data. Are you sure?' );
		}

		$this->data_regenerator->initiate_regeneration();
		$table_name = $this->lookup_data_store->get_lookup_table_name();
		$this->log( "%GSuccess:%n The regeneration of the data in the %W{$table_name}%n table has been initiated." );
	}

	/**
	 * Regenerate the product attributes lookup table immediately, without using scheduled tasks.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Don't prompt for confirmation if the product attributes lookup table isn't empty.
	 *
	 * [--from-scratch]
	 * : Start table regeneration from scratch even if a regeneration is already in progress.
	 *
	 * [--disable-db-optimization]
	 * : Don't use optimized database access even if products are stored as custom post types.
	 *
	 * [--batch-size=<size>]
	 * : How many products to process in each iteration of the loop.
	 * ---
	 * default: 10
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc palt regenerate --force --from-scratch --batch-size=20
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 */
	public function regenerate( array $args = array(), array $assoc_args = array() ) {
		return $this->invoke( 'regenerate_core', $args, $assoc_args );
	}

	/**
	 * Core method for the "regenerate" command.
	 *
	 * @param array $args Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments (options) passed to the command.
	 * @throws \Exception Invalid batch size argument.
	 */
	private function regenerate_core( array $args = array(), array $assoc_args = array() ) {
		global $wpdb;

		$table_name = $this->lookup_data_store->get_lookup_table_name();

		$batch_size = $assoc_args['batch-size'] ?? DataRegenerator::PRODUCTS_PER_GENERATION_STEP;
		if ( ! is_numeric( $batch_size ) || $batch_size < 1 ) {
			throw new \Exception( 'batch_size must be a number bigger than 0' );
		}

		$was_enabled = 'yes' === get_option( 'woocommerce_attribute_lookup_enabled' );

		// phpcs:ignore Generic.Commenting.Todo.TaskFound
		// TODO: adjust for non-CPT datastores (this is only used for the progress bar, though).
		$products_count = wp_count_posts( 'product' );
		$products_count = intval( $products_count->publish ) + intval( $products_count->pending ) + intval( $products_count->draft );

		if ( ! $this->lookup_data_store->regeneration_is_in_progress() || array_key_exists( 'from-scratch', $assoc_args ) ) {
			$info = $this->get_lookup_table_info();
			if ( $info['total_rows'] > 0 && ! array_key_exists( 'force', $assoc_args ) ) {
				$this->warning( "The %W{$table_name}%n table contains %C{$info['total_rows']}%n rows corresponding to %G{$info['products_count']}%n products." );
				WP_CLI::confirm( 'Triggering the regeneration will first delete the data. Are you sure?' );
			}

			$this->data_regenerator->finalize_regeneration( false );
			$last_product_id = $this->data_regenerator->initiate_regeneration( false );
			if ( 0 === $last_product_id ) {
				$this->data_regenerator->finalize_regeneration( $was_enabled );
				WP_CLI::log( 'No products exist in the database, the table is left empty.' );
				return;
			}
			$processed_count = 0;
		} else {
			$last_product_id = get_option( 'woocommerce_attribute_lookup_last_product_id_to_process' );
			if ( false === $last_product_id ) {
				WP_CLI::error( 'Regeneration seems to be already in progress, but the woocommerce_attribute_lookup_last_product_id_to_process option isn\'t there. Try %9wp cli palt cleanup_regeneration_progress%n first." );' );
				return 1;
			}
			$processed_count = get_option( 'woocommerce_attribute_lookup_processed_count', 0 );
			$this->log( "Resuming regeneration, %C{$processed_count}%n products have been processed already" );
			$this->lookup_data_store->set_regeneration_in_progress_flag();
		}

		$this->data_regenerator->cancel_regeneration_scheduled_action();

		$use_db_optimization = ! array_key_exists( 'disable-db-optimization', $assoc_args );
		$this->check_can_use_db_optimization( $use_db_optimization );
		$progress = WP_CLI\Utils\make_progress_bar( '', $products_count );
		$this->log( "Regenerating %W{$table_name}%n..." );
		$progress->tick( $processed_count );

		$regeneration_step_failed = false;
		while ( $this->data_regenerator->do_regeneration_step( $batch_size, $use_db_optimization ) ) {
			$progress->tick( $batch_size );
			$regeneration_step_failed = $regeneration_step_failed || $this->data_regenerator->get_last_regeneration_step_failed();
		}

		$this->data_regenerator->finalize_regeneration( $was_enabled );
		$time = $progress->formatTime( $progress->elapsed() );
		$progress->finish();

		if ( $regeneration_step_failed ) {
			$this->warning( "Lookup data regeneration failed for at least one product.\nSee the WooCommerce logs (source is %9palt-updates%n) for details.\n" );
			$this->log( "Table %W{$table_name}%n regenerated in {$time}." );
		} else {
			$this->log( "%GSuccess:%n Table %W{$table_name}%n regenerated in {$time}." );
		}

		$info = $this->get_lookup_table_info();
		$this->log( "The table contains now %C{$info['total_rows']}%n rows corresponding to %G{$info['products_count']}%n products." );
	}

	// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

	/**
	 * Get information about the product attributes lookup table.
	 *
	 * @return array Array containing the 'total_rows' and 'products_count' keys.
	 */
	private function get_lookup_table_info(): array {
		global $wpdb;

		$table_name = $this->lookup_data_store->get_lookup_table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$info = $wpdb->get_row( 'select count(1), count(distinct(product_or_parent_id)) from ' . $table_name, ARRAY_N );
		return array(
			'total_rows'     => absint( $info[0] ),
			'products_count' => absint( $info[1] ),
		);
	}

	/**
	 * Invoke a method from the class, and if an exception is thrown, show it using WP_CLI::error.
	 *
	 * @param string $method_name Name of the method to invoke.
	 * @param array  $args Positional arguments to pass to the method.
	 * @param array  $assoc_args Associative arguments to pass to the method.
	 * @return mixed Result from the method, or 1 if an exception is thrown.
	 */
	private function invoke( string $method_name, array $args, array $assoc_args ) {
		try {
			return call_user_func( array( $this, $method_name ), $args, $assoc_args );
		} catch ( \Exception $e ) {
			WP_CLI::error( $e->getMessage() );
			return 1;
		}
	}

	/**
	 * Show a log message using the WP_CLI text colorization feature.
	 *
	 * @param string $text Text to show.
	 */
	private function log( string $text ) {
		WP_CLI::log( WP_CLI::colorize( $text ) );
	}

	/**
	 * Show a warning message using the WP_CLI text colorization feature.
	 *
	 * @param string $text Text to show.
	 */
	private function warning( string $text ) {
		WP_CLI::warning( WP_CLI::colorize( $text ) );
	}

	/**
	 * Show a success message using the WP_CLI text colorization feature.
	 *
	 * @param string $text Text to show.
	 */
	private function success( string $text ) {
		WP_CLI::success( WP_CLI::colorize( $text ) );
	}

	/**
	 * Show an error message using the WP_CLI text colorization feature.
	 *
	 * @param string $text Text to show.
	 */
	private function error( string $text ) {
		WP_CLI::error( WP_CLI::colorize( $text ) );
	}
}
