<?php
/**
 * REST API Product Export controller
 *
 * Handles requests to the /catalog endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   8.x.x
 */

use Automattic\WooCommerce\Enums\ProductType;

defined( 'ABSPATH' ) || exit;

// Include required exporter classes
if ( ! class_exists( 'WC_Product_JSON_Exporter' ) ) {
	include_once WC_ABSPATH . 'includes/export/class-wc-product-json-exporter.php';
}
if ( ! class_exists( 'WC_Product_CSV_Exporter' ) ) {
	include_once WC_ABSPATH . 'includes/export/class-wc-product-csv-exporter.php';
}

/**
 * REST API Product Export controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Controller
 */
class WC_REST_Product_Export_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'catalog';

	/**
	 * Check if a given request has permission to export products.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|bool
	 */
	public function export_products_permissions_check( $request ) {
		return true;
	}

	/**
	 * Check if a given request has permission to download export files.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|bool
	 */
	public function download_export_file_permissions_check( $request ) {
		return true;
	}

	/**
	 * Download export file.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function download_export_file( $request ) {
		$filename = sanitize_file_name( $request->get_param( 'filename' ) );
		$format = $request->get_param( 'format' );
		$compress = $request->get_param( 'compress' ) ?: 'none';

		if ( empty( $filename ) ) {
			return new WP_Error( 'woocommerce_rest_export_invalid_filename', __( 'Invalid filename provided.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		// Deduce format from filename extension if not provided
		if ( empty( $format ) ) {
			$file_info = pathinfo( $filename );
			$extension = strtolower( $file_info['extension'] ?? '' );

			if ( 'csv' === $extension ) {
				$format = 'csv';
			} elseif ( 'json' === $extension ) {
				$format = 'json';
			} else {
				return new WP_Error( 'woocommerce_rest_export_invalid_format', __( 'Unable to determine file format from filename. Please specify format parameter.', 'woocommerce' ), array( 'status' => 400 ) );
			}
		}

		$upload_dir = wp_upload_dir();
		$file_path = trailingslashit( $upload_dir['basedir'] ) . $filename;

		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'woocommerce_rest_export_file_not_found', __( 'Export file not found.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		// Handle compression
		if ( 'none' !== $compress ) {
			$compressed_file_path = $this->compress_file( $file_path, $compress, $format );
			if ( is_wp_error( $compressed_file_path ) ) {
				return $compressed_file_path;
			}

			// Update file path and filename for compressed file
			$file_path = $compressed_file_path;
			$filename = basename( $compressed_file_path );
		}

		$file_size = filesize( $file_path );
		$mime_type = $this->get_mime_type( $format, $compress );

		// For GZIP files, suggest the original filename when decompressed
		$download_filename = $filename;
		if ( 'gzip' === $compress && substr( $filename, -3 ) === '.gz' ) {
			$original_filename = substr( $filename, 0, -3 );
			$download_filename = $filename;
			// Add custom header to suggest original filename
			header( 'X-Original-Filename: ' . $original_filename );
		}

		// Set headers for file download
		header( 'Content-Type: ' . $mime_type );
		header( 'Content-Disposition: attachment; filename="' . $download_filename . '"' );
		header( 'Content-Length: ' . $file_size );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );

		// Output file content
		readfile( $file_path );

		// Schedule file cleanup after 1 day
		$files_to_cleanup = array( $file_path );

		// Also schedule cleanup of original file if we created a compressed version
		if ( 'none' !== $compress ) {
			$original_file = trailingslashit( $upload_dir['basedir'] ) . $request->get_param( 'filename' );
			if ( file_exists( $original_file ) ) {
				$files_to_cleanup[] = $original_file;
			}
		}

		// Schedule cleanup using WordPress cron (1 day = 86400 seconds)
		wp_schedule_single_event( time() + DAY_IN_SECONDS, 'wc_cleanup_export_files', array( $files_to_cleanup ) );

		exit;
	}

	/**
	 * Register the routes for product exports.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'export_products' ),
					'permission_callback' => array( $this, 'export_products_permissions_check' ),
					'args'                => array(
						'format' => array(
							'description' => __( 'Export format (json or csv).', 'woocommerce' ),
							'type'        => 'string',
							'enum'        => array( 'json', 'csv' ),
							'default'     => 'json',
						),
						'columns' => array(
							'description' => __( 'Column names for export.', 'woocommerce' ),
							'type'        => 'array',
						),
						'selected_columns' => array(
							'description' => __( 'Selected columns to export.', 'woocommerce' ),
							'type'        => 'array',
						),
						'export_meta' => array(
							'description' => __( 'Whether to export meta data.', 'woocommerce' ),
							'type'        => 'boolean',
						),
						'export_types' => array(
							'description' => __( 'Product types to export.', 'woocommerce' ),
							'type'        => 'array',
						),
						'export_category' => array(
							'description' => __( 'Product category to export.', 'woocommerce' ),
							'type'        => 'array',
						),
						'export_product_ids' => array(
							'description' => __( 'Specific product IDs to export.', 'woocommerce' ),
							'type'        => 'string',
						),
						'filename' => array(
							'description' => __( 'Export filename.', 'woocommerce' ),
							'type'        => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/status/(?P<job_id>[a-zA-Z0-9_]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_export_status' ),
					'permission_callback' => array( $this, 'export_products_permissions_check' ),
					'args'                => array(
						'job_id' => array(
							'description' => __( 'Export job ID.', 'woocommerce' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/download',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'download_export_file' ),
					'permission_callback' => array( $this, 'download_export_file_permissions_check' ),
					'args'                => array(
						'filename' => array(
							'description' => __( 'Export filename to download.', 'woocommerce' ),
							'type'        => 'string',
							'required'    => true,
						),
						'compress' => array(
							'description' => __( 'Compression format (zip, gzip, or none).', 'woocommerce' ),
							'type'        => 'string',
							'enum'        => array( 'zip', 'gzip', 'none' ),
							'default'     => 'none',
						),
					),
				),
			)
		);
	}

	/**
	 * Export products.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function export_products( $request ) {
		// Check permissions
		$permission_check = $this->export_products_permissions_check( $request );
		if ( is_wp_error( $permission_check ) ) {
			return $permission_check;
		}

		$format = $request->get_param( 'format' );
		$format = in_array( $format, array( 'csv', 'json' ), true ) ? $format : 'json';

		// Generate a unique filename if not provided
		$filename = $request->get_param( 'filename' );
		if ( empty( $filename ) ) {
			$filename = 'wc-product-export-' . date( 'Y-m-d-H-i-s' ) . '.' . $format;
		}

		// Generate a unique job ID without using Action Scheduler for immediate processing
		$job_id = 'export_' . time() . '_' . wp_rand( 1000, 9999 );

		// Prepare export parameters for background job
		$export_params = array(
			'job_id' => $job_id,
			'format' => $format,
			'filename' => $filename,
			'columns' => $request->get_param( 'columns' ),
			'selected_columns' => $request->get_param( 'selected_columns' ),
			'export_meta' => $request->get_param( 'export_meta' ),
			'export_types' => $request->get_param( 'export_types' ),
			'export_category' => $request->get_param( 'export_category' ),
			'export_product_ids' => $request->get_param( 'export_product_ids' ),
			'created_at' => current_time( 'mysql' ),
		);

		// Store job metadata
		update_option( "wc_product_export_job_{$job_id}", array(
			'status' => 'pending',
			'format' => $format,
			'filename' => $filename,
			'created_at' => current_time( 'mysql' ),
			'progress' => 0,
			'total_products' => 0,
			'columns' => $export_params['columns'],
			'selected_columns' => $export_params['selected_columns'],
			'export_meta' => $export_params['export_meta'],
			'export_types' => $export_params['export_types'],
			'export_category' => $export_params['export_category'],
			'export_product_ids' => $export_params['export_product_ids'],
		) );

		// Log for debugging
		error_log( "WooCommerce Product Export: Created job {$job_id} for {$format} export" );

		// Use ActionScheduler with immediate processing trigger
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			// Enqueue the async action
			$action_id = as_enqueue_async_action(
				'woocommerce_product_export_background_process',
				array( $export_params ),
				'wc_product_export'
			);
			error_log( "WooCommerce Product Export: Scheduled async action {$action_id} for job {$job_id}" );

			// Store ActionScheduler ID for status tracking
			$job_data = get_option( "wc_product_export_job_{$job_id}", array() );
			$job_data['action_id'] = $action_id;
			update_option( "wc_product_export_job_{$job_id}", $job_data );

			// Trigger processing in a background thread using non-blocking HTTP request
			$this->trigger_background_processing( $action_id );

		} else {
			error_log( "WooCommerce Product Export: ActionScheduler functions not available" );
		}


		// Return job information
		$response_data = array(
			'job_id' => $job_id,
			'status' => 'pending',
			'format' => $format,
			'filename' => $filename,
			'created_at' => current_time( 'mysql' ),
			'status_url' => rest_url( "wc/v3/catalog/status/{$job_id}" ),
		);

		return rest_ensure_response( $response_data );
	}


	/**
	 * Get export job status.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_export_status( $request ) {
		$job_id = $request->get_param( 'job_id' );

		// Get job metadata
		$job_data = get_option( "wc_product_export_job_{$job_id}", false );

		if ( false === $job_data ) {
			return new WP_Error(
				'woocommerce_rest_export_job_not_found',
				__( 'Export job not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		// Check Action Scheduler status to update our status if needed
		$action_status = $this->get_action_scheduler_status( $job_id );

		// Update job status based on Action Scheduler status if it has changed
		if ( 'pending' === $job_data['status'] && 'complete' === $action_status ) {
			$job_data['status'] = 'complete';
			$job_data['progress'] = 100;
			update_option( "wc_product_export_job_{$job_id}", $job_data );
		} elseif ( 'pending' === $job_data['status'] && 'failed' === $action_status ) {
			$job_data['status'] = 'failed';
			update_option( "wc_product_export_job_{$job_id}", $job_data );
		} elseif ( 'pending' === $job_data['status'] && in_array( $action_status, array( 'in-progress', 'running' ) ) ) {
			$job_data['status'] = 'processing';
			update_option( "wc_product_export_job_{$job_id}", $job_data );
		}

		$response_data = array(
			'job_id' => $job_id,
			'status' => $job_data['status'],
			'format' => $job_data['format'],
			'filename' => $job_data['filename'],
			'created_at' => $job_data['created_at'],
			'progress' => isset( $job_data['progress'] ) ? $job_data['progress'] : 0,
			'total_products' => isset( $job_data['total_products'] ) ? $job_data['total_products'] : 0,
			'action_scheduler_status' => $action_status, // Debug info
		);

		// Add download URL if export is complete
		if ( 'complete' === $job_data['status'] ) {
			$response_data['download_url'] = rest_url( 'wc/v3/catalog/download' ) .
				'?filename=' . urlencode( $job_data['filename'] ) .
				'&format=' . $job_data['format'];
		}

		// Add error message if failed
		if ( 'failed' === $job_data['status'] && isset( $job_data['error_message'] ) ) {
			$response_data['error_message'] = $job_data['error_message'];
		}

		return rest_ensure_response( $response_data );
	}

	/**
	 * Get Action Scheduler status for a job.
	 *
	 * @param string $job_id Job ID.
	 * @return string Status: pending, in-progress, complete, failed, or canceled.
	 */
	private function get_action_scheduler_status( $job_id ) {
		if ( ! function_exists( 'as_get_scheduled_actions' ) || ! class_exists( 'ActionScheduler_Store' ) ) {
			return 'unknown';
		}

		// Get the job data to find the ActionScheduler action_id
		$job_data = get_option( "wc_product_export_job_{$job_id}", array() );
		$action_id = isset( $job_data['action_id'] ) ? $job_data['action_id'] : null;

		if ( ! $action_id ) {
			error_log( "Action Scheduler: No action_id found for job {$job_id}" );
			return 'not_found';
		}

		try {
			$store = ActionScheduler_Store::instance();

			// Try to fetch the action by ActionScheduler ID
			$action = $store->fetch_action( $action_id );

			if ( ! $action ) {
				error_log( "Action Scheduler: No action found for action_id {$action_id} (job {$job_id})" );
				return 'not_found';
			}

			// Get the status directly from the store for this action
			$status = $store->get_status( $action_id );
			error_log( "Action Scheduler: Action {$action_id} (job {$job_id}) has status: {$status}" );

			// If failed, try to get the failure reason
			if ( 'failed' === $status ) {
				try {
					$logs = $store->get_logs( $action_id );
					foreach ( $logs as $log ) {
						error_log( "Action Scheduler Log for action {$action_id} (job {$job_id}): " . $log->get_message() );
					}
				} catch ( Exception $e ) {
					error_log( "Could not retrieve logs for action {$action_id} (job {$job_id}): " . $e->getMessage() );
				}
			}

			// Map Action Scheduler statuses to our statuses
			switch ( $status ) {
				case 'pending':
					return 'pending';
				case 'in-progress':
				case 'running':
					return 'in-progress';
				case 'complete':
					return 'complete';
				case 'failed':
					return 'failed';
				case 'canceled':
					return 'canceled';
				default:
					return $status; // Return as-is for debugging
			}

		} catch ( Exception $e ) {
			error_log( 'Error fetching action scheduler status for action ' . $action_id . ' (job ' . $job_id . '): ' . $e->getMessage() );
			return 'error';
		}
	}

	/**
	 * Stream export all products to avoid memory accumulation.
	 *
	 * @param WC_Product_CSV_Exporter|WC_Product_JSON_Exporter $exporter The exporter instance.
	 * @param string $format Export format (json or csv).
	 * @return array Response with status and next step info.
	 */
	private function stream_export_all_products( $exporter, $format ) {
		// Get file path using upload directory
		$upload_dir = wp_upload_dir();
		$filename = $exporter->get_filename();
		$file_path = trailingslashit( $upload_dir['basedir'] ) . $filename;

		// Process all batches in one API call by creating new exporters
		$step = 1;
		$max_steps = 1000; // Safety limit
		$total_exported = 0;

		while ( $step <= $max_steps ) {
			// Clear WordPress object cache like a new request would
			wp_cache_flush();

			// Log memory usage before each batch
			$memory_before = memory_get_usage();
			$peak_before = memory_get_peak_usage();
			error_log( "REST Export: Starting batch {$step}, Memory: " . round($memory_before / 1024 / 1024, 2) . " MB, Peak: " . round($peak_before / 1024 / 1024, 2) . " MB" );

			// Create a new exporter for this batch to ensure clean memory
			$batch_exporter = $this->create_exporter_for_batch( $exporter, $step );

			// Set a smaller batch size for better memory management
			$batch_exporter->set_limit( 250 ); // Use 250 instead of default 1000

			// Use the exporter's built-in pagination like AJAX export does
			$batch_exporter->set_page( $step );

			// Use the exporter's generate_file method like AJAX export does
			$batch_exporter->generate_file();

			// Check if we're done
			if ( 100 === $batch_exporter->get_percent_complete() ) {
				break;
			}

			// Log memory usage after each batch
			$memory_after = memory_get_usage();
			$peak_after = memory_get_peak_usage();
			error_log( "REST Export: Completed batch {$step}, Memory: " . round($memory_after / 1024 / 1024, 2) . " MB, Peak: " . round($peak_after / 1024 / 1024, 2) . " MB" );

			// Check memory limit but don't stop early - let it fail naturally
			$memory_limit = $this->get_memory_limit_bytes();
			$current_memory = memory_get_usage();
			$memory_usage_percent = ( $current_memory / $memory_limit ) * 100;
			error_log( "REST Export: Memory usage at {$memory_usage_percent}% of limit" );

			// Clear the batch exporter to free memory
			unset( $batch_exporter );

			$step++;
		}

		// Export is complete
		return array(
			'status' => 'complete',
			'total_exported' => $total_exported,
			'percentage' => 100,
			'download_url' => rest_url( 'wc/v3/catalog/download' ) . '?filename=' . urlencode( $exporter->get_filename() ) . '&format=' . $format,
			'filename' => $exporter->get_filename()
		);
	}

	/**
	 * Create a new exporter for a specific batch to ensure clean memory.
	 *
	 * @param WC_Product_CSV_Exporter|WC_Product_JSON_Exporter $original_exporter The original exporter instance.
	 * @param int $step The batch step.
	 * @return WC_Product_CSV_Exporter|WC_Product_JSON_Exporter New exporter instance.
	 */
	private function create_exporter_for_batch( $original_exporter, $step ) {
		// Create a new exporter instance
		if ( $original_exporter instanceof WC_Product_JSON_Exporter ) {
			$batch_exporter = new WC_Product_JSON_Exporter();
		} else {
			$batch_exporter = new WC_Product_CSV_Exporter();
		}

		// Copy settings from original exporter using reflection
		$reflection = new ReflectionClass( $original_exporter );

		// Copy product types to export
		$product_types_property = $reflection->getProperty( 'product_types_to_export' );
		$product_types_property->setAccessible( true );
		$product_types = $product_types_property->getValue( $original_exporter );
		$batch_exporter->set_product_types_to_export( $product_types );

		// Copy product category to export
		$product_category_property = $reflection->getProperty( 'product_category_to_export' );
		$product_category_property->setAccessible( true );
		$product_category = $product_category_property->getValue( $original_exporter );
		if ( ! empty( $product_category ) ) {
			$batch_exporter->set_product_category_to_export( $product_category );
		}

		// Copy product IDs to export
		$product_ids_property = $reflection->getProperty( 'product_ids_to_export' );
		$product_ids_property->setAccessible( true );
		$product_ids = $product_ids_property->getValue( $original_exporter );
		if ( ! empty( $product_ids ) ) {
			$batch_exporter->set_product_ids_to_export( $product_ids );
		}

		// Copy enable meta export setting
		$enable_meta_property = $reflection->getProperty( 'enable_meta_export' );
		$enable_meta_property->setAccessible( true );
		$enable_meta = $enable_meta_property->getValue( $original_exporter );
		$batch_exporter->enable_meta_export( $enable_meta );

		// Copy filename
		$batch_exporter->set_filename( $original_exporter->get_filename() );

		// Copy columns to export
		$batch_exporter->set_columns_to_export( $original_exporter->get_columns_to_export() );

		return $batch_exporter;
	}

	/**
	 * Get products for a specific batch using the exporter's methods.
	 *
	 * @param WC_Product_CSV_Exporter|WC_Product_JSON_Exporter $exporter The exporter instance.
	 * @param int $step The batch step.
	 * @return array Products for this batch.
	 */
	private function get_products_for_batch( $exporter, $step ) {
		// Use the exporter's built-in pagination
		$exporter->set_page( $step );

		// Prepare data using the exporter's method
		$exporter->prepare_data_to_export();

		// Get the prepared data using reflection to access protected method
		$reflection = new ReflectionClass( $exporter );
		$get_data_method = $reflection->getMethod( 'get_data_to_export' );
		$get_data_method->setAccessible( true );
		$data = $get_data_method->invoke( $exporter );

		if ( empty( $data ) ) {
			return array();
		}

		// Convert the data back to product objects for processing
		$products = array();
		foreach ( $data as $row_data ) {
			if ( isset( $row_data['id'] ) ) {
				$product = wc_get_product( $row_data['id'] );
				if ( $product ) {
					$products[] = $product;
				}
			}
		}

		return $products;
	}

	/**
	 * Convert a product directly to JSON string.
	 *
	 * @param WC_Product $product The product object.
	 * @return string JSON string.
	 */
	private function product_to_json_string( $product ) {
		// Build JSON string directly to avoid array memory usage
		$json_parts = array();

		$json_parts[] = '"id":' . $product->get_id();
		$json_parts[] = '"type":"' . $product->get_type() . '"';
		$json_parts[] = '"sku":"' . addslashes( $product->get_sku() ?? '' ) . '"';
		$json_parts[] = '"name":"' . addslashes( $product->get_name() ?? '' ) . '"';
		$json_parts[] = '"published":' . ( $product->get_status() === 'publish' ? 1 : 0 );
		$json_parts[] = '"featured":' . ( $product->get_featured() ? 1 : 0 );
		$json_parts[] = '"catalog_visibility":"' . $product->get_catalog_visibility() . '"';
		$json_parts[] = '"short_description":"' . addslashes( $product->get_short_description() ?? '' ) . '"';
		$json_parts[] = '"description":"' . addslashes( $product->get_description() ?? '' ) . '"';
		$json_parts[] = '"date_on_sale_from":"' . $product->get_date_on_sale_from() . '"';
		$json_parts[] = '"date_on_sale_to":"' . $product->get_date_on_sale_to() . '"';
		$json_parts[] = '"tax_status":"' . $product->get_tax_status() . '"';
		$json_parts[] = '"tax_class":"' . $product->get_tax_class() . '"';
		$json_parts[] = '"weight":"' . $product->get_weight() . '"';
		$json_parts[] = '"length":"' . $product->get_length() . '"';
		$json_parts[] = '"width":"' . $product->get_width() . '"';
		$json_parts[] = '"height":"' . $product->get_height() . '"';
		$json_parts[] = '"price":"' . $product->get_price() . '"';
		$json_parts[] = '"regular_price":"' . $product->get_regular_price() . '"';
		$json_parts[] = '"sale_price":"' . $product->get_sale_price() . '"';
		$json_parts[] = '"stock_quantity":"' . $product->get_stock_quantity() . '"';
		$json_parts[] = '"stock_status":"' . $product->get_stock_status() . '"';
		$json_parts[] = '"backorders":"' . $product->get_backorders() . '"';
		$json_parts[] = '"sold_individually":' . ( $product->get_sold_individually() ? 1 : 0 );
		$json_parts[] = '"virtual":' . ( $product->get_virtual() ? 1 : 0 );
		$json_parts[] = '"downloadable":' . ( $product->get_downloadable() ? 1 : 0 );
		$json_parts[] = '"reviews_allowed":' . ( $product->get_reviews_allowed() ? 1 : 0 );
		$json_parts[] = '"purchase_note":"' . addslashes( $product->get_purchase_note() ?? '' ) . '"';
		$json_parts[] = '"menu_order":' . $product->get_menu_order();
		$json_parts[] = '"category_ids":"' . implode( ',', $product->get_category_ids() ) . '"';
		$json_parts[] = '"tag_ids":"' . implode( ',', $product->get_tag_ids() ) . '"';
		$json_parts[] = '"shipping_class_id":' . $product->get_shipping_class_id();
		$json_parts[] = '"images":"' . ( $product->get_image_id() ? wp_get_attachment_url( $product->get_image_id() ) : '' ) . '"';

		// Add parent ID for variations
		if ( $product->get_parent_id() ) {
			$json_parts[] = '"parent_id":' . $product->get_parent_id();
		}

		return '{' . implode( ',', $json_parts ) . '}';
	}

	/**
	 * Convert a product directly to CSV string.
	 *
	 * @param WC_Product $product The product object.
	 * @return string CSV string.
	 */
	private function product_to_csv_string( $product ) {
		$data = array(
			$product->get_id(),
			$product->get_type(),
			$product->get_sku(),
			'', // GTIN field
			$product->get_name(),
			$product->get_status() === 'publish' ? 1 : 0,
			$product->get_featured() ? 1 : 0,
			$product->get_catalog_visibility(),
			$product->get_short_description(),
			$product->get_description(),
			$product->get_date_on_sale_from(),
			$product->get_date_on_sale_to(),
			$product->get_tax_status(),
			$product->get_tax_class(),
			$product->get_weight(),
			$product->get_length(),
			$product->get_width(),
			$product->get_height(),
			$product->get_price(),
			$product->get_regular_price(),
			$product->get_sale_price(),
			$product->get_stock_quantity(),
			$product->get_stock_status(),
			$product->get_backorders(),
			$product->get_sold_individually() ? 1 : 0,
			$product->get_virtual() ? 1 : 0,
			$product->get_downloadable() ? 1 : 0,
			$product->get_reviews_allowed() ? 1 : 0,
			$product->get_purchase_note(),
			$product->get_menu_order(),
			implode( ',', $product->get_category_ids() ),
			implode( ',', $product->get_tag_ids() ),
			$product->get_shipping_class_id(),
			$product->get_image_id() ? wp_get_attachment_url( $product->get_image_id() ) : '',
		);

		// Add parent ID for variations
		if ( $product->get_parent_id() ) {
			$data[] = $product->get_parent_id();
		}

		// Create CSV row using fputcsv equivalent
		$output = fopen( 'php://temp', 'r+' );
		fputcsv( $output, $data );
		rewind( $output );
		$csv_string = stream_get_contents( $output );
		fclose( $output );

		return $csv_string;
	}

	/**
	 * Get the schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'product_export',
			'type'       => 'object',
			'properties' => array(
				'format' => array(
					'description' => __( 'Export format used.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'total_products' => array(
					'description' => __( 'Total number of products exported.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'download_url' => array(
					'description' => __( 'REST API URL to download the exported file.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'exported_at' => array(
					'description' => __( 'Export timestamp.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'filename' => array(
					'description' => __( 'Filename used for export.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Compress a file using the specified compression method.
	 *
	 * @param string $file_path Path to the original file.
	 * @param string $compress Compression method ('zip' or 'gzip').
	 * @param string $format Original file format.
	 * @return string|WP_Error Path to compressed file or error.
	 */
	private function compress_file( $file_path, $compress, $format ) {
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'woocommerce_rest_export_file_not_found', __( 'Source file not found for compression.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$upload_dir = wp_upload_dir();
		$base_filename = pathinfo( $file_path, PATHINFO_FILENAME );

		if ( 'zip' === $compress ) {
			if ( ! class_exists( 'ZipArchive' ) ) {
				return new WP_Error( 'woocommerce_rest_export_zip_not_available', __( 'ZIP compression not available on this server.', 'woocommerce' ), array( 'status' => 500 ) );
			}

			$zip_filename = $base_filename . '.zip';
			$zip_path = trailingslashit( $upload_dir['basedir'] ) . $zip_filename;

			$zip = new ZipArchive();
			if ( $zip->open( $zip_path, ZipArchive::CREATE ) === true ) {
				$zip->addFile( $file_path, basename( $file_path ) );
				$zip->close();

				if ( file_exists( $zip_path ) ) {
					return $zip_path;
				}
			}

			return new WP_Error( 'woocommerce_rest_export_zip_failed', __( 'Failed to create ZIP archive.', 'woocommerce' ), array( 'status' => 500 ) );

		} elseif ( 'gzip' === $compress ) {
			if ( ! function_exists( 'gzopen' ) ) {
				return new WP_Error( 'woocommerce_rest_export_gzip_not_available', __( 'GZIP compression not available on this server.', 'woocommerce' ), array( 'status' => 500 ) );
			}

			$original_filename = basename( $file_path );
			$gzip_filename = $original_filename . '.gz';
			$gzip_path = trailingslashit( $upload_dir['basedir'] ) . $gzip_filename;

			$source = fopen( $file_path, 'rb' );
			$dest = gzopen( $gzip_path, 'wb9' );

			if ( $source && $dest ) {
				while ( ! feof( $source ) ) {
					gzwrite( $dest, fread( $source, 8192 ) );
				}
				fclose( $source );
				gzclose( $dest );

				if ( file_exists( $gzip_path ) ) {
					return $gzip_path;
				}
			}

			return new WP_Error( 'woocommerce_rest_export_gzip_failed', __( 'Failed to create GZIP archive.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		return new WP_Error( 'woocommerce_rest_export_invalid_compression', __( 'Invalid compression method specified.', 'woocommerce' ), array( 'status' => 400 ) );
	}

	/**
	 * Get the appropriate MIME type based on format and compression.
	 *
	 * @param string $format Original file format ('json' or 'csv').
	 * @param string $compress Compression method ('zip', 'gzip', or 'none').
	 * @return string MIME type.
	 */
	private function get_mime_type( $format, $compress ) {
		if ( 'zip' === $compress ) {
			return 'application/zip';
		} elseif ( 'gzip' === $compress ) {
			return 'application/gzip';
		}

		return 'json' === $format ? 'application/json' : 'text/csv';
	}

	/**
	 * Get memory limit in bytes.
	 *
	 * @return int Memory limit in bytes
	 */
	private function get_memory_limit_bytes() {
		$memory_limit = ini_get( 'memory_limit' );

		if ( -1 === $memory_limit ) {
			return PHP_INT_MAX;
		}

		$unit = strtolower( substr( $memory_limit, -1 ) );
		$value = (int) substr( $memory_limit, 0, -1 );

		switch ( $unit ) {
			case 'g':
				$value *= 1024;
				// Fall through
			case 'm':
				$value *= 1024;
				// Fall through
			case 'k':
				$value *= 1024;
				break;
		}

		return $value;
	}


	/**
	 * Trigger background processing using non-blocking HTTP request.
	 *
	 * @param int $action_id ActionScheduler action ID.
	 */
	private function trigger_background_processing( $action_id ) {
		error_log( "WooCommerce Product Export: Triggering background processing for action {$action_id}" );

		// Create a special endpoint URL for triggering ActionScheduler
		$trigger_url = add_query_arg( array(
			'action' => 'wc_trigger_export_processing',
			'action_id' => $action_id,
			'nonce' => wp_create_nonce( "trigger_export_{$action_id}" ),
		), admin_url( 'admin-ajax.php' ) );

		// Make a non-blocking HTTP request to trigger processing
		$response = wp_remote_post( $trigger_url, array(
			'timeout'   => 0.01,  // Very short timeout
			'blocking'  => false, // Non-blocking
			'sslverify' => false, // For local requests
			'headers'   => array(
				'User-Agent' => 'WooCommerce Export Trigger',
			),
		) );

		if ( is_wp_error( $response ) ) {
			error_log( "WooCommerce Product Export: Failed to trigger background processing: " . $response->get_error_message() );
		} else {
			error_log( "WooCommerce Product Export: Background processing trigger sent for action {$action_id}" );
		}
	}

	/**
	 * Force immediate processing of ActionScheduler action.
	 *
	 * @param int $action_id ActionScheduler action ID.
	 */
	private function run_action_immediately( $action_id ) {
		error_log( "WooCommerce Product Export: Attempting immediate processing of action {$action_id}" );

		try {
			// Multiple approaches to force immediate processing

			// Approach 1: Trigger the queue runner hook directly
			error_log( "WooCommerce Product Export: Triggering action_scheduler_run_queue hook" );
			do_action( 'action_scheduler_run_queue', 'WooCommerce Export' );

			// Approach 2: Force ActionScheduler runner directly (most reliable)
			if ( class_exists( 'ActionScheduler' ) && method_exists( 'ActionScheduler', 'runner' ) ) {
				error_log( "WooCommerce Product Export: Running ActionScheduler runner directly" );
				$runner = ActionScheduler::runner();
				if ( method_exists( $runner, 'run' ) ) {
					// Run with a small time limit to process our action
					$runner->run( 'WooCommerce Export' );
				}
			}

			// Approach 3: If we have QueueRunner class, use it directly
			if ( class_exists( 'ActionScheduler_QueueRunner' ) ) {
				error_log( "WooCommerce Product Export: Using ActionScheduler_QueueRunner directly" );
				$queue_runner = ActionScheduler_QueueRunner::instance();
				if ( method_exists( $queue_runner, 'run' ) ) {
					$queue_runner->run( 'WooCommerce Export' );
				}
			}
		} catch ( Exception $e ) {
			error_log( "WooCommerce Product Export: Failed to trigger immediate processing: " . $e->getMessage() );
		}
	}

	/**
	 * Process product export in background.
	 * This is called by Action Scheduler.
	 *
	 * @param array $export_params Export parameters including job_id.
	 */
	public function process_export_background( $export_params ) {
		error_log( 'WooCommerce Product Export: Background process handler called with params: ' . wp_json_encode( $export_params ) );

		// Get job ID from export parameters
		$job_id = isset( $export_params['job_id'] ) ? $export_params['job_id'] : null;

		if ( ! $job_id ) {
			error_log( 'WooCommerce Product Export: No job ID provided in export parameters' );
			return;
		}

		// Implement mutex lock to prevent duplicate execution
		$lock_key = "wc_export_lock_{$job_id}";
		$lock_value = time();
		$lock_timeout = 300; // 5 minutes

		// Try to acquire lock
		if ( ! add_option( $lock_key, $lock_value, '', 'no' ) ) {
			// Lock exists, check if it's stale
			$existing_lock = get_option( $lock_key );
			if ( $existing_lock && ( time() - $existing_lock ) < $lock_timeout ) {
				error_log( "WooCommerce Product Export: Job {$job_id} is already running, skipping duplicate execution" );
				return;
			}
			// Stale lock, update it
			update_option( $lock_key, $lock_value );
		}

		try {
			// Get current job data
			$job_data_current = get_option( "wc_product_export_job_{$job_id}", array() );

			// Check if job is already completed or processing
			if ( isset( $job_data_current['status'] ) && in_array( $job_data_current['status'], array( 'processing', 'complete' ) ) ) {
				error_log( "WooCommerce Product Export: Job {$job_id} already {$job_data_current['status']}, skipping" );
				delete_option( $lock_key ); // Release lock
				return;
			}

			// Update job status to processing
			$processing_data = array_merge( $job_data_current, array(
				'status' => 'processing',
				'started_at' => current_time( 'mysql' ),
				'progress' => 0,
			) );

			update_option( "wc_product_export_job_{$job_id}", $processing_data );
			error_log( "WooCommerce Product Export: Starting job {$job_id}" );

			$format = $export_params['format'];

			// Create the appropriate exporter
			if ( 'json' === $format ) {
				include_once WC_ABSPATH . 'includes/export/class-wc-product-json-exporter.php';
				$exporter = new WC_Product_JSON_Exporter();
			} else {
				include_once WC_ABSPATH . 'includes/export/class-wc-product-csv-exporter.php';
				$exporter = new WC_Product_CSV_Exporter();
			}

			// Set up the exporter with the provided parameters
			if ( ! empty( $export_params['columns'] ) ) {
				$exporter->set_column_names( $export_params['columns'] );
			}

			if ( ! empty( $export_params['selected_columns'] ) ) {
				$exporter->set_columns_to_export( $export_params['selected_columns'] );
			}

			if ( ! empty( $export_params['export_meta'] ) ) {
				$exporter->enable_meta_export( true );
			}

			if ( ! empty( $export_params['export_types'] ) ) {
				$exporter->set_product_types_to_export( $export_params['export_types'] );
			}

			if ( ! empty( $export_params['export_category'] ) ) {
				$exporter->set_product_category_to_export( $export_params['export_category'] );
			}

			if ( ! empty( $export_params['export_product_ids'] ) ) {
				$ids_raw = explode( ',', sanitize_text_field( $export_params['export_product_ids'] ) );
				if ( ! empty( $ids_raw ) ) {
					$exporter->set_product_ids_to_export( $ids_raw );
				}
			}

			$exporter->set_filename( $export_params['filename'] );

			// Use the existing streaming approach
			$controller = new self();
			$export_status = $controller->stream_export_all_products( $exporter, $format );

			// Update job status based on export result
			$completion_data = array_merge( $job_data_current, array(
				'status' => 'complete', // Always mark as complete if we reach here
				'completed_at' => current_time( 'mysql' ),
				'progress' => 100,
				'total_products' => isset( $export_status['total_exported'] ) ? $export_status['total_exported'] : 0,
			) );

			update_option( "wc_product_export_job_{$job_id}", $completion_data );
			error_log( "WooCommerce Product Export: Job {$job_id} completed successfully" );
		} catch ( Exception $e ) {
			// Update job status to failed
			update_option( "wc_product_export_job_{$job_id}", array(
				'status' => 'failed',
				'format' => $export_params['format'],
				'filename' => $export_params['filename'],
				'created_at' => $export_params['created_at'],
				'failed_at' => current_time( 'mysql' ),
				'error_message' => $e->getMessage(),
				'progress' => 0,
				'total_products' => 0,
			) );

			error_log( 'WooCommerce Product Export Background Job Failed: ' . $e->getMessage() );
		} finally {
			// Always release the lock
			delete_option( $lock_key );
			error_log( "WooCommerce Product Export: Released lock for job {$job_id}" );
		}
	}

}