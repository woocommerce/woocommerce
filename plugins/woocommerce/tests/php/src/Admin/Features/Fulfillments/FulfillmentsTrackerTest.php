<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsTracker;
use WC_Unit_Test_Case;

/**
 * Tests for the FulfillmentsTracker class.
 */
class FulfillmentsTrackerTest extends WC_Unit_Test_Case {

	/**
	 * @testdox determine_tracking_entry_method returns api when source is api.
	 */
	public function test_determine_entry_method_returns_api_for_api_source(): void {
		$result = FulfillmentsTracker::determine_tracking_entry_method( 'api', 'tracking-number' );

		$this->assertSame( 'api', $result, 'API source should always return api entry method' );
	}

	/**
	 * @testdox determine_tracking_entry_method returns ui_auto_lookup for tracking-number option from UI.
	 */
	public function test_determine_entry_method_returns_ui_auto_lookup(): void {
		$result = FulfillmentsTracker::determine_tracking_entry_method( 'fulfillments_modal', 'tracking-number' );

		$this->assertSame( 'ui_auto_lookup', $result, 'tracking-number option from modal should return ui_auto_lookup' );
	}

	/**
	 * @testdox determine_tracking_entry_method returns ui_manual for manual-entry from UI.
	 */
	public function test_determine_entry_method_returns_ui_manual(): void {
		$result = FulfillmentsTracker::determine_tracking_entry_method( 'fulfillments_modal', 'manual-entry' );

		$this->assertSame( 'ui_manual', $result, 'manual-entry from modal should return ui_manual' );
	}

	/**
	 * @testdox determine_tracking_entry_method returns api for unknown shipping option from UI.
	 */
	public function test_determine_entry_method_returns_api_for_unknown_option(): void {
		$result = FulfillmentsTracker::determine_tracking_entry_method( 'fulfillments_modal', 'no-info' );

		$this->assertSame( 'api', $result, 'Unknown shipping option from modal should fall back to api' );
	}

	/**
	 * @testdox determine_tracking_entry_method returns api for empty shipping option from UI.
	 */
	public function test_determine_entry_method_returns_api_for_empty_option(): void {
		$result = FulfillmentsTracker::determine_tracking_entry_method( 'fulfillments_modal', '' );

		$this->assertSame( 'api', $result, 'Empty shipping option from modal should fall back to api' );
	}

	/**
	 * Data provider for entry method scenarios.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public function entry_method_data_provider(): array {
		return array(
			'API with tracking-number'     => array( 'api', 'tracking-number', 'api' ),
			'API with manual-entry'        => array( 'api', 'manual-entry', 'api' ),
			'UI auto lookup'               => array( 'fulfillments_modal', 'tracking-number', 'ui_auto_lookup' ),
			'UI manual entry'              => array( 'fulfillments_modal', 'manual-entry', 'ui_manual' ),
			'UI no-info falls back to api' => array( 'fulfillments_modal', 'no-info', 'api' ),
			'Unknown source falls back'    => array( 'bulk_action', 'tracking-number', 'api' ),
		);
	}

	/**
	 * @testdox determine_tracking_entry_method returns expected value for various input combinations.
	 *
	 * @dataProvider entry_method_data_provider
	 *
	 * @param string $source          The request source.
	 * @param string $shipping_option The shipping option.
	 * @param string $expected        The expected entry method.
	 */
	public function test_determine_entry_method_data_provider( string $source, string $shipping_option, string $expected ): void {
		$result = FulfillmentsTracker::determine_tracking_entry_method( $source, $shipping_option );

		$this->assertSame( $expected, $result );
	}

	/**
	 * @testdox track_fulfillment_modal_opened is callable with expected parameters.
	 */
	public function test_track_fulfillment_modal_opened_is_callable(): void {
		$this->assertTrue(
			method_exists( FulfillmentsTracker::class, 'track_fulfillment_modal_opened' ),
			'track_fulfillment_modal_opened method should exist'
		);

		$reflection = new \ReflectionMethod( FulfillmentsTracker::class, 'track_fulfillment_modal_opened' );
		$params     = $reflection->getParameters();

		$this->assertCount( 2, $params, 'track_fulfillment_modal_opened should accept 2 parameters' );
		$this->assertSame( 'source', $params[0]->getName() );
		$this->assertSame( 'order_id', $params[1]->getName() );
	}

