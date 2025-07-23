<?php
/**
 * WooCommerce order fulfillments renderer script.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;
use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;
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
	 * Registers the hooks related to fulfillments.
	 */
	public function register() {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// Hook into column definitions and add the new fulfillment columns.
			add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_fulfillment_columns' ) );
			// Hook into the column rendering and render the new fulfillment columns.
			add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_fulfillment_column_row_data' ), 10, 2 );
		} else {
			// For legacy orders table, hook into column definitions and add the new fulfillment columns.
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_fulfillment_columns' ) );
			// Hook into the column rendering and render the new fulfillment columns.
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_fulfillment_column_row_data_legacy' ), 25, 1 );
		}
		// Hook into the admin footer to add the fulfillment drawer slot, which the React component will mount on.
		add_action( 'admin_footer', array( $this, 'render_fulfillment_drawer_slot' ) );
		// Hook into the admin enqueue scripts to load the fulfillment drawer component.
		add_action( 'admin_enqueue_scripts', array( $this, 'load_components' ) );
		// Hook into the order details page to render the fulfillment badges.
		add_action( 'woocommerce_admin_order_data_header_right', array( $this, 'render_order_details_badges' ) );
		// Hook into the order details before order table to render the fulfillment customer details.
		add_action( 'woocommerce_order_details_before_order_table', array( $this, 'render_fulfillment_customer_details' ) );
		// Initialize the renderer for bulk actions.
		add_action( 'admin_init', array( $this, 'init_admin_hooks' ) );
		// Hook into the order status text to append the fulfillment status.
		add_filter( 'woocommerce_order_details_status', array( $this, 'render_fulfillment_status_text' ), 10, 2 );
		add_filter( 'woocommerce_order_tracking_status', array( $this, 'render_fulfillment_status_text' ), 10, 2 );
	}

	/**
	 * Initialize the hooks that should run after `admin_init` hook.
	 */
	public function init_admin_hooks() {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// For custom orders table, we need to add the bulk actions to the custom orders table.
			add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'define_fulfillment_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'handle_fulfillment_bulk_actions' ), 10, 3 );
			// For custom orders table, we need to filter the query to include fulfillment status.
			add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'render_fulfillment_filters' ) );
			add_filter( 'woocommerce_order_query_args', array( $this, 'filter_orders_list_table_query' ), 10, 1 );
		} else {
			// For legacy orders table, we need to add the bulk actions to the legacy orders table.
			add_filter( 'bulk_actions-edit-shop_order', array( $this, 'define_fulfillment_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_fulfillment_bulk_actions' ), 10, 3 );
			// For legacy orders table, we need to filter the query to include fulfillment status.
			add_action( 'restrict_manage_posts', array( $this, 'render_fulfillment_filters_legacy' ) );
			add_action( 'pre_get_posts', array( $this, 'filter_legacy_orders_list_query' ) );
		}
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
	 * Render the fulfillment column row data for legacy order list support.
	 *
	 * @param string $column_name The name of the column.
	 */
	public function render_fulfillment_column_row_data_legacy( string $column_name ) {
		global $the_order;
		// This method is kept for legacy support, but the main rendering logic is now in render_fulfillment_column_row_data.
		return $this->render_fulfillment_column_row_data( $column_name, $the_order );
	}

	/**
	 * Render the fulfillment status column.
	 *
	 * @param string   $column_name The name of the column.
	 * @param WC_Order $order The order object.
	 */
	public function render_fulfillment_column_row_data( string $column_name, WC_Order $order ) {
		$fulfillments = $this->maybe_read_fulfillments( $order );

		// Render the column data based on the column name.
		switch ( $column_name ) {
			case 'fulfillment_status':
				$this->render_order_fulfillment_status_column_row_data( $order );
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
	 * @param WC_Order $order The order object.
	 */
	private function render_order_fulfillment_status_column_row_data( WC_Order $order ) {
		$order_fulfillment_status = $order->meta_exists( '_fulfillment_status' ) ? $order->get_meta( '_fulfillment_status', true ) : 'no_fulfillments';

		echo "<div class='fulfillment-status-wrapper'>";
		$this->render_order_fulfillment_status_badge( $order, $order_fulfillment_status );
		echo '</div>';
	}

	/**
	 * Render the fulfillment status badge.
	 *
	 * @param WC_Order $order The order object.
	 * @param string   $order_fulfillment_status The fulfillment status of the order.
	 */
	private function render_order_fulfillment_status_badge( $order, string $order_fulfillment_status ) {
		$status_props = FulfillmentUtils::get_order_fulfillment_statuses()[ $order_fulfillment_status ];
		if ( ! $status_props ) {
			$status_props = array(
				'label'            => __( 'Unknown', 'woocommerce' ),
				'background_color' => '#f0f0f0',
				'text_color'       => '#000',
			);
		}

		echo '<mark class="fulfillment-status" style="background-color:' . esc_attr( $status_props['background_color'] ) . '; color: ' . esc_attr( $status_props['text_color'] ) . '"><span>' . esc_html( $status_props['label'] ) . '</span></mark>';
		echo "<a href='#' class='fulfillments-trigger' data-order-id='" . esc_attr( $order->get_id() ) . "' title='" . esc_attr__( 'View Fulfillments', 'woocommerce' ) . "'>
			<svg width='16' height='16' viewBox='0 0 12 14' xmlns='http://www.w3.org/2000/svg'>
				<path d='M11.8333 2.83301L9.33329 0.333008L2.24996 7.41634L1.41663 10.7497L4.74996 9.91634L11.8333 2.83301ZM5.99996 12.4163H0.166626V13.6663H5.99996V12.4163Z' />
			</svg>
		</a>";
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
	 * Render the fulfillment drawer.
	 */
	public function render_fulfillment_drawer_slot() {
		if ( ! $this->should_render_fulfillment_drawer() ) {
			return;
		}
		?>
		<div id="wc_order_fulfillments_panel_container"></div>
		<?php
	}

	/**
	 * Define bulk actions for fulfillments.
	 *
	 * @param array $actions Existing actions.
	 * @return array
	 */
	public function define_fulfillment_bulk_actions( $actions ) {
		$actions['fulfill'] = __( 'Mark as fulfilled', 'woocommerce' );

		return $actions;
	}

	/**
	 * Handle bulk actions for fulfillments.
	 *
	 * @param string $redirect_to The redirect URL.
	 * @param string $action The action being performed.
	 * @param array  $post_ids The post IDs being acted upon.
	 * @return string
	 */
	public function handle_fulfillment_bulk_actions( $redirect_to, $action, $post_ids ) {
		if ( 'fulfill' === $action ) {
			foreach ( $post_ids as $post_id ) {
				$order = wc_get_order( $post_id );
				if ( ! $order ) {
					continue;
				}

				$fulfillments = $this->maybe_read_fulfillments( $order );

				// Fulfill all existing fulfillments.
				foreach ( $fulfillments as $fulfillment ) {
					$fulfillment->set_status( 'fulfilled' );
					$fulfillment->save();
				}

				// Create a fulfillment for the order, containing all remaining items in the order.
				$remaining_items = array_map(
					function ( $item ) {
						return array(
							'item_id' => $item['item_id'],
							'qty'     => $item['qty'],
						);
					},
					FulfillmentUtils::get_pending_items( $order, $fulfillments )
				);

				if ( 0 < count( $remaining_items ) ) {
					$fulfillment = new Fulfillment();
					$fulfillment->set_entity_type( WC_Order::class );
					$fulfillment->set_entity_id( (string) $order->get_id() );
					$fulfillment->set_status( 'fulfilled' );
					$fulfillment->set_items( $remaining_items );
					$fulfillment->save();
				}
			}
			$redirect_to = add_query_arg( array( 'bulk_action' => $action ), $redirect_to );
		}
		return $redirect_to;
	}

	/**
	 * Render the fulfillment status text in the order details page and the order tracking page.
	 *
	 * @param string   $order_status The order status text.
	 * @param WC_Order $order The order object.
	 *
	 * @return string The fulfillment status appended order status text.
	 */
	public function render_fulfillment_status_text( string $order_status, WC_Order $order ): string {
		$fulfillments       = $this->maybe_read_fulfillments( $order );
		$fulfillment_status = FulfillmentUtils::get_order_fulfillment_status_text( $order, $fulfillments );
		return sprintf( '%s %s', $order_status, $fulfillment_status );
	}

	/**
	 * Render the fulfillment customer details in the order details page.
	 *
	 * @param WC_Order $order The order object.
	 */
	public function render_fulfillment_customer_details( WC_Order $order ) {
		$fulfillments = $this->maybe_read_fulfillments( $order );

		if ( ! empty( $fulfillments ) ) {
			?>
<section class="woocommerce-order-details">
	<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
		<thead>
			<?php
			foreach ( $fulfillments as $index => $fulfillment ) {
				if ( ! $fulfillment->get_is_fulfilled() ) {
					continue;
				}
				?>
			<tr>
				<th class="woocommerce-table__shipment-info shipment-info" style="font-weight: normal;">
				<?php
				printf(
					/* translators: %1$s is the shipment index, %2$s is the shipment date */
					wp_kses( __( '<b>Shipment %1$s</b> was shipped on <b>%2$s</b>', 'woocommerce' ), 'b' ),
					intval( $index ) + 1,
					esc_html(
						gmdate(
							'F j, Y',
							strtotime(
								$fulfillment->get_date_fulfilled() // Get the fulfilled date.
								?? $fulfillment->get_date_updated() // Fallback to the updated date if fulfilled date is not set.
							)
						)
					)
				);
				?>
				</th>
				<th class="woocommerce-table__shipment-tracking shipment-tracking" style="font-weight: normal;">
					<?php echo wp_kses( FulfillmentUtils::get_tracking_info_html( $fulfillment ), 'a' ); ?>
				</th>
			</tr>
				<?php
			}
			?>
		</thead>
	</table>
</section>
			<?php
		}
	}

	/**
	 * Render the fulfillment badges in the order details page.
	 *
	 * @param WC_Order $order The order object.
	 */
	public function render_order_details_badges( WC_Order $order ) {
		echo '<div class="wc-order-fulfillment-badges">';

		// Get the fulfillment status for the order.
		$fulfillments             = $this->maybe_read_fulfillments( $order );
		$order_fulfillment_status = FulfillmentUtils::calculate_order_fulfillment_status( $order, $fulfillments );

		// Render order status badge.
		$order_status = $order->get_status();
		echo '<mark class="order-status status-' . esc_attr( $order_status ) . '"><span>' . esc_html( wc_get_order_status_name( $order_status ) ) . '</span></mark>';

		// Render fulfillment status badge.
		$this->render_order_fulfillment_status_badge( $order, $order_fulfillment_status );
		echo '</div>';
	}

	/**
	 * Loads the fulfillments scripts and styles.
	 */
	public function load_components() {
		if ( ! $this->should_render_fulfillment_drawer() ) {
			return;
		}

		$this->register_fulfillments_assets();
		$this->load_fulfillments_js_settings();
	}

	/**
	 * Register the fulfillment assets.
	 */
	protected function register_fulfillments_assets() {
		WCAdminAssets::register_style( 'fulfillments', 'style', array( 'wp-components' ) );
		WCAdminAssets::register_script( 'wp-admin-scripts', 'fulfillments', true );
	}

	/**
	 * Load the fulfillments JS settings.
	 *
	 * @return void
	 */
	protected function load_fulfillments_js_settings() {
		$fulfillment_settings = array(
			'providers'                  => FulfillmentUtils::get_shipping_providers_object(),
			'currency_symbols'           => get_woocommerce_currency_symbols(),
			'fulfillment_statuses'       => FulfillmentUtils::get_fulfillment_statuses(),
			'order_fulfillment_statuses' => FulfillmentUtils::get_order_fulfillment_statuses(),
		);

		wp_localize_script( 'wc-admin-fulfillments', 'wcFulfillmentSettings', $fulfillment_settings );
	}

	/**
	 * Render the fulfillment filters in the orders table.
	 */
	public function render_fulfillment_filters() {
		if ( ! self::should_render_fulfillment_drawer() ) {
			return;
		}
		?>
		<?php
		// This is a read-only filter on the admin orders table, so nonce verification is not required.
		// phpcs:ignore WordPress.Security.NonceVerification ?>
			<?php $selected_status = isset( $_GET['fulfillment_status'] ) ? sanitize_text_field( wp_unslash( $_GET['fulfillment_status'] ) ) : ''; ?>
		<select id="fulfillment-status-filter" name="fulfillment_status">
			<option value="" <?php selected( $selected_status, '' ); ?>><?php esc_html_e( 'Filter by fulfillment', 'woocommerce' ); ?></option>
				<?php foreach ( FulfillmentUtils::get_order_fulfillment_statuses() as $status => $props ) : ?>
				<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $selected_status, $status ); ?>>
					<?php echo esc_html( $props['label'] ?? '' ); ?>
				</option>
			<?php endforeach; ?>
		</select>
			<?php
	}

	/**
	 * Render the fulfillment filters in the legacy orders table.
	 */
	public function render_fulfillment_filters_legacy() {
		global $typenow;

		if ( 'shop_order' !== $typenow ) {
			return;
		}

		$this->render_fulfillment_filters();
	}

	/**
	 * Apply the fulfillment status filter to the orders list.
	 *
	 * @param array $args The query arguments for the orders list.
	 * @return array The modified query arguments.
	 */
	public function filter_orders_list_table_query( $args ) {
		// This is a read-only filter on the admin orders table, so nonce verification is not required.
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['fulfillment_status'] ) && ! empty( $_GET['fulfillment_status'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$fulfillment_status = sanitize_text_field( wp_unslash( $_GET['fulfillment_status'] ) );

			// Ensure the fulfillment status is one of the allowed values.
			if ( FulfillmentUtils::is_valid_order_fulfillment_status( $fulfillment_status ) ) {
				if ( ! isset( $args['meta_query'] ) ) {
					$args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				}
				if ( 'no_fulfillments' === $fulfillment_status ) {
					// If the status is 'no_fulfillments', we need to check for orders that have no fulfillments.
					$args['meta_query'][] = array(
						'relation' => 'OR',
						array(
							'key'     => '_fulfillment_status',
							'compare' => 'NOT EXISTS',
						),
					);
				} else {
					$args['meta_query'][] = array(
						'key'     => '_fulfillment_status',
						'value'   => $fulfillment_status,
						'compare' => '=',
					);
				}
			}
		}

		return $args;
	}

	/**
	 * Filter the legacy orders list query to include fulfillment status.
	 *
	 * @param \WP_Query $query The WP_Query object.
	 */
	public function filter_legacy_orders_list_query( $query ) {
		if (
		is_admin()
		&& $query->is_main_query()
		&& $query->get( 'post_type' ) === 'shop_order'
		&& isset( $_GET['fulfillment_status'] ) && ! empty( $_GET['fulfillment_status'] ) // phpcs:ignore WordPress.Security.NonceVerification
		) {
			$status = sanitize_text_field( wp_unslash( $_GET['fulfillment_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			// Ensure the fulfillment status is one of the allowed values.
			if ( FulfillmentUtils::is_valid_order_fulfillment_status( $status ) ) {
				$query->set(
					'meta_query',
					'no_fulfillments' === $status ?
					array(
						'relation' => 'OR',
						array(
							'key'     => '_fulfillment_status',
							'compare' => 'NOT EXISTS',
						),
					) :
					array(
						array(
							'key'     => '_fulfillment_status',
							'value'   => $status,
							'compare' => '=',
						),
					)
				);
			}
		}
	}

	/**
	 * Check if the fulfillment drawer should be rendered (admin only).
	 *
	 * @return bool True if the fulfillment drawer should be rendered, false otherwise.
	 */
	protected function should_render_fulfillment_drawer(): bool {
		if ( ! is_admin() ) {
			return false;
		}

		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$current_screen = get_current_screen();
		if ( ! $current_screen || ! $current_screen->id ) {
			return false;
		}

		return 'woocommerce_page_wc-orders' === $current_screen->id // HPOS screen.
		|| 'edit-shop_order' === $current_screen->id // Legacy screen.
		|| 'shop_order' === $current_screen->id; // Order details screen (legacy).
	}

	/**
	 * Fetches the fulfillments for the given order, caching them to avoid multiple fetches.
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return array The fulfillments for the order.
	 */
	private function maybe_read_fulfillments( WC_Order $order ): array {
		// Check if we've already fetched the fulfillments for this order.
		if ( isset( $this->fulfillments_cache[ $order->get_id() ] ) ) {
			return $this->fulfillments_cache[ $order->get_id() ];
		}

		// If not, fetch them and cache them.
		$data_store                                   = wc_get_container()->get( FulfillmentsDataStore::class );
		$fulfillments                                 = $data_store->read_fulfillments( WC_Order::class, '' . $order->get_id() );
		$this->fulfillments_cache[ $order->get_id() ] = $fulfillments;

		return $fulfillments;
	}
}
