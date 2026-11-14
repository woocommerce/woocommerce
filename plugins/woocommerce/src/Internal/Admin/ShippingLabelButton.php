<?php
/**
 * WooCommerce "Create shipping label" button for the order edit screen.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use WP_Post;

/**
 * Adds a "Shipping Label" metabox to the top of the order edit screen with a "Create shipping label"
 * button that opens a modal recommending shipping label extensions. Extensions can remove it with the
 * `woocommerce_admin_order_should_render_shipping_label_button` filter and add their own metabox instead.
 */
class ShippingLabelButton {

	/**
	 * Classes that identify active extensions which already provide shipping labels.
	 */
	private const LABEL_PROVIDER_CLASSES = array(
		'\Automattic\WCShipping\Utils',
		'\WC_Connect_Loader',
		'\WC_ShipStation_Integration',
		'\WC_Stamps_Integration',
		'\WC_Integration_ShippingEasy',
	);

	/**
	 * Plugin slugs that identify active extensions which already provide shipping labels.
	 */
	private const LABEL_PROVIDER_PLUGIN_SLUGS = array(
		'packlink-pro-shipping',
	);

	/**
	 * ID of the metabox registered by ShippingLabelBanner, which has its own label button.
	 */
	private const SHIPPING_LABEL_BANNER_METABOX_ID = 'woocommerce-admin-print-label';

	/**
	 * ID of the metabox this class registers.
	 */
	private const METABOX_ID = 'woocommerce-create-shipping-label';

