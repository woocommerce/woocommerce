<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Importer' ) ) {
	return;
}

/**
 * Product importer controller - handles file upload and forms in admin.
 *
 * @author      Automattic
 * @category    Admin
 * @package     WooCommerce/Admin/Importers
 * @version     3.1.0
 */
class WC_Product_CSV_Importer_Controller {

	/**
	 * The path to the current file.
	 *
	 * @var string
	 */
	protected $file = '';

	/**
	 * The current import step.
	 *
	 * @var string
	 */
	protected $step = '';

	/**
	 * Progress steps.
	 *
	 * @var array
	 */
	protected $steps = array();

	/**
	 * Errors.
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * The current delimiter for the file being read.
	 *
	 * @var string
	 */
	protected $delimiter = ',';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->steps = array(
			'upload' => array(
				'name'    => __( 'Upload CSV file', 'woocommerce' ),
				'view'    => array( $this, 'upload_form' ),
				'handler' => array( $this, 'upload_form_handler' ),
			),
			'mapping' => array(
				'name'    => __( 'Column mapping', 'woocommerce' ),
				'view'    => array( $this, 'mapping_form' ),
				'handler' => '',
			),
			'import' => array(
				'name'    => __( 'Import', 'woocommerce' ),
				'view'    => array( $this, 'import' ),
				'handler' => '',
			),
			'done' => array(
				'name'    => __( 'Done!', 'woocommerce' ),
				'view'    => array( $this, 'done' ),
				'handler' => '',
			),
		);
		$this->step = isset( $_REQUEST['step'] ) ? sanitize_key( $_REQUEST['step'] ) : current( array_keys( $this->steps ) );
		$this->file = isset( $_REQUEST['file'] ) ? wc_clean( $_REQUEST['file'] ) : '';
	}

	/**
	 * Get the URL for the next step's screen.
	 * @param string step   slug (default: current step)
	 * @return string       URL for next step if a next step exists.
	 *                      Admin URL if it's the last step.
	 *                      Empty string on failure.
	 */
	public function get_next_step_link( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}

		$keys = array_keys( $this->steps );

		if ( end( $keys ) === $step ) {
			return admin_url();
		}

		$step_index = array_search( $step, $keys );

		if ( false === $step_index ) {
			return '';
		}

		$params = array(
			'step'      => $keys[ $step_index + 1 ],
			'file'      => $this->file,
			'delimiter' => $this->delimiter,
			'_wpnonce'  => wp_create_nonce( 'woocommerce-csv-importer' ), // wp_nonce_url() escapes & to &amp; breaking redirects.
		);

		return add_query_arg( $params );
	}

	/**
	 * Output header view.
	 */
	protected function output_header() {
		include( dirname( __FILE__ ) . '/views/html-csv-import-header.php' );
	}

	/**
	 * Output steps view.
	 */
	protected function output_steps() {
		include( dirname( __FILE__ ) . '/views/html-csv-import-steps.php' );
	}

	/**
	 * Output footer view.
	 */
	protected function output_footer() {
		include( dirname( __FILE__ ) . '/views/html-csv-import-footer.php' );
	}

	/**
	 * Get importer instance.
	 *
	 * @param  string $file File to import.
	 * @param  array  $args Importer arguments.
	 * @return WC_Product_CSV_Importer
	 */
	protected function get_importer( $file, $args = array() ) {
		$importer_class = apply_filters( 'woocommerce_product_csv_importer_class', 'WC_Product_CSV_Importer' );
		return new $importer_class( $file, $args );
	}

	/**
	 * Add error message.
	 */
	protected function add_error( $error ) {
		$this->errors[] = $error;
	}

	/**
	 * Add error message.
	 */
	protected function output_errors() {
		if ( $this->errors ) {
			foreach ( $this->errors as $error ) {
				echo '<p class="error inline">' . esc_html( $error ) . '</p>';
			}
		}
	}

	/**
	 * Dispatch current step and show correct view.
	 */
	public function dispatch() {
		if ( ! empty( $_POST['save_step'] ) && ! empty( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'], $this );
		}
		$this->output_header();
		$this->output_steps();
		$this->output_errors();
		call_user_func( $this->steps[ $this->step ]['view'], $this );
		$this->output_footer();

		/*switch ( $this->step ) {
			case 2 :
				check_admin_referer( 'woocommerce-csv-importer' );
				$file           = null;
				$this->id       = isset( $_REQUEST['file_id'] ) ? absint( $_REQUEST['file_id'] ) : '';
				$this->file_url = isset( $_REQUEST['file_url'] ) ? sanitize_text_field( $_REQUEST['file_url'] ) : '';

				if ( $this->id ) {
					$file = get_attached_file( $this->id );
				} elseif ( $this->file_url ) {
					$file = ABSPATH . $this->file_url;
				}

				$this->import( $file );

				break;
		}*/
	}

	/**
	 * Output information about the uploading process.
	 */
	protected function upload_form() {
		$bytes      = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$size       = size_format( $bytes );
		$upload_dir = wp_upload_dir();

		include( dirname( __FILE__ ) . '/views/html-product-csv-import-form.php' );
	}

	/**
	 * Handle the upload form and store options.
	 */
	public function upload_form_handler() {
		check_admin_referer( 'woocommerce-csv-importer' );

		$file = $this->handle_upload();

		if ( is_wp_error( $file ) ) {
			$this->add_error( $file->get_error_message() );
			return;
		} else {
			$this->file = $file;
		}

		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Handles the CSV upload and initial parsing of the file to prepare for.
	 * displaying author import options.
	 *
	 * @return string|WP_Error
	 */
	public function handle_upload() {
		if ( empty( $_POST['file_url'] ) ) {
			if ( ! isset( $_FILES['import'] ) ) {
				return new WP_Error(  'woocommerce_product_csv_importer_upload_file_empty', __( 'File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your php.ini or by post_max_size being defined as smaller than upload_max_filesize in php.ini.', 'woocommerce' ) );
			}

			$overrides                 = array( 'test_form' => false, 'test_type' => false );
			$_FILES['import']['name'] .= '.txt';
			$upload                    = wp_handle_upload( $_FILES['import'], $overrides );

			if ( isset( $upload['error'] ) ) {
				return new WP_Error( 'woocommerce_product_csv_importer_upload_error', $upload['error'] );
			}

			// Construct the object array
			$object = array(
				'post_title'     => basename( $upload['file'] ),
				'post_content'   => $upload['url'],
				'post_mime_type' => $upload['type'],
				'guid'           => $upload['url'],
				'context'        => 'import',
				'post_status'    => 'private',
			);

			// Save the data
			$id = wp_insert_attachment( $object, $upload['file'] );

			/*
			 * Schedule a cleanup for one day from now in case of failed
			 * import or missing wp_import_cleanup() call.
			 */
			wp_schedule_single_event( time() + DAY_IN_SECONDS, 'importer_scheduled_cleanup', array( $id ) );

			return $upload['file'];
		} elseif ( file_exists( ABSPATH . $_POST['file_url'] ) ) {
			return ABSPATH . $_POST['file_url'];
		}

		return new WP_Error( 'woocommerce_product_csv_importer_upload_invalid_file', __( 'Please upload or provide the link to a valid CSV file.', 'woocommerce' ) );
	}

	/**
	 * Mapping step @todo
	 */
	protected function mapping_form() {
		$importer     = $this->get_importer( $this->file, array( 'lines' => 1 ) );
		$headers      = $importer->get_raw_keys();
		$mapped_items = $this->auto_map_columns( $headers );
		$sample       = current( $importer->get_raw_data() );

		if ( empty( $sample ) ) {
			$this->add_error( __( 'The file is empty, please try again with a new file.', 'woocommerce' ) );
			return;
		}

		// Check if all fields matches.
		if ( 0 === count( array_diff( $mapped_items, $this->get_default_fields() ) ) ) {
			wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
			exit;
		}

		include_once( dirname( __FILE__ ) . '/views/html-csv-import-mapping.php' );
	}

	/**
	 * Import the file if it exists and is valid.
	 */
	public function import() {
		if ( ! is_file( $this->file ) ) {
			$this->add_error( __( 'The file does not exist, please try again.', 'woocommerce' ) );
			return;
		}

		$args = array( 'parse' => true );

		if ( ! empty( $_POST['map_to'] ) ) {
			$args['mapping'] = wp_unslash( $_POST['map_to'] );
		}

		$importer = $this->get_importer( $this->file, $args );
		$data     = $importer->import();
		$imported = count( $data['imported'] );
		$failed   = count( $data['failed'] );

		$results = sprintf(
			/* translators: %d: products count */
			_n( 'Imported %s product.', 'Imported %s products.', $imported, 'woocommerce' ),
			'<strong>' . number_format_i18n( $imported ) . '</strong>'
		);

		// @todo create a view to display errors or log with WC_Logger.
		if ( 0 < $failed ) {
			$results .= ' ' . sprintf(
				/* translators: %d: products count */
				_n( 'Failed %s product.', 'Failed %s products.', $failed, 'woocommerce' ),
				'<strong>' . number_format_i18n( $failed ) . '</strong>'
			);
		}

		// Show result.
		echo '<div class="updated settings-error"><p>';
		/* translators: %d: import results */
		printf( __( 'Import complete: %s', 'woocommerce' ), $results );
		echo '</p></div>';
	}

	/**
	 * @todo
	 */
	protected function done() {

	}

	/**
	 * Get default fields.
	 *
	 * @return array
	 */
	protected function get_default_fields() {
		$fields = array(
			'id',
			'type',
			'sku',
			'name',
			'status',
			'featured',
			'catalog_visibility',
			'short_description',
			'description',
			'date_on_sale_from',
			'date_on_sale_to',
			'tax_status',
			'tax_class',
			'stock_status',
			'backorders',
			'sold_individually',
			'weight',
			'length',
			'width',
			'height',
			'reviews_allowed',
			'purchase_note',
			'price',
			'regular_price',
			'manage_stock',
			'stock_quantity',
			'category_ids',
			'tag_ids',
			'shipping_class_id',
			'images',
			'downloads',
			'download_limit',
			'download_expiry',
			'parent_id',
			'upsell_ids',
			'cross_sell_ids',
		);

		return apply_filters( 'woocommerce_csv_product_default_fields', $fields );
	}

	/**
	 * Auto map column names.
	 *
	 * @param  array $fields Header columns.
	 * @return array
	 */
	protected function auto_map_columns( $fields ) {
		$weight_unit          = get_option( 'woocommerce_weight_unit' );
		$dimension_unit       = get_option( 'woocommerce_dimension_unit' );
		$default_column_names = array_flip( array(
			'id'                 => __( 'ID', 'woocommerce' ),
			'type'               => __( 'Type', 'woocommerce' ),
			'sku'                => __( 'SKU', 'woocommerce' ),
			'name'               => __( 'Name', 'woocommerce' ),
			'published'          => __( 'Published', 'woocommerce' ),
			'featured'           => __( 'Is featured?', 'woocommerce' ),
			'catalog_visibility' => __( 'Visibility in catalog', 'woocommerce' ),
			'short_description'  => __( 'Short Description', 'woocommerce' ),
			'description'        => __( 'Description', 'woocommerce' ),
			'date_on_sale_from'  => __( 'Date sale price starts', 'woocommerce' ),
			'date_on_sale_to'    => __( 'Date sale price ends', 'woocommerce' ),
			'tax_status'         => __( 'Tax Class', 'woocommerce' ),
			'stock_status'       => __( 'In stock?', 'woocommerce' ),
			'stock'              => __( 'Stock', 'woocommerce' ),
			'backorders'         => __( 'Backorders allowed?', 'woocommerce' ),
			'sold_individually'  => __( 'Sold individually?', 'woocommerce' ),
			'weight'             => sprintf( __( 'Weight (%s)', 'woocommerce' ), $weight_unit ),
			'length'             => sprintf( __( 'Length (%s)', 'woocommerce' ), $dimension_unit ),
			'width'              => sprintf( __( 'Width (%s)', 'woocommerce' ), $dimension_unit ),
			'height'             => sprintf( __( 'Height (%s)', 'woocommerce' ), $dimension_unit ),
			'reviews_allowed'    => __( 'Allow customer reviews?', 'woocommerce' ),
			'purchase_note'      => __( 'Purchase Note', 'woocommerce' ),
			'sale_price'         => __( 'Sale Price', 'woocommerce' ),
			'regular_price'      => __( 'Regular Price', 'woocommerce' ),
			'category_ids'       => __( 'Categories', 'woocommerce' ),
			'tag_ids'            => __( 'Tags', 'woocommerce' ),
			'shipping_class_id'  => __( 'Shipping Class', 'woocommerce' ),
			'image_id'           => __( 'Images', 'woocommerce' ),
			'download_limit'     => __( 'Download Limit', 'woocommerce' ),
			'download_expiry'    => __( 'Download Expiry Days', 'woocommerce' ),
			'parent_id'          => __( 'Parent', 'woocommerce' ),
			'upsell_ids'         => __( 'Upsells', 'woocommerce' ),
			'cross_sell_ids'     => __( 'Cross-sells', 'woocommerce' ),
		) );
		$special_data = array_map( array( $this, 'sanitize_special_column_name_regex' ), array(
			'attributes:name'    => __( 'Attribute %d Name', 'woocommerce' ),
			'attributes:value'   => __( 'Attribute %d Value(s)', 'woocommerce' ),
			'attributes:default' => __( 'Attribute %d Default', 'woocommerce' ),
			'downloads:name'     => __( 'Download %d Name', 'woocommerce' ),
			'downloads:url'      => __( 'Download %d URL', 'woocommerce' ),
			'meta:'              => __( 'Meta: %s', 'woocommerce' ),
		) );

		$new_fields = array();
		foreach ( $fields as $index => $field ) {
			$new_fields[ $index ] = $field;

			if ( isset( $default_column_names[ $field ] ) ) {
				$new_fields[ $index ] = $default_column_names[ $field ];
			} else {
				foreach ( $special_data as $special_key => $regex ) {
					if ( preg_match( $regex, $field, $matches ) ) {
						$new_fields[ $index ] = $special_key . $matches[1];
						break;
					}
				}
			}
		}

		return apply_filters( 'woocommerce_csv_product_import_mapped_fields', $new_fields, $fields );
	}

	/**
	 * Sanitize special column name regex.
	 *
	 * @param  string $value Raw special column name.
	 * @return string
	 */
	protected function sanitize_special_column_name_regex( $value ) {
		return '/' . str_replace( array( '%d', '%s' ), '(.*)', quotemeta( $value ) ) . '/';
	}

	/**
	 * Get mapping options.
	 *
	 * @param  string $item Item name
	 * @return array
	 */
	protected function get_mapping_options( $item = '' ) {
		// Get number for special column names.
		$special_index = $item;
		if ( preg_match('/\d+$/', $item, $matches ) ) {
			$special_index = $matches[0];
		}
		$meta = str_replace( 'meta:', '', $item );

		$weight_unit    = get_option( 'woocommerce_weight_unit' );
		$dimension_unit = get_option( 'woocommerce_dimension_unit' );
		$options        = array(
			'id'                 => __( 'ID', 'woocommerce' ),
			'type'               => __( 'Type', 'woocommerce' ),
			'sku'                => __( 'SKU', 'woocommerce' ),
			'name'               => __( 'Name', 'woocommerce' ),
			'published'          => __( 'Published', 'woocommerce' ),
			'featured'           => __( 'Is featured?', 'woocommerce' ),
			'catalog_visibility' => __( 'Visibility in catalog', 'woocommerce' ),
			'short_description'  => __( 'Short Description', 'woocommerce' ),
			'description'        => __( 'Description', 'woocommerce' ),
			'date_on_sale_from'  => __( 'Date sale price starts', 'woocommerce' ),
			'date_on_sale_to'    => __( 'Date sale price ends', 'woocommerce' ),
			'tax_status'         => __( 'Tax Status', 'woocommerce' ),
			'tax_class'          => __( 'Tax Class', 'woocommerce' ),
			'stock_status'       => __( 'In stock?', 'woocommerce' ),
			'backorders'         => __( 'Backorders allowed?', 'woocommerce' ),
			'sold_individually'  => __( 'Sold individually?', 'woocommerce' ),
			/* translators: %s: weight unit */
			'weight'             => sprintf( __( 'Weight (%s)', 'woocommerce' ), $weight_unit ),
			'dimensions'         => array(
				'name'    => __( 'Dimensions', 'woocommerce' ),
				'options' => array(
					/* translators: %s: dimension unit */
					'length'             => sprintf( __( 'Length (%s)', 'woocommerce' ), $dimension_unit ),
					/* translators: %s: dimension unit */
					'width'              => sprintf( __( 'Width (%s)', 'woocommerce' ), $dimension_unit ),
					/* translators: %s: dimension unit */
					'height'             => sprintf( __( 'Height (%s)', 'woocommerce' ), $dimension_unit ),
				),
			),
			'reviews_allowed'    => __( 'Allow customer reviews?', 'woocommerce' ),
			'purchase_note'      => __( 'Purchase Note', 'woocommerce' ),
			'sale_price'         => __( 'Sale Price', 'woocommerce' ),
			'regular_price'      => __( 'Regular Price', 'woocommerce' ),
			'stock'              => __( 'Stock', 'woocommerce' ),
			'category_ids'       => __( 'Categories', 'woocommerce' ),
			'tag_ids'            => __( 'Tags', 'woocommerce' ),
			'shipping_class_id'  => __( 'Shipping Class', 'woocommerce' ),
			'image_id'           => __( 'Images', 'woocommerce' ),
			'parent_id'          => __( 'Parent', 'woocommerce' ),
			'upsell_ids'         => __( 'Upsells', 'woocommerce' ),
			'cross_sell_ids'     => __( 'Cross-sells', 'woocommerce' ),
			'downloads'          => array(
				'name'    => __( 'Downloads', 'woocommerce' ),
				'options' => array(
					'downloads:name' . $special_index => __( 'Download Name', 'woocommerce' ),
					'downloads:url' . $special_index  => __( 'Download URL', 'woocommerce' ),
					'download_limit'                  => __( 'Download Limit', 'woocommerce' ),
					'download_expiry'                 => __( 'Download Expiry Days', 'woocommerce' ),
				),
			),
			'attributes'         => array(
				'name'    => __( 'Attributes', 'woocommerce' ),
				'options' => array(
					'attributes:name' . $special_index    => __( 'Attributes name', 'woocommerce' ),
					'attributes:value' . $special_index   => __( 'Attributes value', 'woocommerce' ),
					'attributes:default' . $special_index => __( 'Default attribute', 'woocommerce' ),
				),
			),
			'meta:' . $meta      => __( 'Import as meta', 'woocommerce' ),
		);

		return apply_filters( 'woocommerce_csv_product_import_mapping_options', $options, $item );
	}
}
