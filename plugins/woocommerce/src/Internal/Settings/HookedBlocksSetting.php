<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a General setting to turn off the cart and account icons WooCommerce automatically adds to block theme headers.
 *
 * The setting writes the woocommerce_hooked_blocks_version option. Saving a header in the Site Editor stores the
 * hooked blocks in the template part, after which the option no longer affects it. So the checkbox is only shown
 * while every header template part is uncustomized; otherwise a note points to the Site Editor instead.
 *
 * @since 11.2.0
 */
class HookedBlocksSetting {

	/**
	 * Option read by BlockHooksTrait to decide whether to hook blocks.
	 */
	public const OPTION_NAME = 'woocommerce_hooked_blocks_version';

	/**
	 * Id of the virtual checkbox field. It is never stored.
	 */
	public const FIELD_ID = 'woocommerce_hooked_blocks_enabled';

	/**
	 * Id of the note shown when a header template part is customized.
	 */
	public const NOTE_ID = 'woocommerce_hooked_blocks_customized_header_note';

	/**
	 * Option value that turns hooked blocks off.
	 */
	private const DISABLED_VALUE = 'no';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_general_settings', array( $this, 'handle_woocommerce_general_settings' ) );
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . self::FIELD_ID, array( $this, 'handle_woocommerce_admin_settings_sanitize_option' ) );
	}

	/**
	 * Append the header icons setting to the General settings on block themes.
	 *
	 * @internal
	 *
	 * @param mixed $settings General settings fields.
	 * @return mixed
	 */
	public function handle_woocommerce_general_settings( $settings ) {
		if ( ! is_array( $settings ) || ! $this->is_block_theme() ) {
			return $settings;
		}

		$customized_header_id = $this->get_customized_header_id();

		$settings[] = array(
			'title' => __( 'Store header', 'woocommerce' ),
			'type'  => 'title',
			'id'    => 'woocommerce_store_header_options',
		);

		$settings[] = null === $customized_header_id
			? array(
				'title'    => __( 'Header icons', 'woocommerce' ),
				'desc'     => __( 'Automatically add cart and account icons to the header navigation', 'woocommerce' ),
				'desc_tip' => __( 'Applies to headers that haven’t been customized in the Site Editor.', 'woocommerce' ),
				'id'       => self::FIELD_ID,
				'type'     => 'checkbox',
				// No 'default': WC_Install::create_options() would store the virtual field.
				'value'    => $this->is_enabled() ? 'yes' : 'no',
			)
			: array(
				'title' => __( 'Header icons', 'woocommerce' ),
				'text'  => sprintf(
					/* translators: %s: URL of the customized header template part in the Site Editor. */
					__( 'Your header has been customized. Remove the cart or account icons in the <a href="%s">Site Editor</a>.', 'woocommerce' ),
					esc_url(
						add_query_arg(
							array(
								'postType' => 'wp_template_part',
								'postId'   => rawurlencode( $customized_header_id ),
								'canvas'   => 'edit',
							),
							admin_url( 'site-editor.php' )
						)
					)
				),
				'id'    => self::NOTE_ID,
				'type'  => 'info',
			);

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'woocommerce_store_header_options',
		);

		return $settings;
	}

	/**
	 * Write the hooked blocks option from the checkbox and skip storing the virtual field.
	 *
	 * @internal
	 *
	 * @param mixed $value Sanitized checkbox value, "yes" or "no".
	 * @return null
	 */
	public function handle_woocommerce_admin_settings_sanitize_option( $value ) {
		if ( 'yes' !== $value ) {
			update_option( self::OPTION_NAME, self::DISABLED_VALUE );
		} elseif ( ! $this->is_enabled() ) {
			// Keep an existing version so stores don't opt in to placements newer than their current gate.
			update_option( self::OPTION_NAME, WC()->stable_version() );
		}

		return null;
	}

	/**
	 * Whether hooked blocks are currently turned on.
	 *
	 * @return bool
	 */
	private function is_enabled(): bool {
		$version = get_option( self::OPTION_NAME );
		return is_string( $version ) && '' !== $version && self::DISABLED_VALUE !== $version;
	}

	/**
	 * Whether the active theme supports block template parts.
	 *
	 * @return bool
	 */
	private function is_block_theme(): bool {
		return wp_is_block_theme() || current_theme_supports( 'block-template-parts' );
	}

	/**
	 * Get the id of the first customized header template part for the active theme.
	 *
	 * @return string|null Template part id, or null when no header is customized.
	 */
	private function get_customized_header_id(): ?string {
		foreach ( get_block_templates( array( 'area' => 'header' ), 'wp_template_part' ) as $template_part ) {
			if ( 'custom' === $template_part->source ) {
				return $template_part->id;
			}
		}
		return null;
	}
}
