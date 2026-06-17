<?php
/**
 * WooPaymentsCustomerService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use WC_Customer;
use WC_Order;

/**
 * Core-owned customer persistence for the native WooPayments runtime.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsCustomerService {

	/**
	 * Deprecated customer ID option key.
	 */
	public const DEPRECATED_CUSTOMER_ID_OPTION = '_wcpay_customer_id';

	/**
	 * Live-mode customer ID option key.
	 */
	public const LIVE_CUSTOMER_ID_OPTION = '_wcpay_customer_id_live';

	/**
	 * Test-mode customer ID option key.
	 */
	public const TEST_CUSTOMER_ID_OPTION = '_wcpay_customer_id_test';

	/**
	 * Session key used for guest customer IDs.
	 */
	public const CUSTOMER_ID_SESSION_KEY = 'wcpay_customer_id';

	/**
	 * Native API client.
	 *
	 * @var WooPaymentsApiClient
	 */
	private WooPaymentsApiClient $api_client;

	/**
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsApiClient      $api_client      Native API client.
	 * @param WooPaymentsAccountService $account_service WooPayments account service.
	 */
	final public function init( WooPaymentsApiClient $api_client, WooPaymentsAccountService $account_service ): void {
		$this->api_client      = $api_client;
		$this->account_service = $account_service;
	}

	/**
	 * Get or create the WooPayments customer ID associated with an order.
	 *
	 * @param WC_Order $order Order being charged.
	 * @return string
	 */
	public function get_or_create_customer_id_for_order( WC_Order $order ): string {
		$order_customer_id = (string) $order->get_meta( '_stripe_customer_id', true );
		if ( '' !== $order_customer_id ) {
			return $this->update_customer_for_order( $order_customer_id, $order );
		}

		$user_id     = $this->get_order_user_id( $order );
		$customer_id = $this->get_customer_id_by_user_id( $user_id );

		if ( null !== $customer_id ) {
			return $this->update_customer_for_order( $customer_id, $order );
		}

		$customer_id = $this->api_client->create_customer( $this->map_customer_data( $order ) );
		$this->persist_customer_id( $user_id, $customer_id );

		return $customer_id;
	}

	/**
	 * Update the WooPayments customer associated with an order.
	 *
	 * If the remote customer no longer exists, recreate it and update local persistence.
	 *
	 * @param string   $customer_id WooPayments customer ID.
	 * @param WC_Order $order       Order being charged.
	 * @return string Updated or recreated WooPayments customer ID.
	 * @throws WooPaymentsApiException When updating the remote customer fails for reasons other than a missing customer.
	 */
	public function update_customer_for_order( string $customer_id, WC_Order $order ): string {
		$user_id       = $this->get_order_user_id( $order );
		$customer_data = $this->map_customer_data( $order );

		try {
			$this->api_client->update_customer( $customer_id, $customer_data );
			$this->persist_customer_id( $user_id, $customer_id );

			return $customer_id;
		} catch ( WooPaymentsApiException $exception ) {
			if ( 'resource_missing' === $exception->get_error_code() ) {
				return $this->recreate_customer_for_order( $order );
			}

			throw $exception;
		}
	}

	/**
	 * Recreate the WooPayments customer associated with an order.
	 *
	 * @param WC_Order $order Order being charged.
	 * @return string
	 */
	public function recreate_customer_for_order( WC_Order $order ): string {
		$user_id = $this->get_order_user_id( $order );
		$this->delete_customer_id( $user_id );

		$customer_id = $this->api_client->create_customer( $this->map_customer_data( $order ) );
		$this->persist_customer_id( $user_id, $customer_id );

		return $customer_id;
	}

	/**
	 * Get or create the WooPayments customer ID associated with a WordPress user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return string
	 */
	public function get_or_create_customer_id_for_user( int $user_id ): string {
		$customer_id = $this->get_customer_id_by_user_id( $user_id );
		if ( null !== $customer_id ) {
			return $customer_id;
		}

		$customer_id = $this->api_client->create_customer( $this->map_customer_data_for_user( $user_id ) );
		$this->persist_customer_id( $user_id, $customer_id );

		return $customer_id;
	}

	/**
	 * Map WooCommerce order data to the WooPayments customer payload.
	 *
	 * @param WC_Order $order Order being charged.
	 * @return array<string,mixed>
	 */
	public function map_customer_data( WC_Order $order ): array {
		$user_id     = $this->get_order_user_id( $order );
		$user        = $user_id > 0 ? get_user_by( 'id', $user_id ) : false;
		$wc_customer = new WC_Customer( $user_id > 0 ? $user_id : 0 );
		$name        = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

		/* translators: 1: customer full name, 2: WordPress username. */
		$registered_customer_description = sprintf( __( 'Name: %1$s, Username: %2$s', 'woocommerce' ), $name, $wc_customer->get_username() );
		/* translators: %s: customer full name. */
		$guest_customer_description = sprintf( __( 'Name: %1$s, Guest', 'woocommerce' ), $name );

		$customer_data = array(
			'name'        => $name,
			'description' => $user ? $registered_customer_description : $guest_customer_description,
			'email'       => $order->get_billing_email(),
			'phone'       => $order->get_billing_phone(),
			'address'     => array(
				'line1'       => $order->get_billing_address_1(),
				'line2'       => $order->get_billing_address_2(),
				'postal_code' => $order->get_billing_postcode(),
				'city'        => $order->get_billing_city(),
				'state'       => $order->get_billing_state(),
				'country'     => $order->get_billing_country(),
			),
		);

		if ( '' !== $order->get_shipping_postcode() ) {
			$customer_data['shipping'] = array(
				'name'    => trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() ),
				'address' => array(
					'line1'       => $order->get_shipping_address_1(),
					'line2'       => $order->get_shipping_address_2(),
					'postal_code' => $order->get_shipping_postcode(),
					'city'        => $order->get_shipping_city(),
					'state'       => $order->get_shipping_state(),
					'country'     => $order->get_shipping_country(),
				),
			);
		}

		return $customer_data;
	}

	/**
	 * Map WordPress customer data to the WooPayments customer payload.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array<string,mixed>
	 */
	private function map_customer_data_for_user( int $user_id ): array {
		$customer = new WC_Customer( $user_id );
		$user     = get_user_by( 'id', $user_id );
		$name     = trim( $customer->get_first_name() . ' ' . $customer->get_last_name() );

		/* translators: 1: customer full name, 2: WordPress username. */
		$description = sprintf( __( 'Name: %1$s, Username: %2$s', 'woocommerce' ), $name, $customer->get_username() );

		return array(
			'name'        => $name,
			'description' => $description,
			'email'       => $user ? $user->user_email : $customer->get_email(),
			'phone'       => $customer->get_billing_phone(),
			'address'     => array(
				'line1'       => $customer->get_billing_address_1(),
				'line2'       => $customer->get_billing_address_2(),
				'postal_code' => $customer->get_billing_postcode(),
				'city'        => $customer->get_billing_city(),
				'state'       => $customer->get_billing_state(),
				'country'     => $customer->get_billing_country(),
			),
		);
	}

	/**
	 * Get the persisted customer ID for a user or guest checkout.
	 *
	 * @param int|null $user_id WordPress user ID or null for guests.
	 * @return string|null
	 */
	private function get_customer_id_by_user_id( ?int $user_id ): ?string {
		if ( null === $user_id || 0 === $user_id ) {
			$customer_id = WC()->session ? WC()->session->get( self::CUSTOMER_ID_SESSION_KEY ) : null;
			return is_string( $customer_id ) && '' !== $customer_id ? $customer_id : null;
		}

		$customer_id = get_user_option( $this->get_customer_id_option(), $user_id );
		if ( false === $customer_id ) {
			$this->maybe_migrate_deprecated_customer_id( $user_id );
			$customer_id = get_user_option( $this->get_customer_id_option(), $user_id );
		}

		return is_string( $customer_id ) && '' !== $customer_id ? $customer_id : null;
	}

	/**
	 * Persist a WooPayments customer ID for a user or guest session.
	 *
	 * @param int|null $user_id     WordPress user ID or null for guests.
	 * @param string   $customer_id WooPayments customer ID.
	 */
	private function persist_customer_id( ?int $user_id, string $customer_id ): void {
		if ( null !== $user_id && 0 !== $user_id ) {
			update_user_option( $user_id, $this->get_customer_id_option(), $customer_id );
		}

		if ( WC()->session ) {
			WC()->session->set( self::CUSTOMER_ID_SESSION_KEY, $customer_id );
		}
	}

	/**
	 * Delete a persisted customer ID for a user or guest session.
	 *
	 * @param int|null $user_id WordPress user ID or null for guests.
	 */
	private function delete_customer_id( ?int $user_id ): void {
		if ( null !== $user_id && 0 !== $user_id ) {
			delete_user_option( $user_id, $this->get_customer_id_option() );
			delete_user_option( $user_id, self::DEPRECATED_CUSTOMER_ID_OPTION );
		}

		if ( WC()->session ) {
			WC()->session->set( self::CUSTOMER_ID_SESSION_KEY, null );
		}
	}

	/**
	 * Migrate the deprecated WooPayments customer ID option to the current mode-aware key.
	 *
	 * @param int $user_id WordPress user ID.
	 */
	private function maybe_migrate_deprecated_customer_id( int $user_id ): void {
		$customer_id = get_user_option( self::DEPRECATED_CUSTOMER_ID_OPTION, $user_id );
		if ( ! is_string( $customer_id ) || '' === $customer_id ) {
			return;
		}

		update_user_option( $user_id, $this->get_customer_id_option(), $customer_id );
		delete_user_option( $user_id, self::DEPRECATED_CUSTOMER_ID_OPTION );
	}

	/**
	 * Get the mode-aware user option key for WooPayments customer IDs.
	 *
	 * @return string
	 */
	private function get_customer_id_option(): string {
		return $this->account_service->is_test_mode_enabled() ? self::TEST_CUSTOMER_ID_OPTION : self::LIVE_CUSTOMER_ID_OPTION;
	}

	/**
	 * Get the order's associated WordPress user ID when present.
	 *
	 * @param WC_Order $order Order being charged.
	 * @return int|null
	 */
	private function get_order_user_id( WC_Order $order ): ?int {
		$user_id = (int) $order->get_customer_id();

		return $user_id > 0 ? $user_id : null;
	}
}
