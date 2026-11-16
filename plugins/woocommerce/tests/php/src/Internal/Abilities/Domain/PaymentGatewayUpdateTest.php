<?php
/**
 * PaymentGatewayUpdateTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * Tests for the payment-gateway-update ability.
 */
class PaymentGatewayUpdateTest extends \WC_Unit_Test_Case {

	/**
	 * Administrator used as the current user.
	 *
	 * @var int
	 */
	private static $administrator_id;

	/**
	 * Init action count restored in tearDown.
	 *
	 * @var int|null
	 */
	private $original_init_count;

	/**
	 * Create the administrator once for the class.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$administrator_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Start each test from an empty abilities registry and freshly loaded gateways.
	 */
	public function setUp(): void {
		global $wp_actions;

		if ( ! function_exists( 'wp_register_ability' ) ) {
			require_once WC_ABSPATH . 'vendor/wordpress/abilities-api/includes/bootstrap.php';
		}

		// The Abilities API registry refuses to initialize before `init` has fired.
		$this->original_init_count = $wp_actions['init'] ?? null;
		$wp_actions['init']        = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		parent::setUp();

		wc_get_container()->get( AbilitiesLoader::class )->init();
		self::reset_registries();
		wp_set_current_user( self::$administrator_id );
		self::reload_payment_gateways();
	}

