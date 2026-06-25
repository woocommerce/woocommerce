<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Database;

use Automattic\WooCommerce\Internal\Theme\CustomizerSettings;
use WC_Unit_Test_Case;

/**
 * Tests for WooCommerce Customizer settings when switching between classic and block themes.
 */
class CustomizerSettingsOnThemeSwitch extends WC_Unit_Test_Case {

	/**
	 * Product catalog and image Customizer settings with their block-theme defaults.
	 *
	 * @var array<string, mixed>
	 */
	private const DEFAULT_SETTINGS = array(
		'woocommerce_shop_page_display'                => '',
		'woocommerce_category_archive_display'         => '',
		'woocommerce_default_catalog_orderby'          => 'menu_order',
		'woocommerce_catalog_columns'                  => 4,
		'woocommerce_catalog_rows'                     => 4,
		'woocommerce_single_image_width'               => 600,
		'woocommerce_thumbnail_image_width'            => 300,
		'woocommerce_thumbnail_cropping'               => '1:1',
		'woocommerce_thumbnail_cropping_custom_width'  => '4',
		'woocommerce_thumbnail_cropping_custom_height' => '3',
	);

	/**
	 * Non-default classic theme Customizer settings.
	 *
	 * @var array<string, mixed>
	 */
	private const CLASSIC_THEME_SETTINGS = array(
		'woocommerce_shop_page_display'                => 'both',
		'woocommerce_category_archive_display'         => 'subcategories',
		'woocommerce_default_catalog_orderby'          => 'price-desc',
		'woocommerce_catalog_columns'                  => '6',
		'woocommerce_catalog_rows'                     => '8',
		'woocommerce_single_image_width'               => '900',
		'woocommerce_thumbnail_image_width'            => '450',
		'woocommerce_thumbnail_cropping'               => 'custom',
		'woocommerce_thumbnail_cropping_custom_width'  => '5',
		'woocommerce_thumbnail_cropping_custom_height' => '4',
	);

	/**
	 * Run before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		switch_theme( 'storefront' );
		check_theme_switched();

		$this->delete_backup();
		$this->reset_customizer_settings();
	}

	/**
	 * Run after each test.
	 */
	public function tearDown(): void {
		switch_theme( 'storefront' );
		check_theme_switched();

		$this->delete_backup();
		$this->reset_customizer_settings();

		parent::tearDown();
	}

	/**
	 * @testdox Should reset Customizer settings when switching from a classic theme to a block theme.
	 */
	public function test_resets_customizer_settings_when_switching_from_classic_to_block_theme(): void {
		$this->update_customizer_settings( self::CLASSIC_THEME_SETTINGS );
		$add_product_grid_support = function () {
			add_theme_support(
				'woocommerce',
				array(
					'product_grid' => array(
						'default_columns' => 2,
						'default_rows'    => 3,
					),
				)
			);
		};

		add_action( 'after_switch_theme', $add_product_grid_support, 1 );
		switch_theme( 'twentytwentyfour' );
		check_theme_switched();
		remove_action( 'after_switch_theme', $add_product_grid_support, 1 );

		foreach ( self::DEFAULT_SETTINGS as $option_name => $expected_value ) {
			$this->assertSame(
				$expected_value,
				get_option( $option_name ),
				"Expected {$option_name} to use its block-theme default."
			);
		}

		$backup = get_option( CustomizerSettings::BACKUP_OPTION_NAME );
		$this->assertIsArray( $backup, 'Expected Customizer settings backup to be stored.' );

		foreach ( self::CLASSIC_THEME_SETTINGS as $option_name => $expected_value ) {
			$this->assertTrue(
				$backup[ $option_name ]['exists'],
				"Expected {$option_name} to be marked as present in the backup."
			);
			$this->assertSame(
				$expected_value,
				$backup[ $option_name ]['value'],
				"Expected {$option_name} to be backed up with its classic theme value."
			);
		}
	}

	/**
	 * @testdox Should restore Customizer settings when switching back to a classic theme.
	 */
	public function test_restores_customizer_settings_when_switching_back_to_classic_theme(): void {
		$this->update_customizer_settings( self::CLASSIC_THEME_SETTINGS );

		switch_theme( 'twentytwentyfour' );
		check_theme_switched();

		switch_theme( 'storefront' );
		check_theme_switched();

		foreach ( self::CLASSIC_THEME_SETTINGS as $option_name => $expected_value ) {
			$this->assertSame(
				$expected_value,
				get_option( $option_name ),
				"Expected {$option_name} to be restored to its classic theme value."
			);
		}

		$this->assertFalse(
			get_option( CustomizerSettings::BACKUP_OPTION_NAME ),
			'Expected the Customizer settings backup to be deleted after restoring.'
		);
	}

	/**
	 * @testdox Should keep previously absent Customizer settings absent when switching back to a classic theme.
	 */
	public function test_restores_absent_customizer_settings_when_switching_back_to_classic_theme(): void {
		delete_option( 'woocommerce_category_archive_display' );
		delete_option( 'woocommerce_thumbnail_cropping_custom_height' );

		switch_theme( 'twentytwentyfour' );
		check_theme_switched();

		$this->assertSame( '', get_option( 'woocommerce_category_archive_display' ) );
		$this->assertSame( '3', get_option( 'woocommerce_thumbnail_cropping_custom_height' ) );

		switch_theme( 'storefront' );
		check_theme_switched();

		$this->assertFalse(
			get_option( 'woocommerce_category_archive_display' ),
			'Expected previously absent category archive display option to remain absent.'
		);
		$this->assertFalse(
			get_option( 'woocommerce_thumbnail_cropping_custom_height' ),
			'Expected previously absent custom cropping height option to remain absent.'
		);
		$this->assertFalse(
			get_option( CustomizerSettings::BACKUP_OPTION_NAME ),
			'Expected the Customizer settings backup to be deleted after restoring.'
		);
	}

	/**
	 * Update Customizer settings.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return void
	 */
	private function update_customizer_settings( array $settings ): void {
		foreach ( $settings as $option_name => $value ) {
			update_option( $option_name, $value );
		}
	}

	/**
	 * Reset Customizer settings to defaults.
	 *
	 * @return void
	 */
	private function reset_customizer_settings(): void {
		$this->update_customizer_settings( self::DEFAULT_SETTINGS );
	}

	/**
	 * Delete the Customizer settings backup.
	 *
	 * @return void
	 */
	private function delete_backup(): void {
		delete_option( CustomizerSettings::BACKUP_OPTION_NAME );
	}
}
