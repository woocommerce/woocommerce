<?php
/**
 * WooCommerce Order Detail Redesign feature loader.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Features\OrderDetailRedesign;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order;

/**
 * Loads support for the redesigned WooCommerce order detail page.
 *
 * Manually instantiated from `Features::load_features()` when the
 * `order-detail-redesign` feature flag is enabled (see
 * `client/admin/config/core.json`). Lives in the `Internal` namespace
 * because the feature class is not part of the public API surface.
 *
 * Currently a non-mergeable visual prototype: registers a new submit
 * metabox (status + Update + Trash) at the top of the order side
 * column, enqueues prototype CSS/JS, and prints the side-panel HTML.
 *
 * @since 10.9.0
 */
class Init {

	const FEATURE_ID = 'order-detail-redesign';

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'admin_body_class', array( $this, 'handle_admin_body_class' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_prototype_assets' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_submit_metabox' ), 99, 1 );
		add_action( 'admin_footer', array( $this, 'render_side_panel' ) );
	}

	/**
	 * Adds the feature body class to every admin page while the feature is enabled.
	 *
	 * @internal
	 *
	 * @param string $classes Existing space-separated body classes.
	 * @return string
	 */
	public function handle_admin_body_class( string $classes ): string {
		return $classes . ' woocommerce-feature-enabled-' . self::FEATURE_ID;
	}

	/**
	 * Enqueue the prototype CSS + JS on the order edit screen.
	 *
	 * @internal
	 */
	public function enqueue_prototype_assets(): void {
		if ( ! OrderUtil::is_order_edit_screen() ) {
			return;
		}

		$assets_url = plugins_url(
			'src/Internal/Features/OrderDetailRedesign/assets/',
			WC_PLUGIN_FILE
		);
		$version    = defined( 'WC_VERSION' ) ? WC_VERSION : '1.0.0';

		wp_enqueue_style(
			'wc-order-detail-redesign-prototype',
			$assets_url . 'prototype.css',
			array(),
			$version
		);

		wp_enqueue_script(
			'wc-order-detail-redesign-prototype',
			$assets_url . 'prototype.js',
			array( 'jquery' ),
			$version,
			true
		);
	}

	/**
	 * Register the new Submit metabox at the top of the side column.
	 *
	 * Registered at 'high' priority and then re-ordered to the top of
	 * `$wp_meta_boxes[ side ][ high ]` so it renders ABOVE the existing
	 * `woocommerce-order-actions` metabox (also registered at 'high').
	 *
	 * @internal
	 *
	 * @param string $screen_id Current screen ID.
	 */
	public function register_submit_metabox( $screen_id ): void {
		if ( ! OrderUtil::is_order_edit_screen() ) {
			return;
		}

		add_meta_box(
			'woocommerce-order-submit',
			__( 'Order status', 'woocommerce' ),
			array( $this, 'render_submit_metabox' ),
			$screen_id,
			'side',
			'high'
		);

		// Move to the top of the side/high priority bucket so it renders first.
		global $wp_meta_boxes;
		if ( isset( $wp_meta_boxes[ $screen_id ]['side']['high']['woocommerce-order-submit'] ) ) {
			$submit = $wp_meta_boxes[ $screen_id ]['side']['high']['woocommerce-order-submit'];
			unset( $wp_meta_boxes[ $screen_id ]['side']['high']['woocommerce-order-submit'] );
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reordering metabox display in the standard $wp_meta_boxes registry; non-mergeable prototype.
			$wp_meta_boxes[ $screen_id ]['side']['high'] = array_merge(
				array( 'woocommerce-order-submit' => $submit ),
				$wp_meta_boxes[ $screen_id ]['side']['high']
			);
		}
	}

	/**
	 * Render the submit metabox: status dropdown + Update + Move to Trash.
	 *
	 * @internal
	 *
	 * @param \WC_Order|\WP_Post $post_or_order Order or post object.
	 */
	public function render_submit_metabox( $post_or_order ): void {
		$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order );
		if ( ! $order ) {
			return;
		}

		$current_status = $order->get_status();
		$statuses       = wc_get_order_statuses();
		$delete_url     = get_delete_post_link( $order->get_id() );
		?>
		<div class="wc-order-submit">
			<p class="wc-order-submit__field">
				<label for="wc-order-submit-status">
					<?php esc_html_e( 'Status', 'woocommerce' ); ?>
				</label>
				<select
					id="wc-order-submit-status"
					name="order_status"
					class="wc-enhanced-select"
				>
					<?php foreach ( $statuses as $status => $status_name ) : ?>
						<option
							value="<?php echo esc_attr( $status ); ?>"
							<?php selected( 'wc-' . $current_status, $status ); ?>
						>
							<?php echo esc_html( $status_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
		</div>
		<div class="wc-order-submit__footer">
			<a
				class="wc-order-submit__trash submitdelete deletion"
				href="<?php echo esc_url( $delete_url ); ?>"
			>
				<?php esc_html_e( 'Move to Trash', 'woocommerce' ); ?>
			</a>
			<button
				type="submit"
				class="button button-primary button-large wc-order-submit__update"
				name="save"
				value="<?php esc_attr_e( 'Update', 'woocommerce' ); ?>"
			>
				<?php esc_html_e( 'Update', 'woocommerce' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Print the side panel HTML in the admin footer.
	 *
	 * Rendered into document.body via a portal-like pattern: the panel
	 * lives at the end of the DOM so it can sit above all metaboxes
	 * with its overlay covering the page.
	 *
	 * @internal
	 */
	public function render_side_panel(): void {
		if ( ! OrderUtil::is_order_edit_screen() ) {
			return;
		}

		$order = $this->get_current_order();
		if ( ! $order ) {
			return;
		}

		$billing        = $order->get_address( 'billing' );
		$shipping       = $order->get_address( 'shipping' );
		$note           = $order->get_customer_note();
		$customer       = $order->get_customer_id();
		$customer_user  = $customer ? get_userdata( $customer ) : null;
		$customer_label = $customer_user
			? sprintf(
				/* translators: 1: customer name, 2: customer email */
				__( '%1$s (%2$s)', 'woocommerce' ),
				$customer_user->display_name,
				$customer_user->user_email
			)
			: __( 'Guest', 'woocommerce' );
		?>
		<div
			class="wc-order-side-panel-overlay"
			id="wc-order-side-panel-overlay"
			hidden
		></div>
		<aside
			class="wc-order-side-panel"
			id="wc-order-side-panel"
			role="dialog"
			aria-modal="true"
			aria-labelledby="wc-order-side-panel-title"
			hidden
		>
			<header class="wc-order-side-panel__header">
				<h2 id="wc-order-side-panel-title">
					<?php esc_html_e( 'Edit order details', 'woocommerce' ); ?>
				</h2>
				<button
					type="button"
					class="wc-order-side-panel__close"
					id="wc-order-side-panel-close"
					aria-label="<?php esc_attr_e( 'Close', 'woocommerce' ); ?>"
				>
					&times;
				</button>
			</header>

			<form class="wc-order-side-panel__body" id="wc-order-side-panel-form">
				<section class="wc-order-side-panel__section" data-section="customer">
					<h3><?php esc_html_e( 'Customer', 'woocommerce' ); ?></h3>
					<p class="wc-order-side-panel__field">
						<label for="wc-osp-customer"><?php esc_html_e( 'Customer', 'woocommerce' ); ?></label>
						<input
							type="text"
							id="wc-osp-customer"
							class="wc-osp-input"
							value="<?php echo esc_attr( $customer_label ); ?>"
							readonly
						/>
					</p>
				</section>

				<section class="wc-order-side-panel__section" data-section="billing">
					<h3><?php esc_html_e( 'Billing address', 'woocommerce' ); ?></h3>
					<?php $this->render_address_fields( 'billing', $billing ); ?>
				</section>

				<section class="wc-order-side-panel__section" data-section="shipping">
					<h3><?php esc_html_e( 'Shipping address', 'woocommerce' ); ?></h3>
					<p class="wc-order-side-panel__section-meta">
						<a href="#" id="wc-osp-copy-billing">
							<?php esc_html_e( 'Copy billing to shipping', 'woocommerce' ); ?>
						</a>
					</p>
					<?php $this->render_address_fields( 'shipping', $shipping ); ?>
				</section>

				<section class="wc-order-side-panel__section" data-section="note">
					<h3><?php esc_html_e( 'Customer provided note', 'woocommerce' ); ?></h3>
					<p class="wc-order-side-panel__field">
						<label for="wc-osp-note">
							<?php esc_html_e( 'Note from customer at checkout', 'woocommerce' ); ?>
						</label>
						<textarea
							id="wc-osp-note"
							class="wc-osp-input"
							rows="3"
						><?php echo esc_textarea( $note ); ?></textarea>
					</p>
				</section>
			</form>

			<footer class="wc-order-side-panel__footer">
				<span class="wc-order-side-panel__dirty" id="wc-osp-dirty">
					<?php esc_html_e( 'Unsaved changes', 'woocommerce' ); ?>
				</span>
				<div class="wc-order-side-panel__actions">
					<button
						type="button"
						class="button button-secondary"
						id="wc-osp-cancel"
					>
						<?php esc_html_e( 'Cancel', 'woocommerce' ); ?>
					</button>
					<button
						type="button"
						class="button button-primary"
						id="wc-osp-save"
						disabled
					>
						<?php esc_html_e( 'Save', 'woocommerce' ); ?>
					</button>
				</div>
			</footer>
		</aside>
		<?php
	}

	/**
	 * Render a labelled set of address inputs for the side panel.
	 *
	 * @param string $type    'billing' or 'shipping'.
	 * @param array  $address Address fields keyed by sub-field.
	 */
	private function render_address_fields( string $type, array $address ): void {
		$fields = array(
			'first_name' => __( 'First name', 'woocommerce' ),
			'last_name'  => __( 'Last name', 'woocommerce' ),
			'company'    => __( 'Company', 'woocommerce' ),
			'address_1'  => __( 'Address line 1', 'woocommerce' ),
			'address_2'  => __( 'Address line 2', 'woocommerce' ),
			'city'       => __( 'City', 'woocommerce' ),
			'postcode'   => __( 'Postcode', 'woocommerce' ),
			'state'      => __( 'State', 'woocommerce' ),
			'country'    => __( 'Country', 'woocommerce' ),
		);

		if ( 'billing' === $type ) {
			$fields['email'] = __( 'Email', 'woocommerce' );
			$fields['phone'] = __( 'Phone', 'woocommerce' );
		}

		echo '<div class="wc-order-side-panel__grid">';
		foreach ( $fields as $key => $label ) {
			$id    = sprintf( 'wc-osp-%s-%s', $type, str_replace( '_', '-', $key ) );
			$value = isset( $address[ $key ] ) ? $address[ $key ] : '';
			$full  = in_array( $key, array( 'company', 'address_1', 'address_2' ), true );
			printf(
				'<p class="wc-order-side-panel__field%s">'
				. '<label for="%s">%s</label>'
				. '<input type="text" id="%s" class="wc-osp-input" data-field="%s.%s" value="%s" />'
				. '</p>',
				$full ? ' is-full' : '',
				esc_attr( $id ),
				esc_html( $label ),
				esc_attr( $id ),
				esc_attr( $type ),
				esc_attr( $key ),
				esc_attr( $value )
			);
		}
		echo '</div>';
	}

	/**
	 * Best-effort lookup of the order being edited for footer rendering.
	 *
	 * Uses GET/POST id then falls back to the global $post object on
	 * the legacy edit screen.
	 *
	 * @return \WC_Order|false
	 */
	private function get_current_order() {
		$id = 0;
		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- Read-only lookup of the order ID being rendered.
		if ( isset( $_GET['id'] ) ) {
			$id = absint( $_GET['id'] );
		} elseif ( isset( $_POST['post_ID'] ) ) {
			$id = absint( $_POST['post_ID'] );
		} elseif ( isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof \WP_Post ) {
			$id = (int) $GLOBALS['post']->ID;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing

		return $id ? wc_get_order( $id ) : false;
	}
}
