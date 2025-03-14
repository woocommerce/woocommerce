<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Email;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Helper class for syncing email styles with theme styles.
 *
 * @internal Just for internal use.
 */
class EmailStyleSync implements RegisterHooksInterface {

	/**
	 * Option name for auto-sync setting.
	 */
	const AUTO_SYNC_OPTION = 'woocommerce_email_auto_sync_with_theme';

	/**
	 * Flag to prevent recursive syncing.
	 *
	 * @var bool
	 */
	private $is_syncing = false;

	/**
	 * Register hooks and filters.
	 */
	public function register() {
		// Hook into theme change events.
		add_action( 'after_switch_theme', array( $this, 'sync_email_styles_with_theme' ) );
		add_action( 'customize_save_after', array( $this, 'sync_email_styles_with_theme' ) );

		// Hook into theme.json and global styles changes.
		add_action( 'wp_theme_json_data_updated', array( $this, 'sync_email_styles_with_theme' ) );
		add_action( 'rest_after_insert_global_styles', array( $this, 'sync_email_styles_with_theme' ) );
		add_action( 'update_option_wp_global_styles', array( $this, 'sync_email_styles_with_theme' ) );
		add_action( 'save_post_wp_global_styles', array( $this, 'sync_email_styles_with_theme' ) );

		// Hook into the theme editor save action.
		add_action( 'wp_ajax_wp_save_styles', array( $this, 'sync_email_styles_with_theme' ), 999 );

		// Hook into auto-sync option update to trigger sync when enabled.
		add_action( 'update_option_' . self::AUTO_SYNC_OPTION, array( $this, 'maybe_sync_on_option_update' ), 10, 3 );
	}

	/**
	 * Trigger sync when auto-sync option is enabled.
	 *
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $new_value The new option value.
	 * @param string $option    The option name.
	 */
	public function maybe_sync_on_option_update( $old_value, $new_value, $option ) {
		if ( 'yes' === $new_value && 'yes' !== $old_value ) {
			// Force sync regardless of current auto-sync setting since we know it's being enabled.
			$this->is_syncing = true;
			try {
				$this->update_email_colors();
			} finally {
				$this->is_syncing = false;
			}
		}
	}

	/**
	 * Check if auto-sync is enabled.
	 *
	 * @return bool Whether auto-sync is enabled.
	 */
	public function is_auto_sync_enabled() {
		return 'yes' === get_option( self::AUTO_SYNC_OPTION, 'no' );
	}

	/**
	 * Set auto-sync enabled status.
	 *
	 * @param bool $enabled Whether auto-sync should be enabled.
	 * @return bool Whether the option was updated.
	 */
	public function set_auto_sync( bool $enabled ) {
		return update_option( self::AUTO_SYNC_OPTION, $enabled ? 'yes' : 'no' );
	}

	/**
	 * Sync email styles with theme styles if auto-sync is enabled.
	 *
	 * Uses a flag to prevent recursive calls.
	 */
	public function sync_email_styles_with_theme() {
		if ( $this->is_syncing || ! $this->is_auto_sync_enabled() || ! wp_theme_has_theme_json() ) {
			return;
		}

		$this->is_syncing = true;

		try {
			$this->update_email_colors();
		} finally {
			$this->is_syncing = false;
		}
	}

	/**
	 * Update email colors from theme colors.
	 */
	protected function update_email_colors() {
		$colors = $this->get_theme_colors();
		if ( empty( $colors ) ) {
			return;
		}

		if ( ! empty( $colors['base_color'] ) ) {
			update_option( 'woocommerce_email_base_color', $colors['base_color'] );
		}

		if ( ! empty( $colors['bg_color'] ) ) {
			update_option( 'woocommerce_email_background_color', $colors['bg_color'] );
		}

		if ( ! empty( $colors['body_bg_color'] ) ) {
			update_option( 'woocommerce_email_body_background_color', $colors['body_bg_color'] );
		}

		if ( ! empty( $colors['body_text_color'] ) ) {
			update_option( 'woocommerce_email_text_color', $colors['body_text_color'] );
		}

		if ( ! empty( $colors['footer_text_color'] ) ) {
			update_option( 'woocommerce_email_footer_text_color', $colors['footer_text_color'] );
		}
	}

	/**
	 * Get theme colors from theme.json.
	 *
	 * @param array|null $override_styles Optional array of styles to override.
	 * @return array Array of theme colors.
	 */
	protected function get_theme_colors( ?array $override_styles = null ) {
		if ( ! function_exists( 'wp_get_global_styles' ) ) {
			return array();
		}

		$global_styles = $override_styles ?: wp_get_global_styles( array(), array( 'transforms' => array( 'resolve-variables' ) ) );

		$default_colors = EmailColors::get_default_colors();
		$base_color_default = $default_colors['base_color_default'];
		$bg_color_default = $default_colors['bg_color_default'];
		$body_bg_color_default = $default_colors['body_bg_color_default'];
		$body_text_color_default = $default_colors['body_text_color_default'];
		$footer_text_color_default = $default_colors['footer_text_color_default'];

		$base_color = ! empty( $global_styles['elements']['button']['color']['background'] )
			? sanitize_hex_color( $global_styles['elements']['button']['color']['background'] )
			: $base_color_default;

		$bg_color = ! empty( $global_styles['color']['background'] )
			? sanitize_hex_color( $global_styles['color']['background'] )
			: $bg_color_default;

		$body_bg_color = ! empty( $global_styles['color']['background'] )
			? sanitize_hex_color( $global_styles['color']['background'] )
			: $body_bg_color_default;

		$body_text_color = ! empty( $global_styles['color']['text'] )
			? sanitize_hex_color( $global_styles['color']['text'] )
			: $body_text_color_default;

		$footer_text_color = ! empty( $global_styles['elements']['caption']['color']['text'] )
			? sanitize_hex_color( $global_styles['elements']['caption']['color']['text'] )
			: $footer_text_color_default;

		return array(
			'base_color' => $base_color,
			'bg_color' => $bg_color,
			'body_bg_color' => $body_bg_color,
			'body_text_color' => $body_text_color,
			'footer_text_color' => $footer_text_color,
		);
	}
}
