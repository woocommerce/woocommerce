<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyAdminNoticesController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyAdminNoticesController class.
 */
class MultiCurrencyAdminNoticesControllerTest extends WC_Unit_Test_Case {

	private const NOTICE_OPTION   = 'wcpay_multi_currency_show_store_currency_changed_notice';
	private const NOTICE_QUERY    = 'wcpay-multi-currency-hide-notice';
	private const NONCE_QUERY     = '_wcpay_multi_currency_notice_nonce';
	private const NONCE_ACTION    = 'wcpay_multi_currency_hide_notices_nonce';
	private const ADMIN_NOTICES   = 'admin_notices';
	private const WP_LOADED       = 'wp_loaded';
	private const NOTICE_MESSAGE  = 'The store currency was recently changed. The following currencies are set to manual rates and may need updates: Canadian dollar, Euro';
	private const FORBIDDEN_ERROR = 'Sorry, you are not allowed to do that.';
	private const NONCE_ERROR     = 'Action failed. Please refresh the page and retry.';

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down(): void {
		remove_all_filters( self::ADMIN_NOTICES );
		remove_all_filters( self::WP_LOADED );
		delete_option( self::NOTICE_OPTION );
		unset( $_GET[ self::NOTICE_QUERY ], $_GET[ self::NONCE_QUERY ] );
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * @testdox Should not register admin notice hooks when plugin owns runtime.
	 */
	public function test_does_not_register_admin_notice_hooks_when_plugin_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );

		$sut->register();

