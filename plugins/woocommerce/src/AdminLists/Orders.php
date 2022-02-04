<?php

namespace Automattic\WooCommerce\AdminLists;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
require_once dirname( WC_PLUGIN_FILE ) . '/includes/admin/list-tables/trait-wc-admin-list-table-orders.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Orders extends \WP_List_Table {
	use \WC_Admin_Table_Orders_Trait;

	/**
	 * Object being shown on the row.
	 *
	 * @var object|null
	 */
	protected $object = null;

	/**
	 * The data store object to use.
	 *
	 * @var OrdersTableDataStore
	 */
	private $data_store;

	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Order', 'woocommerce' ),
				'plural'   => __( 'Orders', 'woocommerce' ),
				'ajax'     => false,
				'screen'   => null
			)
		);
		$this->items = wc_get_orders( array() )['orders'];
		add_filter( 'manage_woocommerce_page_shop-orders_columns', array( $this, 'define_columns' ) );
	}

	final public function init( OrdersTableDataStore $data_store ) {
		$this->data_store = $data_store;
	}

	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
		);
		return $this->define_columns( $columns );
	}

	protected function column_default( $item, $column_name ) {
		$this->object = $item;
		$this->render_columns( $column_name, $item->get_id() );
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @param \WC_Order $item The current WP_Post object.
	 */
	public function column_cb( $item ) {
		// Restores the more descriptive, specific name for use within this method.
		$show = current_user_can( 'edit_shop_orders', $item->get_id() );

		if ( $show ) :
			?>
			<label class="screen-reader-text" for="cb-select-<?php echo $item->get_id(); ?>">
				<?php
					printf( __( 'Select Order' )  );
				?>
			</label>
			<input id="cb-select-<?php echo $item->get_id(); ?>" type="checkbox" name="post[]" value="<?php echo $item->get_id(); ?>" />
			<div class="locked-indicator">
				<span class="locked-indicator-icon" aria-hidden="true"></span>
				<span class="screen-reader-text">
				<?php
					printf( __( '&#8220;&#8221; Order is locked' ) );
				?>
				</span>
			</div>
		<?php
		endif;
	}

}
