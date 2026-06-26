<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Theme;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Handles WooCommerce Customizer settings when switching between classic and block themes.
 *
 * @since 11.0.0
 * @internal
 */
class CustomizerSettings implements RegisterHooksInterface {

	/**
	 * Option used to temporarily store classic theme Customizer settings.
	 */
	public const BACKUP_OPTION_NAME = 'woocommerce_classic_theme_customizer_settings';

	/**
	 * Option used to store whether Store Notice was active in a classic theme.
	 */
	private const ENABLE_STORE_NOTICE_IN_CLASSIC_THEME_OPTION = 'woocommerce_enable_store_notice_in_classic_theme';

	/**
	 * Option used to store whether Store Notice is active.
	 */
	private const STORE_NOTICE_ACTIVE_OPTION = 'woocommerce_demo_store';

	/**
	 * Product catalog and image Customizer settings with their block-theme defaults.
	 */
	private const CUSTOMIZER_SETTINGS = array(
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
	 * Default value used to check whether an option exists.
	 */
	private const OPTION_DOES_NOT_EXIST = '__woocommerce_customizer_setting_does_not_exist__';

	/**
	 * Register this class instance to the appropriate hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'after_switch_theme', array( $this, 'backup_customizer_settings' ), 5, 2 );
		add_action( 'after_switch_theme', array( $this, 'reset_or_restore_customizer_settings' ), 20, 2 );
	}

	/**
	 * Back up WooCommerce Customizer settings when switching from a classic theme to a block theme.
	 *
	 * @param string    $old_name Old theme name.
	 * @param \WP_Theme $old_theme Instance of the old theme.
	 * @return void
	 */
	public function backup_customizer_settings( $old_name, $old_theme ) {
		if ( ! $old_theme->is_block_theme() && wp_is_block_theme() ) {
			$this->backup_settings();
		}
	}

	/**
	 * Reset or restore WooCommerce Customizer settings when switching theme types.
	 *
	 * @param string    $old_name Old theme name.
	 * @param \WP_Theme $old_theme Instance of the old theme.
	 * @return void
	 */
	public function reset_or_restore_customizer_settings( $old_name, $old_theme ) {
		if ( ! $old_theme->is_block_theme() && wp_is_block_theme() ) {
			$this->disable_store_notice_for_block_theme();
			$this->reset_settings();
		} elseif ( $old_theme->is_block_theme() && ! wp_is_block_theme() ) {
			$this->restore_store_notice_for_classic_theme();
			$this->restore_settings();
		}
	}

	/**
	 * Update Store Notice visibility when switching between classic and block themes.
	 *
	 * @internal
	 *
	 * @param string    $old_name Old theme name.
	 * @param \WP_Theme $old_theme Instance of the old theme.
	 * @return void
	 *
	 * @since 11.0.0
	 */
	public function update_store_notice_visible_on_theme_switch( $old_name, $old_theme ) {
		if ( ! $old_theme->is_block_theme() && wp_is_block_theme() ) {
			$this->disable_store_notice_for_block_theme();
		} elseif ( $old_theme->is_block_theme() && ! wp_is_block_theme() ) {
			$this->restore_store_notice_for_classic_theme();
		}
	}

	/**
	 * Back up classic theme Customizer settings.
	 *
	 * @return void
	 */
	private function backup_settings() {
		$settings = array();

		foreach ( array_keys( self::CUSTOMIZER_SETTINGS ) as $option_name ) {
			$settings[ $option_name ] = array(
				'exists' => $this->option_exists( $option_name ),
				'value'  => get_option( $option_name ),
			);
		}

		update_option( self::BACKUP_OPTION_NAME, $settings );
	}

	/**
	 * Replace Customizer settings with block-theme defaults.
	 *
	 * @return void
	 */
	private function reset_settings() {
		foreach ( self::CUSTOMIZER_SETTINGS as $option_name => $default_value ) {
			update_option( $option_name, $default_value );
		}
	}

	/**
	 * Restore backed-up Customizer settings for classic themes.
	 *
	 * @return void
	 */
	private function restore_settings() {
		$settings = get_option( self::BACKUP_OPTION_NAME, array() );

		if ( ! is_array( $settings ) ) {
			return;
		}

		foreach ( $settings as $option_name => $setting ) {
			if ( ! array_key_exists( $option_name, self::CUSTOMIZER_SETTINGS ) || ! is_array( $setting ) ) {
				continue;
			}

			if ( ! empty( $setting['exists'] ) ) {
				update_option( $option_name, $setting['value'] ?? null );
			} else {
				delete_option( $option_name );
			}
		}

		delete_option( self::BACKUP_OPTION_NAME );
	}

	/**
	 * Disable Store Notice for block themes and remember if it should be restored.
	 *
	 * @return void
	 */
	private function disable_store_notice_for_block_theme() {
		if ( ! is_store_notice_showing() ) {
			return;
		}

		update_option( self::STORE_NOTICE_ACTIVE_OPTION, wc_bool_to_string( false ) );
		add_option( self::ENABLE_STORE_NOTICE_IN_CLASSIC_THEME_OPTION, wc_bool_to_string( true ) );
	}

	/**
	 * Restore Store Notice when switching back to a classic theme.
	 *
	 * @return void
	 */
	private function restore_store_notice_for_classic_theme() {
		$enable_store_notice_in_classic_theme = wc_string_to_bool( get_option( self::ENABLE_STORE_NOTICE_IN_CLASSIC_THEME_OPTION, 'no' ) );

		if ( ! $enable_store_notice_in_classic_theme ) {
			return;
		}

		update_option( self::STORE_NOTICE_ACTIVE_OPTION, wc_bool_to_string( true ) );
		delete_option( self::ENABLE_STORE_NOTICE_IN_CLASSIC_THEME_OPTION );
	}

	/**
	 * Check whether an option exists in the database.
	 *
	 * @param string $option_name Option name.
	 * @return bool
	 */
	private function option_exists( string $option_name ): bool {
		return self::OPTION_DOES_NOT_EXIST !== get_option( $option_name, self::OPTION_DOES_NOT_EXIST );
	}
}
