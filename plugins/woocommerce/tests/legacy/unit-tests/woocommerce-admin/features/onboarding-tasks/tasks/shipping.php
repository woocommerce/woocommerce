<?php
/**
 * Test the Tasks/Shipping class.
 *
 * @package WooCommerce\Admin\Tests\OnboardingTasks/Tasks/Shipping
 */

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Shipping;

/**
 * class WC_Admin_Tests_OnboardingTasks_Task_Shipping
 */
class WC_Admin_Tests_OnboardingTasks_Task_Shipping extends WC_Unit_Test_Case {

	/**
	 * Task list.
	 *
	 * @var Task|null
	 */
	protected $task = null;

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->task = new Shipping( new TaskList() );
		add_filter( 'woocommerce_admin_features', array( $this, 'turn_on_smart_shipping_defaults_feature' ), 20, 1 );

		update_option(
			OnboardingProfile::DATA_OPTION,
			array(
				'product_types' => array(
					'physical',
				),
			)
		);
	}


	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_filter( 'woocommerce_admin_features', array( $this, 'turn_on_smart_shipping_defaults_feature' ), 1 );

		delete_option( OnboardingProfile::DATA_OPTION );
	}


	/**
	 * Filter to enable shipping-smart-defaults feature.
	 *
	 * @param  array $features Array of active features.
	 */
	public static function turn_on_smart_shipping_defaults_feature( $features ) {
		return array_merge( $features, array( 'shipping-smart-defaults' ) );
	}

	/**
	 * Test can_view function of task when store only sells physical products.
	 */
	public function test_can_view_return_true_when_sell_only_physical_type() {
		update_option(
			OnboardingProfile::DATA_OPTION,
			array(
				'product_types' => array(
					'physical',
				),
			)
		);
		$this->assertEquals( $this->task->can_view(), true );
	}

	/**
	 * Test can_view function of task when store sells physical and digital products.
	 */
	public function test_can_view_return_true_when_sell_physical_and_digital_type() {
		update_option(
			OnboardingProfile::DATA_OPTION,
			array(
				'product_types' => array(
					'physical',
					'digital',
				),
			)
		);
		$this->assertEquals( $this->task->can_view(), true );
	}

	/**
	 * Test can_view function of task when store only sells digital products.
	 */
	public function test_can_view_return_false_when_sell_only_digital_type() {
		update_option(
			OnboardingProfile::DATA_OPTION,
			array(
				'product_types' => array(
					'downloads',
				),
			)
		);
		$this->assertEquals( $this->task->can_view(), false );
	}

	/**
	 * Test can_view function of task when store location is an eligible country.
	 *
	 * @dataProvider data_provider_can_view_eligible_countries
	 * @param string $country Country to test.
	 */
	public function test_can_view_return_true_for_eligible_countries( $country ) {
		update_option(
			'woocommerce_default_country',
			$country
		);
		$this->assertEquals( $this->task->can_view(), true );
	}

	/**
	 * Test can_view function of task when store location is unknown.
	 *
	 */
	public function test_can_view_return_true_when_store_location_is_unknown() {
		delete_option( 'woocommerce_default_country' );

		$this->assertEquals( $this->task->can_view(), true );

		update_option(
			'woocommerce_default_country',
			'US:CA'
		);

		delete_option(
			'woocommerce_store_address',
			''
		);

		$this->assertEquals( $this->task->can_view(), true );
	}


	/**
	 * Data provider for test_can_view_return_true_for_eligible_countries.
	 *
	 */
	public function data_provider_can_view_eligible_countries() {
		return array(
			array( 'AU' ),
			array( 'CA' ),
			array( 'GB' ),
			array( 'ES' ),
			array( 'IT' ),
			array( 'DE' ),
			array( 'FR' ),
			array( 'MX' ),
			array( 'CO' ),
			array( 'CL' ),
			array( 'AR' ),
			array( 'PE' ),
			array( 'BR' ),
			array( 'UY' ),
			array( 'GT' ),
			array( 'NL' ),
			array( 'AT' ),
			array( 'BE' ),
		);
	}
}
