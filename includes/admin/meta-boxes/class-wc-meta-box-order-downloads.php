<?php
/**
 * Order Downloads
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Meta Boxes
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Order_Downloads Class.
 */
class WC_Meta_Box_Order_Downloads {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		global $post, $wpdb;
		?>
		<div class="order_download_permissions wc-metaboxes-wrapper">

			<div class="wc-metaboxes"><?php
				$data_store = WC_Data_Store::load( 'customer-download' );
				$download_permissions = $data_store->get_downloads( array(
					'order_id' => $post->ID,
					'orderby'  => 'product_id',
				) );

				$product      = null;
				$loop         = 0;
				$file_counter = 1;

				if ( $download_permissions && sizeof( $download_permissions ) > 0 ) {
					foreach ( $download_permissions as $download ) {
						if ( ! $product || $product->get_id() !== $download->get_product_id() ) {
							$product      = wc_get_product( absint( $download->get_product_id() ) );
							$file_counter = 1;
						}

						// don't show permissions to files that have since been removed
						if ( ! $product || ! $product->exists() || ! $product->has_file( $download->get_download_id() ) ) {
							continue;
						}

						// Show file title instead of count if set
						$file       = $product->get_file( $download->get_download_id() );
						$file_count = isset( $file['name'] ) ? $file['name'] : sprintf( __( 'File %d', 'woocommerce' ), $file_counter );

						include( 'views/html-order-download-permission.php' );

						$loop++;
						$file_counter++;
					}
				}
			?></div>

			<div class="toolbar">
				<p class="buttons">
					<input type="hidden" id="grant_access_id" name="grant_access_id" data-multiple="true" class="wc-product-search" style="width: 400px;" data-placeholder="<?php esc_attr_e( 'Search for a downloadable product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_downloadable_products_and_variations" />
					<button type="button" class="button grant_access"><?php _e( 'Grant access', 'woocommerce' ); ?></button>
				</p>
				<div class="clear"></div>
			</div>

		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	public static function save( $post_id, $post ) {
		global $wpdb;

		if ( isset( $_POST['download_id'] ) ) {
			$download_ids        = $_POST['download_id'];
			$product_ids         = $_POST['product_id'];
			$downloads_remaining = $_POST['downloads_remaining'];
			$access_expires      = $_POST['access_expires'];
			$product_ids_max     = max( array_keys( $product_ids ) );

			for ( $i = 0; $i <= $product_ids_max; $i ++ ) {
				if ( ! isset( $product_ids[ $i ] ) ) {
					continue;
				}

				// @todo $download = new WC_Customer_Download( $data );

				$data = array(
					'downloads_remaining' => wc_clean( $downloads_remaining[ $i ] ),
				);

				$format = array(
					'%s',
				);

				$expiry  = ( array_key_exists( $i, $access_expires ) && '' != $access_expires[ $i ] ) ? date_i18n( 'Y-m-d', strtotime( $access_expires[ $i ] ) ) : null;

				$data['access_expires'] = $expiry;
				$format[]               = '%s';

				$wpdb->update( $wpdb->prefix . "woocommerce_downloadable_product_permissions",
					$data,
					array(
						'order_id' 		=> $post_id,
						'product_id' 	=> absint( $product_ids[ $i ] ),
						'download_id'	=> wc_clean( $download_ids[ $i ] ),
						),
					$format, array( '%d', '%d', '%s' )
				);
			}
		}
	}
}
