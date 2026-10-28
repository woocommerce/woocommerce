<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Onboarding;

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingSetupWizard;
use RuntimeException;
use WC_Unit_Test_Case;
use WPDieException;

/**
 * Tests for the OnboardingSetupWizard class.
 */
class OnboardingSetupWizardTest extends WC_Unit_Test_Case {

	private const ACTION = 'woocommerce_skip_onboarding_setup';

	/**
	 * The System Under Test.
	 *
	 * @var OnboardingSetupWizard
	 */
	private $sut;

	/**
	 * Shared administrator user ID.
	 *
	 * @var int
	 */
	private static $admin_id;

	/**
	 * Shared subscriber user ID.
	 *
	 * @var int
	 */
	private static $subscriber_id;

	/**
	 * Shared shop manager user ID.
	 *
	 * @var int
	 */
	private static $shop_manager_id;

	/**
	 * Create immutable user fixtures once for the class.
	 *
	 * @param \WP_UnitTest_Factory $factory Test fixture factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$admin_id        = $factory->user->create( array( 'role' => 'administrator' ) );
		self::$subscriber_id   = $factory->user->create( array( 'role' => 'subscriber' ) );
		self::$shop_manager_id = $factory->user->create( array( 'role' => 'shop_manager' ) );
	}

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = OnboardingSetupWizard::instance();

		delete_option( OnboardingProfile::DATA_OPTION );
	}

	/**
	 * @testdox The production singleton handles only the authenticated skip action.
	 */
	public function test_registers_authenticated_skip_action_only(): void {
		$callback = array( $this->sut, 'handle_admin_post_woocommerce_skip_onboarding_setup' );
		set_current_screen( 'dashboard' );
		$this->sut->init();

		$this->assertSame(
			10,
			has_action( 'admin_post_' . self::ACTION, $callback ),
			'The authenticated admin-post action should use the production singleton callback.'
		);
		$this->assertFalse(
			has_action( 'admin_post_nopriv_' . self::ACTION ),
			'The skip fallback must not be available to logged-out requests.'
		);
	}

	/**
	 * @testdox An administrator receives a raw, nonce-protected admin-post fallback URL while setup can still be skipped.
	 *
	 * @dataProvider data_provider_test_component_settings_exposes_fallback_url
	 *
	 * @param array<string, mixed>|null $profile Existing profile, or null when absent.
	 * @param array<string, string>     $query   Query variables for the request.
	 */
	public function test_component_settings_exposes_fallback_url( ?array $profile, array $query ): void {
		$this->set_profile( $profile );
		wp_set_current_user( self::$admin_id );
		$_GET = $query;
		set_current_screen( 'dashboard' );

		$settings = $this->sut->component_settings( array() );
		$url      = $settings['onboarding']['skipOnboardingFallbackUrl'] ?? '';
		$query    = array();
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$nonce = is_string( $query['_wpnonce'] ?? null ) ? $query['_wpnonce'] : '';

		$this->assertSame(
			admin_url( 'admin-post.php' ),
			remove_query_arg( array( 'action', '_wpnonce' ), $url ),
			'The fallback should use the WordPress admin-post endpoint.'
		);
		$this->assertSame( self::ACTION, $query['action'] ?? null, 'The fallback URL should carry the exact action.' );
		$this->assertCount( 2, $query, 'The fallback URL should contain only the action and nonce.' );
		$this->assertContains(
			wp_verify_nonce( $nonce, self::ACTION ),
			array( 1, 2 ),
			'The fallback nonce should verify for the skip action.'
		);
		$this->assertStringNotContainsString( '&amp;', $url, 'The preloaded URL must remain raw for JSON serialization.' );
		$this->assertSame( admin_url( 'site-health.php' ), $settings['onboarding']['siteHealthUrl'] ?? null, 'Administrators who can open Site Health should get its URL.' );
	}

