<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Coupon_Data_Store_CPT class.
 *
 * @package WooCommerce\Tests\DataStores
 */

/**
 * Class WC_Coupon_Data_Store_CPT_Test.
 *
 * Covers the woocommerce_coupon_updated_props action (per-save payload) and its
 * deprecated predecessor woocommerce_coupon_object_updated_props (payload
 * accumulated across saves through one data store instance). Tests without a
 * listener on the deprecated action also implicitly verify that saving a coupon
 * triggers no deprecation notice, because the WordPress test case fails on any
 * unexpected deprecation.
 */
class WC_Coupon_Data_Store_CPT_Test extends WC_Unit_Test_Case {

	/**
	 * The payload of every woocommerce_coupon_updated_props fire, in order.
	 *
	 * @var array[]
	 */
	private $captured_payloads = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->captured_payloads = array();
	}

	/**
	 * Record the payload of every woocommerce_coupon_updated_props fire.
	 *
	 * Registered at priority 10 so that it observes the outer payload before any
	 * listener registered at a later priority can trigger a nested save.
	 */
	private function capture_updated_props(): void {
		add_action(
			'woocommerce_coupon_updated_props',
			function ( $coupon, $updated_props ) {
				$this->captured_payloads[] = $updated_props;
			},
			10,
			2
		);
	}

	/**
	 * Create a coupon and save it once before any assertion.
	 *
	 * WC_Helper_Coupon::create_coupon() writes every mapped meta key except
	 * date_expires (it writes the legacy expiry_date instead). On the first save
	 * that absent row is created with a null value, which counts as an update and
	 * puts 'date_expires' in the payload. This throwaway save creates the row, and
	 * reloading the coupon gives the assertions a fresh data store without that
	 * throwaway write in its updated-props accumulator.
	 *
	 * @return WC_Coupon
	 */
	private function create_settled_coupon(): WC_Coupon {
		$coupon = WC_Helper_Coupon::create_coupon( 'updated-props-test' );
		$coupon->save();

		return new WC_Coupon( $coupon->get_id() );
	}

	/**
	 * @testdox Should not accumulate props across repeated saves of the same coupon object.
	 */
	public function test_updated_props_do_not_accumulate_across_saves(): void {
		$coupon = $this->create_settled_coupon();
		$this->capture_updated_props();

		$coupon->set_amount( 5 );
		$coupon->save();

		$coupon->set_amount( 10 );
		$coupon->save();

		$this->assertSame(
			array( array( 'amount' ), array( 'amount' ) ),
			$this->captured_payloads,
			'Each save should report only the props that save changed, with no duplicates carried over.'
		);
	}

	/**
	 * @testdox Should report an empty prop list for a save that changes nothing.
	 */
	public function test_no_op_save_reports_no_updated_props(): void {
		$coupon = $this->create_settled_coupon();
		$this->capture_updated_props();

		$coupon->set_amount( 5 );
		$coupon->save();

		$coupon->save();

		$this->assertSame(
			array( array( 'amount' ), array() ),
			$this->captured_payloads,
			'A save that changes nothing must not report props left over from an earlier save.'
		);
	}

	/**
	 * @testdox Should report only the current save's props when different props change.
	 */
	public function test_each_save_reports_only_its_own_props(): void {
		$coupon = $this->create_settled_coupon();
		$this->capture_updated_props();

		$coupon->set_amount( 5 );
		$coupon->save();

		$coupon->set_usage_limit( 3 );
		$coupon->save();

		$this->assertSame(
			array( array( 'amount' ), array( 'usage_limit' ) ),
			$this->captured_payloads,
			'The second save should report usage_limit only, not the amount from the first save.'
		);
	}

	/**
	 * @testdox Should not carry props from a create into a subsequent update.
	 */
	public function test_update_after_create_reports_only_changed_props(): void {
		$this->capture_updated_props();

		$coupon = new WC_Coupon();
		$coupon->set_code( 'create-then-update' );
		$coupon->set_amount( 5 );
		$coupon->save();

		$coupon->set_amount( 10 );
		$coupon->save();

		$this->assertCount( 2, $this->captured_payloads, 'Create and update should each fire the action exactly once.' );
		$this->assertContains( 'amount', $this->captured_payloads[0], 'The create should report the amount it wrote.' );
		$this->assertSame(
			array( 'amount' ),
			$this->captured_payloads[1],
			'The update should report amount only, not the props written during the create.'
		);
	}

	/**
	 * @testdox Should report only its own props for a save triggered from inside the hook.
	 */
	public function test_nested_save_from_listener_reports_only_its_own_props(): void {
		$coupon = $this->create_settled_coupon();
		$this->capture_updated_props();

		$nested_save_done = false;

		add_action(
			'woocommerce_coupon_updated_props',
			function ( $listener_coupon ) use ( &$nested_save_done ) {
				if ( $nested_save_done ) {
					return;
				}
				$nested_save_done = true;

				$listener_coupon->set_usage_limit( 3 );
				$listener_coupon->save();
			},
			20,
			2
		);

		$coupon->set_amount( 5 );
		$coupon->save();

		$this->assertSame(
			array( array( 'amount' ), array( 'usage_limit' ) ),
			$this->captured_payloads,
			'A save triggered from inside the hook must report only its own props, not the outer save\'s.'
		);
	}

	/**
	 * @testdox Should isolate nested and outer props when re-entered during a metadata update.
	 */
	public function test_nested_save_from_metadata_hook_does_not_contaminate_outer_payload(): void {
		$coupon = $this->create_settled_coupon();
		$this->capture_updated_props();

		// The nested save re-enters mid-write, when individual_use's meta row is written, so both
		// payloads land in $captured_payloads. Which slot each lands in is guaranteed: the nested
		// save runs to completion inside the outer save's update_or_delete_post_meta() call, so its
		// hook always fires first, whichever meta key triggers the re-entry. The order of the props
		// within a payload is not guaranteed — it follows the $meta_key_to_props map — so the slots
		// are pinned below but their contents compare canonicalized.
		$nested_save_done  = false;
		$metadata_listener = function ( $meta_id, $object_id, $meta_key ) use ( $coupon, &$nested_save_done ) {
			unset( $meta_id );

			if ( $nested_save_done || $coupon->get_id() !== $object_id || 'individual_use' !== $meta_key ) {
				return;
			}
			$nested_save_done = true;

			$coupon->set_usage_limit( 3 );
			$coupon->save();
		};

		add_action( 'updated_post_meta', $metadata_listener, 10, 3 );

		try {
			$coupon->set_amount( 5 );
			$coupon->set_individual_use( true );
			$coupon->save();
		} finally {
			remove_action( 'updated_post_meta', $metadata_listener, 10 );
		}

		$this->assertCount( 2, $this->captured_payloads, 'The nested save and the outer save should each fire the action exactly once.' );
		$this->assertEqualsCanonicalizing(
			array( 'usage_limit' ),
			$this->captured_payloads[0],
			'The nested save fires first, and must report only the prop it wrote.'
		);
		$this->assertEqualsCanonicalizing(
			array( 'amount', 'individual_use' ),
			$this->captured_payloads[1],
			'A nested metadata-hook save must not erase or contaminate the outer save\'s props.'
		);
	}

	/**
	 * @testdox Should keep firing the deprecated action with its historical accumulated payload.
	 */
	public function test_deprecated_action_receives_accumulated_props(): void {
		$this->setExpectedDeprecated( 'woocommerce_coupon_object_updated_props' );

		$coupon = $this->create_settled_coupon();

		$received = array();
		add_action(
			'woocommerce_coupon_object_updated_props',
			function ( $coupon, $updated_props ) use ( &$received ) {
				$received[] = $updated_props;
			},
			10,
			2
		);

		$coupon->set_amount( 5 );
		$coupon->save();

		$coupon->set_amount( 10 );
		$coupon->save();

		$this->assertSame(
			array( array( 'amount' ), array( 'amount', 'amount' ) ),
			$received,
			'A listener on the deprecated action must keep receiving the historical accumulate-with-duplicates payload.'
		);
	}

	/**
	 * @testdox Should accumulate deprecated-action props across coupons when a store instance is shared.
	 */
	public function test_deprecated_action_accumulates_across_coupons_when_store_is_shared(): void {
		$this->setExpectedDeprecated( 'woocommerce_coupon_object_updated_props' );

		$store = new WC_Coupon_Data_Store_CPT();

		// Returning an object shares one store instance across every coupon in this test.
		$store_filter = function () use ( $store ) {
			return $store;
		};
		add_filter( 'woocommerce_coupon_data_store', $store_filter );

		$fires = array();

		try {
			$coupon_a = WC_Helper_Coupon::create_coupon( 'shared-store-a' );
			$coupon_a->save();
			$coupon_b = WC_Helper_Coupon::create_coupon( 'shared-store-b' );
			$coupon_b->save();

			add_action(
				'woocommerce_coupon_object_updated_props',
				function ( $coupon, $accumulated_props ) use ( &$fires ) {
					$fires[] = array(
						'coupon_id'   => $coupon->get_id(),
						'accumulated' => $accumulated_props,
					);
				},
				10,
				2
			);

			$coupon_a->set_amount( 5 );
			$coupon_a->save();

			$coupon_b->set_usage_limit( 3 );
			$coupon_b->save();
		} finally {
			remove_filter( 'woocommerce_coupon_data_store', $store_filter );
		}

		$this->assertCount( 2, $fires, 'Each coupon save should fire the deprecated action exactly once.' );
		$this->assertSame( $coupon_b->get_id(), $fires[1]['coupon_id'], 'The second fire belongs to coupon B.' );
		$this->assertSame(
			array( 'amount', 'usage_limit' ),
			array_slice( $fires[1]['accumulated'], -2 ),
			'With a shared store instance, coupon B\'s payload retains the prop written for coupon A: accumulation belongs to the store instance, not the coupon.'
		);
	}

	/**
	 * @testdox Should keep accumulating in the deprecated updated-props property.
	 */
	public function test_deprecated_updated_props_property_keeps_accumulating(): void {
		$store = new class() extends WC_Coupon_Data_Store_CPT {
			/**
			 * Expose the deprecated updated-props state that a subclass may still read.
			 *
			 * @return array
			 */
			public function get_updated_props(): array {
				return $this->updated_props;
			}
		};

		// Returning an object shares one store instance across every coupon in this test.
		$store_filter = function () use ( $store ) {
			return $store;
		};
		add_filter( 'woocommerce_coupon_data_store', $store_filter );

		try {
			$coupon = $this->create_settled_coupon();

			$coupon->set_amount( 5 );
			$coupon->save();

			$coupon->set_amount( 10 );
			$coupon->save();
		} finally {
			remove_filter( 'woocommerce_coupon_data_store', $store_filter );
		}

		$this->assertSame(
			array( 'amount', 'amount' ),
			array_slice( $store->get_updated_props(), -2 ),
			'A subclass reading the deprecated property must keep seeing the historical accumulated list after saves.'
		);
	}

	/**
	 * @testdox Should update coupon with empty post_date_gmt without error and without pinning date_created.
	 */
	public function test_update_coupon_with_empty_post_date_gmt(): void {
		global $wpdb;

		$coupon    = $this->create_settled_coupon();
		$coupon_id = $coupon->get_id();

		// Simulate empty post_date_gmt as found in unpublished or draft posts.
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_status'   => 'draft',
				'post_date_gmt' => '0000-00-00 00:00:00',
			),
			array( 'ID' => $coupon_id )
		);
		clean_post_cache( $coupon_id );

		$coupon = new WC_Coupon( $coupon_id );
		$this->assertNull( $coupon->get_date_created( 'edit' ) );

		$coupon->set_description( 'Updated description' );
		$coupon->save();

		$reloaded_coupon = new WC_Coupon( $coupon_id );
		$this->assertNull( $reloaded_coupon->get_date_created( 'edit' ) );
		$this->assertSame( 'Updated description', $reloaded_coupon->get_description() );
	}

	/**
	 * Create a published coupon with a per-user usage limit.
	 *
	 * @param string $code           Coupon code.
	 * @param int    $limit_per_user Usage limit per user, 0 for none.
	 * @return WC_Coupon
	 */
	private function create_limited_coupon( string $code, int $limit_per_user = 1 ): WC_Coupon {
		WC_Helper_Coupon::create_coupon( $code, array( 'usage_limit_per_user' => $limit_per_user ? (string) $limit_per_user : '' ) );
		return new WC_Coupon( $code );
	}

	/**
	 * Get the `_used_by_email` rows of a coupon.
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 * @return string[]
	 */
	private function get_used_by_emails( WC_Coupon $coupon ): array {
		$values = get_post_meta( $coupon->get_id(), '_used_by_email', false );
		sort( $values );
		return $values;
	}

	/**
	 * @testdox A use counted under a user ID records the account email and the order billing email.
	 */
	public function test_increase_usage_count_records_account_and_billing_email_for_user(): void {
		$user_id = $this->factory->user->create( array( 'user_email' => 'Account@Example.com' ) );
		$coupon  = $this->create_limited_coupon( 'record_user_emails' );
		$order   = WC_Helper_Order::create_order( $user_id );
		$order->set_billing_email( 'billing@example.com' );
		$order->save();

		$coupon->increase_usage_count( (string) $user_id, $order );

		$this->assertSame(
			array( $user_id . ':account@example.com', $user_id . ':billing@example.com' ),
			$this->get_used_by_emails( $coupon )
		);
	}

	/**
	 * @testdox No email record is written for guest uses or for coupons without a per-user limit.
	 */
	public function test_increase_usage_count_skips_email_record_for_guests_and_unlimited_coupons(): void {
		$user_id   = $this->factory->user->create( array( 'user_email' => 'unlimited@example.com' ) );
		$unlimited = $this->create_limited_coupon( 'no_per_user_limit', 0 );
		$limited   = $this->create_limited_coupon( 'guest_use' );

		$unlimited->increase_usage_count( (string) $user_id );
		$limited->increase_usage_count( 'guest@example.com' );

		$this->assertSame( array(), $this->get_used_by_emails( $unlimited ) );
		$this->assertSame( array(), $this->get_used_by_emails( $limited ) );
	}

	/**
	 * @testdox Repeated uses by the same user do not duplicate their email records.
	 */
	public function test_increase_usage_count_does_not_duplicate_email_records(): void {
		$user_id = $this->factory->user->create( array( 'user_email' => 'repeat@example.com' ) );
		$coupon  = $this->create_limited_coupon( 'repeat_user', 3 );

		$coupon->increase_usage_count( (string) $user_id );
		$coupon->increase_usage_count( (string) $user_id );

		$this->assertSame( array( $user_id . ':repeat@example.com' ), $this->get_used_by_emails( $coupon ) );
		$this->assertCount( 2, ( new WC_Coupon( 'repeat_user' ) )->get_used_by() );
	}

	/**
	 * @testdox A user's email records are removed only when their last use of the coupon is removed.
	 */
	public function test_decrease_usage_count_removes_email_records_after_last_use(): void {
		$user_id  = $this->factory->user->create( array( 'user_email' => 'release@example.com' ) );
		$other_id = $this->factory->user->create( array( 'user_email' => 'other@example.com' ) );
		$coupon   = $this->create_limited_coupon( 'release_user', 3 );

		$coupon->increase_usage_count( (string) $user_id );
		$coupon->increase_usage_count( (string) $user_id );
		$coupon->increase_usage_count( (string) $other_id );

		$coupon->decrease_usage_count( (string) $user_id );
		$this->assertContains( $user_id . ':release@example.com', $this->get_used_by_emails( $coupon ), 'One use remains, so the record stays' );

		$coupon->decrease_usage_count( (string) $user_id );
		$this->assertSame( array( $other_id . ':other@example.com' ), $this->get_used_by_emails( $coupon ) );
	}

	/**
	 * @testdox Recorded user IDs are added for matching emails on the same coupon only.
	 */
	public function test_add_recorded_user_ids_to_aliases_maps_emails_to_users(): void {
		$user_id = $this->factory->user->create( array( 'user_email' => 'mapped@example.com' ) );
		$coupon  = $this->create_limited_coupon( 'map_aliases' );
		$other   = $this->create_limited_coupon( 'map_aliases_other' );
		$coupon->increase_usage_count( (string) $user_id );
		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'renamed@example.com',
			)
		);

		$data_store = WC_Data_Store::load( 'coupon' );

		$this->assertEqualsCanonicalizing(
			array( 'Mapped@Example.com', $user_id ),
			$data_store->add_recorded_user_ids_to_aliases( $coupon, array( 'Mapped@Example.com' ) )
		);
		$this->assertSame( array( 'mapped@example.com' ), $data_store->add_recorded_user_ids_to_aliases( $other, array( 'mapped@example.com' ) ) );
		$this->assertSame( array( 'unrelated@example.com' ), $data_store->add_recorded_user_ids_to_aliases( $coupon, array( 'unrelated@example.com' ) ) );
	}

	/**
	 * @testdox Usage by aliases counts each use once, including uses found through a recorded email and current holds.
	 */
	public function test_get_usage_by_aliases_counts_each_use_once(): void {
		$user_id = $this->factory->user->create( array( 'user_email' => 'count@example.com' ) );
		$coupon  = $this->create_limited_coupon( 'count_aliases', 5 );
		$coupon->increase_usage_count( (string) $user_id );
		$coupon->increase_usage_count( 'count@example.com' );

		$data_store = WC_Data_Store::load( 'coupon' );

		$this->assertSame( 2, $data_store->get_usage_by_aliases( $coupon, array( 'count@example.com' ) ) );
		$this->assertSame( 2, $data_store->get_usage_by_aliases( $coupon, array( 'count@example.com', $user_id, (string) $user_id ) ) );

		$data_store->check_and_hold_coupon_for_user( $coupon, array( 'count@example.com' ), 'count@example.com' );
		$this->assertSame( 3, $data_store->get_usage_by_aliases( $coupon, array( 'count@example.com' ) ) );
	}

	/**
	 * @testdox Email records are not exposed as coupon meta data.
	 */
	public function test_used_by_email_is_internal_meta(): void {
		$user_id = $this->factory->user->create( array( 'user_email' => 'internal@example.com' ) );
		$coupon  = $this->create_limited_coupon( 'internal_meta' );
		$coupon->increase_usage_count( (string) $user_id );

		$keys = wp_list_pluck( ( new WC_Coupon( 'internal_meta' ) )->get_meta_data(), 'key' );

		$this->assertNotContains( '_used_by_email', $keys );
	}
}
