<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Importer' ) ) {
	return;
}

/**
 * Product importer - import products into WooCommerce.
 *
 * @author      Automattic
 * @category    Admin
 * @package     WooCommerce/Admin/Importers
 * @version     3.1.0
 */
class WC_Product_Importer extends WP_Importer {

	/**
	 * The current file id.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The current file url.
	 *
	 * @var string
	 */
	public $file_url;

	/**
	 * The current import page.
	 *
	 * @var string
	 */
	public $import_page;

	/**
	 * The current delimiter.
	 *
	 * @var string
	 */
	public $delimiter;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->import_page = 'woocommerce_product_csv';
		$this->delimiter   = empty( $_POST['delimiter'] ) ? ',' : (string) wc_clean( $_POST['delimiter'] );
	}

	/**
	 * Registered callback function for the WordPress Importer.
	 *
	 * Manages the three separate stages of the CSV import process.
	 */
	public function dispatch() {

		$this->header();

		$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];

		switch ( $step ) {

			case 0:
				$this->greet();
				break;

			case 1:
				check_admin_referer( 'import-upload' );

				if ( $this->handle_upload() ) {

					if ( $this->id ) {
						$file = get_attached_file( $this->id );
					} else {
						$file = ABSPATH . $this->file_url;
					}

					$this->import( $file );
				}
				break;
		}

		$this->footer();
	}

	/**
	 * Import is starting.
	 */
	private function import_start() {
		if ( function_exists( 'gc_enable' ) ) {
			gc_enable();
		}
		wc_set_time_limit( 0 );
		@ob_flush();
		@flush();
		@ini_set( 'auto_detect_line_endings', '1' );
	}

	/**
	 * Import the file if it exists and is valid.
	 *
	 * @param mixed $file
	 */
	public function import( $file ) {
		if ( ! is_file( $file ) ) {
			$this->import_error( __( 'The file does not exist, please try again.', 'woocommerce' ) );
		}

		$this->import_start();

		$data = $this->read_csv( $file, array( 'parse' => true ) );

		// Show Result
		echo '<div class="updated settings-error"><p>';
		/* translators: %d: products count */
		printf(
			__( 'Import complete - imported %d products.', 'woocommerce' ),
			'<strong>' . count( $data ) . '</strong>'
		);
		echo '</p></div>';

		$this->import_end();
	}

	/**
	 * Performs post-import cleanup of files and the cache.
	 */
	public function import_end() {
		echo '<p>' . __( 'All done!', 'woocommerce' ) . ' <a href="' . admin_url( 'edit.php?post_type=product' ) . '">' . __( 'View products', 'woocommerce' ) . '</a>' . '</p>';

		do_action( 'import_end' );
	}

	/**
	 * Handles the CSV upload and initial parsing of the file to prepare for.
	 * displaying author import options.
	 *
	 * @return bool False if error uploading or invalid file, true otherwise
	 */
	public function handle_upload() {
		if ( empty( $_POST['file_url'] ) ) {

			$file = wp_import_handle_upload();

			if ( isset( $file['error'] ) ) {
				$this->import_error( $file['error'] );
			}

			$this->id = absint( $file['id'] );

		} elseif ( file_exists( ABSPATH . $_POST['file_url'] ) ) {
			$this->file_url = esc_attr( $_POST['file_url'] );
		} else {
			$this->import_error();
		}

		return true;
	}

	/**
	 * Read a CSV file.
	 *
	 * @param mixed $file
	 * @param array $args See $default_args
	 * @return array
	 */
	public function read_csv( $file, $args = array() ) {

		$default_args = array(
			'start_pos' => 0, // File pointer start.
			'end_pos'   => -1, // File pointer end.
			'lines'     => -1, // Max lines to read.
			'mapping'   => array(), // Column mapping. csv_heading => schema_heading.
			'parse'     => false, // Whether to sanitize and format data.
		);
		$args = wp_parse_args( $args, $default_args );

		$data = array(
			'raw_headers' => array(),
			'data'        => array(),
		);

		if ( false !== ( $handle = fopen( $file, 'r' ) ) ) {

			$data['raw_headers'] = fgetcsv( $handle, 0, $this->delimiter );

			if ( 0 !== $args['start_pos'] ) {
				fseek( $handle, (int) $args['start_pos'] );
			}

			while ( false !== ( $row = fgetcsv( $handle, 0, $this->delimiter ) ) ) {
				$data['data'][] = $row;

	            if ( ( $args['end_pos'] > 0 && ftell( $handle ) >= $args['end_pos'] ) || 0 === --$args['lines'] ) {
	            	break;
				}
			}
		}

		if ( ! empty( $args['mapping'] ) ) {
			$data = $this->map_headers( $data, $args['mapping'] );
		}

		if ( $args['parse'] ) {
			$data = $this->parse_data( $data );
		}

		return apply_filters( 'woocommerce_csv_product_import_data', $data, $file, $args );
	}

	/**
	 * Map raw headers to known headers.
	 *
	 * @param array $data
	 * @param array $mapping 'raw column name' => 'schema column name'
	 * @return array
	 */
	public function map_headers( $data, $mapping ) {
		$data['headers'] = array();
		foreach ( $data['raw_headers'] as $heading ) {
			$data['headers'] = isset( $mapping[ $heading ] ) ? $mapping[ $heading ] : $heading;
		}
		return $data;
	}

	/**
	 * Map and format raw data to known fields.
	 *
	 * @param array $data
	 * @return array
	 */
	public function parse_data( $data ) {

		/**
		 * Columns not mentioned here will get parsed with 'esc_attr'.
		 * column_name => callback
		 * @todo Use slugs instead of full column name once mapping is completed.
		 */
		$data_formatting = array(
			'ID'                       => 'absint',
			'Published'                => array( $this, 'parse_bool_field' ),
			'Is featured?'             => array( $this, 'parse_bool_field' ),
			'Date sale price starts'   => 'strtotime',
			'Date sale price ends'     => 'strtotime',
			'In stock?'                => array( $this, 'parse_bool_field' ),
			'Backorders allowed?'      => array( $this, 'parse_bool_field' ),
			'Sold individually?'       => array( $this, 'parse_bool_field' ),
			'Weight'                   => array( $this, 'parse_float_field' ),
			'Length'                   => array( $this, 'parse_float_field' ),
			'Height'                   => array( $this, 'parse_float_field' ),
			'Width'                    => array( $this, 'parse_float_field' ),
			'Allow customer reviews?'  => array( $this, 'parse_bool_field' ),
			'Purchase Note'            => 'wp_kses',
			'Price'                    => 'wc_format_decimal',
			'Regular Price'            => 'wc_format_decimal',
			'Stock'                    => 'absint',
			'Categories'               => array( $this, 'parse_categories' ),
			'Tags'                     => array( $this, 'parse_comma_field' ),
			'Images'                   => array( $this, 'parse_comma_field' ),
			'Upsells'                  => array( $this, 'parse_comma_field' ),
			'Cross-sells'              => array( $this, 'parse_comma_field' ),
			'Download Limit'           => 'absint',
			'Download Expiry Days'     => 'absint',
		);
		$regex_match_data_formatting = array(
			'/Attribute * Value\(s\)/' => array( $this, 'parse_comma_field' ),
			'/Attribute * Visible/'    => array( $this, 'parse_bool_field' ),
			'/Download * URL/'         => 'esc_url',
		);

		$headers = ! empty( $data['headers'] ) ? $data['headers'] : $data['raw_headers'];
		$parse_functions = array();
		$parsed_data = array();

		// Figure out the parse function for each column.
		foreach ( $headers as $index => $heading ) {

			$parse_function = 'esc_attr';
			if ( isset( $data_formatting[ $heading ] ) ) {
				$parse_function = $data_formatting[ $heading ];
			} else {
				foreach ( $regex_match_data_formatting as $regex => $callback ) {
					if ( preg_match( $regex, $heading ) ) {
						$parse_function = $callback;
						break;
					}
				}
			}

			$parse_functions[] = $parse_function;
		}

		// Parse the data.
		foreach ( $data['data'] as $row ) {
			$item = array();
			foreach ( $row as $index => $field ) {
				$item[ $headers[ $index ] ] = call_user_func( $parse_functions[ $index ], $field );
			}
			$parsed_data[] = $item;
		}

		return apply_filters( 'woocommerce_csv_product_parsed_data', $parsed_data, $data );
	}

	/**
	 * Parse a comma-delineated field from a CSV.
	 *
	 * @param string $field
	 * @return array
	 */
	public function parse_comma_field( $field ) {
		if ( empty( $field ) ) {
			return array();
		}

		return array_map( 'esc_attr', array_map( 'trim', explode( ',', $field ) ) );
	}

	/**
	 * Parse a field that is generally '1' or '0' but can be something else.
	 *
	 * @param string $field
	 * @return bool|string
	 */
	public function parse_bool_field( $field ) {
		if ( '0' === $field ) {
			return false;
		}

		if ( '1' === $field ) {
			return true;
		}

		// Don't return explicit true or false for empty fields or values like 'notify'.
		return esc_attr( $field );
	}

	/**
	 * Parse a float value field.
	 *
	 * @param string $field
	 * @return float|string
	 */
	public function parse_float_field( $field ) {
		if ( '' === $field ) {
			return $field;
		}

		return floatval( $field );
	}

	/**
	 * Parse a category field from a CSV.
	 * Categories are separated by commas and subcategories are "parent > subcategory".
	 *
	 * @param string $field
	 * @return array of arrays with "parent" and "name" keys.
	 */
	public function parse_categories( $field ) {
		if ( empty( $field ) ) {
			return array();
		}

		$sections = array_map( 'trim', explode( ',', $field ) );
		$categories = array();

		foreach ( $sections as $section ) {

			// Top level category.
			if ( false === strpos( $section, '>' ) ) {
				$categories[] = array(
					'parent' => false,
					'name'   => esc_attr( $section ),
				);

			// Subcategory.
			} else {
				$chunks = array_map( 'trim', explode( '>', $section ) );
				$categories[] = array(
					'parent' => esc_attr( reset( $chunks ) ),
					'name'   => esc_attr( end( $chunks ) ),
				);
			}
		}

		return $categories;
	}

	/**
	 * Output header html.
	 */
	public function header() {
		echo '<div class="wrap">';
		echo '<h1>' . __( 'Import products', 'woocommerce' ) . '</h1>';
	}

	/**
	 * Output footer html.
	 */
	public function footer() {
		echo '</div>';
	}

	/**
	 * Output information about the uploading process.
	 */
	public function greet() {

		echo '<div class="narrow">';
		echo '<p>' . __( 'Hi there! Upload a CSV file containing products to import them into your shop. Choose a .csv file to upload, then click "Upload file and import".', 'woocommerce' ) . '</p>';

		$action = 'admin.php?import=woocommerce_product_csv&step=1';

		$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$size = size_format( $bytes );
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) :
			?><div class="error"><p><?php _e( 'Before you can upload your import file, you will need to fix the following error:', 'woocommerce' ); ?></p>
			<p><strong><?php echo $upload_dir['error']; ?></strong></p></div><?php
		else :
			?>
			<form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo esc_attr( wp_nonce_url( $action, 'import-upload' ) ); ?>">
				<table class="form-table">
					<tbody>
						<tr>
							<th>
								<label for="upload"><?php _e( 'Choose a file from your computer:', 'woocommerce' ); ?></label>
							</th>
							<td>
								<input type="file" id="upload" name="import" size="25" />
								<input type="hidden" name="action" value="save" />
								<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
								<small><?php
									/* translators: %s: maximum upload size */
									printf(
										__( 'Maximum size: %s', 'woocommerce' ),
										$size
									);
								?></small>
							</td>
						</tr>
						<tr>
							<th>
								<label for="file_url"><?php _e( 'OR enter path to file:', 'woocommerce' ); ?></label>
							</th>
							<td>
								<?php echo ' ' . ABSPATH . ' '; ?><input type="text" id="file_url" name="file_url" size="25" />
							</td>
						</tr>
						<tr>
							<th><label><?php _e( 'Delimiter', 'woocommerce' ); ?></label><br/></th>
							<td><input type="text" name="delimiter" placeholder="," size="2" /></td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" class="button" value="<?php esc_attr_e( 'Upload file and import', 'woocommerce' ); ?>" />
				</p>
			</form>
			<?php
		endif;

		echo '</div>';
	}

	/**
	 * Show import error and quit.
	 * @param  string $message
	 */
	private function import_error( $message = '' ) {
		echo '<p><strong>' . __( 'Sorry, there has been an error.', 'woocommerce' ) . '</strong><br />';
		if ( $message ) {
			echo esc_html( $message );
		}
		echo '</p>';
		$this->footer();
		die();
	}

}