	/**
	 * @testdox A shop manager gets the fallback URL but no Site Health URL, because they cannot open Site Health.
	 */
	public function test_component_settings_omits_site_health_url_for_shop_manager(): void {
		wp_set_current_user( self::$shop_manager_id );
		$_GET = array(
			'page' => 'wc-admin',
			'path' => '/setup-wizard',
		);
		set_current_screen( 'dashboard' );

		$settings = $this->sut->component_settings( array() );

		$this->assertArrayHasKey( 'skipOnboardingFallbackUrl', $settings['onboarding'], 'Shop managers can skip setup.' );
		$this->assertArrayNotHasKey( 'siteHealthUrl', $settings['onboarding'], 'Shop managers cannot open Site Health.' );
	}

	/**
	 * Data provider for fallback URL exposure tests.
	 *
	 * @return array<string, array{array<string, mixed>|null, array<string, string>}>
	 */
	public static function data_provider_test_component_settings_exposes_fallback_url(): array {
		return array(
			'setup wizard after onboarding was completed' => array(
				array( 'completed' => true ),
				array(
					'page' => 'wc-admin',
					'path' => '/setup-wizard',
				),
			),
			'Home while onboarding is pending'            => array(
				null,
				array( 'page' => 'wc-admin' ),
			),
		);
	}

	/**
	 * @testdox The fallback URL is hidden once onboarding is done outside the setup wizard, and from users without permission.
	 *
	 * @dataProvider data_provider_test_component_settings_hides_fallback_url_without_access
	 *
	 * @param string                    $role    Fixture role to use.
	 * @param array<string, mixed>|null $profile Existing profile, or null when absent.
	 * @param array<string, string>     $query   Query variables for the request.
	 */
	public function test_component_settings_hides_fallback_url_without_access( string $role, ?array $profile, array $query ): void {
		$this->set_profile( $profile );
		$user_id = 'administrator' === $role ? self::$admin_id : self::$subscriber_id;
		wp_set_current_user( $user_id );
		$_GET = $query;
		set_current_screen( 'dashboard' );

		$settings = $this->sut->component_settings( array() );

		$this->assertArrayNotHasKey(
			'skipOnboardingFallbackUrl',
			$settings['onboarding'],
			'The fallback URL should only be preloaded for authorized users who can still skip setup.'
		);
	}

	/**
	 * Data provider for fallback URL visibility tests.
	 *
	 * @return array<string, array{string, array<string, mixed>|null, array<string, string>}>
	 */
	public static function data_provider_test_component_settings_hides_fallback_url_without_access(): array {
		return array(
			'administrator on Home after skipping'   => array(
				'administrator',
				array( 'skipped' => true ),
				array( 'page' => 'wc-admin' ),
			),
			'administrator on Home after completion' => array(
				'administrator',
				array( 'completed' => true ),
				array( 'page' => 'wc-admin' ),
			),
			'subscriber on setup wizard'             => array(
				'subscriber',
				null,
				array(
					'page' => 'wc-admin',
					'path' => '/setup-wizard',
				),
			),
		);
	}

	/**
	 * @testdox A valid request persists only the skipped flag and confirms it with links to Site Health and WooCommerce Home.
	 *
	 * @dataProvider data_provider_test_valid_request_persists_skipped_and_confirms
	 *
	 * @param array<string, mixed>|string|null $profile          Existing profile, or null when absent.
	 * @param array<int|string, mixed>         $expected_profile Expected persisted profile.
	 */
	public function test_valid_request_persists_skipped_and_confirms( $profile, array $expected_profile ): void {
		$this->set_profile( $profile );
		$this->prepare_valid_request( self::$admin_id );
		$rest_update_actions = 0;
		$observe_rest_update = static function () use ( &$rest_update_actions ): void {
			++$rest_update_actions;
		};
		add_action( 'woocommerce_onboarding_profile_data_updated', $observe_rest_update, 10, 0 );

		try {
			$response = $this->dispatch_request();
		} finally {
			remove_action( 'woocommerce_onboarding_profile_data_updated', $observe_rest_update, 10 );
		}
		$stored = get_option( OnboardingProfile::DATA_OPTION );

		$page = $response->getMessage();
		$this->assertSame( 200, $response->getCode(), 'A successful fallback should answer with HTTP 200.' );
		$this->assertStringStartsWith( '<h1>WooCommerce setup skipped</h1><p>Your choice to skip setup is saved.', $page, 'The confirmation should open with its heading and say the skip is saved.' );
		$this->assertStringContainsString(
			'<p>If WooCommerce screens keep failing to load, check <a href="' . esc_url( admin_url( 'site-health.php' ) ) . '">Site Health</a> for problems with the REST API.</p>',
			$page,
			'Administrators should be pointed to Site Health.'
		);
		$this->assertStringEndsWith(
			'<p><a class="button button-primary" href="' . esc_url( wc_admin_url() ) . '">Continue to WooCommerce Home</a></p>',
			$page,
			'The confirmation should end with a link to WooCommerce Home.'
		);
		$this->assertSame( $expected_profile, $stored, 'The fallback should preserve all profile fields except skipped.' );
		$this->assertTrue( $stored['skipped'], 'The persisted skipped value should be strictly true.' );
		$this->assertSame( 0, $rest_update_actions, 'The fallback should not fire the REST-only profile update action.' );
	}

