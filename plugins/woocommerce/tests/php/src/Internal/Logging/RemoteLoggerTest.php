<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Logging;

use Automattic\WooCommerce\Internal\Logging\RemoteLogger;

/**
 * Class RemoteLoggerTest.
 */
class RemoteLoggerTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var RemoteLogger;
	 */
	private $sut;


	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = wc_get_container()->get( RemoteLogger::class );

		WC()->version = '9.2.0';
	}

	/**
	 * Tear down.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$this->cleanup_filters();

		delete_option( 'woocommerce_feature_remote_logging_enabled' );
		delete_transient( RemoteLogger::WC_LATEST_VERSION_TRANSIENT );
	}

	/**
	 * Clean up filters.
	 *
	 * @return void
	 */
	private function cleanup_filters() {
		remove_all_filters( 'option_woocommerce_admin_remote_feature_enabled' );
		remove_all_filters( 'option_woocommerce_allow_tracking' );
		remove_all_filters( 'option_woocommerce_version' );
		remove_all_filters( 'option_woocommerce_remote_variant_assignment' );
		remove_all_filters( 'plugins_api' );
	}

	/**
	 * @testdox Test that remote logging is allowed when all conditions are met.
	 */
	public function test_remote_logging_allowed() {
		update_option( 'woocommerce_feature_remote_logging_enabled', 'yes' );

		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'yes';
			}
		);
		add_filter(
			'option_woocommerce_remote_variant_assignment',
			function () {
				return 5;
			}
		);

		add_filter(
			'plugins_api',
			function ( $result, $action, $args ) {
				if ( 'plugin_information' === $action && 'woocommerce' === $args->slug ) {
					return (object) array(
						'version' => '9.2.0',
					);
				}
				return $result;
			},
			10,
			3
		);

		$this->assertTrue( $this->sut->is_remote_logging_allowed() );
	}

	/**
	 * @testdox Test that remote logging is not allowed when the feature flag is disabled.
	 */
	public function test_remote_logging_not_allowed_feature_flag_disabled() {
		update_option( 'woocommerce_feature_remote_logging_enabled', 'no' );

		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'yes';
			}
		);
		add_filter(
			'option_woocommerce_remote_variant_assignment',
			function () {
				return 5;
			}
		);

		set_transient( RemoteLogger::WC_LATEST_VERSION_TRANSIENT, '9.2.0', DAY_IN_SECONDS );

		$this->assertFalse( $this->sut->is_remote_logging_allowed() );
	}

	/**
	 * @testdox Test that remote logging is not allowed when user tracking is not opted in.
	 */
	public function test_remote_logging_not_allowed_tracking_opted_out() {
		update_option( 'woocommerce_feature_remote_logging_enabled', 'yes' );
		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'no';
			}
		);
		add_filter(
			'option_woocommerce_remote_variant_assignment',
			function () {
				return 5;
			}
		);

		set_transient( RemoteLogger::WC_LATEST_VERSION_TRANSIENT, '9.2.0', DAY_IN_SECONDS );

		$this->assertFalse( $this->sut->is_remote_logging_allowed() );
	}

	/**
	 * @testdox Test that remote logging is not allowed when the WooCommerce version is outdated.
	 */
	public function test_remote_logging_not_allowed_outdated_version() {
		update_option( 'woocommerce_feature_remote_logging_enabled', 'yes' );
		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'yes';
			}
		);
		add_filter(
			'option_woocommerce_remote_variant_assignment',
			function () {
				return 5;
			}
		);

		set_transient( RemoteLogger::WC_LATEST_VERSION_TRANSIENT, '9.2.0', DAY_IN_SECONDS );
		WC()->version = '9.0.0';

		$this->assertFalse( $this->sut->is_remote_logging_allowed() );
	}

	/**
	 * @testdox Test that remote logging is not allowed when the variant assignment is high.
	 */
	public function test_remote_logging_not_allowed_high_variant_assignment() {
		update_option( 'woocommerce_feature_remote_logging_enabled', 'yes' );
		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'yes';
			}
		);
		add_filter(
			'option_woocommerce_version',
			function () {
				return '9.2.0';
			}
		);
		add_filter(
			'option_woocommerce_remote_variant_assignment',
			function () {
				return 15;
			}
		);

		set_transient( RemoteLogger::WC_LATEST_VERSION_TRANSIENT, '9.2.0', DAY_IN_SECONDS );

		$this->assertFalse( $this->sut->is_remote_logging_allowed() );
	}

	/**
	 * @testdox Test that the fetch_latest_woocommerce_version method retries fetching the latest WooCommerce version when the API call fails.
	 */
	public function test_fetch_latest_woocommerce_version_retry() {
		update_option( 'woocommerce_feature_remote_logging_enabled', 'yes' );

		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'yes';
			}
		);
		add_filter(
			'option_woocommerce_remote_variant_assignment',
			function () {
				return 5;
			}
		);

		add_filter(
			'plugins_api', // phpcs:ignore PEAR.Functions.FunctionCallSignature.MultipleArguments
			function ( $result, $action, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
				return new \WP_Error();
			},
			10,
			3
		);

		$this->sut->is_remote_logging_allowed();
		$this->assertEquals( 1, get_transient( RemoteLogger::FETCH_LATEST_VERSION_RETRY ) );

		$this->sut->is_remote_logging_allowed();
		$this->assertEquals( 2, get_transient( RemoteLogger::FETCH_LATEST_VERSION_RETRY ) );

		$this->sut->is_remote_logging_allowed();
		$this->assertEquals( 3, get_transient( RemoteLogger::FETCH_LATEST_VERSION_RETRY ) );

		// After 3 retries, the transient should not be updated.
		$this->sut->is_remote_logging_allowed();
		$this->assertEquals( 3, get_transient( RemoteLogger::FETCH_LATEST_VERSION_RETRY ) );
	}
}
