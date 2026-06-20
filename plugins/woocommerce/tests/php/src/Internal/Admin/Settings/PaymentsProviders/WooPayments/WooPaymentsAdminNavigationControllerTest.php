<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsAdminNavigationController;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAdminMenuBadgeService;
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
		unset( $_GET['page'], $_GET['tab'], $_GET['path'] );

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
		$this->assertSame( 10, has_filter( 'woocommerce_admin_shared_settings', array( $sut, 'preload_shared_settings' ) ) );

		remove_action( 'admin_menu', array( $sut, 'add_menu_items' ), 70 );
		remove_action( 'admin_init', array( $sut, 'redirect_legacy_payment_paths' ), 10 );
		remove_filter( 'woocommerce_admin_shared_settings', array( $sut, 'preload_shared_settings' ), 10 );
	}

	/**
	 * @testdox Should not register the WooPayments navigation hook when native runtime does not own payments.
	 */
	public function test_does_not_register_admin_menu_hook_when_native_runtime_does_not_own_payments(): void {
		$sut = $this->create_controller( false );

		$sut->register();

		$this->assertFalse( has_action( 'admin_menu', array( $sut, 'add_menu_items' ) ) );
		$this->assertFalse( has_action( 'admin_init', array( $sut, 'redirect_legacy_payment_paths' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_admin_shared_settings', array( $sut, 'preload_shared_settings' ) ) );
	}

	/**
	 * @testdox Should preload the Reports feature flag for native WooPayments routes on the Payments settings page.
	 */
	public function test_preloads_reports_feature_flag_on_payments_settings_request(): void {
		$_GET['page'] = 'wc-settings';
		$_GET['tab']  = 'checkout';
		$sut          = $this->create_controller(
			true,
			array(
				'is_reports_enabled' => true,
			)
		);

		$settings = $sut->preload_shared_settings(
			array(
				'woopaymentsSettings' => array(
					'featureFlags' => array(
						'existingFlag' => true,
					),
				),
			)
		);

		$this->assertTrue( $settings['woopaymentsSettings']['featureFlags']['reportsArea'] );
		$this->assertTrue( $settings['woopaymentsSettings']['featureFlags']['existingFlag'] );
	}

	/**
	 * @testdox Should preload full admin route availability for a valid native account.
	 */
	public function test_preloads_full_admin_route_availability_for_valid_native_account(): void {
		$sut = $this->create_controller(
			true,
			array(
				'is_reports_enabled'         => true,
				'is_documents_enabled'       => true,
				'is_card_present_eligible'   => true,
				'has_card_readers_available' => true,
				'has_previous_capital_loans' => true,
			)
		);

		$availability = $this->preload_route_availability( $sut );
		$routes       = $availability['allowedRoutes'];

		$this->assertTrue( $availability['gatewayEnabled'] );
		$this->assertSame( 'full', $availability['accountState'] );
		$this->assertTrue( $routes['/woopayments/settings'] );
		$this->assertTrue( $routes['/woopayments/overview'] );
		$this->assertTrue( $routes['/woopayments/payouts'] );
		$this->assertTrue( $routes['/woopayments/payouts/details'] );
		$this->assertTrue( $routes['/woopayments/transactions'] );
		$this->assertTrue( $routes['/woopayments/transactions/details'] );
		$this->assertTrue( $routes['/woopayments/disputes'] );
		$this->assertTrue( $routes['/woopayments/disputes/details'] );
		$this->assertTrue( $routes['/woopayments/disputes/challenge'] );
		$this->assertTrue( $routes['/woopayments/reports'] );
		$this->assertTrue( $routes['/woopayments/card-readers'] );
		$this->assertTrue( $routes['/woopayments/loans'] );
		$this->assertTrue( $routes['/woopayments/documents'] );
	}

	/**
	 * @testdox Should preload reduced admin route availability for restricted native accounts.
	 * @dataProvider provider_restricted_account_states
	 *
	 * @param array<string,bool> $account_state Account state overrides.
	 */
	public function test_preloads_restricted_admin_route_availability( array $account_state ): void {
		$sut = $this->create_controller(
			true,
			array_merge(
				$account_state,
				array(
					'is_reports_enabled'         => true,
					'is_documents_enabled'       => true,
					'is_card_present_eligible'   => true,
					'has_card_readers_available' => true,
					'has_previous_capital_loans' => true,
				)
			)
		);

		$availability = $this->preload_route_availability( $sut );
		$routes       = $availability['allowedRoutes'];

		$this->assertTrue( $availability['gatewayEnabled'] );
		$this->assertSame( 'restricted', $availability['accountState'] );
		$this->assertTrue( $routes['/woopayments/settings'] );
		$this->assertTrue( $routes['/woopayments/overview'] );
		$this->assertTrue( $routes['/woopayments/transactions'] );
		$this->assertTrue( $routes['/woopayments/transactions/details'] );
		$this->assertTrue( $routes['/woopayments/disputes'] );
		$this->assertTrue( $routes['/woopayments/disputes/details'] );
		$this->assertTrue( $routes['/woopayments/disputes/challenge'] );
		$this->assertFalse( $routes['/woopayments/onboarding'] );
		$this->assertFalse( $routes['/woopayments/payouts'] );
		$this->assertFalse( $routes['/woopayments/payouts/details'] );
		$this->assertFalse( $routes['/woopayments/reports'] );
		$this->assertFalse( $routes['/woopayments/card-readers'] );
		$this->assertFalse( $routes['/woopayments/loans'] );
		$this->assertFalse( $routes['/woopayments/documents'] );
	}

	/**
	 * @testdox Should preload unavailable admin route availability when the gateway is disabled.
	 */
	public function test_preloads_unavailable_admin_route_availability_when_gateway_is_disabled(): void {
		$sut = $this->create_controller(
			true,
			array(
				'is_gateway_enabled'                     => false,
				'is_reports_enabled'                     => true,
				'is_documents_enabled'                   => true,
				'is_card_present_eligible'               => true,
				'has_card_readers_available'             => true,
				'has_previous_capital_loans'             => true,
				'has_valid_account_for_admin_navigation' => true,
			)
		);

		$availability = $this->preload_route_availability( $sut );
		$routes       = $availability['allowedRoutes'];

		$this->assertFalse( $availability['gatewayEnabled'] );
		$this->assertSame( 'disabled', $availability['accountState'] );
		$this->assertTrue( $routes['/woopayments/settings'] );
		$this->assertFalse( $routes['/woopayments/onboarding'] );
		$this->assertFalse( $routes['/woopayments/overview'] );
		$this->assertFalse( $routes['/woopayments/transactions'] );
		$this->assertFalse( $routes['/woopayments/disputes'] );
		$this->assertFalse( $routes['/woopayments/reports'] );
		$this->assertFalse( $routes['/woopayments/card-readers'] );
		$this->assertFalse( $routes['/woopayments/loans'] );
		$this->assertFalse( $routes['/woopayments/documents'] );
	}

	/**
	 * @testdox Should preload onboarding-only admin route availability for accounts that are not ready.
	 * @dataProvider provider_not_ready_account_states
	 *
	 * @param array<string,bool> $account_state Account state overrides.
	 * @param string             $expected_title Expected menu item title.
	 */
	public function test_preloads_onboarding_only_admin_route_availability_for_accounts_that_are_not_ready( array $account_state, string $expected_title ): void {
		unset( $expected_title );

		$sut = $this->create_controller(
			true,
			array_merge(
				$account_state,
				array(
					'is_reports_enabled'         => true,
					'is_documents_enabled'       => true,
					'is_card_present_eligible'   => true,
					'has_card_readers_available' => true,
					'has_previous_capital_loans' => true,
				)
			)
		);

		$availability = $this->preload_route_availability( $sut );
		$routes       = $availability['allowedRoutes'];

		$this->assertTrue( $availability['gatewayEnabled'] );
		$this->assertSame( 'onboarding', $availability['accountState'] );
		$this->assertTrue( $routes['/woopayments/settings'] );
		$this->assertTrue( $routes['/woopayments/onboarding'] );
		$this->assertFalse( $routes['/woopayments/overview'] );
		$this->assertFalse( $routes['/woopayments/payouts'] );
		$this->assertFalse( $routes['/woopayments/transactions'] );
		$this->assertFalse( $routes['/woopayments/disputes'] );
		$this->assertFalse( $routes['/woopayments/reports'] );
		$this->assertFalse( $routes['/woopayments/card-readers'] );
		$this->assertFalse( $routes['/woopayments/loans'] );
		$this->assertFalse( $routes['/woopayments/documents'] );
	}

	/**
	 * @testdox Should preload optional admin routes only when their account-service predicates are true.
	 */
	public function test_preloads_optional_admin_routes_only_when_available(): void {
		$sut = $this->create_controller( true );

		$routes = $this->preload_route_availability( $sut )['allowedRoutes'];

		$this->assertTrue( $routes['/woopayments/settings'] );
		$this->assertTrue( $routes['/woopayments/overview'] );
		$this->assertFalse( $routes['/woopayments/reports'] );
		$this->assertFalse( $routes['/woopayments/card-readers'] );
		$this->assertFalse( $routes['/woopayments/loans'] );
		$this->assertFalse( $routes['/woopayments/documents'] );
	}

	/**
	 * @testdox Should not preload WooPayments route flags outside the Payments settings page.
	 */
	public function test_does_not_preload_reports_feature_flag_outside_payments_settings_request(): void {
		$_GET['page'] = 'wc-admin';
		$_GET['tab']  = 'checkout';
		$sut          = $this->create_controller(
			true,
			array(
				'is_reports_enabled' => true,
			)
		);

		$settings = $sut->preload_shared_settings( array() );

		$this->assertSame( array(), $settings );
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
	 * @testdox Should map legacy WooPayments WC Admin Documents URLs to the native Documents route when eligible.
	 */
	public function test_maps_legacy_payment_documents_url_to_native_route_when_documents_are_enabled(): void {
		$sut = $this->create_controller(
			true,
			array(
				'is_documents_enabled' => true,
			)
		);

		$url = $sut->get_legacy_payment_path_redirect_url(
			array(
				'page'          => 'wc-admin',
				'path'          => '%2Fpayments%2Fdocuments',
				'document_id'   => 'doc_native',
				'document_type' => 'vat_invoice',
			)
		);

		$this->assertStringContainsString( 'admin.php?page=wc-settings&tab=checkout', $url );
		$this->assertStringContainsString( 'path=/woopayments/documents', $url );
		$this->assertStringContainsString( 'document_id=doc_native', $url );
		$this->assertStringContainsString( 'document_type=vat_invoice', $url );
		$this->assertStringNotContainsString( 'page=wc-admin', $url );
	}

	/**
	 * @testdox Should not map legacy WooPayments WC Admin Documents URLs when Documents are not eligible.
	 */
	public function test_does_not_map_legacy_payment_documents_url_when_documents_are_disabled(): void {
		$sut = $this->create_controller(
			true,
			array(
				'is_documents_enabled' => false,
			)
		);

		$this->assertSame(
			'',
			$sut->get_legacy_payment_path_redirect_url(
				array(
					'page' => 'wc-admin',
					'path' => '%2Fpayments%2Fdocuments',
				)
			)
		);
	}

	/**
	 * @testdox Should map legacy WooPayments WC Admin Reports URLs to the native Reports route when Reports are enabled.
	 */
	public function test_maps_legacy_payment_reports_url_to_native_route_when_reports_are_enabled(): void {
		$sut = $this->create_controller(
			true,
			array(
				'is_reports_enabled' => true,
			)
		);

		$url = $sut->get_legacy_payment_path_redirect_url(
			array(
				'page'     => 'wc-admin',
				'path'     => '%2Fpayments%2Freports',
				'tab'      => 'fees',
				'view'     => 'fees',
				'currency' => 'USD',
			)
		);
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );

		$this->assertStringContainsString( 'admin.php?page=wc-settings&tab=checkout', $url );
		$this->assertStringContainsString( 'path=/woopayments/reports', $url );
		$this->assertStringContainsString( 'currency=USD', $url );
		$this->assertStringContainsString( 'view=fees', $url );
		$this->assertSame( 'checkout', $query['tab'] );
		$this->assertSame( 'fees', $query['report_tab'] );
		$this->assertStringNotContainsString( 'page=wc-admin', $url );
	}

	/**
	 * @testdox Should not map legacy WooPayments WC Admin Reports URLs when Reports are disabled.
	 */
	public function test_does_not_map_legacy_payment_reports_url_when_reports_are_disabled(): void {
		$sut = $this->create_controller(
			true,
			array(
				'is_reports_enabled' => false,
			)
		);

		$this->assertSame(
			'',
			$sut->get_legacy_payment_path_redirect_url(
				array(
					'page' => 'wc-admin',
					'path' => '%2Fpayments%2Freports',
				)
			)
		);
	}

	/**
	 * @testdox Should not map legacy full-access WooPayments WC Admin URLs for restricted accounts.
	 */
	public function test_does_not_map_legacy_full_access_urls_for_restricted_accounts(): void {
		$sut = $this->create_controller(
			true,
			array(
				'is_account_under_review' => true,
			)
		);

		$this->assertSame(
			'',
			$sut->get_legacy_payment_path_redirect_url(
				array(
					'page' => 'wc-admin',
					'path' => '%2Fpayments%2Fdeposits',
				)
			)
		);
	}

	/**
	 * @testdox Should map legacy WooPayments WC Admin Card Readers URLs to the native route when Card Readers are available.
	 */
	public function test_maps_legacy_payment_card_readers_url_to_native_route_when_card_readers_are_available(): void {
		$sut = $this->create_controller(
			true,
			array(
				'is_card_present_eligible'   => true,
				'has_card_readers_available' => true,
			)
		);

		$url = $sut->get_legacy_payment_path_redirect_url(
			array(
				'page' => 'wc-admin',
				'path' => '%2Fpayments%2Fcard-readers',
			)
		);

		$this->assertStringContainsString( 'admin.php?page=wc-settings&tab=checkout', $url );
		$this->assertStringContainsString( 'path=/woopayments/card-readers', $url );
		$this->assertStringNotContainsString( 'page=wc-admin', $url );
	}

	/**
	 * @testdox Should not map legacy WooPayments WC Admin Card Readers URLs when Card Readers are unavailable.
	 */
	public function test_does_not_map_legacy_payment_card_readers_url_when_card_readers_are_unavailable(): void {
		$sut = $this->create_controller(
			true,
			array(
				'is_card_present_eligible'   => true,
				'has_card_readers_available' => false,
			)
		);

		$this->assertSame(
			'',
			$sut->get_legacy_payment_path_redirect_url(
				array(
					'page' => 'wc-admin',
					'path' => '%2Fpayments%2Fcard-readers',
				)
			)
		);
	}

	/**
	 * @testdox Should map legacy WooPayments WC Admin Capital URLs to the native route when Capital is available.
	 */
	public function test_maps_legacy_payment_capital_url_to_native_route_when_capital_is_available(): void {
		$sut = $this->create_controller(
			true,
			array(
				'has_previous_capital_loans' => true,
			)
		);

		$url = $sut->get_legacy_payment_path_redirect_url(
			array(
				'page' => 'wc-admin',
				'path' => '%2Fpayments%2Floans',
			)
		);

		$this->assertStringContainsString( 'admin.php?page=wc-settings&tab=checkout', $url );
		$this->assertStringContainsString( 'path=/woopayments/loans', $url );
		$this->assertStringNotContainsString( 'page=wc-admin', $url );
	}

	/**
	 * @testdox Should not map legacy WooPayments WC Admin Capital URLs when Capital is unavailable.
	 */
	public function test_does_not_map_legacy_payment_capital_url_when_capital_is_unavailable(): void {
		$sut = $this->create_controller(
			true,
			array(
				'has_previous_capital_loans' => false,
			)
		);

		$this->assertSame(
			'',
			$sut->get_legacy_payment_path_redirect_url(
				array(
					'page' => 'wc-admin',
					'path' => '%2Fpayments%2Floans',
				)
			)
		);
	}

	/**
	 * @testdox Should not map legacy WooPayments WC Admin paths when the native gateway is disabled.
	 */
	public function test_does_not_map_legacy_payment_paths_when_native_gateway_is_disabled(): void {
		$sut = $this->create_controller(
			true,
			array(
				'is_gateway_enabled' => false,
			)
		);

		$this->assertSame(
			'',
			$sut->get_legacy_payment_path_redirect_url(
				array(
					'page' => 'wc-admin',
					'path' => '%2Fpayments%2Ftransactions',
				)
			)
		);
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
	 * @testdox Should not add WooPayments navigation when the native gateway is disabled.
	 */
	public function test_does_not_add_menu_items_when_native_gateway_is_disabled(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$sut = $this->create_controller(
			true,
			array(
				'is_gateway_enabled' => false,
			)
		);
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
				'is_documents_enabled'          => false,
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
	 * @testdox Should add a Disputes badge and direct the submenu to awaiting-response disputes.
	 */
	public function test_adds_disputes_badge_and_awaiting_response_filter(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$sut = $this->create_controller(
			true,
			array(),
			array(
				'disputes_awaiting_response' => 4,
			)
		);
		$sut->add_menu_items();

		$disputes_item = $this->get_submenu_item_by_title( 'Disputes' );

		$this->assertStringContainsString( 'wcpay-menu-badge awaiting-mod count-4', $disputes_item[0] );
		$this->assertStringContainsString( '<span class="plugin-count">4</span>', $disputes_item[0] );
		$this->assertStringContainsString( 'path=/woopayments/disputes', $disputes_item[2] );
		$this->assertStringContainsString( 'filter=awaiting_response', $disputes_item[2] );
	}

	/**
	 * @testdox Should omit the Disputes badge and filter when there are no awaiting-response disputes.
	 */
	public function test_omits_disputes_badge_for_zero_count(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$sut = $this->create_controller( true );
		$sut->add_menu_items();

		$disputes_item = $this->get_submenu_item_by_title( 'Disputes' );

		$this->assertSame( 'Disputes', $disputes_item[0] );
		$this->assertSame( $this->get_settings_url( '/woopayments/disputes' ), $disputes_item[2] );
	}

	/**
	 * @testdox Should add a Transactions badge when uncaptured transactions exist.
	 */
	public function test_adds_transactions_badge_for_uncaptured_transactions(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$sut = $this->create_controller(
			true,
			array(),
			array(
				'uncaptured_transactions' => 3,
			)
		);
		$sut->add_menu_items();

		$transactions_item = $this->get_submenu_item_by_title( 'Transactions' );

		$this->assertStringContainsString( 'wcpay-menu-badge awaiting-mod count-3', $transactions_item[0] );
		$this->assertStringContainsString( '<span class="plugin-count">3</span>', $transactions_item[0] );
		$this->assertSame( $this->get_settings_url( '/woopayments/transactions' ), $transactions_item[2] );
	}

	/**
	 * @testdox Should omit the Transactions badge when there are no uncaptured transactions.
	 */
	public function test_omits_transactions_badge_for_zero_count(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$sut = $this->create_controller( true );
		$sut->add_menu_items();

		$transactions_item = $this->get_submenu_item_by_title( 'Transactions' );

		$this->assertSame( 'Transactions', $transactions_item[0] );
		$this->assertSame( $this->get_settings_url( '/woopayments/transactions' ), $transactions_item[2] );
	}

	/**
	 * @testdox Should add Documents navigation only when Documents are eligible while Reports are disabled by default.
	 */
	public function test_adds_documents_menu_item_only_when_documents_are_enabled_and_reports_are_disabled_by_default(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$sut = $this->create_controller(
			true,
			array(
				'is_documents_enabled' => false,
			)
		);
		$sut->add_menu_items();

		$titles = array_map(
			static function ( array $item ): string {
				return trim( wp_strip_all_tags( $item[0] ) );
			},
			$this->get_payments_submenu_items()
		);

		$this->assertNotContains( 'Reports', $titles );
		$this->assertNotContains( 'Documents', $titles );

		$sut = $this->create_controller(
			true,
			array(
				'is_documents_enabled' => true,
			)
		);
		$sut->add_menu_items();

		$titles         = array_map(
			static function ( array $item ): string {
				return trim( wp_strip_all_tags( $item[0] ) );
			},
			$this->get_payments_submenu_items()
		);
		$documents_item = $this->get_submenu_item_by_title( 'Documents' );

		$this->assertNotContains( 'Reports', $titles );
		$this->assertContains( 'Documents', $titles );
		$this->assertSame( $this->get_settings_url( '/woopayments/documents' ), $documents_item[2] );
	}

	/**
	 * @testdox Should add Reports after Transactions for a valid native account when Reports are enabled.
	 */
	public function test_adds_reports_menu_item_after_transactions_when_reports_are_enabled(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$sut = $this->create_controller(
			true,
			array(
				'is_reports_enabled' => true,
			)
		);
		$sut->add_menu_items();

		$items = $this->get_payments_submenu_items();

		$this->assertSame(
			array(
				'Overview',
				'Payouts',
				'Transactions',
				'Reports',
				'Disputes',
				'Settings',
			),
			array_column( $items, 0 )
		);
		$this->assertSame( $this->get_settings_url( '/woopayments/reports' ), $this->get_submenu_item_by_title( 'Reports' )[2] );
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
		$this->assertNotContains( 'Reports', array_column( $items, 0 ) );
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
	 * @param array<string,int>  $badge_counts    Badge count overrides.
	 * @return WooPaymentsAdminNavigationController
	 */
	private function create_controller( bool $native_register, array $account_state = array(), array $badge_counts = array() ): WooPaymentsAdminNavigationController {
		$class_name = WooPaymentsAdminNavigationController::class;
		$this->assertTrue( class_exists( $class_name ), 'WooPayments admin navigation controller should exist.' );

		$arbiter = $this->getMockBuilder( NativePaymentsRuntimeArbiter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_native_register' ) )
			->getMock();
		$arbiter->method( 'should_native_register' )->willReturn( $native_register );

		$account_service = $this->create_account_service( $account_state );
		$badge_service   = $this->create_badge_service( $badge_counts );
		$controller      = new $class_name();
		$controller->init( $arbiter, $account_service, $badge_service );

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
				'is_gateway_enabled'                     => true,
				'is_card_present_eligible'               => false,
				'has_card_readers_available'             => false,
				'has_previous_capital_loans'             => false,
				'is_documents_enabled'                   => false,
				'is_reports_enabled'                     => false,
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
	 * Create a native badge service test double.
	 *
	 * @param array<string,int> $overrides Badge count overrides.
	 * @return WooPaymentsAdminMenuBadgeService&MockObject
	 */
	private function create_badge_service( array $overrides = array() ): WooPaymentsAdminMenuBadgeService {
		$counts = array_merge(
			array(
				'disputes_awaiting_response' => 0,
				'uncaptured_transactions'    => 0,
			),
			$overrides
		);

		$badge_service = $this->getMockBuilder( WooPaymentsAdminMenuBadgeService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_disputes_awaiting_response_count', 'get_uncaptured_transactions_count' ) )
			->getMock();

		$badge_service->method( 'get_disputes_awaiting_response_count' )->willReturn( $counts['disputes_awaiting_response'] );
		$badge_service->method( 'get_uncaptured_transactions_count' )->willReturn( $counts['uncaptured_transactions'] );

		return $badge_service;
	}

	/**
	 * Preload admin route availability on a Payments settings request.
	 *
	 * @param WooPaymentsAdminNavigationController $controller Controller under test.
	 * @return array{gatewayEnabled:bool,accountState:string,allowedRoutes:array<string,bool>}
	 */
	private function preload_route_availability( WooPaymentsAdminNavigationController $controller ): array {
		$_GET['page'] = 'wc-settings';
		$_GET['tab']  = 'checkout';

		return $controller->preload_shared_settings( array() )['woopaymentsSettings']['adminRouteAvailability'];
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
	 * Get a submenu item by its visible title prefix.
	 *
	 * @param string $title Menu title.
	 * @return array<int,string>
	 */
	private function get_submenu_item_by_title( string $title ): array {
		foreach ( $this->get_payments_submenu_items() as $item ) {
			if ( str_starts_with( $item[0], $title ) ) {
				return $item;
			}
		}

		$this->fail( sprintf( 'Expected submenu item "%s" to exist.', $title ) );
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
