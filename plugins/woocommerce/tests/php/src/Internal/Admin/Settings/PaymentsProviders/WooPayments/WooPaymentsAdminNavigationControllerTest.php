<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsAdminNavigationController;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsAdminNavigationController class.
 */
class WooPaymentsAdminNavigationControllerTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $submenu;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Isolate admin menu assertions in this test.
		$submenu = array();
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * @testdox Should register the WooPayments navigation after the Core Payments menu exists.
	 */
	public function test_registers_admin_menu_hook_after_core_payments_menu(): void {
		$sut = $this->create_controller( true );

		$sut->register();

		$this->assertSame( 70, has_action( 'admin_menu', array( $sut, 'add_menu_items' ) ) );
		$this->assertSame( 10, has_action( 'admin_init', array( $sut, 'redirect_legacy_payment_paths' ) ) );

		remove_action( 'admin_menu', array( $sut, 'add_menu_items' ), 70 );
		remove_action( 'admin_init', array( $sut, 'redirect_legacy_payment_paths' ), 10 );
	}

	/**
	 * @testdox Should not register the WooPayments navigation hook when native runtime does not own payments.
	 */
	public function test_does_not_register_admin_menu_hook_when_native_runtime_does_not_own_payments(): void {
		$sut = $this->create_controller( false );

		$sut->register();

		$this->assertFalse( has_action( 'admin_menu', array( $sut, 'add_menu_items' ) ) );
		$this->assertFalse( has_action( 'admin_init', array( $sut, 'redirect_legacy_payment_paths' ) ) );
	}

	/**
	 * @testdox Should map legacy WooPayments WC Admin transaction URLs to native Settings Payments routes.
	 */
	public function test_maps_legacy_payment_transaction_details_url_to_native_route(): void {
		$sut = $this->create_controller( true );

		$url = $sut->get_legacy_payment_path_redirect_url(
			array(
				'page'           => 'wc-admin',
				'path'           => '%2Fpayments%2Ftransactions%2Fdetails',
				'id'             => 'pi_native',
				'transaction_id' => 'txn_native',
				'type'           => 'dispute',
			)
		);

		$this->assertStringContainsString( 'admin.php?page=wc-settings&tab=checkout', $url );
		$this->assertStringContainsString( 'path=/woopayments/transactions/details', $url );
		$this->assertStringContainsString( 'id=pi_native', $url );
		$this->assertStringContainsString( 'transaction_id=txn_native', $url );
		$this->assertStringContainsString( 'type=dispute', $url );
		$this->assertStringNotContainsString( 'page=wc-admin', $url );
	}

	/**
	 * @testdox Should not redirect unrelated WC Admin paths.
	 */
	public function test_does_not_map_unrelated_wc_admin_paths(): void {
		$sut = $this->create_controller( true );

		$this->assertSame(
			'',
			$sut->get_legacy_payment_path_redirect_url(
				array(
					'page' => 'wc-admin',
					'path' => '/analytics/revenue',
				)
			)
		);
	}

	/**
	 * @testdox Should not add WooPayments navigation when native runtime does not own payments.
	 */
	public function test_does_not_add_menu_items_when_native_runtime_does_not_own_payments(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$sut = $this->create_controller( false );
		$sut->add_menu_items();

		$this->assertSame( array(), $this->get_payments_submenu_items() );
	}

	/**
	 * @testdox Should not add WooPayments navigation for users without manage_woocommerce.
	 */
	public function test_does_not_add_menu_items_without_manage_woocommerce_capability(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'customer' ) ) );

		$sut = $this->create_controller( true );
		$sut->add_menu_items();

		$this->assertSame( array(), $this->get_payments_submenu_items() );
	}

	/**
	 * @testdox Should add full WooPayments navigation for a valid native account.
	 */
	public function test_adds_full_menu_items_for_valid_native_account(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$sut = $this->create_controller(
			true,
			array(
				'is_card_present_eligible'      => true,
				'has_card_readers_available'    => true,
				'has_previous_capital_loans'    => true,
				'has_valid_admin_account_state' => true,
			)
		);
		$sut->add_menu_items();

		$items = $this->get_payments_submenu_items();

		$this->assertSame(
			array(
				'Overview',
				'Payouts',
				'Transactions',
				'Disputes',
				'Card Readers',
				'Capital Loans',
				'Settings',
			),
			array_column( $items, 0 )
		);
		$this->assertSame(
			array(
				$this->get_settings_url( '/woopayments/overview' ),
				$this->get_settings_url( '/woopayments/payouts' ),
				$this->get_settings_url( '/woopayments/transactions' ),
				$this->get_settings_url( '/woopayments/disputes' ),
				$this->get_settings_url( '/woopayments/card-readers' ),
				$this->get_settings_url( '/woopayments/loans' ),
				$this->get_settings_url( '/woopayments/settings' ),
			),
			array_column( $items, 2 )
		);
		$this->assertStringNotContainsString( 'wc-admin&path=/payments', implode( "\n", array_column( $items, 2 ) ) );
	}

	/**
	 * @testdox Should add reduced WooPayments navigation for rejected and under-review native accounts.
	 * @dataProvider provider_restricted_account_states
	 *
	 * @param array<string,bool> $account_state Account state overrides.
	 */
	public function test_adds_reduced_menu_items_for_restricted_account_states( array $account_state ): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$sut = $this->create_controller( true, $account_state );
		$sut->add_menu_items();

		$items = $this->get_payments_submenu_items();

		$this->assertSame(
			array(
				'Overview',
				'Transactions',
				'Disputes',
			),
			array_column( $items, 0 )
		);
		$this->assertSame(
			array(
				$this->get_settings_url( '/woopayments/overview' ),
				$this->get_settings_url( '/woopayments/transactions' ),
				$this->get_settings_url( '/woopayments/disputes' ),
			),
			array_column( $items, 2 )
		);
	}

	/**
	 * Restricted account state provider.
	 *
	 * @return array<string,array{account_state:array<string,bool>}>
	 */
	public function provider_restricted_account_states(): array {
		return array(
			'rejected account'     => array(
				'account_state' => array(
					'is_account_rejected' => true,
				),
			),
			'under-review account' => array(
				'account_state' => array(
					'is_account_under_review' => true,
				),
			),
		);
	}

	/**
	 * @testdox Should add onboarding navigation for native accounts that are not ready.
	 * @dataProvider provider_not_ready_account_states
	 *
	 * @param array<string,bool> $account_state Account state overrides.
	 * @param string             $expected_title Expected menu item title.
	 */
	public function test_adds_onboarding_menu_items_for_accounts_that_are_not_ready( array $account_state, string $expected_title ): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$sut = $this->create_controller( true, $account_state );
		$sut->add_menu_items();

		$items = $this->get_payments_submenu_items();

		$this->assertSame( array( $expected_title ), array_column( $items, 0 ) );
		$this->assertSame( array( $this->get_settings_url( '/woopayments/onboarding' ) ), array_column( $items, 2 ) );
	}

	/**
	 * Not-ready account state provider.
	 *
	 * @return array<string,array{account_state:array<string,bool>,expected_title:string}>
	 */
	public function provider_not_ready_account_states(): array {
		return array(
			'not connected'   => array(
				'account_state'  => array(
					'has_account'                   => false,
					'is_details_submitted'          => false,
					'has_valid_admin_account_state' => false,
				),
				'expected_title' => 'Onboarding',
			),
			'details missing' => array(
				'account_state'  => array(
					'has_account'                   => true,
					'is_details_submitted'          => false,
					'has_valid_admin_account_state' => false,
				),
				'expected_title' => 'Continue onboarding',
			),
		);
	}

	/**
	 * Create the controller under test.
	 *
	 * @param bool               $native_register Whether native should own menu registration.
	 * @param array<string,bool> $account_state   Account state overrides.
	 * @return WooPaymentsAdminNavigationController
	 */
	private function create_controller( bool $native_register, array $account_state = array() ): WooPaymentsAdminNavigationController {
		$class_name = WooPaymentsAdminNavigationController::class;
		$this->assertTrue( class_exists( $class_name ), 'WooPayments admin navigation controller should exist.' );

		$arbiter = $this->getMockBuilder( NativePaymentsRuntimeArbiter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_native_register' ) )
			->getMock();
		$arbiter->method( 'should_native_register' )->willReturn( $native_register );

		$account_service = $this->create_account_service( $account_state );
		$controller      = new $class_name();
		$controller->init( $arbiter, $account_service );

		return $controller;
	}

	/**
	 * Create a native account service test double.
	 *
	 * @param array<string,bool> $overrides Account state overrides.
	 * @return WooPaymentsAccountService&MockObject
	 */
	private function create_account_service( array $overrides = array() ): WooPaymentsAccountService {
		$state = array_merge(
			array(
				'has_account'                            => true,
				'has_valid_account_for_admin_navigation' => true,
				'is_account_rejected'                    => false,
				'is_account_under_review'                => false,
				'is_details_submitted'                   => true,
				'is_card_present_eligible'               => false,
				'has_card_readers_available'             => false,
				'has_previous_capital_loans'             => false,
			),
			$overrides
		);

		if ( isset( $state['has_valid_admin_account_state'] ) ) {
			$state['has_valid_account_for_admin_navigation'] = $state['has_valid_admin_account_state'];
			unset( $state['has_valid_admin_account_state'] );
		}

		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array_keys( $state ) )
			->getMock();

		foreach ( $state as $method => $value ) {
			$account_service->method( $method )->willReturn( $value );
		}

		return $account_service;
	}

	/**
	 * Get WooPayments submenu items from the Core Payments parent.
	 *
	 * @return array<int,array<int,string>>
	 */
	private function get_payments_submenu_items(): array {
		global $submenu;

		return array_values( $submenu[ $this->get_parent_slug() ] ?? array() );
	}

	/**
	 * Get the Core Payments parent menu slug.
	 *
	 * @return string
	 */
	private function get_parent_slug(): string {
		return 'admin.php?page=wc-settings&tab=checkout&from=' . Payments::FROM_PAYMENTS_MENU_ITEM;
	}

	/**
	 * Get a canonical native WooPayments settings route URL.
	 *
	 * @param string $path Native WooPayments settings route path.
	 * @return string
	 */
	private function get_settings_url( string $path ): string {
		return Utils::wc_payments_settings_url( $path, array( 'from' => Payments::FROM_PAYMENTS_MENU_ITEM ) );
	}
}
