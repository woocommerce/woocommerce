<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order;
use WC_Rate_Limiter;

defined( 'ABSPATH' ) || exit;

/**
 * Controller for EU withdrawal (right of withdrawal) functionality.
 *
 * Handles the withdrawal request lifecycle: form display, submission processing,
 * data persistence, and email notifications. Integrates with the My Account
 * page via existing endpoint/template architecture.
 *
 * @since 10.9.0
 */
class WithdrawalController implements RegisterHooksInterface {

	/**
	 * Meta key used to store withdrawal requests on orders.
	 *
	 * @var string
	 */
	private const WITHDRAWAL_REQUESTS_META_KEY = '_withdrawal_requests';

	/**
	 * Default withdrawal window in days (14 days per EU Consumer Rights Directive).
	 * Merchants can customize this via the `woocommerce_withdrawal_window_days` filter.
	 *
	 * @var int
	 */
	private const DEFAULT_WITHDRAWAL_WINDOW_DAYS = 14;

	/**
	 * Register hooks for the withdrawal controller.
	 *
	 * @return void
	 */
	public function register() {
		// Endpoint content handlers.
		add_action( 'woocommerce_account_withdrawals_endpoint', array( $this, 'output_withdrawals' ) );
		add_action( 'woocommerce_account_request-withdrawal_endpoint', array( $this, 'output_request_withdrawal' ) );

		// Form submission handler.
		add_action( 'template_redirect', array( $this, 'handle_withdrawal_request' ) );

		// Enqueue styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Admin meta box.
		add_action( 'add_meta_boxes', array( $this, 'add_withdrawal_meta_box' ) );
		add_action( 'wp_ajax_woocommerce_update_withdrawal_request', array( $this, 'ajax_update_withdrawal_request' ) );

		// Rewrite flushing on plugin update.
		add_action( 'woocommerce_updated', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );
	}

	/**
	 * Enqueue styles for withdrawal endpoints.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! is_account_page() ) {
			return;
		}

		$endpoint = $this->get_current_endpoint();
		if ( ! in_array( $endpoint, array( 'withdrawals', 'request-withdrawal' ), true ) ) {
			return;
		}

		wp_enqueue_style(
			'woocommerce-withdrawal',
			plugins_url( 'assets/css/withdrawal.css', WC_PLUGIN_FILE ),
			array(),
			\WC_VERSION
		);
	}

	/**
	 * Add withdrawal management meta box to order edit screen.
	 *
	 * @return void
	 */
	public function add_withdrawal_meta_box() {
		$screen = OrderUtil::get_order_admin_screen();
		if ( ! $screen ) {
			return;
		}

		add_meta_box(
			'woocommerce-order-withdrawal',
			__( 'Withdrawal Requests', 'woocommerce' ),
			array( $this, 'render_withdrawal_meta_box' ),
			$screen,
			'side',
			'default'
		);
	}

