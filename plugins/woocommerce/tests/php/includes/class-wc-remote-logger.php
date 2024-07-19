<?php
declare( strict_types = 1 );

/**
 * Class WC_Remote_Logger_Test.
 */
class WC_Remote_Logger_Test extends \WC_Unit_Test_Case {

	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		include_once WC_ABSPATH . 'includes/class-wc-remote-logger.php';

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
	}

	/**
	 * Test that remote logging is allowed when all conditions are met.
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

		$checker = new WC_Remote_Logger();
		$this->assertTrue( $checker->is_remote_logging_allowed() );
	}

	/**
	 * Test that remote logging is not allowed when the feature flag is disabled.
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

		set_transient( 'latest_woocommerce_version', '9.2.0', DAY_IN_SECONDS );

		$checker = new WC_Remote_Logger();
		$this->assertFalse( $checker->is_remote_logging_allowed() );
	}

	/**
	 * Test that remote logging is not allowed when user tracking is not opted in.
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

		set_transient( 'latest_woocommerce_version', '9.2.0', DAY_IN_SECONDS );

		$checker = new WC_Remote_Logger();
		$this->assertFalse( $checker->is_remote_logging_allowed() );
	}

	/**
	 * Test that remote logging is not allowed when the WooCommerce version is outdated.
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

		set_transient( 'latest_woocommerce_version', '9.2.0', DAY_IN_SECONDS );
		WC()->version = '9.0.0';

		$checker = new WC_Remote_Logger();
		$this->assertFalse( $checker->is_remote_logging_allowed() );
	}

	/**
	 * Test that remote logging is not allowed when the variant assignment is high.
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

		set_transient( 'latest_woocommerce_version', '9.2.0', DAY_IN_SECONDS );

		$checker = new WC_Remote_Logger();
		$this->assertFalse( $checker->is_remote_logging_allowed() );
	}
}
