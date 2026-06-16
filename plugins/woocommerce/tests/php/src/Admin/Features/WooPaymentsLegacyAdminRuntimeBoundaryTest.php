<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features;

use WC_Unit_Test_Case;

/**
 * Tests legacy admin WooPayments runtime boundaries.
 */
class WooPaymentsLegacyAdminRuntimeBoundaryTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Legacy admin sources should route WooPayments runtime access through WooPaymentsLegacyRuntime.
	 */
	public function test_legacy_admin_sources_use_woopayments_legacy_runtime_boundary(): void {
		$assertions = array(
			'src/Admin/API/Plugins.php'                   => array(
				"class_exists( 'WC_Payments' )",
			),
			'src/Admin/Features/Blueprint/Exporters/ExportWCPaymentGateways.php' => array(
				"class_exists( 'WC_Payments' )",
				'\\WC_Payments::hide_gateways_on_settings_page',
			),
			'src/Admin/Features/OnboardingTasks/Init.php' => array(
				"class_exists( '\\WC_Payments' )",
				'\\WC_Payments::get_gateway',
			),
			'src/Admin/Features/OnboardingTasks/Tasks/Payments.php' => array(
				"class_exists( '\\WC_Payments' )",
				"class_exists( '\\WC_Payments_Utils' )",
				'\\WC_Payments_Utils::supported_countries',
			),
			'src/Admin/Features/OnboardingTasks/Tasks/WooCommercePayments.php' => array(
				"class_exists( '\\WC_Payments' )",
				'@return \\WC_Payments|null',
			),
		);

		foreach ( $assertions as $relative_path => $forbidden_strings ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local source for boundary assertion.
			$source = (string) file_get_contents( WC()->plugin_path() . '/' . $relative_path );

			foreach ( $forbidden_strings as $forbidden_string ) {
				$this->assertStringNotContainsString(
					$forbidden_string,
					$source,
					"{$relative_path} should not access WooPayments runtime symbol {$forbidden_string} directly."
				);
			}
		}
	}
}
