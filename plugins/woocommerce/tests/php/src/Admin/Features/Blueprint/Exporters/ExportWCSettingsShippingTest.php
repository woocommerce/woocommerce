<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCSettingsShipping;
use Automattic\WooCommerce\Admin\Features\Blueprint\SettingOptions;
use Automattic\WooCommerce\Blueprint\Steps\RunSql;
use WC_Shipping_Zone;
use WC_Unit_Test_Case;

/**
 * Test ExportWCSettingsShipping class.
 */
class ExportWCSettingsShippingTest extends WC_Unit_Test_Case {
	/**
	 * Build the exporter with a stubbed settings page so export() only yields
	 * the shipping RunSql steps we care about here.
	 *
	 * @return ExportWCSettingsShipping
	 */
	private function get_exporter(): ExportWCSettingsShipping {
		$setting_options_mock = $this->getMockBuilder( SettingOptions::class )
			->disableOriginalConstructor()
			->getMock();
		$setting_options_mock->method( 'get_page_options' )->willReturn( array() );

		return new ExportWCSettingsShipping( $setting_options_mock );
	}

	/**
	 * Test that exported SQL uses the table prefix placeholder instead of the
	 * local database prefix, so Blueprints import on sites with a different prefix.
	 */
	public function test_exported_sql_uses_table_prefix_placeholder() {
		global $wpdb;

		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Placeholder Zone' );
		$zone->save();
		$zone->add_shipping_method( 'flat_rate' );

		$steps              = $this->get_exporter()->export();
		$run_sql_step_found = false;

		foreach ( $steps as $step ) {
			if ( $step instanceof RunSql ) {
				$run_sql_step_found = true;
				$sql_content        = $step->prepare_json_array()['sql']['contents'];

				$this->assertStringContainsString( RunSql::TABLE_PREFIX_PLACEHOLDER, $sql_content );
				// The literal local prefix must not be baked into the table name.
				$this->assertStringNotContainsString( 'replace into `' . $wpdb->prefix, $sql_content );
			}
		}

		$this->assertTrue( $run_sql_step_found, 'At least one RunSql step should be exported' );

		$zone->delete( true );
	}
}
