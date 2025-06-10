<?php
/**
 * WooCommerce order fulfillments renderer script.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;
use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;
use WC_Order;

/**
 * FulfillmentsRenderer class.
 */
class FulfillmentsRenderer {

	/**
	 * Fulfillments cache, that holds the fulfillments for each order to eliminate
	 * fetching fulfillment records of an order on each column render.
	 *
	 * @var array
	 */
	private array $fulfillments_cache = array();

	/**
	 * CLass constructor.
	 */
	public function __construct() {
		// Hook into column definitions and add the new fulfillment columns.
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_fulfillment_columns' ) );
		// Hook into the column rendering and render the new fulfillment columns.
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_fulfillment_column_row_data' ), 10, 2 );
		// Hook into the admin footer to add the fulfillment drawer slot, which the React component will mount on.
		add_action( 'admin_footer', array( $this, 'render_fulfillment_drawer_slot' ) );
		// Hook into the admin enqueue scripts to load the fulfillment drawer component.
		add_action( 'admin_enqueue_scripts', array( $this, 'load_components' ) );
	}

	/**
	 * Add the fulfillment related columns to the orders table, after the order_status column.
	 *
	 * @param array $columns The columns in the orders page.
	 * @return array The modified columns.
	 */
	public function add_fulfillment_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $column_name => $column_info ) {
			$new_columns[ $column_name ] = $column_info;
			if ( 'order_status' === $column_name ) {
				$new_columns[ $column_name ]       = 'Order Status';
				$new_columns['fulfillment_status'] = __( 'Fulfillment Status', 'woocommerce' );
				$new_columns['shipment_tracking']  = __( 'Shipment Tracking', 'woocommerce' );
				$new_columns['shipment_provider']  = __( 'Shipment Provider', 'woocommerce' );
			}
		}
		return $new_columns;
	}


	/**
	 * Render the fulfillment status column.
	 *
	 * @param string   $column_name The name of the column.
	 * @param WC_Order $order The order object.
	 */
	public function render_fulfillment_column_row_data( string $column_name, WC_Order $order ) {
		// Check if we've already fetched the fulfillments for this order.
		$fulfillments = $this->fulfillments_cache[ $order->get_id() ] ?? null;

		// If not, fetch them and cache them.
		if ( null === $fulfillments ) {
			$data_store                                   = wc_get_container()->get( FulfillmentsDataStore::class );
			$fulfillments                                 = $data_store->read_fulfillments( WC_Order::class, '' . $order->get_id() );
			$this->fulfillments_cache[ $order->get_id() ] = $fulfillments;
		}

		// Render the column data based on the column name.
		switch ( $column_name ) {
			case 'fulfillment_status':
				$this->render_fulfillment_status_column_row_data( $order, $fulfillments );
				break;
			case 'shipment_tracking':
				$this->render_shipment_tracking_column_row_data( $order, $fulfillments );
				break;
			case 'shipment_provider':
				$this->render_shipment_provider_column_row_data( $order, $fulfillments );
				break;
		}
	}

	/**
	 * Render the fulfillment status column row data.
	 *
	 * @param WC_Order      $order The order object.
	 * @param Fulfillment[] $fulfillments The fulfillments.
	 */
	private function render_fulfillment_status_column_row_data( WC_Order $order, array $fulfillments ) {
		$order_fulfillment_status = $this->get_fulfillment_status( $fulfillments );
		echo "<div class='fulfillment-status-wrapper'>";
		switch ( $order_fulfillment_status ) {
			case 'no_fulfillments':
			case 'unfulfilled':
				echo '<mark class="fulfillment-status status-failed"><span>' . esc_html__( 'Unfulfilled', 'woocommerce' ) . '</span></mark>';
				break;
			case 'partially_fulfilled':
				echo '<mark class="fulfillment-status status-completed"><span>' . esc_html__( 'Partially fulfilled', 'woocommerce' ) . '</span></mark>';
				break;
			case 'fulfilled':
				echo '<mark class="fulfillment-status status-processing"><span>' . esc_html__( 'Fulfilled', 'woocommerce' ) . '</span></mark>';
				break;
			default:
				echo '<mark class="fulfillment-status status-completed"><span>' . esc_html__( 'Unknown', 'woocommerce' ) . '</span></mark>';
		}
		echo "<a href='#' class='fulfillments-trigger' data-order-id='" . esc_attr( $order->get_id() ) . "' title='" . esc_attr__( 'View Fulfillments', 'woocommerce' ) . "'>
			<svg width='16' height='16' viewBox='0 0 12 14' fill='none' xmlns='http://www.w3.org/2000/svg'>
				<path d='M11.8333 2.83301L9.33329 0.333008L2.24996 7.41634L1.41663 10.7497L4.74996 9.91634L11.8333 2.83301ZM5.99996 12.4163H0.166626V13.6663H5.99996V12.4163Z' fill='#3858E9'/>
			</svg>
		</a>";
		echo '</div>';
	}

	/**
	 * Render the shipment provider column row data.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $fulfillments The fulfillments.
	 */
	private function render_shipment_provider_column_row_data( WC_Order $order, array $fulfillments ) {
		$providers = array();
		foreach ( $fulfillments as $fulfillment ) {
			$providers[] = $fulfillment->get_meta( '_shipment_provider' ) ?? null;
		}

		$providers = array_filter(
			$providers,
			function ( $provider ) {
				return ! empty( $provider );
			}
		);

		if ( count( $providers ) > 1 ) {
			echo '<span>' . esc_html__( 'Multiple providers', 'woocommerce' ) . '</span>';
		} elseif ( 1 === count( $providers ) ) {
			echo '<span>' . esc_html( array_shift( $providers ) ) . '</span>';
		} else {
			echo '<span>--</span>';
		}
	}

	/**
	 * Render the shipment tracking column row data.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $fulfillments The fulfillments.
	 */
	private function render_shipment_tracking_column_row_data( WC_Order $order, array $fulfillments ) {
		$tracking = array();
		foreach ( $fulfillments as $fulfillment ) {
			$tracking[] = $fulfillment->get_meta( '_tracking_number' ) ?? null;
		}

		$tracking = array_filter(
			$tracking,
			function ( $provider ) {
				return ! empty( $provider );
			}
		);

		if ( count( $tracking ) > 1 ) {
			echo '<span>' . esc_html__( 'Multiple trackings', 'woocommerce' ) . '</span>';
		} elseif ( 1 === count( $tracking ) ) {
			echo '<span>' . esc_html( array_shift( $tracking ) ) . '</span>';
		} else {
			echo '<span>--</span>';
		}
	}

	/**
	 * Get the fulfillment status of the entity. This runs like a computed property, where
	 * it checks the fulfillment status of each fulfillment attached to the order,
	 * and computes the overall fulfillment status of the order.
	 *
	 * @param array $fulfillments The fulfillments.
	 *
	 * @return string The fulfillment status.
	 */
	private function get_fulfillment_status( array $fulfillments ): string {
		$has_fulfillments = ! empty( $fulfillments );
		$all_fulfilled    = true;
		$some_fulfilled   = false;

		if ( $has_fulfillments ) {
			foreach ( $fulfillments as $fulfillment ) {
				if ( ! $fulfillment->get_is_fulfilled() ) {
					$all_fulfilled = false;
				} else {
					$some_fulfilled = true;
				}
			}

			if ( $all_fulfilled ) {
				return 'fulfilled';
			} elseif ( $some_fulfilled ) {
				return 'partially_fulfilled';
			} else {
				return 'unfulfilled';
			}
		} else {
			return 'no_fulfillments';
		}
	}

	/**
	 * Render the fulfillment drawer.
	 */
	public function render_fulfillment_drawer_slot() {
		if ( ! self::should_render_fulfillment_drawer() ) {
			return;
		}
		?>
		<div id="wc_order_fulfillments_panel_container"></div>
		<?php
	}

	/**
	 * Loads the payment method promotions scripts and styles.
	 */
	public static function load_components() {
		if ( ! self::should_render_fulfillment_drawer() ) {
			return;
		}
		WCAdminAssets::register_style( 'fulfillments', 'style', array( 'wp-components' ) );
		WCAdminAssets::register_script( 'wp-admin-scripts', 'fulfillments', true );
	}

	/**
	 * Check if the fulfillment drawer should be rendered.
	 *
	 * @return bool True if the fulfillment drawer should be rendered, false otherwise.
	 */
	private static function should_render_fulfillment_drawer(): bool {
		$current_screen = get_current_screen();
		if ( ! $current_screen || ! $current_screen->id ) {
			return false;
		}
		return 'woocommerce_page_wc-orders' === $current_screen->id;
	}
}
