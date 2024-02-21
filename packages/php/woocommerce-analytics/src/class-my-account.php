<?php
/**
 * Events tracked on the My Account page.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

/**
 * Filters and Actions added to My Account pages to perform analytics
 */
class My_Account {

	use Woo_Analytics_Trait;

	/**
	 * Constructor.
	 */
	public function init_hooks() {
		add_action( 'woocommerce_account_content', array( $this, 'track_tabs' ) );
		add_action( 'woocommerce_account_content', array( $this, 'track_logouts' ) );
		add_action( 'woocommerce_customer_save_address', array( $this, 'track_save_address' ), 10, 2 );
		add_action( 'wp_head', array( $this, 'trigger_queued_events' ) );
		add_action( 'wp', array( $this, 'track_add_payment_method' ) );
		add_action( 'wp', array( $this, 'track_delete_payment_method' ) );
		add_action( 'woocommerce_save_account_details', array( $this, 'track_save_account_details' ) );
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'add_initiator_prop_to_my_account_action_links' ) );
		add_action( 'woocommerce_cancelled_order', array( $this, 'track_order_cancel_event' ), 10, 0 );
		add_action( 'before_woocommerce_pay', array( $this, 'track_order_pay_event' ) );
		add_action( 'woocommerce_before_account_orders', array( $this, 'add_initiator_prop_to_order_urls' ), 9 );
		add_filter( 'query_vars', array( $this, 'add_initiator_param_to_query_vars' ) );
	}

	/**
	 * Track my account tabs, we only trigger an event if a tab is viewed.
	 *
	 * We also track other events here, like order number clicks, order action clicks,
	 * address clicks, payment method add and delete.
	 */
	public function track_tabs() {
		global $wp;

		// WooCommerce keeps a map of my-account endpoints keys and their custom permalinks.
		$core_endpoints = WC()->query->get_query_vars();

		if ( ! empty( $wp->query_vars ) ) {

			foreach ( $wp->query_vars as $key => $value ) {
				// we skip pagename.
				if ( 'pagename' === $key ) {
					continue;
				}

				// When no permalink is set, the first page is page_id, so we skip it.
				if ( 'page_id' === $key ) {
					continue;
				}

				// We don't want to track our own analytics params.
				if ( '_wca_initiator' === $key ) {
					continue;
				}

				if ( isset( $core_endpoints['view-order'] ) && $core_endpoints['view-order'] === $key && is_numeric( $value ) ) {
					$initiator = get_query_var( '_wca_initiator' );
					if ( 'number' === $initiator ) {
						$this->record_event( 'woocommerceanalytics_my_account_order_number_click' );
						continue;
					}
					if ( 'action' === $initiator ) {
						$this->record_event( 'woocommerceanalytics_my_account_order_action_click', array( 'action' => 'view' ) );
						continue;
					}
				}

				if ( isset( $core_endpoints['edit-address'] ) && $core_endpoints['edit-address'] === $key && in_array( $value, array( 'billing', 'shipping' ), true ) ) {
					$refer = wp_get_referer();
					if ( $refer === wc_get_endpoint_url( 'edit-address', $value ) ) {
						// It means we're likely coming from the same page after a failed save and don't want to retrigger the address click event.
						continue;
					}

					$this->record_event( 'woocommerceanalytics_my_account_address_click', array( 'address' => $value ) );
					continue;
				}

				if ( isset( $core_endpoints['add-payment-method'] ) && $core_endpoints['add-payment-method'] === $key ) {
					$this->record_event( 'woocommerceanalytics_my_account_payment_add' );
					continue;
				}

				if ( isset( $core_endpoints['edit-address'] ) && $core_endpoints['edit-address'] ) {
					$refer = wp_get_referer();
					if ( $refer === wc_get_endpoint_url( 'edit-address', 'billing' ) || $refer === wc_get_endpoint_url( 'edit-address', 'shipping' ) ) {
						// It means we're likely coming from the edit page save and don't want to retrigger the page view event.
						continue;
					}
				}
				/**
				 * The main dashboard view has page as key, so we rename it.
				 */
				if ( 'page' === $key ) {
					$key = 'dashboard';
				}

				/**
				 * If a custom permalink is used for one of the pages, query_vars will have 2 keys, the custom permalink and the core endpoint key.
				 * To avoid triggering the event twice, we skip the core one and only track the custom one.
				 * Tracking the custom endpoint is safer than hoping the duplicated, redundant core endpoint is always present.
				 */
				if ( isset( $core_endpoints[ $key ] ) && $core_endpoints[ $key ] !== $key ) {
					continue;
				}

				/**
				 * $core_endpoints is an array of core_permalink => custom_permalink,
				 * query_vars gives us the custom_permalink, but we want to track it as core_permalink.
				 */
				if ( array_search( $key, $core_endpoints, true ) ) {
					$key = array_search( $key, $core_endpoints, true );
				}

				$this->record_event( 'woocommerceanalytics_my_account_page_view', array( 'tab' => $key ) );
			}
		}
	}

	/**
	 * Track address save events, this can only come from the my account page.
	 *
	 * @param int    $customer_id The customer id.
	 * @param string $load_address The address type (billing, shipping).
	 */
	public function track_save_address( $customer_id, $load_address ) {
		$this->queue_event( 'woocommerceanalytics_my_account_address_save', array( 'address' => $load_address ) );
	}

	/**
	 * Track payment method add events, this can only come from the my account page.
	 */
	public function track_add_payment_method() {
		if ( isset( $_POST['woocommerce_add_payment_method'] ) && isset( $_POST['payment_method'] ) ) {

			$nonce_value = wc_get_var( $_REQUEST['woocommerce-add-payment-method-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash

			if ( ! wp_verify_nonce( $nonce_value, 'woocommerce-add-payment-method' ) ) {
				return;
			}

			$this->queue_event( 'woocommerceanalytics_my_account_payment_save' );
			return;
		}
	}

	/**
	 * Track payment method delete events.
	 */
	public function track_delete_payment_method() {
		global $wp;
		if ( isset( $wp->query_vars['delete-payment-method'] ) ) {
			$this->queue_event( 'woocommerceanalytics_my_account_payment_delete' );
			return;
		}
	}

	/**
	 * Track order cancel events.
	 */
	public function track_order_cancel_event() {
		if ( isset( $_GET['_wca_initiator'] ) && ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'woocommerce-cancel_order' ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$this->queue_event( 'woocommerceanalytics_my_account_order_action_click', array( 'action' => 'cancel' ) );
		}
	}

	/**
	 * Track order pay events.
	 */
	public function track_order_pay_event() {
		if ( isset( $_GET['_wca_initiator'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Recommended
			$this->record_event( 'woocommerceanalytics_my_account_order_action_click', array( 'action' => 'pay' ) );
		}
	}

	/**
	 * Track account details save events, this can only come from the my account page.
	 */
	public function track_save_account_details() {
		$this->queue_event( 'woocommerceanalytics_my_account_details_save' );
	}

	/**
	 * Track logout events.
	 */
	public function track_logouts() {
		$common_props = $this->render_properties_as_js(
			$this->get_common_properties()
		);

		wc_enqueue_js(
			"
			jQuery(document).ready(function($) {
					// Attach event listener to the logout link
				jQuery('.woocommerce-MyAccount-navigation-link--customer-logout').on('click', function() {
					_wca.push({
							'_en': 'woocommerceanalytics_my_account_tab_click',
							'tab': 'logout'," .
							$common_props . '
					});
				});
			});
			'
		);
	}

	/**
	 * Add referrer prop to my account action links
	 *
	 * @param array $actions My account action links.
	 * @return array
	 */
	public function add_initiator_prop_to_my_account_action_links( $actions ) {
		foreach ( $actions as $key => $action ) {
			if ( ! isset( $action['url'] ) ) {
				continue;
			}

			// Check if the action key is view, pay, or cancel.
			if ( ! in_array( $key, array( 'view', 'pay', 'cancel' ), true ) ) {
				continue;
			}

			$url                    = add_query_arg( array( '_wca_initiator' => 'action' ), $action['url'] );
			$actions[ $key ]['url'] = $url;
		}

		return $actions;
	}

	/**
	 * Add an initiator prop to the order url.
	 *
	 * The get_view_order_url is used in a lot of places,
	 * so we want to limit it just to my account page.
	 */
	public function add_initiator_prop_to_order_urls() {
		add_filter(
			'woocommerce_get_view_order_url',
			function ( $url ) {
				return add_query_arg( array( '_wca_initiator' => 'number' ), $url );
			},
			10,
			1
		);

		add_filter(
			'woocommerce_get_endpoint_url',
			function ( $url, $endpoint ) {
				if ( 'edit-address' === $endpoint ) {
					return add_query_arg( array( '_wca_initiator' => 'action' ), $url );
				}
				return $url;
			},
			10,
			2
		);
	}

	/**
	 * Add initiator to query vars
	 *
	 * @param array $query_vars Query vars.
	 * @return array
	 */
	public function add_initiator_param_to_query_vars( $query_vars ) {
		$query_vars[] = '_wca_initiator';
		return $query_vars;
	}

	/**
	 * Record all queued up events in session.
	 *
	 * This is called on every page load, and will record all events that were queued up in session.
	 */
	public function trigger_queued_events() {
		if ( is_object( WC()->session ) ) {
			$events = WC()->session->get( 'wca_queued_events', array() );

			foreach ( $events as $event ) {
				$this->record_event(
					$event['event_name'],
					$event['event_props']
				);
			}

			// Clear data, now that these events have been recorded.
			WC()->session->set( 'wca_queued_events', array() );

		}
	}

	/**
	 * Queue an event in session to be recorded later on next page load.
	 *
	 * @param string $event_name The event name.
	 * @param array  $event_props The event properties.
	 */
	protected function queue_event( $event_name, $event_props = array() ) {
		if ( is_object( WC()->session ) ) {
			$events   = WC()->session->get( 'wca_queued_events', array() );
			$events[] = array(
				'event_name'  => $event_name,
				'event_props' => $event_props,
			);
			WC()->session->set( 'wca_queued_events', $events );
		}
	}
}
