<?php
/**
 * Tax Rates importer - import tax rates and local tax rates into WooCommerce.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Importers
 * @version     1.7.0
 */
 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( class_exists( 'WP_Importer' ) ) {
	class WC_CSV_Tax_Rates_Import extends WP_Importer {
		
		var $id;
		var $file_url;
		var $import_page;
		var $delimiter;
		var $posts = array();
		var $imported;
		var $skipped;
	
		/**
		 * __construct function.
		 * 
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->import_page = 'woocommerce_tax_rate_csv';
		}
	
		/**
		 * Registered callback function for the WordPress Importer
		 *
		 * Manages the three separate stages of the CSV import process
		 */
		function dispatch() {
			$this->header();
	
			if ( ! empty( $_POST['delimiter'] ) )
				$this->delimiter = stripslashes( trim( $_POST['delimiter'] ) );
	
			if ( ! $this->delimiter ) 
				$this->delimiter = ',';
	
			$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];
			switch ( $step ) {
				case 0:
					$this->greet();
					break;
				case 1:
					check_admin_referer( 'import-upload' );
					if ( $this->handle_upload() ) {

						if ( $this->id )
							$file = get_attached_file( $this->id );
						else
							$file = ABSPATH . $this->file_url;
		
						add_filter( 'http_request_timeout', array( &$this, 'bump_request_timeout' ) );
		
						if ( function_exists( 'gc_enable' ) )
							gc_enable();
		
						@set_time_limit(0);
						@ob_flush();
						@flush();
		
						$this->import( $file );
					}
					break;
			}
			$this->footer();
		}
	
		/**
		 * format_data_from_csv function.
		 * 
		 * @access public
		 * @param mixed $data
		 * @param mixed $enc
		 * @return void
		 */
		function format_data_from_csv( $data, $enc ) {
			return ( $enc == 'UTF-8' ) ? $data : utf8_encode( $data );
		}

		/**
		 * import function.
		 * 
		 * @access public
		 * @param mixed $file
		 * @return void
		 */
		function import( $file ) {
			global $woocommerce, $wpdb;
	
			$this->imported = $this->skipped = 0;
			
			if ( ! is_file($file) ) {
				echo '<p><strong>' . __( 'Sorry, there has been an error.', 'woocommerce' ) . '</strong><br />';
				echo __( 'The file does not exist, please try again.', 'woocommerce' ) . '</p>';
				$this->footer();
				die();
			}
	
			$new_rates = array();
	
			if ( ( $handle = fopen( $file, "r" ) ) !== FALSE ) {
			
				$header = fgetcsv( $handle, 0, $this->delimiter );
				
				if ( sizeof( $header ) == 6 ) {
					
					$rates = get_option( 'woocommerce_tax_rates' );
					
					while ( ( $row = fgetcsv( $handle, 0, $this->delimiter ) ) !== FALSE ) {
		            
						$rate = array();
						
						list( $rate['countries'], $rate['class'], $rate['label'], $rate['rate'], $rate['compound'], $rate['shipping'] ) = $row;
						
						$parsed_countries 	= array();
						$rate['countries'] 	= array_map( 'trim', explode( '|', $rate['countries'] ) );
						
						foreach( $rate['countries'] as $country ) {
							if ( strstr( $country, ':' ) ) {
								list( $country, $state ) = array_map( 'trim', explode( ':', $country ) );
								
								if ( ! isset( $parsed_countries[ $country ] ) )
									$parsed_countries[ $country ] = array();
								
								$parsed_countries[ $country ][] = $state;
							} else {
								
								if ( ! isset( $parsed_countries[ $country ] ) )
									$parsed_countries[ $country ] = array();
									
								$parsed_countries[ $country ][] = '*';
							}
						}
						
						$rate['countries']	= $parsed_countries;
						$rate['shipping'] 	= $rate['shipping'] ? 'yes' : 'no';
						$rate['compound'] 	= $rate['compound'] ? 'yes' : 'no';
						
						$new_rates[] = $rate;
						
						unset( $rate, $row );
						
						$this->imported++;
				    }
				    
				    $rates = array_merge( $rates, $new_rates );
			
					update_option( 'woocommerce_tax_rates', $rates );
					
				} elseif ( sizeof( $header ) == 8 ) {
					
					$rates = get_option( 'woocommerce_local_tax_rates' );
					
					while ( ( $row = fgetcsv( $handle, 0, $this->delimiter ) ) !== FALSE ) {
		            
						$rate = array();
						
						list( $rate['country'], $rate['state'], $rate['postcode'], $rate['class'], $rate['label'], $rate['rate'], $rate['compound'], $rate['shipping'] ) = $row;
						
						if ( ! $rate['country'] || ! $rate['postcode'] || ! $rate['rate'] ) {
							$this->skipped++;
							continue;
						}
						
						$rate['state']		= $rate['state'] ? $rate['state'] : '*';
						$rate['shipping'] 	= $rate['shipping'] ? 'yes' : 'no';
						$rate['compound'] 	= $rate['compound'] ? 'yes' : 'no';
						$rate['postcode'] 	= array_map( 'trim', explode( '|', $rate['postcode'] ) );
						
						$new_rates[] = $rate;
						
						unset( $rate, $row );
						
						$this->imported++;
				    }
				    
				    $rates = array_merge( $rates, $new_rates );
			
					update_option( 'woocommerce_local_tax_rates', $rates );
					
				} else {
					
					echo '<p><strong>' . __( 'Sorry, there has been an error.', 'woocommerce' ) . '</strong><br />';
					echo __( 'The CSV is invalid.', 'woocommerce' ) . '</p>';
					$this->footer();
					die();
				
				}
			    
			    fclose( $handle );
			}
			
			// Show Result
			echo '<div class="updated settings-error below-h2"><p>
				'.sprintf( __( 'Import complete - imported <strong>%s</strong> tax rates and skipped <strong>%s</strong>.', 'woocommerce' ), $this->imported, $this->skipped ).'
			</p></div>';
	
			$this->import_end();
		}
	
		/**
		 * Performs post-import cleanup of files and the cache
		 */
		function import_end() {
			echo '<p>' . __( 'All done!', 'woocommerce' ) . ' <a href="' . admin_url('admin.php?page=woocommerce_settings&tab=tax') . '">' . __( 'View Tax Rates', 'woocommerce' ) . '</a>' . '</p>';
	
			do_action( 'import_end' );
		}
	
		/**
		 * Handles the CSV upload and initial parsing of the file to prepare for
		 * displaying author import options
		 *
		 * @return bool False if error uploading or invalid file, true otherwise
		 */
		function handle_upload() {
	
			if ( empty( $_POST['file_url'] ) ) {
	
				$file = wp_import_handle_upload();
	
				if ( isset( $file['error'] ) ) {
					echo '<p><strong>' . __( 'Sorry, there has been an error.', 'woocommerce' ) . '</strong><br />';
					echo esc_html( $file['error'] ) . '</p>';
					return false;
				}
	
				$this->id = (int) $file['id'];
	
			} else {
	
				if ( file_exists( ABSPATH . $_POST['file_url'] ) ) {
	
					$this->file_url = esc_attr( $_POST['file_url'] );
	
				} else {
	
					echo '<p><strong>' . __( 'Sorry, there has been an error.', 'woocommerce' ) . '</strong></p>';
					return false;
	
				}
	
			}
	
			return true;
		}
			
		/**
		 * header function.
		 * 
		 * @access public
		 * @return void
		 */
		function header() {
			echo '<div class="wrap"><div class="icon32" id="icon-woocommerce-importer"><br></div>';
			echo '<h2>' . __( 'Import Tax Rates', 'woocommerce' ) . '</h2>';
		}

		/**
		 * footer function.
		 * 
		 * @access public
		 * @return void
		 */
		function footer() {
			echo '</div>';
		}
	
		/**
		 * greet function.
		 * 
		 * @access public
		 * @return void
		 */
		function greet() {
			global $woocommerce;
			
			echo '<div class="narrow">';
			echo '<p>' . __( 'Hi there! Upload a CSV file containing tax rates to import the contents into your shop. Choose a .csv file to upload, then click "Upload file and import".', 'woocommerce' ).'</p>';
			
			echo '<p>' . sprintf( __( 'Tax rates need to be defined with columns in a specific order (6 columns). <a href="%s">Click here to download a sample</a>.', 'woocommerce' ), $woocommerce->plugin_url() . '/admin/importers/samples/sample_tax_rates.csv' ) . '</p>';
			
			echo '<p>' . sprintf( __( 'Local tax rates also need to be defined with columns in a specific order (8 columns). <a href="%s">Click here to download a sample</a>.', 'woocommerce' ), $woocommerce->plugin_url() . '/admin/importers/samples/sample_local_tax_rates.csv' ) . '</p>';
	
			$action = 'admin.php?import=woocommerce_tax_rate_csv&step=1';
	
			$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
			$size = wp_convert_bytes_to_hr( $bytes );
			$upload_dir = wp_upload_dir();
			if ( ! empty( $upload_dir['error'] ) ) :
				?><div class="error"><p><?php _e('Before you can upload your import file, you will need to fix the following error:'); ?></p>
				<p><strong><?php echo $upload_dir['error']; ?></strong></p></div><?php
			else :
				?>
				<form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo esc_attr(wp_nonce_url($action, 'import-upload')); ?>">
					<table class="form-table">
						<tbody>
							<tr>
								<th>
									<label for="upload"><?php _e( 'Choose a file from your computer:' ); ?></label>
								</th>
								<td>
									<input type="file" id="upload" name="import" size="25" />
									<input type="hidden" name="action" value="save" />
									<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
									<small><?php printf( __('Maximum size: %s' ), $size ); ?></small>
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
						<input type="submit" class="button" value="<?php esc_attr_e( 'Upload file and import' ); ?>" />
					</p>
				</form>
				<?php
			endif;
	
			echo '</div>';
		}
	
		/**
		 * Added to http_request_timeout filter to force timeout at 60 seconds during import
		 * @return int 60
		 */
		function bump_request_timeout() {
			return 60;
		}
	}
}