	/**
	 * Data provider for successful fallback requests.
	 *
	 * @return array<string, array{array<string, mixed>|string|null, array<int|string, mixed>}>
	 */
	public static function data_provider_test_valid_request_persists_skipped_and_confirms(): array {
		return array(
			'absent profile'  => array(
				null,
				array( 'skipped' => true ),
			),
			'scalar profile'  => array(
				'unexpected',
				array(
					0         => 'unexpected',
					'skipped' => true,
				),
			),
			'neutral fields'  => array(
				array(
					'business_choice' => 'im_already_selling',
					'completed'       => false,
					'setup_client'    => true,
				),
				array(
					'business_choice' => 'im_already_selling',
					'completed'       => false,
					'setup_client'    => true,
					'skipped'         => true,
				),
			),
			'already skipped' => array(
				array(
					'skipped'  => true,
					'industry' => array( 'fashion-apparel-accessories' ),
				),
				array(
					'skipped'  => true,
					'industry' => array( 'fashion-apparel-accessories' ),
				),
			),
		);
	}

	/**
	 * @testdox A shop manager's confirmation points to the hosting provider instead of Site Health.
	 */
	public function test_shop_manager_confirmation_points_to_hosting_provider(): void {
		$this->prepare_valid_request( self::$shop_manager_id );

		$response = $this->dispatch_request();

		$this->assertSame( 200, $response->getCode(), 'Shop managers can skip setup.' );
		$this->assertStringContainsString( 'hosting provider', $response->getMessage(), 'Users who cannot open Site Health should be told whom to ask.' );
		$this->assertStringNotContainsString( 'site-health.php', $response->getMessage(), 'Users who cannot open Site Health should not get a link to it.' );
		$this->assertTrue( get_option( OnboardingProfile::DATA_OPTION )['skipped'], 'The skip should be saved for shop managers.' );
	}

	/**
	 * @testdox The confirmation points to the general settings only when the profiler could not save the store location.
	 */
	public function test_confirmation_mentions_unsaved_store_location_only_when_flagged(): void {
		$this->prepare_valid_request( self::$admin_id );
		// wp_kses_post() turns esc_url()'s &#038; into &amp;, so the rendered link uses &amp;.
		$settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&amp;tab=general' ) . '">WooCommerce &gt; Settings &gt; General</a>';

		$unflagged_page = $this->dispatch_request()->getMessage();

		$_GET['store_location_not_saved'] = '1';
		$flagged_page                     = $this->dispatch_request()->getMessage();

		$this->assertStringNotContainsString( 'store location', $unflagged_page, 'Without the flag, the confirmation should not mention the store location.' );
		$this->assertStringContainsString(
			'until that is fixed.</p><p>Your store location and currency were not saved. Set them in ' . $settings_link . ', since taxes and prices depend on them.</p><p>If WooCommerce screens keep failing to load',
			$flagged_page,
			'With the flag, the confirmation should link to the general settings in its own paragraph, between the summary and the next step.'
		);
	}

	/**
	 * @testdox An invalid nonce cannot mutate the profile.
	 */
	public function test_invalid_nonce_does_not_mutate_profile(): void {
		$profile = array(
			'completed' => false,
			'purpose'   => 'setup',
		);
		$this->set_profile( $profile );
		$this->prepare_request( self::$admin_id, 'invalid' );

		$exception = $this->dispatch_request();

		$this->assertSame( 403, $exception->getCode(), 'The invalid nonce should be rejected with HTTP 403.' );
		$this->assertSame( $profile, get_option( OnboardingProfile::DATA_OPTION ), 'An invalid nonce must not mutate the profile.' );
	}

