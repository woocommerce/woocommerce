<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use Automattic\WooCommerce\Internal\POS\Actors\AccessProfileRegistry;
use Automattic\WooCommerce\Internal\POS\OrderAttribution;
use Automattic\WooCommerce\Internal\StoreActors\ActorAccessRepository;
use Automattic\WooCommerce\Internal\StoreActors\ActorRepository;
use Automattic\WooCommerce\Tests\Internal\StoreActors\Concerns\EnablesActorsFeature;
use WC_Order;
use WC_Unit_Test_Case;
use WP_Error;
use WP_REST_Request;

/**
 * @since 10.9.0
 * @group pos-actors
 */
class OrderAttributionTest extends WC_Unit_Test_Case {

	use EnablesActorsFeature;

	private OrderAttribution $attribution;
	private ActorRepository $actors;
	private ActorAccessRepository $access;

	public function setUp(): void {
		parent::setUp();
		$this->install_actor_tables();
		$this->attribution = wc_get_container()->get( OrderAttribution::class );
		$this->actors      = wc_get_container()->get( ActorRepository::class );
		$this->access      = wc_get_container()->get( ActorAccessRepository::class );

		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( 'DELETE FROM ' . $this->access->get_table_name() );
		$wpdb->query( 'DELETE FROM ' . $this->actors->get_table_name() );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	private function make_actor( string $profile_key, string $name = 'Test' ): int {
		$id = $this->actors->insert( array( 'display_name' => $name ) );
		$this->access->insert(
			array(
				'actor_id'           => $id,
				'access_profile_key' => $profile_key,
			)
		);
		return $id;
	}

	private function make_order_with_meta( array $meta ): WC_Order {
		$order = new WC_Order();
		foreach ( $meta as $k => $v ) {
			$order->add_meta_data( $k, $v, true );
		}
		return $order;
	}

	private function invoke_pre_insert( WC_Order $order ) {
		return $this->attribution->handle_pre_insert( $order, new WP_REST_Request(), true );
	}

	public function test_no_pos_meta_passes_through(): void {
		$order  = new WC_Order();
		$result = $this->invoke_pre_insert( $order );
		$this->assertSame( $order, $result );
	}

	public function test_unknown_actor_is_rejected(): void {
		$order  = $this->make_order_with_meta( array( OrderAttribution::META_KEY_STAFF_ID => 999999 ) );
		$result = $this->invoke_pre_insert( $order );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_invalid_attribution', $result->get_error_code() );
	}

	public function test_inactive_actor_is_rejected(): void {
		$id = $this->make_actor( AccessProfileRegistry::PROFILE_CASHIER );
		$this->actors->update( $id, array( 'status' => ActorRepository::STATUS_INACTIVE ) );

		$order  = $this->make_order_with_meta( array( OrderAttribution::META_KEY_STAFF_ID => $id ) );
		$result = $this->invoke_pre_insert( $order );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_valid_attribution_passes(): void {
		$id     = $this->make_actor( AccessProfileRegistry::PROFILE_CASHIER );
		$order  = $this->make_order_with_meta( array( OrderAttribution::META_KEY_STAFF_ID => $id ) );
		$result = $this->invoke_pre_insert( $order );
		$this->assertSame( $order, $result );
	}

	public function test_partial_override_is_rejected(): void {
		$id    = $this->make_actor( AccessProfileRegistry::PROFILE_CASHIER );
		$order = $this->make_order_with_meta(
			array(
				OrderAttribution::META_KEY_STAFF_ID          => $id,
				OrderAttribution::META_KEY_OVERRIDE_STAFF_ID => 5,
				// Missing override reason.
			)
		);
		$result = $this->invoke_pre_insert( $order );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_invalid_override', $result->get_error_code() );
	}

	public function test_self_override_is_rejected(): void {
		$id    = $this->make_actor( AccessProfileRegistry::PROFILE_MANAGER );
		$order = $this->make_order_with_meta(
			array(
				OrderAttribution::META_KEY_STAFF_ID          => $id,
				OrderAttribution::META_KEY_OVERRIDE_STAFF_ID => $id,
				OrderAttribution::META_KEY_OVERRIDE_REASON   => AccessProfileRegistry::TAG_REFUND_ORDERS,
			)
		);
		$result = $this->invoke_pre_insert( $order );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_self_override', $result->get_error_code() );
	}

	public function test_unsupported_override_reason_is_rejected(): void {
		$cashier = $this->make_actor( AccessProfileRegistry::PROFILE_CASHIER, 'Alex' );
		$manager = $this->make_actor( AccessProfileRegistry::PROFILE_MANAGER, 'Morgan' );

		$order = $this->make_order_with_meta(
			array(
				OrderAttribution::META_KEY_STAFF_ID          => $cashier,
				OrderAttribution::META_KEY_OVERRIDE_STAFF_ID => $manager,
				OrderAttribution::META_KEY_OVERRIDE_REASON   => 'edit_pos_settings',
			)
		);
		$result = $this->invoke_pre_insert( $order );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_cashier_cannot_approve_override(): void {
		$cashier_a = $this->make_actor( AccessProfileRegistry::PROFILE_CASHIER, 'Alex' );
		$cashier_b = $this->make_actor( AccessProfileRegistry::PROFILE_CASHIER, 'Bobby' );

		$order = $this->make_order_with_meta(
			array(
				OrderAttribution::META_KEY_STAFF_ID          => $cashier_a,
				OrderAttribution::META_KEY_OVERRIDE_STAFF_ID => $cashier_b,
				OrderAttribution::META_KEY_OVERRIDE_REASON   => AccessProfileRegistry::TAG_REFUND_ORDERS,
			)
		);
		$result = $this->invoke_pre_insert( $order );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_override_forbidden', $result->get_error_code() );
	}

	public function test_manager_can_approve_cashier_override(): void {
		$cashier = $this->make_actor( AccessProfileRegistry::PROFILE_CASHIER, 'Alex' );
		$manager = $this->make_actor( AccessProfileRegistry::PROFILE_MANAGER, 'Morgan' );

		$order = $this->make_order_with_meta(
			array(
				OrderAttribution::META_KEY_STAFF_ID          => $cashier,
				OrderAttribution::META_KEY_OVERRIDE_STAFF_ID => $manager,
				OrderAttribution::META_KEY_OVERRIDE_REASON   => AccessProfileRegistry::TAG_REFUND_ORDERS,
			)
		);
		$result = $this->invoke_pre_insert( $order );
		$this->assertSame( $order, $result );
	}
}
