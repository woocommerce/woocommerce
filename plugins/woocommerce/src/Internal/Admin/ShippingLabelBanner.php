<?php
/**
 * WooCommerce Shipping Label banner.
 */

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\Jetpack\Connection\Manager as Jetpack_Connection_Manager;
use Automattic\WooCommerce\Utilities\OrderUtil;
use function WP_CLI\Utils\get_plugin_name;

/**
 * Shows print shipping label banner on edit order page.
 */
class ShippingLabelBanner {

	/**
	 * Singleton for the display rules class
	 *
	 * @var ShippingLabelBannerDisplayRules
	 */
	private $shipping_label_banner_display_rules;

	private const MIN_COMPATIBLE_WCST_VERSION       = '2.7.0';
	private const MIN_COMPATIBLE_WCSHIPPING_VERSION = '1.1.0';

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 6, 2 );
	}

	/**
	 * Check if WooCommerce Shipping makes sense for this merchant.
	 *
	 * @return bool
	 */
	private function should_show_meta_box() {
		if ( ! $this->shipping_label_banner_display_rules ) {
			$dotcom_connected = null;
			$wcs_version      = null;

			if ( class_exists( Jetpack_Connection_Manager::class ) ) {
				$dotcom_connected = ( new Jetpack_Connection_Manager() )->has_connected_owner();
			}

			if ( class_exists( '\Automattic\WCShipping\Utils' ) ) {
				$wcs_version = \Automattic\WCShipping\Utils::get_wcshipping_version();
			}

			$incompatible_plugins = class_exists( '\WC_Shipping_Fedex_Init' ) ||
				class_exists( '\WC_Shipping_UPS_Init' ) ||
				class_exists( '\WC_Integration_ShippingEasy' ) ||
				class_exists( '\WC_ShipStation_Integration' );

			$this->shipping_label_banner_display_rules =
				new ShippingLabelBannerDisplayRules(
					$dotcom_connected,
					$wcs_version,
					$incompatible_plugins
				);
		}

		return $this->shipping_label_banner_display_rules->should_display_banner();
	}

	/**
	 * Add metabox to order page.
	 */
	public function add_meta_boxes() {
		if ( ! OrderUtil::is_order_edit_screen() ) {
			return;
		}

		if ( $this->should_show_meta_box() ) {
			add_meta_box(
				'woocommerce-admin-print-label',
				__( 'Shipping Label', 'woocommerce' ),
				array( $this, 'meta_box' ),
				null,
				'normal',
				'high',
				array(
					'context' => 'shipping_label',
				)
			);
			add_action( 'admin_enqueue_scripts', array( $this, 'add_print_shipping_label_script' ) );
		}
	}

	/**
	 * Count shippable items
	 *
	 * @param \WC_Order $order Current order.
	 * @return int
	 */
	private function count_shippable_items( \WC_Order $order ) {
		$count = 0;
		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof \WC_Order_Item_Product ) {
				$product = $item->get_product();
				if ( $product && $product->needs_shipping() ) {
					$count += $item->get_quantity();
				}
			}
		}
		return $count;
	}
	/**
	 * Adds JS to order page to render shipping banner.
	 *
	 * @param string $hook current page hook.
	 */
	public function add_print_shipping_label_script( $hook ) {
		WCAdminAssets::register_style( 'print-shipping-label-banner', 'style', array( 'wp-components' ) );
		WCAdminAssets::register_script( 'wp-admin-scripts', 'print-shipping-label-banner', true );
		$wcst_version                 = null;
		$wcshipping_installed_version = null;
		$order                        = wc_get_order();
		if ( class_exists( '\WC_Connect_Loader' ) ) {
			$wcst_version = \WC_Connect_Loader::get_wcs_version();
		}

		$wc_shipping_plugin_file = WP_PLUGIN_DIR . '/woocommerce-shipping/woocommerce-shipping.php';
		if ( file_exists( $wc_shipping_plugin_file ) ) {
			$plugin_data                  = get_plugin_data( $wc_shipping_plugin_file );
			$wcshipping_installed_version = $plugin_data['Version'];
		}

		$payload = array(
			// If WCS&T is not installed, it's considered compatible.
			'is_wcst_compatible'                   => $wcst_version ? (int) version_compare( $wcst_version, self::MIN_COMPATIBLE_WCST_VERSION, '>=' ) : 1,
			'order_id'                             => $order ? $order->get_id() : null,
			// The banner is shown if the plugin is installed but not active, so we need to check if the installed version is compatible.
			'is_incompatible_wcshipping_installed' => $wcshipping_installed_version ?
			(int) version_compare( $wcshipping_installed_version, self::MIN_COMPATIBLE_WCSHIPPING_VERSION, '<' )
			: 0,
		);

		wp_localize_script( 'wc-admin-print-shipping-label-banner', 'wcShippingCoreData', $payload );
	}

	/**
	 * Render placeholder metabox.
	 *
	 * @param \WP_Post $post current post.
	 * @param array    $args empty args.
	 */
	public function meta_box( $post, $args ) {

		?>
		<div id="wc-admin-shipping-banner-root" class="woocommerce <?php echo esc_attr( 'wc-admin-shipping-banner' ); ?>" data-args="<?php echo esc_attr( wp_json_encode( $args['args'] ) ); ?>">
		</div>
		<?php
	}
}
