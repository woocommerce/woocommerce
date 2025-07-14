<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCSettingsTax;
use Automattic\WooCommerce\Blueprint\Steps\RunSql;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;
use WC_Tax;
use WC_Unit_Test_Case;

/**
 * Test ExportWCSettingsTax class.
 */
class ExportWCSettingsTaxTest extends WC_Unit_Test_Case {
	/**
	 * Test that custom tax classes are exported in blueprint.
	 */
	public function test_custom_tax_classes_are_exported() {
		$custom_tax_class = WC_Tax::create_tax_class( 'réduit' );
		$this->assertIsArray( $custom_tax_class );
		$this->assertEquals( 'réduit', $custom_tax_class['name'] );

		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'FR',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'TVA Réduite',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => $custom_tax_class['slug'],
			)
		);

		$setting_options_mock = $this->getMockBuilder( \Automattic\WooCommerce\Admin\Features\Blueprint\SettingOptions::class )
			->disableOriginalConstructor()
			->getMock();

		$setting_options_mock->method( 'get_page_options' )->willReturn( array() );

		$exporter = new ExportWCSettingsTax( $setting_options_mock );
		$steps    = $exporter->export();

		$this->assertIsArray( $steps );
		$this->assertGreaterThan( 1, count( $steps ) );

		$this->assertInstanceOf( SetSiteOptions::class, $steps[0] );

		$tax_class_step_found = false;
		$tax_rate_step_found  = false;

		foreach ( $steps as $step ) {
			if ( $step instanceof RunSql ) {
				$sql_content = $step->prepare_json_array()['sql']['contents'];

				if ( strpos( $sql_content, 'wc_tax_rate_classes' ) !== false && strpos( $sql_content, 'réduit' ) !== false ) {
					$tax_class_step_found = true;
				}

				if ( strpos( $sql_content, 'woocommerce_tax_rates' ) !== false && strpos( $sql_content, 'TVA Réduite' ) !== false ) {
					$tax_rate_step_found = true;
				}
			}
		}

		$this->assertTrue( $tax_class_step_found, 'Custom tax class should be exported' );
		$this->assertTrue( $tax_rate_step_found, 'Tax rate should be exported' );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
		WC_Tax::delete_tax_class_by( 'slug', $custom_tax_class['slug'] );
	}

	/**
	 * Test that tax class export comes before tax rate export (proper order).
	 */
	public function test_export_order_tax_classes_before_rates() {
		$custom_tax_class = WC_Tax::create_tax_class( 'test-order' );
		$this->assertIsArray( $custom_tax_class );

		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CA',
				'tax_rate'          => '8.5000',
				'tax_rate_name'     => 'Test Rate',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => $custom_tax_class['slug'],
			)
		);

		$setting_options_mock = $this->getMockBuilder( \Automattic\WooCommerce\Admin\Features\Blueprint\SettingOptions::class )
			->disableOriginalConstructor()
			->getMock();

		$setting_options_mock->method( 'get_page_options' )->willReturn( array() );

		$exporter = new ExportWCSettingsTax( $setting_options_mock );
		$steps    = $exporter->export();

		$tax_class_position = -1;
		$tax_rate_position  = -1;

		foreach ( $steps as $index => $step ) {
			if ( $step instanceof RunSql ) {
				$sql_content = $step->prepare_json_array()['sql']['contents'];

				if ( false !== strpos( $sql_content, 'wc_tax_rate_classes' ) && -1 === $tax_class_position ) {
					$tax_class_position = $index;
				}

				if ( false !== strpos( $sql_content, 'woocommerce_tax_rates' ) && -1 === $tax_rate_position ) {
					$tax_rate_position = $index;
				}
			}
		}

		$this->assertGreaterThan( -1, $tax_class_position, 'Tax class export step should exist' );
		$this->assertGreaterThan( -1, $tax_rate_position, 'Tax rate export step should exist' );
		$this->assertLessThan( $tax_rate_position, $tax_class_position, 'Tax classes should be exported before tax rates' );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
		WC_Tax::delete_tax_class_by( 'slug', $custom_tax_class['slug'] );
	}

	/**
	 * Test export works when no custom tax classes exist.
	 */
	public function test_export_with_no_custom_tax_classes() {
		$tax_classes = WC_Tax::get_tax_classes();
		foreach ( $tax_classes as $class ) {
			if ( ! in_array( $class, array( 'Reduced rate', 'Zero rate' ), true ) ) {
				WC_Tax::delete_tax_class_by( 'name', $class );
			}
		}

		$setting_options_mock = $this->getMockBuilder( \Automattic\WooCommerce\Admin\Features\Blueprint\SettingOptions::class )
			->disableOriginalConstructor()
			->getMock();

		$setting_options_mock->method( 'get_page_options' )->willReturn( array() );

		$exporter = new ExportWCSettingsTax( $setting_options_mock );
		$steps    = $exporter->export();

		$this->assertIsArray( $steps );
		$this->assertGreaterThan( 0, count( $steps ) );

		$this->assertInstanceOf( SetSiteOptions::class, $steps[0] );
	}

	/**
	 * Test that all expected components are exported.
	 */
	public function test_export_includes_all_components() {
		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CA',
				'tax_rate'          => '8.5000',
				'tax_rate_name'     => 'Test Rate',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			)
		);

		WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, '90210' );

		$setting_options_mock = $this->getMockBuilder( \Automattic\WooCommerce\Admin\Features\Blueprint\SettingOptions::class )
			->disableOriginalConstructor()
			->getMock();

		$setting_options_mock->method( 'get_page_options' )->willReturn( array() );

		$exporter = new ExportWCSettingsTax( $setting_options_mock );
		$steps    = $exporter->export();

		$has_settings      = false;
		$has_tax_classes   = false;
		$has_tax_rates     = false;
		$has_tax_locations = false;

		foreach ( $steps as $step ) {
			if ( $step instanceof SetSiteOptions ) {
				$has_settings = true;
			} elseif ( $step instanceof RunSql ) {
				$sql_content = $step->prepare_json_array()['sql']['contents'];

				if ( strpos( $sql_content, 'wc_tax_rate_classes' ) !== false ) {
					$has_tax_classes = true;
				}
				if ( strpos( $sql_content, 'woocommerce_tax_rates' ) !== false ) {
					$has_tax_rates = true;
				}
				if ( strpos( $sql_content, 'woocommerce_tax_rate_locations' ) !== false ) {
					$has_tax_locations = true;
				}
			}
		}

		$this->assertTrue( $has_settings, 'Should export basic tax settings' );
		$this->assertTrue( $has_tax_classes, 'Should export tax classes table' );
		$this->assertTrue( $has_tax_rates, 'Should export tax rates table' );
		$this->assertTrue( $has_tax_locations, 'Should export tax rate locations table' );

		WC_Tax::_delete_tax_rate( $tax_rate_id );
	}
}
