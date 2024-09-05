<?php
/**
 * Renders order edit page, works with both post and order object.
 */

namespace Automattic\WooCommerce\Internal\Admin\Orders;

use Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes\CustomerHistory;
use Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes\CustomMetaBox;
use Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes\OrderAttribution;
use Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes\TaxonomiesMetaBox;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order;

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
	 * Instance of the TaxonomiesMetaBox class. Used to render meta box for taxonomies.
	 *
	 * @var TaxonomiesMetaBox
	 */
	private $taxonomies_meta_box;

	/**
	 * Instance of WC_Order to be used in metaboxes.
	 *
	 * @var \WC_Order
	 */
	private $order;

	/**
	 * Action name that the form is currently handling. Could be new_order or edit_order.
	 *
	 * @var string
	 */
	private $current_action;

	/**
	 * Message to be displayed to the user. Index of message from the messages array registered when declaring shop_order post type.
	 *
	 * @var int
	 */
	private $message;

	/**
	 * Controller for orders page. Used to determine redirection URLs.
	 *
	 * @var PageController
	 */
	private $orders_page_controller;

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
		self::maybe_register_order_attribution( $screen_id, $title );
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
	 * Returns the PageController for this edit form. This method is protected to allow child classes to overwrite the PageController object and return custom links.
	 *
	 * @since 8.0.0
	 *
	 * @return PageController PageController object.
	 */
	protected function get_page_controller() {
		if ( ! isset( $this->orders_page_controller ) ) {
			$this->orders_page_controller = wc_get_container()->get( PageController::class );
		}
		return $this->orders_page_controller;
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

		if ( ! isset( $this->taxonomies_meta_box ) ) {
			$this->taxonomies_meta_box = wc_get_container()->get( TaxonomiesMetaBox::class );
		}

		$this->add_save_meta_boxes();
		$this->handle_order_update();
		$this->add_order_meta_boxes( $this->screen_id, __( 'Order', 'woocommerce' ) );
		$this->add_order_specific_meta_box();
		$this->add_order_taxonomies_meta_box();

		/**
		 * From wp-admin/includes/meta-boxes.php.
		 *
		 * Fires after all built-in meta boxes have been added. Custom metaboxes may be enqueued here.
		 *
		 * @since 3.8.0.
		 */
		do_action( 'add_meta_boxes', $this->screen_id, $this->order );

		/**
		 * Provides an opportunity to inject custom meta boxes into the order editor screen. This
		 * hook is an analog of `add_meta_boxes_<POST_TYPE>` as provided by WordPress core.
		 *
		 * @since 7.4.0
		 *
		 * @param WC_Order $order The order being edited.
		 */
		do_action( 'add_meta_boxes_' . $this->screen_id, $this->order );

		$this->enqueue_scripts();
	}

	/**
	 * Set the current action for the form.
	 *
	 * @param string $action Action name.
	 */
	public function set_current_action( string $action ) {
		$this->current_action = $action;
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
	 * Render custom meta box.
	 *
	 * @return void
	 */
	private function add_order_taxonomies_meta_box() {
		$this->taxonomies_meta_box->add_taxonomies_meta_boxes( $this->screen_id, $this->order->get_type() );
	}

	/**
	 * Register order attribution meta boxes if the feature is enabled.
	 *
	 * @since 8.5.0
	 *
	 * @param string $screen_id Screen ID.
	 * @param string $title     Title of the page.
	 *
	 * @return void
	 */
	private static function maybe_register_order_attribution( string $screen_id, string $title ) {
		/**
		 * Features controller.
		 *
		 * @var FeaturesController $feature_controller
		 */
		$feature_controller = wc_get_container()->get( FeaturesController::class );
		if ( ! $feature_controller->feature_is_enabled( 'order_attribution' ) ) {
			return;
		}

		/**
		 * Order attribution meta box.
		 *
		 * @var OrderAttribution $order_attribution_meta_box
		 */
		$order_attribution_meta_box = wc_get_container()->get( OrderAttribution::class );

		add_meta_box(
			'woocommerce-order-source-data',
			/* Translators: %s order type name. */
			sprintf( __( '%s attribution', 'woocommerce' ), $title ),
			function( $post_or_order ) use ( $order_attribution_meta_box ) {
				$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order );
				if ( $order instanceof WC_Order ) {
					$order_attribution_meta_box->output( $order );
				}
			},
			$screen_id,
			'side',
			'high'
		);

		// Add customer history meta box if analytics is enabled.
		if ( 'yes' !== get_option( 'woocommerce_analytics_enabled' ) ) {
			return;
		}

		if ( ! OrderUtil::is_order_edit_screen() ) {
			return;
		}

		/**
		 * Customer history meta box.
		 *
		 * @var CustomerHistory $customer_history_meta_box
		 */
		$customer_history_meta_box = wc_get_container()->get( CustomerHistory::class );

		add_meta_box(
			'woocommerce-customer-history',
			__( 'Customer history', 'woocommerce' ),
			function ( $post_or_order ) use ( $customer_history_meta_box ) {
				$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order );
				if ( $order instanceof WC_Order ) {
					$customer_history_meta_box->output( $order );
				}
			},
			$screen_id,
			'side',
			'high'
		);
	}

	/**
	 * Takes care of updating order data. Fires action that metaboxes can hook to for order data updating.
	 *
	 * @return void
	 */
	public function handle_order_update() {
		if ( ! isset( $this->order ) ) {
			return;
		}

		if ( 'edit_order' !== sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) ) ) {
			return;
		}

		check_admin_referer( $this->get_order_edit_nonce_action() );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized later on by taxonomies_meta_box object.
		$taxonomy_input = isset( $_POST['tax_input'] ) ? wp_unslash( $_POST['tax_input'] ) : null;
		$this->taxonomies_meta_box->save_taxonomies( $this->order, $taxonomy_input );

		/**
		 * Save meta for shop order.
		 *
		 * @param int Order ID.
		 * @param \WC_Order Post object.
		 *
		 * @since 2.1.0
		 */
		do_action( 'woocommerce_process_shop_order_meta', $this->order->get_id(), $this->order );

		$this->custom_meta_box->handle_metadata_changes($this->order);

		// Order updated message.
		$this->message = 1;

		// Claim lock.
		$edit_lock = wc_get_container()->get( EditLock::class );
		$edit_lock->lock( $this->order );

		$this->redirect_order( $this->order );
	}

	/**
	 * Helper method to redirect to order edit page.
	 *
	 * @since 8.0.0
	 *
	 * @param \WC_Order $order Order object.
	 */
	private function redirect_order( \WC_Order $order ) {
		$redirect_to = $this->get_page_controller()->get_edit_url( $order->get_id() );
		if ( isset( $this->message ) ) {
			$redirect_to = add_query_arg( 'message', $this->message, $redirect_to );
		}
		wp_safe_redirect(
			/**
			 * Filter the URL used to redirect after an order is updated. Similar to the WP post's `redirect_post_location` filter.
			 *
			 * @param string    $redirect_to The redirect destination URL.
			 * @param int       $order_id The order ID.
			 * @param \WC_Order $order The order object.
			 *
			 * @since 8.0.0
			 */
			apply_filters(
				'woocommerce_redirect_order_location',
				$redirect_to,
				$order->get_id(),
				$order
			)
		);
		exit;
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
		/**
		 * This is used by the order edit page to show messages in the notice fields.
		 * It should be similar to post_updated_messages filter, i.e.:
		 * array(
		 *   {order_type} => array(
		 *      1 => 'Order updated.',
		 *      2 => 'Custom field updated.',
		 * ...
		 * ).
		 *
		 * The index to be displayed is computed from the $_GET['message'] variable.
		 *
		 * @since 7.4.0.
		 */
		$messages = apply_filters( 'woocommerce_order_updated_messages', array() );

		$message = $this->message;
		if ( isset( $_GET['message'] ) ) {
			$message = absint( $_GET['message'] );
		}

		if ( isset( $message ) ) {
			$message = $messages[ $this->order->get_type() ][ $message ] ?? false;
		}

		$this->render_wrapper_start( '', $message );
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
		$post_type = get_post_type_object( $this->order->get_type() );

		$edit_page_url = $this->get_page_controller()->get_edit_url( $this->order->get_id() );
		$form_action   = 'edit_order';
		$referer       = wp_get_referer();
		$new_page_url  = $this->get_page_controller()->get_new_page_url( $this->order->get_type() );

		?>
		<div class="wrap">
		<h1 class="wp-heading-inline">
			<?php
			echo 'new_order' === $this->current_action ? esc_html( $post_type->labels->add_new_item ) : esc_html( $post_type->labels->edit_item );
			?>
		</h1>
		<?php
		if ( 'edit_order' === $this->current_action ) {
			echo ' <a href="' . esc_url( $new_page_url ) . '" class="page-title-action">' . esc_html( $post_type->labels->add_new ) . '</a>';
		}
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
		<?php
		/**
		 * Fires at the top of the order edit form. Can be used as a replacement for edit_form_top hook for HPOS.
		 *
		 * @param \WC_Order $order Order object.
		 *
		 * @since 8.0.0
		 */
		do_action( 'order_edit_form_top', $this->order );

		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
		?>
		<input type="hidden" id="hiddenaction" name="action" value="<?php echo esc_attr( $form_action ); ?>"/>

		<?php
		$order_status = $this->order->get_status( 'edit' );
		?>
		<input type="hidden" id="original_order_status" name="original_order_status" value="<?php echo esc_attr( $order_status ); ?>"/>
		<input type="hidden" id="original_post_status" name="original_post_status" value="<?php echo esc_attr( wc_is_order_status( 'wc-' . $order_status ) ? 'wc-' . $order_status : $order_status ); ?>"/>
		<input type="hidden" id="referredby" name="referredby" value="<?php echo $referer ? esc_url( $referer ) : ''; ?>"/>
		<input type="hidden" id="post_ID" name="post_ID" value="<?php echo esc_attr( $this->order->get_id() ); ?>"/>
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
