<?php
/**
 * Shipping Zones Admin Page
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Shipping_Zones Class.
 */
class WC_Admin_Shipping_Zones {

	/**
	 * Handles output of the reports page in admin.
	 */
	public static function output() {
		if ( isset( $_REQUEST['zone_id'] ) ) {
			self::zone_methods_screen( absint( $_REQUEST['zone_id'] ) );
		} elseif ( isset( $_REQUEST['instance_id'] ) ) {
			self::instance_settings_screen( absint( $_REQUEST['instance_id'] ) );
		} else {
			self::zones_screen();
		}
	}

	public static function zone_methods_screen( $zone_id ) {
		$wc_shipping      = WC_Shipping      ::instance();
		$zone             = WC_Shipping_Zones::get_zone( $zone_id );
		$shipping_methods = $wc_shipping ->get_shipping_methods();

		if ( ! $zone ) {
			wp_die( __( 'Zone does not exist!', 'woocommerce' ) );
		}

		wp_localize_script( 'wc-shipping-zone-methods', 'shippingZoneMethodsLocalizeScript', array(
            'methods'                 => $zone->get_shipping_methods(),
			'zone_id'                 => $zone->get_zone_id(),
			'wc_shipping_zones_nonce' => wp_create_nonce( 'wc_shipping_zones_nonce' ),
			'strings'                 => array(
				'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'woocommerce' ),
				'save_failed'             => __( 'Your changes were not saved. Please retry.', 'woocommerce' )
			),
		) );
		wp_enqueue_script( 'wc-shipping-zone-methods' );

		include_once( 'views/html-admin-page-shipping-zone-methods.php' );
	}

	public static function zones_screen() {
		$allowed_countries = WC()->countries->get_allowed_countries();
        $continents        = WC()->countries->get_continents();

		wp_localize_script( 'wc-shipping-zones', 'shippingZonesLocalizeScript', array(
            'zones'         => WC_Shipping_Zones::get_zones(),
            'default_zone'  => array(
				'zone_id'    => 0,
				'zone_name'  => '',
				'zone_order' => null,
			),
			'wc_shipping_zones_nonce'  => wp_create_nonce( 'wc_shipping_zones_nonce' ),
			'strings'       => array(
				'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'woocommerce' ),
				'save_failed'             => __( 'Your changes were not saved. Please retry.', 'woocommerce' )
			),
		) );
		wp_enqueue_script( 'wc-shipping-zones' );

		include_once( 'views/html-admin-page-shipping-zones.php' );
	}

	public static function instance_settings_screen( $instance_id ) {
		$zone            = WC_Shipping_Zones::get_zone_by( 'instance_id', $instance_id );
		$shipping_method = WC_Shipping_Zones::get_shipping_method( $instance_id );

		if ( ! $shipping_method ) {
			wp_die( __( 'Invalid shipping method!', 'woocommerce' ) );
		}
		if ( ! $zone ) {
			wp_die( __( 'Zone does not exist!', 'woocommerce' ) );
		}

		if ( ! empty( $_POST['save_method'] ) ) {

			if ( empty( $_POST['woocommerce_save_method_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_save_method_nonce'], 'woocommerce_save_method' )) {
				echo '<div class="updated error"><p>' . __( 'Edit failed. Please try again.', 'woocommerce' ) . '</p></div>';

			} elseif ( $shipping_method->process_admin_options() ) {
				echo '<div class="updated success"><p>' . __( 'Your settings have been saved.', 'woocommerce' ) . '</p></div>';
			}

			$shipping_method->display_errors();
		}

		?>
		<div class="wrap woocommerce">
			<h1><?php echo esc_html( $shipping_method->get_method_title() ); ?> <small class="wc-admin-breadcrumb">&lt; <a href=""<?php echo esc_url( admin_url( 'admin.php?page=wc-shipping&zone_id=' . absint( $zone->get_zone_id() ) ) ); ?>"><?php _e( 'Shipping Methods', 'woocommerce' ); ?> (<?php echo esc_html( $zone->get_zone_name() ); ?>)</a> &lt; <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-shipping' ) ); ?>"><?php _e( 'Shipping Zones', 'woocommerce' ); ?></a></small></h1>
			<form id="add-method" method="post">
				<?php $shipping_method->admin_options(); ?>
				<p class="submit"><input type="submit" class="button button-primary" name="save_method" value="<?php _e( 'Save changes', 'woocommerce' ); ?>" /></p>
				<?php wp_nonce_field( 'woocommerce_save_method', 'woocommerce_save_method_nonce' ); ?>
			</form>
		</div>
		<?php
	}
}
