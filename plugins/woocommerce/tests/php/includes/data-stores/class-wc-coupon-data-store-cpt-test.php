<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Coupon_Data_Store_CPT class.
 *
 * @package WooCommerce\Tests\DataStores
 */

/**
 * Class WC_Coupon_Data_Store_CPT_Test.
 */
class WC_Coupon_Data_Store_CPT_Test extends WC_Unit_Test_Case {

	/**
	 * The payload of every woocommerce_coupon_object_updated_props fire, in order.
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
	 * Record the payload of every woocommerce_coupon_object_updated_props fire.
	 *
	 * Registered at priority 10 so that it observes the outer payload before any
	 * listener registered at a later priority can trigger a nested save.
	 */
	private function capture_updated_props(): void {
		add_action(
			'woocommerce_coupon_object_updated_props',
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
			'woocommerce_coupon_object_updated_props',
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

		// The expected payloads below depend on update_post_meta() writing coupon_amount
		// before individual_use, which is the order they appear in its $meta_key_to_props
		// map. Reordering that map changes which write re-enters, and these payloads with it.
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

		$this->assertSame(
			array( array( 'usage_limit' ), array( 'amount', 'individual_use' ) ),
			$this->captured_payloads,
			'A nested metadata-hook save must not erase or contaminate the outer save\'s props.'
		);
	}

	/**
	 * @testdox Should leave the outer save's props in the data store after a listener triggers a nested save.
	 */
	public function test_updated_props_property_settles_on_the_outermost_save(): void {
		$store = new class() extends WC_Coupon_Data_Store_CPT {
			/**
			 * Expose the protected updated-props state, which subclasses may read.
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

			$nested_save_done = false;
			add_action(
				'woocommerce_coupon_object_updated_props',
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
		} finally {
			remove_filter( 'woocommerce_coupon_data_store', $store_filter );
		}

		$this->assertTrue( $nested_save_done, 'The nested save must have run for this test to be meaningful.' );
		$this->assertSame(
			array( 'amount' ),
			$store->get_updated_props(),
			'The nested save completes before the outer one, so the outer save must restore its own props afterwards.'
		);
	}
}
