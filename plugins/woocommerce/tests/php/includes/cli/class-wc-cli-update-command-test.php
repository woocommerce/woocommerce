<?php

/**
 * Tests for the `wp wc update` WP CLI command.
 */
class WC_CLI_Update_Command_Test extends WC_Unit_Test_Case {
	/**
	 * @var array
	 */
	private $db_updates_original_value;

	/**
	 * @var ReflectionProperty
	 */
	private $db_updates_property;

	/**
	 * Load the WC_CLI_Update_Command class definition.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once dirname( WC_PLUGIN_FILE ) . '/includes/cli/class-wc-cli-update-command.php';
	}

	/**
	 * Make WP_Install::$db_updates readable and capture the original value.
	 */
	public function set_up() {
		$this->db_updates_property = new ReflectionProperty( WC_Install::class, 'db_updates' );
		$this->db_updates_property->setAccessible( true );
		$this->db_updates_original_value = $this->db_updates_property->getValue();
	}

	/**
	 * Restore WP_Install::$db_updates to its earlier state.
	 */
	public function tear_down() {
		$this->db_updates_property->setValue( $this->db_updates_original_value );
		$this->db_updates_property->setAccessible( false );
	}

	/**
	 * @testdox After `wp wc update` has run, the `woocommerce_db_option` should be left at the expected value.
	 */
	public function test_db_version_is_updated_if_have_callbacks() {
		$this->mock_wp_cli();

		// Overwrite with some alternative update callbacks.
		$this->db_updates_property->setValue(
			array(
				'5.0.0' => function () {},
				'6.0.0' => function () {},
			)
		);

		update_option( 'woocommerce_db_version', '4.0.0' );
		$sut = new WC_CLI_Update_Command();
		$sut->update();

		$this->assertEquals(
			WC()->version,
			get_option( 'woocommerce_db_version' ),
			'After applying updates via WP CLI, the `woocommerce_db_version` option should match the current `WC()->version` property.'
		);
	}

	/**
	 * @testdox After `wp wc update` has run, the `woocommerce_db_option` should be left at the expected value (even if no update callbacks were executed)
	 */
	public function test_db_version_is_updated_if_no_callbacks() {
		$this->mock_wp_cli();

		// Overwrite with some alternative update callbacks.
		$this->db_updates_property->setValue( array() );

		update_option( 'woocommerce_db_version', '4.0.0' );
		$sut = new WC_CLI_Update_Command();
		$sut->update();

		$this->assertEquals(
			WC()->version,
			get_option( 'woocommerce_db_version' ),
			'After applying updates via WP CLI, the `woocommerce_db_version` option should match the current `WC()->version` property.'
		);
	}

	/**
	 * Mock WP_CLI and related functionality.
	 */
	private function mock_wp_cli() {
		parent::set_up();

		$this->register_legacy_proxy_static_mocks(
			array(
				WP_CLI::class => array(
					'log'     => function () {},
					'success' => function () {},
				),
			)
		);

		$this->register_legacy_proxy_function_mocks(
			array(
				'WP_CLI\Utils\make_progress_bar' => function () {
					return new class() {
						/**
						 * Stub, implemented so that calls to WP CLI methods do not break the tests.
						 */
						public function finish() {}

						/**
						 * Stub, implemented so that calls to WP CLI methods do not break the tests.
						 */
						public function tick() {}
					};
				},
			)
		);
	}
}
