<?php
/**
 * Renders order edit page, works with both post and order object.
 */

namespace Automattic\WooCommerce\Internal\Admin\Orders;

use Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes\CustomMetaBox;

/**
 * Class Edit.
 */
class Edit {

	/**
	 * Screen ID for the edit order screen.
	 *
	 * @var string
	 */
	private $screen_id;

	/**
	 * Instance of the CustomMetaBox class. Used to render meta box for custom meta.
	 *
	 * @var CustomMetaBox
	 */
	private $custom_meta_box;

	/**
	 * Instance of WC_Order to be used in metaboxes.
	 *
	 * @var \WC_Order
	 */
	private $order;

	/**
	 * Hooks all meta-boxes for order edit page. This is static since this may be called by post edit form rendering.
	 *
	 * @param string $screen_id Screen ID.
	 * @param string $title Title of the page.
	 */
	public static function add_order_meta_boxes( string $screen_id, string $title ) {
		/* Translators: %s order type name. */
		add_meta_box( 'woocommerce-order-data', sprintf( __( '%s data', 'woocommerce' ), $title ), 'WC_Meta_Box_Order_Data::output', $screen_id, 'normal', 'high' );
		add_meta_box( 'woocommerce-order-items', __( 'Items', 'woocommerce' ), 'WC_Meta_Box_Order_Items::output', $screen_id, 'normal', 'high' );
		/* Translators: %s order type name. */
		add_meta_box( 'woocommerce-order-notes', sprintf( __( '%s notes', 'woocommerce' ), $title ), 'WC_Meta_Box_Order_Notes::output', $screen_id, 'side', 'default' );
		add_meta_box( 'woocommerce-order-downloads', __( 'Downloadable product permissions', 'woocommerce' ) . wc_help_tip( __( 'Note: Permissions for order items will automatically be granted when the order status changes to processing/completed.', 'woocommerce' ) ), 'WC_Meta_Box_Order_Downloads::output', $screen_id, 'normal', 'default' );
		/* Translators: %s order type name. */
		add_meta_box( 'woocommerce-order-actions', sprintf( __( '%s actions', 'woocommerce' ), $title ), 'WC_Meta_Box_Order_Actions::output', $screen_id, 'side', 'high' );
	}

	/**
	 * Hooks metabox save functions for order edit page.
	 *
	 * @return void
	 */
	public static function add_save_meta_boxes() {
		/**
		 * Save Order Meta Boxes.
		 *
		 * In order:
		 *      Save the order items.
		 *      Save the order totals.
		 *      Save the order downloads.
		 *      Save order data - also updates status and sends out admin emails if needed. Last to show latest data.
		 *      Save actions - sends out other emails. Last to show latest data.
		 */
		add_action( 'woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Items::save', 10 );
		add_action( 'woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Downloads::save', 30, 2 );
		add_action( 'woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40 );
		add_action( 'woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Actions::save', 50, 2 );
	}

	/**
	 * Enqueue necessary scripts for order edit page.
	 */
	private function enqueue_scripts() {
		if ( wp_is_mobile() ) {
			wp_enqueue_script( 'jquery-touch-punch' );
		}
		wp_enqueue_script( 'post' ); // Ensure existing JS libraries are still available for backward compat.
	}

	/**
	 * Setup hooks, actions and variables needed to render order edit page.
	 *
	 * @param \WC_Order $order Order object.
	 */
	public function setup( \WC_Order $order ) {
		$this->order    = $order;
		$current_screen = get_current_screen();
		$current_screen->is_block_editor( false );
		$this->screen_id = $current_screen->id;
		if ( ! isset( $this->custom_meta_box ) ) {
			$this->custom_meta_box = wc_get_container()->get( CustomMetaBox::class );
		}
		$this->add_save_meta_boxes();
		$this->handle_order_update();
		$this->add_order_meta_boxes( $this->screen_id, __( 'Order', 'woocommerce' ) );
		$this->add_order_specific_meta_box();

		/**
		 * From wp-admin/includes/meta-boxes.php.
		 *
		 * Fires after all built-in meta boxes have been added. Custom metaboxes may be enqueued here.
		 *
		 * @since 3.8.0.
		 */
		do_action( 'add_meta_boxes', wc_get_page_screen_id( 'shop-order' ), $this->order );
		$this->enqueue_scripts();
	}

	/**
	 * Hooks meta box for order specific meta.
	 */
	private function add_order_specific_meta_box() {
		add_meta_box(
			'order_custom',
			__( 'Custom Fields', 'woocommerce' ),
			array( $this, 'render_custom_meta_box' ),
			$this->screen_id,
			'normal'
		);
	}

	/**
	 * Takes care of updating order data. Fires action that metaboxes can hook to for order data updating.
	 *
	 * @return void
	 */
	public function handle_order_update() {
		global $theorder;
		if ( ! isset( $this->order ) ) {
			return;
		}

		if ( 'edit_order' !== sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) ) ) {
			return;
		}

