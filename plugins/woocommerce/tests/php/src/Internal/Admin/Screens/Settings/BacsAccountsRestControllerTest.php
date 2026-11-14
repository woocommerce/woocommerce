<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Screens\Settings;

use Automattic\WooCommerce\Internal\Admin\Screens\AdminScreens;
use Automattic\WooCommerce\Internal\Admin\Screens\Settings\BacsAccountsRestController;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the BacsAccountsRestController class.
 */
class BacsAccountsRestControllerTest extends WC_Unit_Test_Case {

	/**
	 * The route under test.
	 */
	private const ROUTE = '/wc-admin/settings/payments/bacs/accounts';

	/**
	 * The System Under Test.
	 *
	 * @var BacsAccountsRestController
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'woocommerce_admin_features', array( $this, 'enable_admin_screens' ) );
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$payments = $this->createMock( Payments::class );
		$payments->method( 'get_country' )->willReturn( 'GB' );

		$this->sut = new BacsAccountsRestController();
		$this->sut->init( $payments );
		$this->create_rest_server_with_routes(
			array(
				function () {
					$this->sut->register_routes();
				},
			),
			true
		);
	}

	/**
	 * Tear down the REST server.
	 */
	public function tearDown(): void {
		try {
			$this->clear_rest_server();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Turn on the settings screen feature flag.
	 *
	 * @param array $features The enabled admin features.
	 * @return array
	 */
	public function enable_admin_screens( $features ) {
		$features[] = AdminScreens::FEATURE_FLAG;
		return $features;
	}

	/**
	 * @testdox Should return the saved accounts, the record ID and the business country.
	 */
	public function test_get_returns_saved_accounts(): void {
		$account = array(
			'account_name'   => 'Main',
			'account_number' => '12345678',
			'bank_name'      => 'Bank',
			'sort_code'      => '112233',
			'iban'           => '',
			'bic'            => '',
		);
		update_option( 'woocommerce_bacs_accounts', array( $account ) );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );

		$data = $response->get_data();
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'accounts', $data['id'], 'core-data needs the record ID' );
		$this->assertSame( array( $account ), $data['accounts'] );
		$this->assertSame( 'GB', $data['default_country'] );
	}

	/**
	 * @testdox Should save the accounts with every stored field and return them as saved.
	 */
	public function test_update_saves_and_returns_accounts(): void {
		$request = new WP_REST_Request( 'PUT', self::ROUTE );
		$request->set_body_params(
			array(
				'accounts' => array(
					array(
						'account_name' => ' Main <b>account</b> ',
						'iban'         => 'GB33BUKB20201555555555',
						'country_code' => 'GB',
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$expected = array(
			array(
				'account_name'   => 'Main account',
				'account_number' => '',
				'bank_name'      => '',
				'sort_code'      => '',
				'iban'           => 'GB33BUKB20201555555555',
				'bic'            => '',
				'country_code'   => 'GB',
			),
		);
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $expected, get_option( 'woocommerce_bacs_accounts' ), 'The option should hold the cleaned accounts' );
		$this->assertSame( $expected, $response->get_data()['accounts'], 'The response should return the saved accounts' );
	}

	/**
	 * @testdox Should fire woocommerce_update_option before saving, as the classic form does.
	 */
	public function test_update_fires_update_option_action(): void {
		$fired_with = null;
		add_action(
			'woocommerce_update_option',
			function ( $option ) use ( &$fired_with ) {
				$fired_with = $option;
			}
		);
		$request = new WP_REST_Request( 'PUT', self::ROUTE );
		$request->set_body_params( array( 'accounts' => array() ) );

		rest_get_server()->dispatch( $request );

		$this->assertSame( array( 'id' => 'woocommerce_bacs_accounts' ), $fired_with );
	}

	/**
	 * @testdox Should refuse users who can't manage payment gateways.
	 */
	public function test_refuses_users_without_capability(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );

		$this->assertSame( 403, $response->get_status() );
	}
}
