<?php
declare( strict_types = 1 );

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- legacy conventions.

/**
 * Regression tests for coupon minimum/maximum spend validation through the
 * V2 REST API controller.
 *
 * @covers WC_REST_Coupons_V2_Controller::prepare_object_for_database
 */
class WC_REST_Coupons_V2_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * @var WC_REST_Coupons_V2_Controller System under test.
	 */
	private WC_REST_Coupons_V2_Controller $sut;

	/**
	 * @var int Admin user ID used for REST permission checks.
	 */
	private int $admin_id;

	/**
	 * @inheritDoc
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut      = new WC_REST_Coupons_V2_Controller();
		$this->admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_id );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Create a real coupon in the database and return it.
	 *
	 * Sets minimum_amount first, then maximum_amount. The setter guards are
	 * safe in this order as long as max >= min, which is always true for
	 * valid fixture data.
	 *
	 * @param string $code Coupon code.
	 * @param string $min  Minimum amount.
	 * @param string $max  Maximum amount.
	 * @return WC_Coupon
	 */
	private function make_coupon( string $code, string $min = '0', string $max = '0' ): WC_Coupon {
		$coupon = new WC_Coupon();
		$coupon->set_code( $code );
		$coupon->set_minimum_amount( $min );
		$coupon->set_maximum_amount( $max );
		$coupon->save();
		return $coupon;
	}

	/**
	 * Build a PATCH REST request for an existing coupon.
	 *
	 * @param int   $id     Coupon post ID.
	 * @param array $params Request parameters.
	 * @return WP_REST_Request
	 */
	private function patch_request( int $id, array $params ): WP_REST_Request {
		$request = new WP_REST_Request( 'PUT', '/wc/v2/coupons/' . $id );
		$request->set_param( 'id', $id );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return $request;
	}

	/**
	 * Build a POST REST request to create a coupon.
	 *
	 * @param array $params Request parameters.
	 * @return WP_REST_Request
	 */
	private function post_request( array $params ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/wc/v2/coupons' );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return $request;
	}

	// -----------------------------------------------------------------------
	// Tests: valid simultaneous updates
	// -----------------------------------------------------------------------

	/**
	 * @testdox A valid simultaneous raise of both minimum and maximum spend succeeds via REST.
	 */
	public function test_rest_allows_raising_both_amounts_together(): void {
		$coupon = $this->make_coupon( 'RAISE-BOTH', '100', '200' );

		$result = $this->sut->update_item(
			$this->patch_request(
				$coupon->get_id(),
				array(
					'minimum_amount' => '250',
					'maximum_amount' => '300',
				)
			)
		);

		$this->assertNotWPError( $result );
		$data = $result->get_data();
		$this->assertSame( '250.00', $data['minimum_amount'] );
		$this->assertSame( '300.00', $data['maximum_amount'] );
	}

	/**
	 * @testdox A valid simultaneous decrease of both minimum and maximum spend succeeds via REST.
	 */
	public function test_rest_allows_lowering_both_amounts_together(): void {
		$coupon = $this->make_coupon( 'LOWER-BOTH', '100', '200' );

		$result = $this->sut->update_item(
			$this->patch_request(
				$coupon->get_id(),
				array(
					'minimum_amount' => '50',
					'maximum_amount' => '75',
				)
			)
		);

		$this->assertNotWPError( $result );
		$data = $result->get_data();
		$this->assertSame( '50.00', $data['minimum_amount'] );
		$this->assertSame( '75.00', $data['maximum_amount'] );
	}

	// -----------------------------------------------------------------------
	// Tests: invalid pair rejected
	// -----------------------------------------------------------------------

	/**
	 * @testdox Creating a coupon with minimum spend exceeding maximum spend returns a 400 error.
	 */
	public function test_rest_create_rejects_invalid_amount_pair(): void {
		$result = $this->sut->create_item(
			$this->post_request(
				array(
					'code'           => 'BAD-CREATE',
					'minimum_amount' => '300',
					'maximum_amount' => '100',
				)
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'coupon_invalid_minimum_amount', $result->get_error_code() );
		$error_data = $result->get_error_data();
		$this->assertSame( 400, $error_data['status'] );
	}

	/**
	 * @testdox Updating a coupon with an invalid amount pair returns a 400 error without persisting either amount.
	 */
	public function test_rest_update_rejects_invalid_amount_pair_without_mutation(): void {
		$coupon = $this->make_coupon( 'NO-MUTATE', '50', '150' );
		$id     = $coupon->get_id();

		$result = $this->sut->update_item(
			$this->patch_request(
				$id,
				array(
					'minimum_amount' => '200',
					'maximum_amount' => '100',
				)
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'coupon_invalid_minimum_amount', $result->get_error_code() );

		// Reload from DB and verify neither amount was written.
		$reloaded = new WC_Coupon( $id );
		$this->assertEquals( 50.0, (float) $reloaded->get_minimum_amount(), 'minimum_amount must not have been mutated' );
		$this->assertEquals( 150.0, (float) $reloaded->get_maximum_amount(), 'maximum_amount must not have been mutated' );
	}

	// -----------------------------------------------------------------------
	// Tests: single-field updates
	// -----------------------------------------------------------------------

	/**
	 * @testdox Updating only minimum spend to a value that exceeds the stored maximum returns a 400 error.
	 */
	public function test_rest_minimum_only_update_rejected_when_exceeds_stored_maximum(): void {
		$coupon = $this->make_coupon( 'MIN-ONLY-FAIL', '50', '100' );

		$result = $this->sut->update_item(
			$this->patch_request(
				$coupon->get_id(),
				array( 'minimum_amount' => '200' )
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'coupon_invalid_minimum_amount', $result->get_error_code() );
	}

	/**
	 * @testdox Updating only maximum spend to a value below the stored minimum returns a 400 error.
	 */
	public function test_rest_maximum_only_update_rejected_when_below_stored_minimum(): void {
		$coupon = $this->make_coupon( 'MAX-ONLY-FAIL', '100', '200' );

		$result = $this->sut->update_item(
			$this->patch_request(
				$coupon->get_id(),
				array( 'maximum_amount' => '50' )
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'coupon_invalid_maximum_amount', $result->get_error_code() );
	}
}