	/**
	 * The order the button is rendered for, set only when the button should render.
	 *
	 * @var WC_Order|null
	 */
	private $order = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		// Runs after ShippingLabelBanner registers its metabox (priority 6) so the banner can be detected.
		add_action( 'add_meta_boxes', array( $this, 'handle_add_meta_boxes' ), 99, 2 );
	}

	/**
	 * Decide whether to render the button once the edited order is known, and hook up rendering and assets.
	 *
	 * @internal
	 *
	 * @param string           $screen_id_or_post_type Screen ID (HPOS) or post type (posts storage).
	 * @param WP_Post|WC_Order $post_or_order          The post or order being edited.
	 */
	public function handle_add_meta_boxes( $screen_id_or_post_type, $post_or_order = null ): void {
		$screen = get_current_screen();
		if ( ! $screen || ! OrderUtil::is_order_edit_screen() ) {
			return;
		}

		$order = $post_or_order instanceof WP_Post ? wc_get_order( $post_or_order->ID ) : $post_or_order;
		if ( ! $order instanceof WC_Order || ! $this->should_render_for_order( $order ) ) {
			return;
		}

		$this->order = $order;
		add_meta_box(
			self::METABOX_ID,
			__( 'Shipping Label', 'woocommerce' ),
			array( $this, 'render_meta_box' ),
			$screen,
			'normal',
			'high'
		);
		$this->move_meta_box_to_top( $screen->id );
		add_filter( "get_user_option_meta-box-order_{$screen->id}", array( $this, 'handle_get_user_option_meta_box_order' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'handle_admin_enqueue_scripts' ) );
	}

	/**
	 * Put the metabox above the other boxes in the main column, for users without a saved box order.
	 *
	 * @param string $screen_id The order edit screen ID.
	 */
	private function move_meta_box_to_top( string $screen_id ): void {
		global $wp_meta_boxes;

		$boxes = $wp_meta_boxes[ $screen_id ]['normal']['high'] ?? null;
		if ( ! is_array( $boxes ) || ! isset( $boxes[ self::METABOX_ID ] ) ) {
			return;
		}

		$wp_meta_boxes[ $screen_id ]['normal']['high'] = array( self::METABOX_ID => $boxes[ self::METABOX_ID ] ) + $boxes; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reordering boxes only.
	}

	/**
	 * Put the metabox first in a saved box order that doesn't place it yet. Nothing is written to the database,
	 * and once the user moves the box, the saved position wins.
	 *
	 * @internal
	 *
	 * @param mixed $order Saved box order, keyed by context with comma-separated box IDs.
	 * @return mixed
	 */
	public function handle_get_user_option_meta_box_order( $order ) {
		if ( ! is_array( $order ) || ! isset( $order['normal'] ) || ! is_string( $order['normal'] ) ) {
			return $order;
		}

		foreach ( $order as $box_ids ) {
			if ( is_string( $box_ids ) && in_array( self::METABOX_ID, explode( ',', $box_ids ), true ) ) {
				return $order;
			}
		}

		$order['normal'] = '' === $order['normal'] ? self::METABOX_ID : self::METABOX_ID . ',' . $order['normal'];

		return $order;
	}

	/**
	 * Whether the button should render for the given order.
	 *
	 * @param WC_Order $order The order being edited.
	 * @return bool
	 */
	public function should_render_for_order( WC_Order $order ): bool {
		if ( 'shop_order' !== $order->get_type()
			|| ! current_user_can( 'install_plugins' )
			|| ! $this->marketplace_suggestions_allowed()
			|| 0 === $this->count_shippable_items( $order )
			|| $this->shipping_label_banner_is_registered()
		) {
			return false;
		}

		/**
		 * Filters whether the "Create shipping label" button is rendered on the order edit screen.
		 *
		 * The button sits in a "Shipping Label" metabox at the top of the main column. Return false to remove
		 * the metabox, for example to add your own label metabox in its place. Only fires for orders with at
		 * least one product that needs shipping, when the current user can install plugins and marketplace
		 * suggestions are enabled. Defaults to false when a known shipping label extension is active.
		 *
		 * @since 11.4.0
		 *
		 * @param bool     $render Whether to render the button.
		 * @param WC_Order $order  The order being edited.
		 */
		return (bool) apply_filters( 'woocommerce_admin_order_should_render_shipping_label_button', ! $this->label_provider_is_active(), $order );
	}

	/**
	 * Enqueue the script and style that open the modal.
	 *
	 * @internal
	 */
	public function handle_admin_enqueue_scripts(): void {
		WCAdminAssets::register_script( 'wp-admin-scripts', 'create-shipping-label', true );

		// A unique handle: WCAdminAssets::register_style() would use the shared `wc-admin-style` handle,
		// which the shipping label banner and Fulfillments also use on this screen.
		$style_handle = 'wc-admin-create-shipping-label';
		wp_enqueue_style(
			$style_handle,
			WCAdminAssets::get_url( 'create-shipping-label/style', 'css' ),
			array( 'wp-components', 'wc-components' ),
			WCAdminAssets::get_file_version( 'css' )
		);
		wp_style_add_data( $style_handle, 'rtl', 'replace' );
	}

	/**
	 * Render the metabox content.
	 *
	 * @internal
	 */
	public function render_meta_box(): void {
		if ( ! $this->order ) {
			return;
		}

		$items_count = $this->count_shippable_items( $this->order );
		?>
		<div class="wc-create-shipping-label">
			<svg class="wc-create-shipping-label__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
				<path d="M3 6.75C3 5.784 3.784 5 4.75 5H15V7.313l.05.027 5.056 2.73.394.212v3.468a1.75 1.75 0 01-1.75 1.75h-.012a2.5 2.5 0 11-4.975 0H9.737a2.5 2.5 0 11-4.975 0H3V6.75zM13.5 14V6.5H4.75a.25.25 0 00-.25.25V14h.965a2.493 2.493 0 011.785-.75c.7 0 1.332.287 1.785.75H13.5zm4.535 0h.715a.25.25 0 00.25-.25v-2.573l-4-2.16v4.568a2.487 2.487 0 011.25-.335c.7 0 1.332.287 1.785.75zM6.282 15.5a1.002 1.002 0 00.968 1.25 1 1 0 10-.968-1.25zm9 0a1 1 0 101.937.498 1 1 0 00-1.938-.498z" />
			</svg>
			<p class="wc-create-shipping-label__text">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: number of order items that need shipping. */
						_n( '%s item is ready to be fulfilled', '%s items are ready to be fulfilled', $items_count, 'woocommerce' ),
						number_format_i18n( $items_count )
					)
				);
				?>
			</p>
			<button
				type="button"
				class="button button-primary wc-create-shipping-label__button"
				aria-haspopup="dialog"
				data-store-country="<?php echo esc_attr( WC()->countries->get_base_country() ); ?>"
			>
				<?php esc_html_e( 'Create shipping label', 'woocommerce' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Whether the store allows marketplace suggestions, which this button is.
	 *
	 * @return bool
	 */
	private function marketplace_suggestions_allowed(): bool {
		if ( 'no' === get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) ) {
			return false;
		}

		/**
		 * Filter allow marketplace suggestions.
		 *
		 * User can disable all suggestions via filter.
		 *
		 * @since 3.6.0
		 */
		return (bool) apply_filters( 'woocommerce_allow_marketplace_suggestions', true );
	}

	/**
	 * Count the quantity of order items whose product needs shipping.
	 *
	 * @param WC_Order $order The order to check.
	 * @return int
	 */
	private function count_shippable_items( WC_Order $order ): int {
		$count = 0;
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();
			if ( $product instanceof WC_Product && $product->needs_shipping() ) {
				$count += (int) $item->get_quantity();
			}
		}

		return $count;
	}

	/**
	 * Whether ShippingLabelBanner registered its metabox, which already offers a label button.
	 *
	 * @return bool
	 */
	private function shipping_label_banner_is_registered(): bool {
		global $wp_meta_boxes;

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || empty( $wp_meta_boxes[ $screen->id ] ) || ! is_array( $wp_meta_boxes[ $screen->id ] ) ) {
			return false;
		}

		foreach ( $wp_meta_boxes[ $screen->id ] as $priorities ) {
			foreach ( (array) $priorities as $boxes ) {
				if ( ! empty( $boxes[ self::SHIPPING_LABEL_BANNER_METABOX_ID ] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Whether a known shipping label extension is active.
	 *
	 * @return bool
	 */
	private function label_provider_is_active(): bool {
		foreach ( self::LABEL_PROVIDER_CLASSES as $class_name ) {
			if ( class_exists( $class_name ) ) {
				return true;
			}
		}

		return (bool) array_intersect( self::LABEL_PROVIDER_PLUGIN_SLUGS, PluginsHelper::get_active_plugin_slugs() );
	}
}