	/**
	 * @testdox Capability denial happens before nonce validation and before reading profile state.
	 */
	public function test_subscriber_is_denied_before_nonce_and_option_access(): void {
		$profile = array( 'completed' => false );
		$this->set_profile( $profile );
		$this->prepare_request( self::$subscriber_id, 'invalid' );
		$option_read_probe = static function ( $pre_option ): string {
			if ( is_string( $pre_option ) ) {
				return $pre_option;
			}

			throw new RuntimeException( 'The onboarding profile was read before capability denial.' );
		};
		add_filter( 'pre_option_' . OnboardingProfile::DATA_OPTION, $option_read_probe );

		try {
			$exception = $this->dispatch_request();
		} finally {
			remove_filter( 'pre_option_' . OnboardingProfile::DATA_OPTION, $option_read_probe );
		}

		$this->assertSame(
			esc_html__( 'You do not have permission to skip WooCommerce setup.', 'woocommerce' ),
			$exception->getMessage(),
			'Capability denial should win over the invalid nonce.'
		);
		$this->assertSame( 403, $exception->getCode(), 'Capability denial should use HTTP 403.' );
		$this->assertSame( $profile, get_option( OnboardingProfile::DATA_OPTION ), 'A denied request must not mutate the profile.' );
	}

	/**
	 * @testdox A failed persistence postcondition stops with HTTP 500 instead of confirming.
	 */
	public function test_failed_persistence_postcondition_stops_with_error(): void {
		$profile = array(
			'completed' => false,
			'purpose'   => 'setup',
		);
		$this->set_profile( $profile );
		$this->prepare_valid_request( self::$admin_id );
		$block_update = static function ( $new_value, $old_value ) {
			unset( $new_value );
			return $old_value;
		};
		add_filter( 'pre_update_option_' . OnboardingProfile::DATA_OPTION, $block_update, 10, 2 );

		try {
			$exception = $this->dispatch_request();
		} finally {
			remove_filter( 'pre_update_option_' . OnboardingProfile::DATA_OPTION, $block_update, 10 );
		}

		$this->assertSame( 500, $exception->getCode(), 'A failed persistence postcondition should use HTTP 500.' );
		$this->assertSame( $profile, get_option( OnboardingProfile::DATA_OPTION ), 'A failed update should leave the profile unchanged.' );
	}

	/**
	 * Store or remove the onboarding profile fixture.
	 *
	 * @param array<string, mixed>|string|null $profile Profile data, or null to remove it.
	 */
	private function set_profile( $profile ): void {
		if ( null === $profile ) {
			delete_option( OnboardingProfile::DATA_OPTION );
			return;
		}

		update_option( OnboardingProfile::DATA_OPTION, $profile );
	}

	/**
	 * Prepare an authenticated admin-post request.
	 *
	 * @param int         $user_id User ID.
	 * @param string|null $nonce   Request nonce, or null to create a valid nonce.
	 */
	private function prepare_request( int $user_id, ?string $nonce ): void {
		wp_set_current_user( $user_id );
		$nonce                = $nonce ?? wp_create_nonce( self::ACTION );
		$_GET['_wpnonce']     = $nonce;
		$_REQUEST['_wpnonce'] = $nonce;
	}

	/**
	 * Prepare an authenticated admin-post request with a valid nonce.
	 *
	 * @param int $user_id User ID.
	 */
	private function prepare_valid_request( int $user_id ): void {
		$this->prepare_request( $user_id, null );
	}

	/**
	 * Dispatch the skip action and capture the page it ends with.
	 *
	 * @return WPDieException The confirmation or error response.
	 */
	private function dispatch_request(): WPDieException {
		try {
			$this->sut->handle_admin_post_woocommerce_skip_onboarding_setup();
		} catch ( WPDieException $exception ) {
			return $exception;
		}

		$this->fail( 'Expected the skip handler to finish with wp_die().' );
	}
}
