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
			'src/Internal/Admin/Settings/PaymentsProviders/WooPayments.php' => array(
				'WCPAY_VERSION_NUMBER',
			),
			'src/Internal/Admin/Settings/PaymentsProviders/WooPayments/WooPaymentsService.php' => array(
				'WCPAY_VERSION_NUMBER',
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

	/**
	 * @testdox Deprecated WcPay welcome page sources should be removed from core.
	 */
	public function test_deprecated_wcpay_welcome_page_surface_is_removed(): void {
		$removed_files = array(
			'src/Internal/Admin/WcPayWelcomePage.php',
			'src/Internal/Admin/Notes/PaymentsMoreInfoNeeded.php',
			'src/Internal/Admin/Notes/PaymentsRemindMeLater.php',
			'assets/client/admin/chunks/wcpay-payment-welcome-page.js',
		);

		foreach ( $removed_files as $removed_file ) {
			$this->assertFileDoesNotExist( WC()->plugin_path() . '/' . $removed_file, "{$removed_file} should be removed with the deprecated WooPayments welcome-page surface." );
		}

		$forbidden_strings = array(
			'WcPayWelcomePage::instance()',
			'PaymentsMoreInfoNeeded::class',
			'PaymentsRemindMeLater::class',
			'admin.php?page=wc-admin&path=/wc-pay-welcome-page',
		);
		$source_files      = array_merge(
			$this->get_php_source_files( WC()->plugin_path() . '/src' ),
			$this->get_php_source_files( WC()->plugin_path() . '/includes' )
		);

		foreach ( $source_files as $source_file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local source for removal assertion.
			$source = (string) file_get_contents( $source_file );

			foreach ( $forbidden_strings as $forbidden_string ) {
				$this->assertStringNotContainsString( $forbidden_string, $source, "{$source_file} should not retain deprecated WooPayments welcome-page symbol {$forbidden_string}." );
			}
		}
	}

	/**
	 * @testdox Deprecated WooPayments onboarding task surfaces should be removed from core.
	 */
	public function test_deprecated_woopayments_onboarding_surface_is_removed(): void {
		$removed_files = array(
			'src/Admin/Features/OnboardingTasks/Tasks/WooCommercePayments.php',
			'src/Internal/Admin/Notes/WooCommercePayments.php',
			'client/admin/client/task-lists/fills/woocommerce-payments.tsx',
			'client/admin/client/task-lists/setup-task-list/components/task-headers/woocommerce-payments.js',
		);

		foreach ( $removed_files as $removed_file ) {
			$this->assertFileDoesNotExist( WC()->plugin_path() . '/' . $removed_file, "{$removed_file} should be removed with the deprecated WooPayments onboarding surface." );
		}

		$forbidden_php_strings = array(
			'WooCommercePayments::class',
			'new WooCommercePayments()',
		);
		$production_php_files  = array_merge(
			$this->get_php_source_files( WC()->plugin_path() . '/src' ),
			$this->get_php_source_files( WC()->plugin_path() . '/includes' )
		);

		foreach ( $production_php_files as $source_file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local source for removal assertion.
			$source = (string) file_get_contents( $source_file );

			foreach ( $forbidden_php_strings as $forbidden_string ) {
				$this->assertStringNotContainsString( $forbidden_string, $source, "{$source_file} should not retain deprecated WooPayments onboarding symbol {$forbidden_string}." );
			}
		}

		$forbidden_client_strings = array(
			'woocommerce-admin-task-wcpay-page',
			'wcpayWelcomePageIncentive',
		);
		$production_client_files  = array_merge(
			$this->get_source_files( WC()->plugin_path() . '/client/admin/client/task-lists', array( 'js', 'jsx', 'ts', 'tsx' ) ),
			$this->get_source_files( WC()->plugin_path() . '/client/admin/client/typings', array( 'ts', 'tsx' ) )
		);

		foreach ( $production_client_files as $source_file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local source for removal assertion.
			$source = (string) file_get_contents( $source_file );

			foreach ( $forbidden_client_strings as $forbidden_string ) {
				$this->assertStringNotContainsString( $forbidden_string, $source, "{$source_file} should not retain deprecated WooPayments onboarding client symbol {$forbidden_string}." );
			}
		}
	}

	/**
	 * @testdox Deprecated WooPayments promotion surfaces should be removed from core.
	 */
	public function test_deprecated_woopayments_promotion_surface_is_removed(): void {
		$removed_files = array(
			'src/Internal/Admin/WCPayPromotion/Init.php',
			'src/Internal/Admin/WCPayPromotion/WCPaymentGatewayPreInstallWCPayPromotion.php',
			'src/Internal/Admin/WCPayPromotion/WCPayPromotionDataSourcePoller.php',
			'src/Internal/Admin/WCPayPromotion/DefaultPromotions.php',
			'client/admin/client/wp-admin-scripts/payment-method-promotions/index.tsx',
			'client/admin/client/wp-admin-scripts/payment-method-promotions/payment-promotion-row.tsx',
			'client/admin/client/wp-admin-scripts/payment-method-promotions/payment-promotion-row.scss',
			'assets/client/admin/wp-admin-scripts/payment-method-promotions.asset.php',
			'assets/client/admin/wp-admin-scripts/payment-method-promotions.js',
			'assets/client/admin/wp-admin-scripts/payment-method-promotions.js.LICENSE.txt',
			'assets/client/admin/payment-method-promotions/style.asset.php',
			'assets/client/admin/payment-method-promotions/style.css',
			'assets/client/admin/payment-method-promotions/style-rtl.css',
		);

		foreach ( $removed_files as $removed_file ) {
			$this->assertFileDoesNotExist( WC()->plugin_path() . '/' . $removed_file, "{$removed_file} should be removed with the deprecated WooPayments promotion surface." );
		}

		$forbidden_php_strings = array(
			'Internal\\Admin\\WCPayPromotion\\Init',
			'Admin\\Features\\WcPayPromotion\\Init',
			'pre_install_woocommerce_payments_promotion',
			'payment-method-promotions',
		);
		$production_php_files  = array_merge(
			$this->get_php_source_files( WC()->plugin_path() . '/src' ),
			$this->get_php_source_files( WC()->plugin_path() . '/includes' )
		);

		foreach ( $production_php_files as $source_file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local source for removal assertion.
			$source = (string) file_get_contents( $source_file );

			foreach ( $forbidden_php_strings as $forbidden_string ) {
				$this->assertStringNotContainsString( $forbidden_string, $source, "{$source_file} should not retain deprecated WooPayments promotion symbol {$forbidden_string}." );
			}
		}

		$forbidden_client_strings = array(
			'wc-pay-promotion',
			'pre_install_woocommerce_payments_promotion',
			'woocommerce_payments_displayed',
		);
		$production_client_files  = array_merge(
			$this->get_source_files( WC()->plugin_path() . '/client/admin/client/payments', array( 'js', 'jsx', 'ts', 'tsx' ) ),
			$this->get_source_files( WC()->plugin_path() . '/client/admin/client/wp-admin-scripts', array( 'js', 'jsx', 'ts', 'tsx' ) ),
			$this->get_source_files( WC()->plugin_path() . '/client/admin/client/typings', array( 'ts', 'tsx' ) ),
			$this->get_source_files( WC()->plugin_path() . '/client/admin/config', array( 'json' ) ),
			$this->get_source_files( WC()->plugin_path() . '/includes/react-admin', array( 'php' ) )
		);

		foreach ( $production_client_files as $source_file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local source for removal assertion.
			$source = (string) file_get_contents( $source_file );

			foreach ( $forbidden_client_strings as $forbidden_string ) {
				$this->assertStringNotContainsString( $forbidden_string, $source, "{$source_file} should not retain deprecated WooPayments promotion client symbol {$forbidden_string}." );
			}
		}
	}

	/**
	 * @testdox Active WooPayments settings assets should use core-owned WooPayments names.
	 */
	public function test_active_woopayments_settings_assets_use_core_owned_names(): void {
		$old_generated_chunk = 'assets/client/admin/chunks/settings-payments-woocommerce-payments.js';
		$this->assertFileDoesNotExist( WC()->plugin_path() . '/' . $old_generated_chunk, "{$old_generated_chunk} should be replaced by the core-owned WooPayments settings chunk name." );

		$old_name_source_files = array(
			'client/admin/client/settings-payments/index.tsx',
			'client/admin/client/settings-payments/settings-payments-woopayments.tsx',
			'client/admin/client/settings-payments/settings-payments-woopayments.scss',
		);

		foreach ( $old_name_source_files as $source_file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local source for boundary assertion.
			$source = (string) file_get_contents( WC()->plugin_path() . '/' . $source_file );

			$this->assertStringNotContainsString(
				'settings-payments-woocommerce-payments',
				$source,
				"{$source_file} should use the core-owned WooPayments settings asset name."
			);
		}
	}

	/**
	 * Get PHP source files under a directory.
	 *
	 * @param string $directory Directory path.
	 * @return string[]
	 */
	private function get_php_source_files( string $directory ): array {
		return $this->get_source_files( $directory, array( 'php' ) );
	}

	/**
	 * Get source files under a directory.
	 *
	 * @param string   $directory Directory path.
	 * @param string[] $extensions Allowed file extensions.
	 * @return string[]
	 */
	private function get_source_files( string $directory, array $extensions ): array {
		$files = array();

		foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $directory ) ) as $file ) {
			if ( ! $file instanceof \SplFileInfo || ! $file->isFile() || ! in_array( $file->getExtension(), $extensions, true ) ) {
				continue;
			}

			if ( str_contains( $file->getPathname(), '/test/' ) || str_contains( $file->getPathname(), '/tests/' ) ) {
				continue;
			}

			$files[] = $file->getPathname();
		}

		return $files;
	}
}
