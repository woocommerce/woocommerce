<?php
/**
 * WooPaymentsMoneyMovementOrderService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore;
use Exception;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

/**
 * Adds WooCommerce-local order context to native WooPayments money movement data.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsMoneyMovementOrderService {

	private const LOG_SOURCE = 'woopayments-money-movement';

	/**
	 * WooPayments order data service.
	 *
	 * @var WooPaymentsOrderDataService
	 */
	private WooPaymentsOrderDataService $order_data_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsOrderDataService $order_data_service Order data service.
	 */
	final public function init( WooPaymentsOrderDataService $order_data_service ): void {
		$this->order_data_service = $order_data_service;
	}

	/**
	 * Map legacy transaction search order tokens to charge IDs.
	 *
	 * @param array<string,mixed> $params Transaction request params.
	 * @return array<string,mixed>
	 */
	public function map_transaction_search_params( array $params ): array {
		if ( empty( $params['search'] ) ) {
			return $params;
		}

		$search = is_array( $params['search'] ) ? $params['search'] : array( $params['search'] );

		$params['search'] = $this->map_search_orders_to_charge_ids( $search );

		return $params;
	}

	/**
	 * Add order and payment intent context to a transaction list response.
	 *
	 * @param array<string,mixed> $response Platform response.
	 * @return array<string,mixed>
	 */
	public function enrich_transactions_list_response( array $response ): array {
		if ( empty( $response['data'] ) || ! is_array( $response['data'] ) ) {
			return $response;
		}

		$charge_ids = array();
		foreach ( $response['data'] as $transaction ) {
			if ( is_array( $transaction ) ) {
				$charge_ids[] = $this->get_charge_id_from_entity( $transaction );
			}
		}

		$orders_by_charge_id = $this->get_orders_by_charge_ids( $charge_ids );

		foreach ( $response['data'] as &$transaction ) {
			if ( ! is_array( $transaction ) ) {
				continue;
			}

			$charge_id = $this->get_charge_id_from_entity( $transaction );
			if ( '' === $charge_id || ! isset( $orders_by_charge_id[ $charge_id ] ) ) {
				continue;
			}

			$order                            = $orders_by_charge_id[ $charge_id ];
			$transaction['order']             = $this->build_list_order_info( $order );
			$transaction['payment_intent_id'] = (string) $order->get_meta( '_intent_id' );
		}
		unset( $transaction );

		return $response;
	}

	/**
	 * Add order context to a transaction detail response.
	 *
	 * @param array<string,mixed> $transaction Platform transaction.
	 * @return array<string,mixed>
	 */
	public function enrich_transaction_detail_response( array $transaction ): array {
		$charge_id = $this->get_charge_id_from_entity( $transaction );

		return $this->add_detail_order_info( $transaction, $charge_id );
	}

	/**
	 * Add order context to a charge detail response.
	 *
	 * @param array<string,mixed> $charge Platform charge.
	 * @return array<string,mixed>
	 */
	public function enrich_charge_response( array $charge ): array {
		return $this->add_detail_order_info( $charge, $this->get_charge_id_from_charge_response( $charge ) );
	}

	/**
	 * Add order context to a payment intent detail response and its embedded charges.
	 *
	 * @param array<string,mixed> $intent Platform payment intent.
	 * @return array<string,mixed>
	 */
	public function enrich_payment_intent_response( array $intent ): array {
		$intent = $this->add_detail_order_info( $intent, $this->get_charge_id_from_payment_intent( $intent ) );

		if ( isset( $intent['charge'] ) && is_array( $intent['charge'] ) ) {
			$intent['charge'] = $this->enrich_charge_response( $intent['charge'] );
		}

		if ( isset( $intent['charges'] ) && is_array( $intent['charges'] ) && isset( $intent['charges']['data'] ) && is_array( $intent['charges']['data'] ) ) {
			foreach ( $intent['charges']['data'] as &$charge ) {
				if ( is_array( $charge ) ) {
					$charge = $this->enrich_charge_response( $charge );
				}
			}
			unset( $charge );
		}

		return $intent;
	}

	/**
	 * Add order context to a dispute list response.
	 *
	 * @param array<string,mixed> $response Platform response.
	 * @return array<string,mixed>
	 */
	public function enrich_disputes_list_response( array $response ): array {
		if ( empty( $response['data'] ) || ! is_array( $response['data'] ) ) {
			return $response;
		}

		$charge_ids = array();
		foreach ( $response['data'] as $dispute ) {
			if ( is_array( $dispute ) ) {
				$charge_ids[] = $this->get_charge_id_from_entity( $dispute );
			}
		}

		$orders_by_charge_id = $this->get_orders_by_charge_ids( $charge_ids );

		foreach ( $response['data'] as &$dispute ) {
			if ( ! is_array( $dispute ) ) {
				continue;
			}

			try {
				$charge_id        = $this->get_charge_id_from_entity( $dispute );
				$dispute['order'] = null;

				if ( '' !== $charge_id && isset( $orders_by_charge_id[ $charge_id ] ) ) {
					$dispute['order'] = $this->build_list_order_info( $orders_by_charge_id[ $charge_id ] );
				}
			} catch ( Exception $exception ) {
				$this->log_enrichment_failure( $exception, 'dispute_list', $this->get_dispute_id_from_entity( $dispute ) );
				continue;
			}
		}
		unset( $dispute );

		return $response;
	}

	/**
	 * Add order context and formatted charge address to a dispute response.
	 *
	 * @param array<string,mixed> $dispute Platform dispute.
	 * @return array<string,mixed>
	 */
	public function enrich_dispute_response( array $dispute ): array {
		$charge_id = $this->get_charge_id_from_entity( $dispute );
		$dispute   = $this->add_detail_order_info( $dispute, $charge_id );

		if ( isset( $dispute['charge'] ) && is_array( $dispute['charge'] ) ) {
			$dispute['charge'] = $this->add_formatted_address_to_charge( $dispute['charge'], ', ' );
		}

		return $dispute;
	}

	/**
	 * Format raw fraud outcome platform rows with local WooCommerce order context.
	 *
	 * @param array<string|int,mixed> $response Platform response.
	 * @param array<string,mixed>     $params   Request params.
	 * @return array<int,array<string,mixed>>
	 */
	public function format_fraud_outcome_transactions( array $response, array $params ): array {
		$outcomes = isset( $response['data'] ) && is_array( $response['data'] ) ? $response['data'] : $response;
		$status   = isset( $params['status'] ) && is_scalar( $params['status'] ) ? (string) $params['status'] : '';
		$search   = isset( $params['search'] ) && is_array( $params['search'] ) ? $params['search'] : array();
		$results  = array();

		foreach ( $outcomes as $outcome ) {
			if ( ! is_array( $outcome ) ) {
				continue;
			}

			$formatted = $this->build_fraud_outcome_transactions_order_info( $outcome );
			if ( empty( $formatted ) ) {
				continue;
			}

			if ( ! $this->fraud_outcome_matches_status( $formatted, $status ) ) {
				continue;
			}

			if ( ! empty( $search ) && ! $this->fraud_outcome_matches_search( $formatted, $search ) ) {
				continue;
			}

			unset( $formatted['manual_review'] );

			$results[] = $formatted;
		}

		$this->sort_fraud_outcome_transactions( $results, $params );

		return $results;
	}

	/**
	 * Paginate formatted fraud outcome transactions.
	 *
	 * @param array<int,array<string,mixed>> $transactions Formatted transactions.
	 * @param array<string,mixed>            $params       Request params.
	 * @return array<int,array<string,mixed>>
	 */
	public function paginate_fraud_outcome_transactions( array $transactions, array $params ): array {
		$page      = isset( $params['page'] ) ? max( 1, (int) $params['page'] ) : 1;
		$page_size = isset( $params['pagesize'] ) ? max( 1, (int) $params['pagesize'] ) : 25;

		return array_slice( $transactions, ( $page - 1 ) * $page_size, $page_size );
	}

	/**
	 * Summarize formatted fraud outcome transactions.
	 *
	 * @param array<int,array<string,mixed>> $transactions Formatted transactions.
	 * @return array<string,mixed>
	 */
	public function summarize_fraud_outcome_transactions( array $transactions ): array {
		$total      = 0;
		$currencies = array();

		foreach ( $transactions as $transaction ) {
			$total += isset( $transaction['amount'] ) && is_numeric( $transaction['amount'] ) ? (int) $transaction['amount'] : 0;

			if ( isset( $transaction['currency'] ) && is_scalar( $transaction['currency'] ) ) {
				$currencies[] = strtolower( (string) $transaction['currency'] );
			}
		}

		return array(
			'count'      => count( $transactions ),
			'total'      => $total,
			'currencies' => array_values( array_unique( $currencies ) ),
		);
	}

	/**
	 * Build fraud outcome search autocomplete results.
	 *
	 * @param array<int,array<string,mixed>> $transactions Formatted transactions.
	 * @param string                         $search_term  Search term.
	 * @return array<int,array<string,string>>
	 */
	public function get_fraud_outcome_transactions_search_autocomplete( array $transactions, string $search_term ): array {
		$results = array_values(
			array_map(
				static function ( array $transaction ): array {
					$order_id      = isset( $transaction['order_id'] ) && is_scalar( $transaction['order_id'] ) ? (string) $transaction['order_id'] : '';
					$customer_name = isset( $transaction['customer_name'] ) && is_scalar( $transaction['customer_name'] ) ? (string) $transaction['customer_name'] : '';

					return array(
						'key'   => 'customer-' . $order_id,
						'label' => $customer_name,
					);
				},
				$transactions
			)
		);

		$order = wc_get_order( $search_term );
		if ( $order instanceof WC_Order ) {
			$prefix = function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order ) ? __( 'Subscription #', 'woocommerce' ) : __( 'Order #', 'woocommerce' );
			array_unshift(
				$results,
				array(
					'key'   => 'order-' . $order->get_id(),
					'label' => $prefix . $search_term,
				)
			);
		}

		return $results;
	}

	/**
	 * Build a fraud outcome transaction from platform and order data.
	 *
	 * @param array<string,mixed> $outcome Fraud outcome row.
	 * @return array<string,mixed>|null
	 */
	private function build_fraud_outcome_transactions_order_info( array $outcome ): ?array {
		$order_id = isset( $outcome['order_id'] ) && is_scalar( $outcome['order_id'] ) ? (string) $outcome['order_id'] : '';
		$order    = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order || 'shop_order_refund' === $order->get_type() ) {
			return null;
		}

		$payment_intent_id                   = isset( $outcome['payment_intent_id'] ) && is_scalar( $outcome['payment_intent_id'] )
			? (string) $outcome['payment_intent_id']
			: (string) $order->get_meta( '_intent_id' );
		$outcome['payment_intent']           = array();
		$outcome['payment_intent']['id']     = '' !== $payment_intent_id ? $payment_intent_id : $order->get_transaction_id();
		$outcome['payment_intent']['status'] = (string) $order->get_meta( '_intention_status' );

		$outcome['amount']              = $this->order_data_service->prepare_amount( (float) $order->get_total(), (string) $order->get_currency() );
		$outcome['currency']            = $order->get_currency();
		$outcome['customer_name']       = wc_clean( $order->get_formatted_billing_full_name() );
		$outcome['manual_review']       = $order->get_meta( '_wcpay_fraud_outcome_manual_entry' );
		$outcome['fraud_meta_box_type'] = $order->get_meta( '_wcpay_fraud_meta_box_type' );

		unset( $outcome['payment_intent_id'] );

		return $outcome;
	}

	/**
	 * Tell whether a fraud outcome row matches the requested status.
	 *
	 * @param array<string,mixed> $outcome Fraud outcome row.
	 * @param string              $status  Requested fraud outcome status.
	 * @return bool
	 */
	private function fraud_outcome_matches_status( array $outcome, string $status ): bool {
		$intent_status       = isset( $outcome['payment_intent']['status'] ) && is_scalar( $outcome['payment_intent']['status'] ) ? (string) $outcome['payment_intent']['status'] : '';
		$manual_review       = $outcome['manual_review'] ?? '';
		$fraud_meta_box_type = isset( $outcome['fraud_meta_box_type'] ) && is_scalar( $outcome['fraud_meta_box_type'] ) ? (string) $outcome['fraud_meta_box_type'] : '';

		if ( 'review' === $status ) {
			return 'requires_capture' === $intent_status && empty( $manual_review ) && 'review' === $fraud_meta_box_type;
		}

		if ( 'block' === $status ) {
			return in_array( $fraud_meta_box_type, array( 'block', 'review_blocked' ), true );
		}

		return true;
	}

	/**
	 * Tell whether a fraud outcome row matches any search term.
	 *
	 * @param array<string,mixed> $outcome Fraud outcome row.
	 * @param array<int,mixed>    $search  Search terms.
	 * @return bool
	 */
	private function fraud_outcome_matches_search( array $outcome, array $search ): bool {
		foreach ( $search as $term ) {
			if ( $this->fraud_outcome_matches_search_term( $outcome, is_scalar( $term ) ? (string) $term : '' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Tell whether a fraud outcome row matches one search term.
	 *
	 * @param array<string,mixed> $outcome Fraud outcome row.
	 * @param string              $term    Search term.
	 * @return bool
	 */
	private function fraud_outcome_matches_search_term( array $outcome, string $term ): bool {
		if ( '' === $term ) {
			return false;
		}

		if ( preg_match( '/#(\d+)/', $term, $matches ) ) {
			$order_id = isset( $outcome['order_id'] ) && is_scalar( $outcome['order_id'] ) ? (string) $outcome['order_id'] : '';

			return $matches[1] === $order_id;
		}

		$customer_name = isset( $outcome['customer_name'] ) && is_scalar( $outcome['customer_name'] ) ? (string) $outcome['customer_name'] : '';

		return 1 === preg_match( '/' . preg_quote( $term, '/' ) . '/i', $customer_name );
	}

	/**
	 * Sort fraud outcome transactions in place.
	 *
	 * @param array<int,array<string,mixed>> $transactions Fraud outcome transactions.
	 * @param array<string,mixed>            $params       Request params.
	 */
	private function sort_fraud_outcome_transactions( array &$transactions, array $params ): void {
		$sort      = isset( $params['sort'] ) && is_scalar( $params['sort'] ) ? (string) $params['sort'] : 'date';
		$direction = isset( $params['direction'] ) && is_scalar( $params['direction'] ) ? (string) $params['direction'] : 'desc';
		$key       = 'date' === $sort ? 'created' : $sort;

		usort(
			$transactions,
			static function ( array $a, array $b ) use ( $key, $direction ): int {
				if ( ! array_key_exists( $key, $a ) || ! array_key_exists( $key, $b ) ) {
					return 0;
				}

				if ( $a[ $key ] === $b[ $key ] ) {
					return 0;
				}

				if ( 'desc' === $direction ) {
					return $a[ $key ] < $b[ $key ] ? 1 : -1;
				}

				return $a[ $key ] < $b[ $key ] ? -1 : 1;
			}
		);
	}

	/**
	 * Map legacy search tokens to WooPayments charge IDs.
	 *
	 * @param array<int,mixed> $search Raw search terms.
	 * @return array<int,mixed>
	 */
	private function map_search_orders_to_charge_ids( array $search ): array {
		$terms = array();

		foreach ( $search as $term ) {
			$charge_ids = $this->get_charge_ids_from_search_term( is_scalar( $term ) ? (string) $term : '' );

			if ( empty( $charge_ids ) ) {
				$terms[] = $term;
				continue;
			}

			foreach ( $charge_ids as $charge_id ) {
				$terms[] = $charge_id;
			}
		}

		return $terms;
	}

	/**
	 * Get charge IDs from a legacy Order/Subscription search token.
	 *
	 * @param string $term Search term.
	 * @return array<int,string>
	 */
	private function get_charge_ids_from_search_term( string $term ): array {
		$order_prefix = __( 'Order #', 'woocommerce' );
		if ( 0 === strpos( $term, $order_prefix ) ) {
			$order_id = substr( $term, strlen( $order_prefix ) );
			$order    = wc_get_order( $order_id );

			if ( $order instanceof WC_Order ) {
				$charge_id = (string) $order->get_meta( '_charge_id' );

				return '' === $charge_id ? array() : array( $charge_id );
			}
		}

		$subscription_prefix = __( 'Subscription #', 'woocommerce' );
		if ( function_exists( 'wcs_get_subscription' ) && 0 === strpos( $term, $subscription_prefix ) ) {
			$subscription_id = substr( $term, strlen( $subscription_prefix ) );
			$subscription    = wcs_get_subscription( $subscription_id );

			if ( is_object( $subscription ) && is_callable( array( $subscription, 'get_related_orders' ) ) ) {
				$related_orders = call_user_func( array( $subscription, 'get_related_orders' ), 'all' );
				if ( ! is_iterable( $related_orders ) ) {
					return array();
				}

				$charge_ids = array();
				foreach ( $related_orders as $order ) {
					$charge_ids[] = $order instanceof WC_Order ? (string) $order->get_meta( '_charge_id' ) : '';
				}

				return array_values( array_filter( $charge_ids ) );
			}
		}

		return array();
	}

	/**
	 * Get orders keyed by charge ID.
	 *
	 * @param array<int,string> $charge_ids Charge IDs.
	 * @return array<string,WC_Order>
	 */
	private function get_orders_by_charge_ids( array $charge_ids ): array {
		$charge_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'strval', $charge_ids )
				)
			)
		);

		if ( empty( $charge_ids ) ) {
			return array();
		}

		$orders = wc_get_orders(
			array(
				'limit'        => count( $charge_ids ),
				'meta_key'     => '_charge_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'   => $charge_ids, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_compare' => 'IN',
			)
		);

		if ( ! is_iterable( $orders ) ) {
			return array();
		}

		$orders_by_charge_id = array();
		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			$charge_id = (string) $order->get_meta( '_charge_id' );
			if ( '' !== $charge_id ) {
				$orders_by_charge_id[ $charge_id ] = $order;
			}
		}

		return $orders_by_charge_id;
	}

	/**
	 * Add detail order info to an entity.
	 *
	 * @param array<string,mixed> $entity    Entity.
	 * @param string              $charge_id Charge ID.
	 * @return array<string,mixed>
	 */
	private function add_detail_order_info( array $entity, string $charge_id ): array {
		$entity['order'] = array();
		$order           = $this->get_order_by_charge_id( $charge_id );

		if ( $order instanceof WC_Order ) {
			$entity['order'] = $this->build_detail_order_info( $order );
		}

		return $entity;
	}

	/**
	 * Get one order by charge ID.
	 *
	 * @param string $charge_id Charge ID.
	 * @return WC_Order|null
	 */
	private function get_order_by_charge_id( string $charge_id ): ?WC_Order {
		$orders_by_charge_id = $this->get_orders_by_charge_ids( array( $charge_id ) );

		return $orders_by_charge_id[ $charge_id ] ?? null;
	}

	/**
	 * Get a charge ID from a charge response.
	 *
	 * @param array<string,mixed> $charge Charge response.
	 * @return string
	 */
	private function get_charge_id_from_charge_response( array $charge ): string {
		if ( isset( $charge['id'] ) && is_scalar( $charge['id'] ) ) {
			return (string) $charge['id'];
		}

		return $this->get_charge_id_from_entity( $charge );
	}

	/**
	 * Get a charge ID from a payment intent response.
	 *
	 * @param array<string,mixed> $intent Payment intent response.
	 * @return string
	 */
	private function get_charge_id_from_payment_intent( array $intent ): string {
		if ( isset( $intent['charge'] ) && is_scalar( $intent['charge'] ) ) {
			return (string) $intent['charge'];
		}

		if ( isset( $intent['charge'] ) && is_array( $intent['charge'] ) ) {
			return $this->get_charge_id_from_charge_response( $intent['charge'] );
		}

		if ( ! isset( $intent['charges'] ) || ! is_array( $intent['charges'] ) || ! isset( $intent['charges']['data'] ) || ! is_array( $intent['charges']['data'] ) ) {
			return '';
		}

		foreach ( $intent['charges']['data'] as $charge ) {
			if ( is_array( $charge ) ) {
				$charge_id = $this->get_charge_id_from_charge_response( $charge );
				if ( '' !== $charge_id ) {
					return $charge_id;
				}
			}
		}

		return '';
	}

	/**
	 * Build the compact order info used by legacy list rows.
	 *
	 * @param WC_Order $order Order.
	 * @return array<string,mixed>
	 */
	private function build_list_order_info( WC_Order $order ): array {
		$order_info = array(
			'number'       => $order->get_order_number(),
			'url'          => $order->get_edit_order_url(),
			'customer_url' => $this->get_customer_url( $order ),
		);

		return $this->add_subscription_info( $order_info, $order );
	}

	/**
	 * Build the richer order info used by legacy detail responses.
	 *
	 * @param WC_Order $order Order.
	 * @return array<string,mixed>
	 */
	private function build_detail_order_info( WC_Order $order ): array {
		$order_info = array(
			'id'                     => $order->get_id(),
			'number'                 => $order->get_order_number(),
			'url'                    => $order->get_edit_order_url(),
			'customer_url'           => $this->get_customer_url( $order ),
			'customer_name'          => trim( $order->get_formatted_billing_full_name() ),
			'customer_email'         => $order->get_billing_email(),
			'fraud_meta_box_type'    => $order->get_meta( '_wcpay_fraud_meta_box_type' ),
			'ip_address'             => $order->get_customer_ip_address(),
			'suggested_product_type' => $this->determine_suggested_product_type( $order ),
		);

		return $this->add_subscription_info( $order_info, $order );
	}

	/**
	 * Add subscription links to order info when WooCommerce Subscriptions is active.
	 *
	 * @param array<string,mixed> $order_info Order info.
	 * @param WC_Order            $order      Order.
	 * @return array<string,mixed>
	 */
	private function add_subscription_info( array $order_info, WC_Order $order ): array {
		if ( ! function_exists( 'wcs_get_subscriptions_for_order' ) ) {
			return $order_info;
		}

		$order_info['subscriptions'] = array();
		$subscriptions               = wcs_get_subscriptions_for_order( $order, array( 'order_type' => array( 'parent', 'renewal' ) ) );

		foreach ( $subscriptions as $subscription ) {
			if ( ! $subscription instanceof WC_Order ) {
				continue;
			}

			$order_info['subscriptions'][] = array(
				'number' => $subscription->get_order_number(),
				'url'    => $subscription->get_edit_order_url(),
			);
		}

		return $order_info;
	}

	/**
	 * Get WooCommerce Analytics customer URL.
	 *
	 * @param WC_Order $order Order.
	 * @return string|null
	 */
	private function get_customer_url( WC_Order $order ): ?string {
		$customer_id = DataStore::get_existing_customer_id_from_order( $order );

		if ( ! $customer_id ) {
			return null;
		}

		return add_query_arg(
			array(
				'page'      => 'wc-admin',
				'path'      => '/customers',
				'filter'    => 'single_customer',
				'customers' => $customer_id,
			),
			'admin.php'
		);
	}

	/**
	 * Add a formatted billing address to an embedded charge object.
	 *
	 * @param array<string,mixed> $charge    Charge.
	 * @param string              $separator Address separator.
	 * @return array<string,mixed>
	 */
	private function add_formatted_address_to_charge( array $charge, string $separator = '<br/>' ): array {
		if ( empty( $charge['billing_details'] ) || ! is_array( $charge['billing_details'] ) ) {
			return $charge;
		}

		$address = $charge['billing_details']['address'] ?? array();
		if ( ! is_array( $address ) ) {
			return $charge;
		}

		$charge['billing_details']['formatted_address'] = WC()->countries->get_formatted_address(
			array(
				'city'      => ! empty( $address['city'] ) ? (string) $address['city'] : '',
				'country'   => ! empty( $address['country'] ) ? (string) $address['country'] : '',
				'address_1' => ! empty( $address['line1'] ) ? (string) $address['line1'] : '',
				'address_2' => ! empty( $address['line2'] ) ? (string) $address['line2'] : '',
				'postcode'  => ! empty( $address['postal_code'] ) ? (string) $address['postal_code'] : '',
				'state'     => ! empty( $address['state'] ) ? (string) $address['state'] : '',
			),
			$separator
		);

		return $charge;
	}

	/**
	 * Determine a dispute evidence product type hint from order items.
	 *
	 * @param WC_Order $order Order.
	 * @return string
	 */
	private function determine_suggested_product_type( WC_Order $order ): string {
		$product_count    = 0;
		$virtual_products = 0;

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();
			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			++$product_count;

			if ( $product->is_virtual() ) {
				++$virtual_products;
			}
		}

		if ( $product_count > 1 ) {
			return 'multiple';
		}

		if ( 1 === $virtual_products ) {
			return 'digital_product_or_service';
		}

		return 'physical_product';
	}

	/**
	 * Get a charge ID from a transaction/dispute entity.
	 *
	 * @param array<string,mixed> $entity Entity.
	 * @return string
	 */
	private function get_charge_id_from_entity( array $entity ): string {
		if ( isset( $entity['charge_id'] ) && is_scalar( $entity['charge_id'] ) ) {
			return (string) $entity['charge_id'];
		}

		if ( isset( $entity['charge'] ) && is_scalar( $entity['charge'] ) ) {
			return (string) $entity['charge'];
		}

		if ( isset( $entity['charge'] ) && is_array( $entity['charge'] ) && isset( $entity['charge']['id'] ) && is_scalar( $entity['charge']['id'] ) ) {
			return (string) $entity['charge']['id'];
		}

		return '';
	}

	/**
	 * Get a dispute ID from an entity.
	 *
	 * @param array<string,mixed> $entity Entity.
	 * @return string
	 */
	private function get_dispute_id_from_entity( array $entity ): string {
		if ( isset( $entity['dispute_id'] ) && is_scalar( $entity['dispute_id'] ) ) {
			return (string) $entity['dispute_id'];
		}

		if ( isset( $entity['id'] ) && is_scalar( $entity['id'] ) ) {
			return (string) $entity['id'];
		}

		return '';
	}

	/**
	 * Log a non-fatal response enrichment failure.
	 *
	 * @param Exception $exception Exception.
	 * @param string    $context   Enrichment context.
	 * @param string    $entity_id Entity ID.
	 */
	private function log_enrichment_failure( Exception $exception, string $context, string $entity_id ): void {
		wc_get_logger()->error(
			'Error adding WooCommerce order info to WooPayments response: ' . $exception->getMessage(),
			array(
				'source'    => self::LOG_SOURCE,
				'context'   => $context,
				'entity_id' => $entity_id,
			)
		);
	}
}