		check_admin_referer( $this->get_order_edit_nonce_action() );

		/**
		 * Save meta for shop order.
		 *
		 * @param int Order ID.
		 * @param \WC_Order Post object.
		 *
		 * @since 2.1.0
		 */
		do_action( 'woocommerce_process_shop_order_meta', $this->order->get_id(), $this->order );

		// Refresh the order from DB.
		$this->order = wc_get_order( $this->order->get_id() );
		$theorder    = $this->order;
	}

	/**
	 * Helper method to get the name of order edit nonce.
	 *
	 * @return string Nonce action name.
	 */
	private function get_order_edit_nonce_action() {
		return 'update-order_' . $this->order->get_id();
	}

	/**
	 * Render meta box for order specific meta.
	 */
	public function render_custom_meta_box() {
		$this->custom_meta_box->output( $this->order );
	}

	/**
	 * Render order edit page.
	 */
	public function display() {
		$this->render_wrapper_start();
		$this->render_meta_boxes();
		$this->render_wrapper_end();
	}

	/**
	 * Helper function to render wrapper start.
	 *
	 * @param string $notice Notice to display, if any.
	 * @param string $message Message to display, if any.
	 */
	private function render_wrapper_start( $notice = '', $message = '' ) {
		$edit_page_url = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $this->order->get_id() );
		$form_action   = 'edit_order';
		$referer       = wp_get_referer();
		?>
		<div class="wrap">
		<h1 class="wp-heading-inline">
			<?php echo esc_html( 'Edit order' ); ?>
		</h1>
		<?php
		echo ' <a href="' . esc_url( admin_url( 'admin.php?page=wc-orders&action=new' ) ) . '" class="page-title-action"> Add order </a>';
		?>
		<hr class="wp-header-end">

		<?php
		if ( $notice ) :
			?>
			<div id="notice" class="notice notice-warning"><p
					id="has-newer-autosave"><?php echo wp_kses_post( $notice ); ?></p></div>
		<?php endif; ?>
		<?php if ( $message ) : ?>
			<div id="message" class="updated notice notice-success is-dismissible">
				<p><?php echo wp_kses_post( $message ); ?></p></div>
			<?php
			endif;
		?>

		<form name="order" action="<?php echo esc_url( $edit_page_url ); ?>" method="post" id="order"
		<?php
		/**
		 * Fires inside the order edit form tag.
		 *
		 * @param \WC_Order $order Order object.
		 *
		 * @since 6.9.0
		 */
		do_action( 'order_edit_form_tag', $this->order );
		?>
		>
		<?php wp_nonce_field( $this->get_order_edit_nonce_action() ); ?>
		<input type="hidden" id="hiddenaction" name="action" value="<?php echo esc_attr( $form_action ); ?>"/>
		<input type="hidden" id="original_order_status" name="original_order_status" value="<?php echo esc_attr( $this->order->get_status() ); ?>"/>
		<input type="hidden" id="referredby" name="referredby" value="<?php echo $referer ? esc_url( $referer ) : ''; ?>"/>
		<div id="poststuff">
		<div id="post-body"
		class="metabox-holder columns-<?php echo ( 1 === get_current_screen()->get_columns() ) ? '1' : '2'; ?>">
		<?php
	}

	/**
	 * Helper function to render meta boxes.
	 */
	private function render_meta_boxes() {
		?>
		<div id="postbox-container-1" class="postbox-container">
			<?php do_meta_boxes( $this->screen_id, 'side', $this->order ); ?>
		</div>
		<div id="postbox-container-2" class="postbox-container">
			<?php
			do_meta_boxes( $this->screen_id, 'normal', $this->order );
			do_meta_boxes( $this->screen_id, 'advanced', $this->order );
			?>
		</div>
		<?php
	}

	/**
	 * Helper function to render wrapper end.
	 */
	private function render_wrapper_end() {
		?>
		</div> <!-- /post-body -->
		</div> <!-- /poststuff  -->
		</form>
		</div> <!-- /wrap -->
		<?php
	}
}
