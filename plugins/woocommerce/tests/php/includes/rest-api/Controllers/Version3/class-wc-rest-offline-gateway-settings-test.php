<?php
/**
 * Compatibility tests for editable Core offline gateway fields in v3.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

/**
 * Exercises the new fields alongside the existing settings contract.
 *
 * @mixin \PHPUnit\Framework\TestCase WordPress's polyfill ancestry is not fully resolved by PHPStan.
 */
class WC_REST_Offline_Gateway_Settings_Test extends WC_REST_Unit_Test_Case {
	/** @var int Shared administrator. */
	private static $admin_id;
	/** @var WC_REST_Payment_Gateways_Controller The System Under Test. */
	private $sut;

	/**
	 * Create the shared administrator.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		$id = $factory->user->create( array( 'role' => 'administrator' ) );
		if ( is_wp_error( $id ) ) {
			self::fail( $id->get_error_message() );
		}
		self::$admin_id = $id;
	}

	/** Delete the shared administrator. */
	public static function wpTearDownAfterClass(): void {
		wp_delete_user( self::$admin_id );
	}

	/** Set up gateways and routes. */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::$admin_id );
		foreach ( array( 'bacs', 'cheque', 'cod' ) as $id ) {
			update_option(
				'woocommerce_' . $id . '_settings',
				array(
					'enabled'            => 'no',
					'title'              => 'Original',
					'description'        => 'Original description',
					'instructions'       => 'Original instructions',
					'enable_for_methods' => array(),
					'enable_for_virtual' => 'yes',
					'unrelated'          => 'Keep me',
				)
			);
		}
		update_option( 'woocommerce_bacs_accounts', array( $this->account() ) );
		$gateways                   = WC_Payment_Gateways::instance();
		$gateways->payment_gateways = array();
		$gateways->init();
		$this->sut = new WC_REST_Payment_Gateways_Controller();
		$this->sut->register_routes();
	}

	/**
	 * An account with a country unsupported by the classic form's visible fields.
	 *
	 * @return array
	 */
	private function account(): array {
		return array(
			'account_name'   => 'Example',
			'account_number' => '12345678',
			'bank_name'      => 'Bank',
			'sort_code'      => '12-34-56',
			'iban'           => '',
			'bic'            => '',
			'country_code'   => 'GB',
		);
	}

	/**
	 * Dispatch a request through the public v3 route.
	 *
	 * @param string $id Gateway ID.
	 * @param array  $data Update data.
	 * @return WP_REST_Response
	 */
	private function save( string $id, array $data ): WP_REST_Response {
		$request = new WP_REST_Request( 'PUT', '/wc/v3/payment_gateways/' . $id );
		$request->set_body_params( $data );
		return $this->server->dispatch( $request );
	}

	/**
	 * @testdox Flat fields and legacy settings describe the same saved values.
	 * @param string $id Core gateway ID.
	 * @testWith ["bacs"]
	 *           ["cheque"]
	 *           ["cod"]
	 */
	public function test_flat_fields_round_trip_with_legacy_settings( string $id ): void {
		$legacy = $this->save(
			$id,
			array(
				'settings' => array(
					'instructions'       => 'Legacy save',
					'enable_for_virtual' => 'no',
				),
			)
		);
		$this->assertSame( 200, $legacy->get_status() );
		$this->assertSame( 'Legacy save', $legacy->get_data()['instructions'] );
		$this->assertFalse( $legacy->get_data()['enable_for_virtual'] );

		$response = $this->save(
			$id,
			array(
				'instructions'       => 'New save',
				'enable_for_virtual' => true,
			)
		);
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'New save', $response->get_data()['settings']['instructions']['value'] );
		$this->assertSame( 'yes', $response->get_data()['settings']['enable_for_virtual']['value'] );
		$this->assertSame( 'Keep me', get_option( 'woocommerce_' . $id . '_settings' )['unrelated'] );
	}

	/** @testdox BACS accounts retain their countries and order when saved with gateway settings. */
	public function test_accounts_and_settings_save_together(): void {
		$other                 = $this->account();
		$other['account_name'] = '<b>Second</b>';
		$other['country_code'] = 'US';
		$response              = $this->save(
			'bacs',
			array(
				'title'           => 'New title',
				'account_details' => array( $other, $this->account() ),
			)
		);
		$this->assertSame( 200, $response->get_status() );
		$accounts = get_option( 'woocommerce_bacs_accounts' );
		$this->assertSame( 'Second', $accounts[0]['account_name'] );
		$this->assertSame( array( 'US', 'GB' ), array_column( $accounts, 'country_code' ) );
		$this->assertSame( $accounts, $response->get_data()['account_details'] );
		$this->assertSame( 'New title', get_option( 'woocommerce_bacs_settings' )['title'] );
	}

	/** @testdox Omitting accounts preserves them and an explicit empty array clears them. */
	public function test_account_omission_and_clear(): void {
		$this->assertSame( 200, $this->save( 'bacs', array( 'instructions' => 'Changed' ) )->get_status() );
		$this->assertSame( array( $this->account() ), get_option( 'woocommerce_bacs_accounts' ) );
		$this->assertSame( 200, $this->save( 'bacs', array( 'account_details' => array() ) )->get_status() );
		$this->assertSame( array(), get_option( 'woocommerce_bacs_accounts' ) );
	}

	/**
	 * @testdox Invalid account data prevents all requested settings changes.
	 * @param array $account Invalid account.
	 * @testWith [{"account_name": ["invalid"]}]
	 *           [{"account_name": "", "account_number": "12345678", "country_code": "GB"}]
	 *           [{"account_name": "Example", "sort_code": "", "country_code": "GB"}]
	 */
	public function test_invalid_accounts_do_not_partially_save( array $account ): void {
		$before   = get_option( 'woocommerce_bacs_settings' );
		$response = $this->save(
			'bacs',
			array(
				'title'           => 'Must not save',
				'instructions'    => 'Must not save',
				'account_details' => array( $account ),
			)
		);
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( $before, get_option( 'woocommerce_bacs_settings' ) );
		$this->assertSame( array( $this->account() ), get_option( 'woocommerce_bacs_accounts' ) );
	}

	/** @testdox A row containing only country metadata is ignored. */
	public function test_country_only_accounts_are_ignored(): void {
		$response = $this->save( 'bacs', array( 'account_details' => array( array( 'country_code' => 'GB' ) ) ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array(), get_option( 'woocommerce_bacs_accounts' ) );
	}

	/** @testdox Countries without a sort code field accept accounts without a sort code. */
	public function test_sort_code_is_country_specific(): void {
		$account                 = $this->account();
		$account['country_code'] = 'FR';
		$account['sort_code']    = '';
		$this->assertSame( 200, $this->save( 'bacs', array( 'account_details' => array( $account ) ) )->get_status() );
		$this->assertSame( array( $account ), get_option( 'woocommerce_bacs_accounts' ) );
	}

	/** @testdox Conflicting flat and legacy values are rejected without changing storage. */
	public function test_conflicting_aliases_do_not_save(): void {
		$before   = get_option( 'woocommerce_cheque_settings' );
		$response = $this->save(
			'cheque',
			array(
				'instructions' => 'One',
				'settings'     => array( 'instructions' => 'Two' ),
			)
		);
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( $before, get_option( 'woocommerce_cheque_settings' ) );
	}

	/** @testdox Matching flat and legacy checkbox values are accepted after normalisation. */
	public function test_matching_aliases_save(): void {
		$response = $this->save(
			'cod',
			array(
				'enable_for_virtual' => false,
				'settings'           => array( 'enable_for_virtual' => 'no' ),
			)
		);
		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['enable_for_virtual'] );
	}

	/** @testdox Invalid legacy settings prevent account writes in a mixed request. */
	public function test_invalid_legacy_settings_prevent_account_writes(): void {
		$before   = get_option( 'woocommerce_bacs_settings' );
		$response = $this->save(
			'bacs',
			array(
				'account_details' => array(),
				'settings'        => array( 'enable_for_virtual' => 'invalid' ),
			)
		);
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( $before, get_option( 'woocommerce_bacs_settings' ) );
		$this->assertSame( array( $this->account() ), get_option( 'woocommerce_bacs_accounts' ) );
	}

	/** @testdox The classic BACS form preserves country metadata written through v3. */
	public function test_classic_bank_form_round_trip(): void {
		$this->assertSame( 200, $this->save( 'bacs', array( 'account_details' => array( $this->account() ) ) )->get_status() );
		$gateway = new WC_Gateway_BACS();
		ob_start();
		$html = $gateway->generate_account_details_html();
		ob_end_clean();
		$this->assertStringContainsString( 'name="bacs_country_code[0]" value="GB"', $html );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is a test fixture, not a request handler.
		$original_post = $_POST;
		try {
			foreach ( $this->account() as $key => $value ) {
				$_POST[ 'bacs_' . $key ] = array( $value );
			}
			$gateway->save_account_details();
		} finally {
			$_POST = $original_post;
		}
		$this->assertSame( array( $this->account() ), get_option( 'woocommerce_bacs_accounts' ) );
	}

	/** @testdox Legacy and new saves retain the existing settings and response filters. */
	public function test_existing_filters_are_preserved(): void {
		$settings_calls = 0;
		$response_calls = 0;
		add_filter(
			'woocommerce_gateway_bacs_settings_values',
			static function ( $values, $gateway ) use ( &$settings_calls ) {
				self::assertSame( $values['title'], $gateway->title );
				++$settings_calls;
				$values['unrelated'] = 'Filtered';
				return $values;
			},
			10,
			2
		);
		add_filter(
			'woocommerce_rest_prepare_payment_gateway',
			static function ( $response ) use ( &$response_calls ) {
				++$response_calls;
				$data = $response->get_data();
				unset( $data['account_details'] );
				$response->set_data( $data );
				return $response;
			}
		);
		foreach ( array(
			array( 'settings' => array( 'instructions' => 'Legacy' ) ),
			array(
				'instructions' => 'New',
				'title'        => 'Updated title',
			),
		) as $input ) {
			$response = $this->save( 'bacs', $input );
			$this->assertSame( 200, $response->get_status() );
			$this->assertArrayNotHasKey( 'account_details', $response->get_data() );
			$this->assertSame( 'Filtered', get_option( 'woocommerce_bacs_settings' )['unrelated'] );
		}
		$this->assertSame( 2, $settings_calls );
		$this->assertSame( 2, $response_calls );
	}

	/** @testdox Users without gateway permissions cannot save the new fields. */
	public function test_permissions_are_unchanged(): void {
		wp_set_current_user( 0 );
		$this->assertSame( 401, $this->save( 'bacs', array( 'account_details' => array() ) )->get_status() );
		$this->assertSame( array( $this->account() ), get_option( 'woocommerce_bacs_accounts' ) );
	}
}