	/**
	 * Render the withdrawal management meta box.
	 *
	 * @param \WP_Post|WC_Order $post_or_order Post or order object.
	 * @return void
	 */
	public function render_withdrawal_meta_box( $post_or_order ) {
		$order = ( $post_or_order instanceof WC_Order ) ? $post_or_order : wc_get_order( $post_or_order->ID );
		if ( ! $order ) {
			echo '<p>' . esc_html__( 'Order not found.', 'woocommerce' ) . '</p>';
			return;
		}

		$requests = $this->get_withdrawal_requests( $order );

		if ( empty( $requests ) ) {
			echo '<p>' . esc_html__( 'No withdrawal requests for this order.', 'woocommerce' ) . '</p>';
			return;
		}

		foreach ( $requests as $index => $request ) {
			$status_label = $this->get_status_label( $request['status'] ?? 'pending' );
			$date         = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $request['date_created'] ?? 0 );
			?>
			<div class="withdrawal-request" style="margin-bottom:1em;padding-bottom:1em;border-bottom:1px solid #ddd;">
				<p>
					<strong><?php esc_html_e( 'Request', 'woocommerce' ); ?> #<?php echo esc_html( $request['request_id'] ?? '' ); ?></strong><br>
					<?php echo esc_html( $date ); ?><br>
					<span class="withdrawal-status" style="display:inline-block;padding:2px 8px;border-radius:3px;background:#f0f0f1;">
						<?php echo esc_html( $status_label ); ?>
					</span>
				</p>

				<?php if ( ! empty( $request['reason'] ) ) : ?>
					<p><strong><?php esc_html_e( 'Reason', 'woocommerce' ); ?>:</strong><br>
					<?php echo esc_html( $request['reason'] ); ?></p>
				<?php endif; ?>

				<?php if ( 'pending' === $request['status'] ) : ?>
					<p>
						<button type="button" class="button button-small withdrawal-action" data-action="approve" data-index="<?php echo esc_attr( (string) $index ); ?>" data-request-id="<?php echo esc_attr( $request['request_id'] ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update_withdrawal_' . $request['request_id'] ) ); ?>">
							<?php esc_html_e( 'Approve', 'woocommerce' ); ?>
						</button>
						<button type="button" class="button button-small withdrawal-action" data-action="reject" data-index="<?php echo esc_attr( (string) $index ); ?>" data-request-id="<?php echo esc_attr( $request['request_id'] ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update_withdrawal_' . $request['request_id'] ) ); ?>">
							<?php esc_html_e( 'Reject', 'woocommerce' ); ?>
						</button>
					</p>
					<p>
						<label for="withdrawal_admin_notes_<?php echo esc_attr( $index ); ?>">
							<?php esc_html_e( 'Admin notes:', 'woocommerce' ); ?>
						</label><br>
						<textarea id="withdrawal_admin_notes_<?php echo esc_attr( $index ); ?>" class="withdrawal-admin-notes" rows="2" style="width:100%;"><?php echo esc_textarea( $request['admin_notes'] ?? '' ); ?></textarea>
					</p>
				<?php elseif ( ! empty( $request['admin_notes'] ) ) : ?>
					<p><strong><?php esc_html_e( 'Admin notes', 'woocommerce' ); ?>:</strong><br>
					<?php echo esc_html( $request['admin_notes'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php
		}

		wc_enqueue_js(
			"
			jQuery( '.withdrawal-action' ).on( 'click', function() {
				var button = jQuery( this );
				var data = {
					action: 'woocommerce_update_withdrawal_request',
					request_action: button.data( 'action' ),
					request_index: button.data( 'index' ),
					request_id: button.data( 'request-id' ),
					order_id: " . absint( $order->get_id() ) . ",
					admin_notes: button.closest( '.withdrawal-request' ).find( '.withdrawal-admin-notes' ).val(),
					_wpnonce: button.data( 'nonce' )
				};

				button.prop( 'disabled', true ).text( '" . esc_js( __( 'Processing…', 'woocommerce' ) ) . "' );

				jQuery.post( '" . esc_js( admin_url( 'admin-ajax.php' ) ) . "', data, function( response ) {
					if ( response.success ) {
						location.reload();
					} else {
						alert( response.data || '" . esc_js( __( 'Error updating withdrawal request.', 'woocommerce' ) ) . "' );
						button.prop( 'disabled', false ).text( button.data( 'action' ) === 'approve' ? '" . esc_js( __( 'Approve', 'woocommerce' ) ) . "' : '" . esc_js( __( 'Reject', 'woocommerce' ) ) . "' );
					}
				} );
			} );
			"
		);
	}

	/**
	 * AJAX handler for updating withdrawal request status (approve/reject).
	 *
	 * @return void
	 */
	public function ajax_update_withdrawal_request() {
		$raw_request_id = isset( $_POST['request_id'] ) ? sanitize_text_field( wp_unslash( $_POST['request_id'] ) ) : '';
		check_ajax_referer( 'update_withdrawal_' . $raw_request_id, '_wpnonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'woocommerce' ) );
		}

		$order_id       = absint( $_POST['order_id'] ?? 0 );
		$request_index  = absint( $_POST['request_index'] ?? 0 );
		$request_action = sanitize_text_field( wp_unslash( $_POST['request_action'] ?? '' ) );
		$admin_notes    = sanitize_textarea_field( wp_unslash( $_POST['admin_notes'] ?? '' ) );

		if ( ! in_array( $request_action, array( 'approve', 'reject' ), true ) ) {
			wp_send_json_error( __( 'Invalid action.', 'woocommerce' ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( __( 'Order not found.', 'woocommerce' ) );
		}

		$requests = $this->get_withdrawal_requests( $order );
		if ( ! isset( $requests[ $request_index ] ) ) {
			wp_send_json_error( __( 'Withdrawal request not found.', 'woocommerce' ) );
		}

		$new_status                                 = 'approve' === $request_action ? 'approved' : 'rejected';
		$requests[ $request_index ]['status']       = $new_status;
		$requests[ $request_index ]['admin_notes']  = $admin_notes;
		$requests[ $request_index ]['date_updated'] = time();

		$this->save_withdrawal_requests( $order, $requests );

		$action_label = 'approve' === $request_action ? __( 'approved', 'woocommerce' ) : __( 'rejected', 'woocommerce' );
		/* translators: %1$s: request ID, %2$s: action (approved/rejected) */
		$order->add_order_note( sprintf( __( 'Withdrawal request #%1$s %2$s.', 'woocommerce' ), $requests[ $request_index ]['request_id'], $action_label ) );

		/**
		 * Fires when a withdrawal request status is updated (approved/rejected).
		 *
		 * @since 10.9.0
		 * @param int    $order_id   Order ID.
		 * @param string $request_id Withdrawal request ID.
		 * @param string $new_status New status (approved/rejected).
		 * @param string $admin_notes Admin notes attached to the update.
		 */
		do_action( 'woocommerce_withdrawal_request_updated', $order->get_id(), $requests[ $request_index ]['request_id'], $new_status, $admin_notes );

		wp_send_json_success();
	}

	/**
	 * Handle withdrawal request form submission.
	 *
	 * @return void
	 */
	public function handle_withdrawal_request() {
		if ( ! is_account_page() ) {
			return;
		}

		if ( ! isset( $_POST['action'] ) || 'request_withdrawal' !== $_POST['action'] ) {
			return;
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'woocommerce-request_withdrawal' ) ) {
			wc_add_notice( __( 'Session expired. Please try again.', 'woocommerce' ), 'error' );
			return;
		}

		$order_id = absint( $_POST['order_id'] ?? 0 );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			wc_add_notice( __( 'Order not found.', 'woocommerce' ), 'error' );
			return;
		}

		// Verify order ownership.
		if ( ! current_user_can( 'view_order', $order_id ) ) {
			wc_add_notice( __( 'You do not have permission to withdraw from this order.', 'woocommerce' ), 'error' );
			return;
		}

		// Verify order is eligible for withdrawal.
		if ( ! $this->is_order_eligible_for_withdrawal( $order ) ) {
			wc_add_notice( __( 'This order is not eligible for withdrawal.', 'woocommerce' ), 'error' );
			return;
		}

		// Rate limiting.
		if ( WC_Rate_Limiter::retried_too_soon( 'woocommerce_withdrawal_' . $order_id ) ) {
			wc_add_notice( __( 'You have already submitted a withdrawal request for this order. Please wait before trying again.', 'woocommerce' ), 'error' );
			return;
		}

		$step = sanitize_text_field( wp_unslash( $_POST['step'] ?? '' ) );

		if ( 'confirm' !== $step ) {
			// Step 1: Persist form data in session, redirect to confirmation step.
			$reason = isset( $_POST['withdrawal_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['withdrawal_reason'] ) ) : '';

			if ( function_exists( 'WC' ) && WC()->session ) {
				WC()->session->set(
					'woocommerce_withdrawal_request_data',
					array(
						'order_id'     => $order_id,
						'reason'       => $reason,
						'date_created' => time(),
					)
				);
			}

			$redirect_url = wc_get_endpoint_url( 'request-withdrawal', $order_id, wc_get_page_permalink( 'myaccount' ) );
			$redirect_url = add_query_arg( 'step', 'confirm', $redirect_url );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// Step 2: Process the actual withdrawal submission.
		$session_data = ( function_exists( 'WC' ) && WC()->session ) ? WC()->session->get( 'woocommerce_withdrawal_request_data' ) : null;
		$reason       = '';

		if ( is_array( $session_data ) && isset( $session_data['order_id'] ) && (int) $session_data['order_id'] === $order_id ) {
			$reason = isset( $session_data['reason'] ) ? (string) $session_data['reason'] : '';
		} else {
			// Fallback to POST (e.g., when session is unavailable).
			$reason = isset( $_POST['withdrawal_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['withdrawal_reason'] ) ) : '';
		}

		// Generate a unique request ID.
		$request_id = wp_generate_uuid4();

		$requests = $this->get_withdrawal_requests( $order );

		$withdrawal_data = array(
			'request_id'                  => $request_id,
			'date_created'                => time(),
			'status'                      => 'pending',
			'reason'                      => $reason,
			'items'                       => array(),
			// Full withdrawal by default.
							'admin_notes' => '',
			'ip_address'                  => wc_get_ip_address(),
		);

		$requests[] = $withdrawal_data;
		$this->save_withdrawal_requests( $order, $requests );

		/**
		 * Fires after a withdrawal request has been saved to the order.
		 *
		 * Used by email classes to send notifications to the customer and store admin.
		 *
		 * @since 10.9.0
		 * @param int    $order_id   Order ID.
		 * @param string $request_id Withdrawal request ID.
		 */
		do_action( 'woocommerce_withdrawal_request_submitted', $order->get_id(), $request_id );

		// Add customer-visible order note.
		/* translators: %s: withdrawal request ID */
		$order->add_order_note( sprintf( __( 'Withdrawal request #%s submitted by customer.', 'woocommerce' ), $request_id ), true );
		$order->save();

		// Redirect to acknowledgment page.
		$redirect_url = wc_get_endpoint_url( 'request-withdrawal', $order_id, wc_get_page_permalink( 'myaccount' ) );
		$redirect_url = add_query_arg(
			array(
				'submitted'  => '1',
				'request_id' => $request_id,
			),
			$redirect_url
		);
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Output the withdrawals list page content.
	 *
	 * @param int $current_page Current page number for pagination.
	 * @return void
	 */
	public function output_withdrawals( $current_page ) {
		$current_page = max( 1, absint( $current_page ) );

		$customer_orders = wc_get_orders(
			array(
				'customer' => get_current_user_id(),
				'limit'    => 10,
				'page'     => $current_page,
				'return'   => 'ids',
			)
		);

		$withdrawal_orders = array();
		foreach ( $customer_orders as $oid ) {
			$order = wc_get_order( $oid );
			if ( ! $order ) {
				continue;
			}
			$requests = $this->get_withdrawal_requests( $order );
			if ( ! empty( $requests ) ) {
				$withdrawal_orders[ $oid ] = array(
					'order'    => $order,
					'requests' => $requests,
				);
			}
		}

		wc_get_template(
			'myaccount/withdrawals.php',
			array(
				'withdrawal_orders' => $withdrawal_orders,
			)
		);
	}

	/**
	 * Output the request withdrawal form page.
	 *
	 * @param int $order_id Order ID from the endpoint query var.
	 * @return void
	 */
	public function output_request_withdrawal( $order_id ) {
		$order_id = absint( $order_id );

		if ( ! $order_id ) {
			// No specific order — show an order selection form.
			$eligible_orders = $this->get_eligible_orders_for_current_user();
			wc_get_template(
				'myaccount/form-request-withdrawal.php',
				array(
					'eligible_orders' => $eligible_orders,
					'order'           => null,
				)
			);
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || ! current_user_can( 'view_order', $order_id ) ) {
			wc_add_notice( __( 'Order not found.', 'woocommerce' ), 'error' );
			return;
		}

		if ( ! $this->is_order_eligible_for_withdrawal( $order ) ) {
			wc_add_notice( __( 'This order is not eligible for withdrawal.', 'woocommerce' ), 'error' );
			return;
		}

		$submitted  = ! empty( $_GET['submitted'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$request_id = ! empty( $_GET['request_id'] ) ? sanitize_text_field( wp_unslash( $_GET['request_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$step       = ! empty( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $submitted && $request_id ) {
			// Acknowledgment screen.
			$requests     = $this->get_withdrawal_requests( $order );
			$request_data = null;
			foreach ( $requests as $r ) {
				if ( isset( $r['request_id'] ) && $r['request_id'] === $request_id ) {
					$request_data = $r;
					break;
				}
			}

			// Authorization: only the order owner (or an admin) may see the acknowledgment.
			if ( ! $request_data || ( (int) $order->get_user_id() !== get_current_user_id() && ! current_user_can( 'manage_woocommerce' ) ) ) {
				wc_add_notice( __( 'You are not allowed to view this withdrawal request.', 'woocommerce' ), 'error' );
				wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
				exit;
			}

			wc_get_template(
				'myaccount/form-request-withdrawal-submitted.php',
				array(
					'order'        => $order,
					'request_id'   => $request_id,
					'request_data' => $request_data,
				)
			);
			return;
		}

		if ( 'confirm' === $step ) {
			// Confirmation step.
			wc_get_template(
				'myaccount/form-request-withdrawal-confirm.php',
				array(
					'order' => $order,
				)
			);
			return;
		}

		// Initial form.
		wc_get_template(
			'myaccount/form-request-withdrawal.php',
			array(
				'eligible_orders' => array( $order ),
				'order'           => $order,
			)
		);
	}

	/**
	 * Get eligible orders for withdrawal for the current customer.
	 *
	 * @return WC_Order[]
	 */
	private function get_eligible_orders_for_current_user() {
		$customer_orders = wc_get_orders(
			array(
				'customer' => get_current_user_id(),
				'limit'    => -1,
				'status'   => $this->get_withdrawable_order_statuses(),
				'return'   => 'ids',
			)
		);

		$eligible = array();
		foreach ( $customer_orders as $oid ) {
			$order = wc_get_order( $oid );
			if ( $order && $this->is_order_eligible_for_withdrawal( $order ) ) {
				$eligible[] = $order;
			}
		}

		return $eligible;
	}

	/**
	 * Check if an order is eligible for withdrawal.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	public function is_order_eligible_for_withdrawal( WC_Order $order ): bool {
		// Must have a valid withdrawable status.
		if ( ! $order->has_status( $this->get_withdrawable_order_statuses() ) ) {
			return false;
		}

		/**
		 * Filters the withdrawal window in days.
		 *
		 * @since 10.9.0
		 * @param int $window_days Withdrawal window in days.
		 */
		$window_days = apply_filters( 'woocommerce_withdrawal_window_days', self::DEFAULT_WITHDRAWAL_WINDOW_DAYS );

		$date_object = $order->get_date_completed() ? $order->get_date_completed() : $order->get_date_created();
		if ( ! $date_object ) {
			return false;
		}
		$completed_date = $date_object->getTimestamp();
		$deadline       = strtotime( '+' . $window_days . ' days', $completed_date );

		if ( time() > $deadline ) {
			return false;
		}

		// Must not already have a pending withdrawal request.
		$requests = $this->get_withdrawal_requests( $order );
		foreach ( $requests as $request ) {
			if ( 'pending' === $request['status'] ) {
				return false;
			}
		}

		/**
		 * Filter whether an order is eligible for withdrawal.
		 *
		 * @since 10.9.0
		 * @param bool     $eligible Whether the order is eligible.
		 * @param WC_Order $order    The order object.
		 */
		return apply_filters( 'woocommerce_order_is_eligible_for_withdrawal', true, $order );
	}

	/**
	 * Get order statuses where withdrawal is allowed.
	 *
	 * @return array
	 */
	public function get_withdrawable_order_statuses(): array {
		/**
		 * Filter the order statuses that allow withdrawal.
		 *
		 * @since 10.9.0
		 * @param array $statuses Order statuses.
		 */
		return apply_filters(
			'woocommerce_valid_order_statuses_for_withdrawal',
			array(
				'processing',
				'on-hold',
				'completed',
			)
		);
	}

	/**
	 * Get withdrawal requests stored on an order.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	public function get_withdrawal_requests( WC_Order $order ): array {
		$requests = $order->get_meta( self::WITHDRAWAL_REQUESTS_META_KEY, true );
		return is_array( $requests ) ? $requests : array();
	}

	/**
	 * Save withdrawal requests to an order.
	 *
	 * @param WC_Order $order    Order object.
	 * @param array    $requests Withdrawal requests array.
	 * @return void
	 */
	public function save_withdrawal_requests( WC_Order $order, array $requests ): void {
		$order->update_meta_data( self::WITHDRAWAL_REQUESTS_META_KEY, $requests );
		$order->save_meta_data();
	}

	/**
	 * Flush rewrite rules when the plugin is updated.
	 *
	 * @return void
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( ! did_action( 'woocommerce_updated' ) ) {
			return;
		}

		$version_option  = 'woocommerce_withdrawal_endpoints_version';
		$current_version = get_option( $version_option, '' );

		if ( \WC_VERSION !== $current_version ) {
			update_option( $version_option, \WC_VERSION );
			flush_rewrite_rules();
		}
	}

	/**
	 * Get the current My Account endpoint.
	 *
	 * @return string
	 */
	private function get_current_endpoint(): string {
		global $wp_query;
		if ( ! isset( $wp_query->query_vars ) ) {
			return '';
		}

		foreach ( array( 'withdrawals', 'request-withdrawal' ) as $endpoint ) {
			if ( isset( $wp_query->query_vars[ $endpoint ] ) ) {
				return $endpoint;
			}
		}

		return '';
	}

	/**
	 * Get a human-readable label for a withdrawal status.
	 *
	 * @param string $status Status key.
	 * @return string
	 */
	private function get_status_label( string $status ): string {
		$labels = array(
			'pending'  => __( 'Pending', 'woocommerce' ),
			'approved' => __( 'Approved', 'woocommerce' ),
			'rejected' => __( 'Rejected', 'woocommerce' ),
		);
		return $labels[ $status ] ?? ucfirst( $status );
	}
}