	/**
	 * @testdox track_fulfillment_creation is callable with expected parameters.
	 */
	public function test_track_fulfillment_creation_is_callable(): void {
		$reflection = new \ReflectionMethod( FulfillmentsTracker::class, 'track_fulfillment_creation' );
		$params     = $reflection->getParameters();

		$this->assertCount( 6, $params, 'track_fulfillment_creation should accept 6 parameters' );
		$this->assertSame( 'source', $params[0]->getName() );
		$this->assertSame( 'initial_status', $params[1]->getName() );
		$this->assertSame( 'fulfillment_type', $params[2]->getName() );
		$this->assertSame( 'item_count', $params[3]->getName() );
		$this->assertSame( 'total_quantity', $params[4]->getName() );
		$this->assertSame( 'notification_sent', $params[5]->getName() );
	}

	/**
	 * @testdox track_fulfillment_tracking_added is callable with expected parameters.
	 */
	public function test_track_fulfillment_tracking_added_is_callable(): void {
		$reflection = new \ReflectionMethod( FulfillmentsTracker::class, 'track_fulfillment_tracking_added' );
		$params     = $reflection->getParameters();

		$this->assertCount( 4, $params, 'track_fulfillment_tracking_added should accept 4 parameters' );
		$this->assertSame( 'fulfillment_id', $params[0]->getName() );
		$this->assertSame( 'entry_method', $params[1]->getName() );
		$this->assertSame( 'provider_name', $params[2]->getName() );
		$this->assertSame( 'is_custom_provider', $params[3]->getName() );
	}

	/**
	 * @testdox track_fulfillment_tracking_lookup_attempt accepts url_generated parameter.
	 */
	public function test_track_fulfillment_tracking_lookup_attempt_has_url_generated_param(): void {
		$reflection = new \ReflectionMethod( FulfillmentsTracker::class, 'track_fulfillment_tracking_lookup_attempt' );
		$params     = $reflection->getParameters();

		$this->assertCount( 3, $params, 'track_fulfillment_tracking_lookup_attempt should accept 3 parameters' );
		$this->assertSame( 'url_generated', $params[2]->getName() );
		$this->assertTrue( $params[2]->isDefaultValueAvailable(), 'url_generated should have a default value' );
		$this->assertFalse( $params[2]->getDefaultValue(), 'url_generated should default to false' );
	}

	/**
	 * @testdox track_fulfillment_email_template_customized is callable with expected parameters.
	 */
	public function test_track_fulfillment_email_template_customized_is_callable(): void {
		$reflection = new \ReflectionMethod( FulfillmentsTracker::class, 'track_fulfillment_email_template_customized' );
		$params     = $reflection->getParameters();

		$this->assertCount( 1, $params, 'track_fulfillment_email_template_customized should accept 1 parameter' );
		$this->assertSame( 'template_name', $params[0]->getName() );
	}

	/**
	 * @testdox track_fulfillment_validation_error is callable with expected parameters.
	 */
	public function test_track_fulfillment_validation_error_is_callable(): void {
		$reflection = new \ReflectionMethod( FulfillmentsTracker::class, 'track_fulfillment_validation_error' );
		$params     = $reflection->getParameters();

		$this->assertCount( 3, $params, 'track_fulfillment_validation_error should accept 3 parameters' );
		$this->assertSame( 'action_attempted', $params[0]->getName() );
		$this->assertSame( 'error_code', $params[1]->getName() );
		$this->assertSame( 'source', $params[2]->getName() );
	}

	/**
	 * @testdox All tracker methods are static.
	 */
	public function test_all_tracker_methods_are_static(): void {
		$reflection = new \ReflectionClass( FulfillmentsTracker::class );
		$methods    = $reflection->getMethods( \ReflectionMethod::IS_PUBLIC );

		foreach ( $methods as $method ) {
			$this->assertTrue(
				$method->isStatic(),
				"Method {$method->getName()} should be static"
			);
		}
	}
}
