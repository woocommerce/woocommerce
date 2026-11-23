<?php
/**
 * ProductsSettingsPageAdapter tests.
 *
 * @package WooCommerce\Tests\Internal\Admin\Settings\SettingsUIPages
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\SettingsUIPages;

use Automattic\WooCommerce\Internal\Admin\Settings\SettingsUISchema;
use Automattic\WooCommerce\Internal\Admin\Settings\SettingsUIPages\ProductsSettingsPageAdapter;
use WC_Unit_Test_Case;

/**
 * Tests for ProductsSettingsPageAdapter.
 */
class ProductsSettingsPageAdapterTest extends WC_Unit_Test_Case {

	/**
	 * @testdox It adds page options to the shop page selector.
	 */
	public function test_get_schema_adds_shop_page_options(): void {
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Shop',
			)
		);

		$settings_page = new class() extends \WC_Settings_Page {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->id    = 'products';
				$this->label = 'Products';
			}

			/**
			 * Get settings.
			 *
			 * @param string $current_section Current section.
			 * @return array
			 */
			public function get_settings( $current_section = '' ) {
				return array(
					array(
						'id'    => 'catalog_options',
						'type'  => 'title',
						'title' => 'Shop pages',
					),
					array(
						'id'    => 'woocommerce_shop_page_id',
						'type'  => 'single_select_page',
						'title' => 'Shop page',
					),
					array(
						'id'   => 'catalog_options',
						'type' => 'sectionend',
					),
				);
			}
		};

		$adapter = new ProductsSettingsPageAdapter( $settings_page );
		$schema  = $adapter->get_schema( '' );
		$field   = $schema['groups']['catalog_options']['fields'][0];

		$this->assertSame( 'woocommerce_shop_page_id', $field['id'] );
		$this->assertContains(
			array(
				'label' => 'Shop',
				'value' => (string) $page_id,
			),
			$field['options']
		);
	}

	/**
	 * @testdox It builds and validates every real Products settings section.
	 */
	public function test_get_schema_builds_valid_products_settings_sections(): void {
		if ( ! class_exists( 'WC_Settings_Products', false ) ) {
			require_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-products.php';
		}

		$settings_page = new \WC_Settings_Products();
		$adapter       = $settings_page->get_settings_ui_page();
		$sections      = array( '', 'inventory', 'downloadable', 'advanced' );

		$this->assertInstanceOf( ProductsSettingsPageAdapter::class, $adapter, 'The Products settings page should use the Settings UI adapter.' );
		$this->assertSame( $sections, array_keys( $settings_page->get_sections() ), 'Every core Products section should be covered by this schema validation test.' );

		foreach ( $sections as $section ) {
			$schema                  = $adapter->get_schema( $section );
			$expected_schema_section = '' === $section ? 'default' : $section;

			SettingsUISchema::assert_valid_schema( $schema );
			$this->assertSame( 'products', $schema['id'], 'The real schema should keep the Products page ID.' );
			$this->assertSame( $expected_schema_section, $schema['section'], 'The real schema should keep the Products section ID.' );
			$this->assertNotEmpty( $schema['groups'], 'Every real Products section should contain settings groups.' );
		}
	}
}