		$this->assertFalse( has_action( self::ADMIN_NOTICES, array( $sut, 'handle_admin_notices' ) ) );
		$this->assertFalse( has_action( self::WP_LOADED, array( $sut, 'handle_wp_loaded' ) ) );
	}

	/**
	 * @testdox Should register admin notice hooks once when core owns runtime.
	 */
	public function test_registers_admin_notice_hooks_once_when_core_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$sut->register();
		$sut->register();

		$this->assertSame( 10, has_action( self::ADMIN_NOTICES, array( $sut, 'handle_admin_notices' ) ) );
		$this->assertSame( 10, has_action( self::WP_LOADED, array( $sut, 'handle_wp_loaded' ) ) );
	}

	/**
	 * @testdox Should render the manual rate notice for users who can manage WooCommerce.
	 */
	public function test_renders_manual_rate_notice_for_users_who_can_manage_woocommerce(): void {
		$this->set_current_user_can_manage_woocommerce();
		update_option( self::NOTICE_OPTION, array( 'Canadian dollar', 'Euro' ) );
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		ob_start();
		$sut->handle_admin_notices();
		$markup = ob_get_clean();

		$this->assertIsString( $markup );
		$this->assertStringContainsString( 'class="notice notice-warning"', $markup );
		$this->assertStringContainsString( self::NOTICE_MESSAGE, $markup );
		$this->assertStringContainsString( self::NOTICE_QUERY . '=currency_changed', $markup );
		$this->assertStringContainsString( self::NONCE_QUERY, $markup );
		$this->assertStringContainsString( 'class="woocommerce-message-close notice-dismiss"', $markup );
	}

	/**
	 * @testdox Should not render notices for users who cannot manage WooCommerce.
	 */
	public function test_does_not_render_notices_for_users_who_cannot_manage_woocommerce(): void {
		$this->set_current_user_cannot_manage_woocommerce();
		update_option( self::NOTICE_OPTION, array( 'Canadian dollar', 'Euro' ) );
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		ob_start();
		$sut->handle_admin_notices();
		$markup = ob_get_clean();

		$this->assertSame( '', $markup );
	}

	/**
	 * @testdox Should hide the currency changed notice for a valid dismissal request.
	 */
	public function test_hides_currency_changed_notice_for_valid_dismissal_request(): void {
		$this->set_current_user_can_manage_woocommerce();
		update_option( self::NOTICE_OPTION, array( 'Canadian dollar' ) );
		$_GET[ self::NOTICE_QUERY ] = 'currency_changed';
		$_GET[ self::NONCE_QUERY ]  = wp_create_nonce( self::NONCE_ACTION );
		$sut                        = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$sut->handle_wp_loaded();

		$this->assertSame( 'no', get_option( self::NOTICE_OPTION ) );
	}

	/**
	 * @testdox Should die for an invalid dismissal nonce.
	 */
	public function test_dies_for_invalid_dismissal_nonce(): void {
		$this->set_current_user_can_manage_woocommerce();
		$_GET[ self::NOTICE_QUERY ] = 'currency_changed';
		$_GET[ self::NONCE_QUERY ]  = 'invalid';
		$messages                   = array();
		$sut                        = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$sut->set_die_handler( $this->create_die_handler( $messages ) );

		$this->expectException( \RuntimeException::class );

		try {
			$sut->handle_wp_loaded();
		} finally {
			$this->assertSame( array( self::NONCE_ERROR ), $messages );
		}
	}

	/**
	 * @testdox Should die for a forbidden dismissal request.
	 */
	public function test_dies_for_forbidden_dismissal_request(): void {
		$this->set_current_user_cannot_manage_woocommerce();
		$_GET[ self::NOTICE_QUERY ] = 'currency_changed';
		$_GET[ self::NONCE_QUERY ]  = wp_create_nonce( self::NONCE_ACTION );
		$messages                   = array();
		$sut                        = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$sut->set_die_handler( $this->create_die_handler( $messages ) );

		$this->expectException( \RuntimeException::class );

		try {
			$sut->handle_wp_loaded();
		} finally {
			$this->assertSame( array( self::FORBIDDEN_ERROR ), $messages );
		}
	}

	/**
	 * Create an admin notices controller.
	 *
	 * @param string $owner Runtime owner.
	 * @return MultiCurrencyAdminNoticesController
	 */
	private function create_controller( string $owner ): MultiCurrencyAdminNoticesController {
		$controller = new MultiCurrencyAdminNoticesController();
		$controller->init( $this->create_arbiter( $owner ) );

		return $controller;
	}

	/**
	 * Create a die handler test double.
	 *
	 * @param array<int,string> $messages Captured die messages.
	 * @return callable
	 */
	private function create_die_handler( array &$messages ): callable {
		return static function ( $message ) use ( &$messages ): void {
			$messages[] = wp_strip_all_tags( (string) $message );

			throw new \RuntimeException( 'wp_die intercepted' );
		};
	}

	/**
	 * Set the current user to one who can manage WooCommerce.
	 *
	 * @return void
	 */
	private function set_current_user_can_manage_woocommerce(): void {
		$user_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		$user    = get_user_by( 'id', $user_id );

		if ( $user instanceof \WP_User ) {
			$user->add_cap( 'manage_woocommerce' );
		}

		wp_set_current_user( $user_id );
	}

	/**
	 * Set the current user to one who cannot manage WooCommerce.
	 *
	 * @return void
	 */
	private function set_current_user_cannot_manage_woocommerce(): void {
		$user_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		$user    = get_user_by( 'id', $user_id );

		if ( $user instanceof \WP_User ) {
			$user->remove_cap( 'manage_woocommerce' );
		}

		wp_set_current_user( $user_id );
	}

	/**
	 * Create a static multi-currency runtime arbiter.
	 *
	 * @param string $owner Runtime owner.
	 * @return MultiCurrencyRuntimeArbiter
	 */
	private function create_arbiter( string $owner ): MultiCurrencyRuntimeArbiter {
		return new class( $owner ) extends MultiCurrencyRuntimeArbiter {
			/**
			 * Runtime owner.
			 *
			 * @var string
			 */
			private string $owner;

			/**
			 * Constructor.
			 *
			 * @param string $owner Runtime owner.
			 */
			public function __construct( string $owner ) {
				$this->owner = $owner;
			}

			/**
			 * Get the multi-currency runtime owner for the current site.
			 *
			 * @return string
			 */
			public function get_runtime_owner(): string {
				return $this->owner;
			}

			/**
			 * Tell whether core multi-currency may register hooks.
			 *
			 * @return bool
			 */
			public function should_core_register(): bool {
				return MultiCurrencyRuntimeArbiter::OWNER_CORE === $this->owner;
			}
		};
	}
}
