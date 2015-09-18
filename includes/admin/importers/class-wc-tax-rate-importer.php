<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Importer' ) ) {
	return;
}

/**
 * Tax Rates importer - import tax rates and local tax rates into WooCommerce.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Importers
 * @version     2.3.0
 */
class WC_Tax_Rate_Importer extends WP_Importer {

	public $id;
	public $file_url;
	public $import_page;
	public $delimiter;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->import_page = 'woocommerce_tax_rate_csv';
		$this->delimiter   = empty( $_POST['delimiter'] ) ? ',' : wc_clean( $_POST['delimiter'] );
	}

	/**
	 * Registered callback function for the WordPress Importer
	 *
	 * Manages the three separate stages of the CSV import process
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

					add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );

					$this->import( $file );
				}
				break;
		}

		$this->footer();
	}

	/**
	 * Import is starting
	 */
	private function import_start() {
		if ( function_exists( 'gc_enable' ) ) {
			gc_enable();
		}
		if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
			@set_time_limit( 0 );
		}
		@ob_flush();
		@flush();
		@ini_set( 'auto_detect_line_endings', '1' );
	}

	/**
	 * format_data_from_csv function.
	 *
	 * @param mixed $data
	 * @param string $enc
	 * @return string
	 */
	public function format_data_from_csv( $data, $enc ) {
		return ( $enc == 'UTF-8' ) ? $data : utf8_encode( $data );
	}

	/**
	 * import function.
	 *
	 * @param mixed $file
	 */
	public function import( $file ) {
		if ( ! is_file( $file ) ) {
			$this->import_error( __( 'The file does not exist, please try again.', 'woocommerce' ) );
		}

		$this->import_start();

		$loop = 0;

		if ( ( $handle = fopen( $file, "r" ) ) !== false ) {

			$header = fgetcsv( $handle, 0, $this->delimiter );

			if ( 10 === sizeof( $header ) ) {

				while ( ( $row = fgetcsv( $handle, 0, $this->delimiter ) ) !== false ) {

					list( $country, $state, $postcode, $city, $rate, $name, $priority, $compound, $shipping, $class ) = $row;

					$tax_rate = array(
						'tax_rate_country'  => $country,
						'tax_rate_state'    => $state,
						'tax_rate'          => $rate,
						'tax_rate_name'     => $name,
						'tax_rate_priority' => $priority,
						'tax_rate_compound' => $compound ? 1 : 0,
						'tax_rate_shipping' => $shipping ? 1 : 0,
						'tax_rate_order'    => $loop ++,
						'tax_rate_class'    => $class
					);

					$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );
					WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, wc_clean( $postcode ) );
					WC_Tax::_update_tax_rate_cities( $tax_rate_id, wc_clean( $city ) );
				}

			} else {
				$this->import_error( __( 'The CSV is invalid.', 'woocommerce' ) );
			}

			fclose( $handle );
		}

		// Show Result
		echo '<div class="updated settings-error below-h2"><p>
			' . sprintf( __( 'Import complete - imported <strong>%s</strong> tax rates.', 'woocommerce' ), $loop ) . '
		</p></div>';

		$this->import_end();
	}

	/**
	 * Performs post-import cleanup of files and the cache
	 */
	public function import_end() {
		echo '<p>' . __( 'All done!', 'woocommerce' ) . ' <a href="' . admin_url('admin.php?page=wc-settings&tab=tax') . '">' . __( 'View Tax Rates', 'woocommerce' ) . '</a>' . '</p>';

		do_action( 'import_end' );
	}

	/**
	 * Handles the CSV upload and initial parsing of the file to prepare for
	 * displaying author import options
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
	 * header function.
	 */
	public function header() {
		echo '<div class="wrap"><div class="icon32 icon32-woocommerce-importer" id="icon-woocommerce"><br></div>';
		echo '<h2>' . __( 'Import Tax Rates', 'woocommerce' ) . '</h2>';
	}

	/**
	 * footer function.
	 */
	public function footer() {
		echo '</div>';
	}

	/**
	 * greet function.
	 */
	public function greet() {

		echo '<div class="narrow">';
		echo '<p>' . __( 'Hi there! Upload a CSV file containing tax rates to import the contents into your shop. Choose a .csv file to upload, then click "Upload file and import".', 'woocommerce' ).'</p>';

		echo '<p>' . sprintf( __( 'Tax rates need to be defined with columns in a specific order (10 columns). <a href="%s">Click here to download a sample</a>.', 'woocommerce' ), WC()->plugin_url() . '/dummy-data/sample_tax_rates.csv' ) . '</p>';

		$action = 'admin.php?import=woocommerce_tax_rate_csv&step=1';

		$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$size = size_format( $bytes );
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) :
			?><div class="error"><p><?php _e( 'Before you can upload your import file, you will need to fix the following error:', 'woocommerce' ); ?></p>
			<p><strong><?php echo $upload_dir['error']; ?></strong></p></div><?php
		else :
			?>
			<form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo esc_attr(wp_nonce_url($action, 'import-upload')); ?>">
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
								<small><?php printf( __('Maximum size: %s', 'woocommerce' ), $size ); ?></small>
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
	 * Show import error and quit
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

	/**
	 * Added to http_request_timeout filter to force timeout at 60 seconds during import
	 *
	 * @param  int $val
	 * @return int 60
	 */
	public function bump_request_timeout( $val ) {
		return 60;
	}
}