	/**
	 * Leave the registries, the loaded gateways and the init count as the next test expects them.
	 */
	public function tearDown(): void {
		global $wp_actions;

		try {
			self::reset_registries();
		} finally {
			parent::tearDown();
		}

		// The gateway instances outlive the rolled-back options.
		self::reload_payment_gateways();

		if ( null !== $this->original_init_count ) {
			$wp_actions['init'] = $this->original_init_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} else {
			unset( $wp_actions['init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * @testdox Should turn on a listed gateway, so the query reports it as enabled.
	 */
	public function test_enables_a_listed_gateway(): void {
		update_option( 'woocommerce_bacs_settings', array( 'enabled' => 'no' ) );
		self::reload_payment_gateways();

		$result = $this->run_ability(
			'woocommerce/payment-gateway-update',
			array(
				'id'      => 'bacs',
				'enabled' => true,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertTrue( $result['payment_gateway']['enabled'] );
		$this->assertSame( 'yes', get_option( 'woocommerce_bacs_settings' )['enabled'] );

		self::reload_payment_gateways();
		$query = $this->run_ability( 'woocommerce/payment-gateways-query', array( 'id' => 'bacs' ) );
		$this->assertTrue( $query['gateways'][0]['enabled'], 'The query should see the gateway enabled after a reload.' );
	}

	/**
	 * @testdox Should report the saved enabled state and title when a settings filter changes them, whichever fields are sent.
	 *
	 * @testWith [{"enabled": true, "title": "Pay by bank"}]
	 *           [{"enabled": true}]
	 *           [{"title": "Pay by bank"}]
	 *
	 * @param array $changes Fields sent with the gateway ID.
	 */
	public function test_reports_the_saved_settings_when_a_filter_changes_them( array $changes ): void {
		update_option(
			'woocommerce_bacs_settings',
			array(
				'enabled' => 'yes',
				'title'   => 'Direct bank transfer',
			)
		);
		self::reload_payment_gateways();
		add_filter(
			'woocommerce_settings_api_sanitized_fields_bacs',
			static function ( $settings ) {
				$settings['enabled'] = 'no';
				$settings['title']   = 'Bank transfer';
				return $settings;
			}
		);

		$result = $this->run_ability( 'woocommerce/payment-gateway-update', array( 'id' => 'bacs' ) + $changes );

		$this->assertNotWPError( $result );
		$this->assertFalse( $result['payment_gateway']['enabled'] );
		$this->assertSame( 'Bank transfer', $result['payment_gateway']['title'] );
	}

	/**
	 * @testdox Should turn off an enabled gateway.
	 */
	public function test_disables_a_gateway(): void {
		update_option( 'woocommerce_cod_settings', array( 'enabled' => 'yes' ) );
		self::reload_payment_gateways();

		$result = $this->run_ability(
			'woocommerce/payment-gateway-update',
			array(
				'id'      => 'cod',
				'enabled' => false,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertFalse( $result['payment_gateway']['enabled'] );
		$this->assertSame( 'no', get_option( 'woocommerce_cod_settings' )['enabled'] );
	}

	/**
	 * @testdox Should refuse any field other than enabled, title and order, and leave the settings unchanged.
	 *
	 * @testWith ["description", "Changed description"]
	 *           ["api_password", "stolen-password"]
	 *           ["settings", {"account_name": "Attacker"}]
	 *           ["method_title", "Changed"]
	 *
	 * @param string $field Field outside the allowlist.
	 * @param mixed  $value Value to write.
	 */
	public function test_refuses_fields_outside_the_allowlist( string $field, $value ): void {
		$settings = array(
			'enabled'     => 'no',
			'title'       => 'Bank transfer',
			'description' => 'Original description',
		);
		update_option( 'woocommerce_bacs_settings', $settings );
		self::reload_payment_gateways();

		$result = $this->run_ability(
			'woocommerce/payment-gateway-update',
			array(
				'id'      => 'bacs',
				'enabled' => true,
				$field    => $value,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
		$this->assertSame( $settings, get_option( 'woocommerce_bacs_settings' ), 'A refused call should write nothing.' );
	}

	/**
	 * @testdox Should write only the fields sent, leaving every other saved setting as it was.
	 */
	public function test_writes_only_the_fields_sent(): void {
		$settings = array(
			'enabled'         => 'no',
			'title'           => 'Bank transfer',
			'description'     => 'Pay by bank transfer.',
			'instructions'    => 'Use your order number as the reference.',
			'account_details' => 'secret-account-details',
		);
		update_option( 'woocommerce_bacs_settings', $settings );
		self::reload_payment_gateways();

		$result = $this->run_ability(
			'woocommerce/payment-gateway-update',
			array(
				'id'      => 'bacs',
				'enabled' => true,
				'title'   => 'Wire transfer',
			)
		);

		$this->assertNotWPError( $result );

		$saved = get_option( 'woocommerce_bacs_settings' );
		$this->assertSame( 'yes', $saved['enabled'] );
		$this->assertSame( 'Wire transfer', $saved['title'] );
		unset( $saved['enabled'], $saved['title'], $settings['enabled'], $settings['title'] );
		$this->assertSame( $settings, array_intersect_key( $saved, $settings ), 'Settings that were not sent should keep their values.' );
		$this->assertStringNotContainsString( 'secret-account-details', wp_json_encode( $result ) );
	}

	/**
	 * @testdox Should strip markup from a new title, as the gateway settings screen does.
	 */
	public function test_sanitizes_the_title(): void {
		$result = $this->run_ability(
			'woocommerce/payment-gateway-update',
			array(
				'id'    => 'cheque',
				'title' => 'Check <script>alert(1)</script>payments',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertStringNotContainsString( '<script', $result['payment_gateway']['title'] );
		$this->assertStringNotContainsString( '<script', get_option( 'woocommerce_cheque_settings' )['title'] );
	}

	/**
	 * @testdox Should refuse to turn on a gateway that needs setup, and tell the agent where the merchant finishes it.
	 */
	public function test_refuses_to_enable_a_gateway_that_needs_setup(): void {
		$gateway  = new class() extends \WC_Payment_Gateway {
			/**
			 * A gateway without a connected account.
			 */
			public function __construct() {
				$this->id           = 'unconnected_gateway';
				$this->method_title = 'Unconnected';
				$this->form_fields  = array(
					'enabled' => array( 'type' => 'checkbox' ),
					'title'   => array( 'type' => 'text' ),
				);
				$this->init_settings();
				$this->enabled = $this->get_option( 'enabled', 'no' );
			}

			/**
			 * Reports that setup is not finished.
			 *
			 * @return bool
			 */
			public function needs_setup() {
				return true;
			}
		};
		$register = static function ( $gateways ) use ( $gateway ) {
			$gateways[] = $gateway;
			return $gateways;
		};
		add_filter( 'woocommerce_payment_gateways', $register );
		update_option( 'woocommerce_unconnected_gateway_settings', array( 'enabled' => 'no' ) );
		self::reload_payment_gateways();

		$result = $this->run_ability(
			'woocommerce/payment-gateway-update',
			array(
				'id'      => 'unconnected_gateway',
				'enabled' => true,
			)
		);

		remove_filter( 'woocommerce_payment_gateways', $register );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_payment_gateway_needs_setup', $result->get_error_code() );
		$this->assertSame( array( 'enabled' => 'no' ), get_option( 'woocommerce_unconnected_gateway_settings' ) );
	}

	/**
	 * @testdox Should move a gateway to the first and the last checkout position, and report the position the query then shows.
	 */
	public function test_moves_a_gateway_in_the_checkout_order(): void {
		$this->register_online_gateway();
		$last = count( $this->get_saved_positions() ) - 1;

		$result = $this->move_gateway( 'online_test_gateway', 0 );
		$after  = $this->get_saved_positions();
		$this->assertSame( 0, $after['online_test_gateway'], 'The gateway should move first.' );
		$this->assertSame( 0, $result['payment_gateway']['order'] );

		$result = $this->move_gateway( 'online_test_gateway', $last );
		$after  = $this->get_saved_positions();
		$this->assertSame( $last, $after['online_test_gateway'], 'The gateway should move last.' );
		$this->assertSame( $last, $result['payment_gateway']['order'] );
	}

	/**
	 * @testdox Should move a gateway last when the position is past the end.
	 */
	public function test_moves_a_gateway_last_for_a_position_past_the_end(): void {
		$this->register_online_gateway();
		$this->move_gateway( 'online_test_gateway', 0 );

		$result = $this->move_gateway( 'online_test_gateway', 99 );

		$after = $this->get_saved_positions();
		$this->assertSame( count( $after ) - 1, $after['online_test_gateway'] );
		$this->assertSame( $after['online_test_gateway'], $result['payment_gateway']['order'] );
	}

	/**
	 * @testdox Should keep an offline method inside the offline methods when it is sent above a non-offline gateway.
	 */
	public function test_keeps_an_offline_method_inside_the_offline_methods(): void {
		$this->register_online_gateway();
		$this->move_gateway( 'online_test_gateway', 0 );
		$this->move_gateway( 'bacs', 99 );

		$result = $this->move_gateway( 'bacs', 0 );

		$after = $this->get_saved_positions();
		$this->assertSame( 0, $after['online_test_gateway'], 'The non-offline gateway should stay first.' );
		$this->assertSame( $this->get_offline_positions( $after )[0], $after['bacs'], 'The offline method should move to the top of the offline methods.' );
		$this->assertSame( $after['bacs'], $result['payment_gateway']['order'], 'The response should give the position checkout uses.' );
		$this->assert_offline_methods_stay_grouped( $after );
	}

	/**
	 * @testdox Should place a non-offline gateway just after the offline methods when it is moved down into them.
	 */
	public function test_places_a_gateway_moved_down_into_the_offline_methods_after_them(): void {
		$this->register_online_gateway();
		$this->move_gateway( 'online_test_gateway', 0 );
		$before = $this->get_saved_positions();

		$result = $this->move_gateway( 'online_test_gateway', $before['cheque'] );

		$after   = $this->get_saved_positions();
		$offline = $this->get_offline_positions( $after );
		$this->assertSame( end( $offline ) + 1, $after['online_test_gateway'] );
		$this->assertSame( $after['online_test_gateway'], $result['payment_gateway']['order'], 'The response should give the position checkout uses.' );
		$this->assert_offline_methods_stay_grouped( $after );
	}

	/**
	 * @testdox Should place a non-offline gateway just before the offline methods when it is moved up into them.
	 */
	public function test_places_a_gateway_moved_up_into_the_offline_methods_before_them(): void {
		$this->register_online_gateway();
		$this->move_gateway( 'online_test_gateway', 99 );
		$before = $this->get_saved_positions();

		$result = $this->move_gateway( 'online_test_gateway', $before['cheque'] );

		$after = $this->get_saved_positions();
		$this->assertSame( $this->get_offline_positions( $after )[0] - 1, $after['online_test_gateway'] );
		$this->assertSame( $after['online_test_gateway'], $result['payment_gateway']['order'], 'The response should give the position checkout uses.' );
		$this->assert_offline_methods_stay_grouped( $after );
	}

	/**
	 * @testdox Should leave the checkout order unchanged when the same move into the offline methods is sent again.
	 */
	public function test_repeats_a_move_into_the_offline_methods_without_changing_the_order(): void {
		$this->register_online_gateway();
		$this->move_gateway( 'online_test_gateway', 99 );
		$target = $this->get_saved_positions()['cheque'];

		$first = $this->move_gateway( 'online_test_gateway', $target );
		$once  = $this->get_saved_positions();

		$second = $this->move_gateway( 'online_test_gateway', $target );

		$this->assertSame( $once, $this->get_saved_positions() );
		$this->assertSame( $first['payment_gateway']['order'], $second['payment_gateway']['order'] );
	}

	/**
	 * @testdox Should move a gateway twice in one request, starting the second move from where the first left it.
	 */
	public function test_moves_a_gateway_twice_in_one_request(): void {
		$offline = $this->get_offline_positions( $this->get_saved_positions() );
		$input   = array( 'id' => 'bacs' );

		$this->run_ability( 'woocommerce/payment-gateway-update', $input + array( 'order' => end( $offline ) ) );
		$result = $this->run_ability( 'woocommerce/payment-gateway-update', $input + array( 'order' => $offline[0] ) );

		$this->assertNotWPError( $result );
		$this->assertSame( $offline[0], $result['payment_gateway']['order'] );
		$this->assertSame( $offline[0], $this->get_saved_positions()['bacs'] );
	}

	/**
	 * @testdox Should refuse a title that is empty once markup and whitespace are removed, and keep the saved title.
	 *
	 * @testWith ["   "]
	 *           ["<b></b>"]
	 *           ["<script>alert(1)</script>"]
	 *
	 * @param string $title Title without visible text.
	 */
	public function test_refuses_a_title_without_visible_text( string $title ): void {
		update_option( 'woocommerce_cheque_settings', array( 'title' => 'Check payments' ) );
		self::reload_payment_gateways();

		$result = $this->run_ability(
			'woocommerce/payment-gateway-update',
			array(
				'id'    => 'cheque',
				'title' => $title,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_payment_gateway_title_invalid', $result->get_error_code() );
		$this->assertSame( 'Check payments', get_option( 'woocommerce_cheque_settings' )['title'] );
	}

	/**
	 * @testdox Should return a not-found error for an unknown gateway ID.
	 */
	public function test_returns_not_found_for_an_unknown_gateway(): void {
		$result = $this->run_ability(
			'woocommerce/payment-gateway-update',
			array(
				'id'      => 'no-such-gateway',
				'enabled' => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_payment_gateway_not_found', $result->get_error_code() );
	}

	/**
	 * @testdox Should refuse a call that sends only the gateway ID.
	 */
	public function test_refuses_a_call_without_a_change(): void {
		$result = $this->run_ability( 'woocommerce/payment-gateway-update', array( 'id' => 'bacs' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should let a shop manager update a gateway.
	 */
	public function test_allows_a_shop_manager(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'shop_manager' ) ) );

		$result = $this->run_ability(
			'woocommerce/payment-gateway-update',
			array(
				'id'      => 'cheque',
				'enabled' => false,
			)
		);

		$this->assertNotWPError( $result );
	}

	/**
	 * Run an ability as the current user, with its permission check.
	 *
	 * @param string $name  Ability name.
	 * @param array  $input Ability input.
	 * @return mixed|\WP_Error
	 */
	private function run_ability( string $name, array $input = array() ) {
		$ability = wp_get_ability( $name );
		$this->assertNotNull( $ability, "Ability {$name} should be registered." );

		return $ability->execute( $input );
	}

	/**
	 * Register a gateway outside the offline methods group. The base teardown removes the filter.
	 */
	private function register_online_gateway(): void {
		$gateway = new class() extends \WC_Payment_Gateway {
			/**
			 * A connected gateway that is not an offline method.
			 */
			public function __construct() {
				$this->id           = 'online_test_gateway';
				$this->method_title = 'Online';
				$this->title        = 'Online';
				$this->enabled      = 'yes';
			}
		};
		add_filter(
			'woocommerce_payment_gateways',
			static function ( $gateways ) use ( $gateway ) {
				$gateways[] = $gateway;
				return $gateways;
			}
		);
		self::reload_payment_gateways();
	}

	/**
	 * Positions as the next request's query reports them.
	 *
	 * @return array<string, int>
	 */
	private function get_saved_positions(): array {
		self::reload_payment_gateways();

		return array_column( $this->run_ability( 'woocommerce/payment-gateways-query' )['gateways'], 'order', 'id' );
	}

	/**
	 * Move a gateway and fail the test if the ability refuses.
	 *
	 * @param string $id    Gateway ID.
	 * @param int    $order Target position.
	 * @return array
	 */
	private function move_gateway( string $id, int $order ): array {
		$result = $this->run_ability(
			'woocommerce/payment-gateway-update',
			array(
				'id'    => $id,
				'order' => $order,
			)
		);
		$this->assertNotWPError( $result );
		self::reload_payment_gateways();

		return $result;
	}

	/**
	 * Sorted positions of the three Core offline methods.
	 *
	 * @param array<string, int> $positions Positions keyed by gateway ID.
	 * @return array<int, int>
	 */
	private function get_offline_positions( array $positions ): array {
		$offline = array_values( array_intersect_key( $positions, array_flip( array( 'bacs', 'cheque', 'cod' ) ) ) );
		sort( $offline );

		return $offline;
	}

	/**
	 * Assert the loaded offline methods sit next to each other in the checkout order.
	 *
	 * @param array<string, int> $positions Positions keyed by gateway ID.
	 */
	private function assert_offline_methods_stay_grouped( array $positions ): void {
		$offline = $this->get_offline_positions( $positions );

		$this->assertCount( 3, $offline, 'The test site should load all three offline methods.' );
		$this->assertSame( count( $offline ) - 1, end( $offline ) - $offline[0], 'Offline methods should stay grouped.' );
	}

	/**
	 * Rebuild the loaded payment gateways from the current options, since init() only appends.
	 */
	private static function reload_payment_gateways(): void {
		WC()->payment_gateways()->payment_gateways = array();
		WC()->payment_gateways()->init();
	}

	/**
	 * Drop every registered ability, so the next read registers them again.
	 */
	private static function reset_registries(): void {
		global $wp_actions;

		foreach ( array( 'WP_Abilities_Registry', 'WP_Ability_Categories_Registry' ) as $registry ) {
			if ( class_exists( $registry ) ) {
				$instance = new \ReflectionProperty( $registry, 'instance' );
				$instance->setAccessible( true );
				$instance->setValue( null, null );
			}
		}
		unset( $wp_actions['wp_abilities_api_init'], $wp_actions['wp_abilities_api_categories_init'] );
	}
}
