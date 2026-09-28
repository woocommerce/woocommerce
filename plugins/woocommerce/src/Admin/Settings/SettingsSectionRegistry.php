<?php
/**
 * Settings section registry.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Settings;

defined( 'ABSPATH' ) || exit;

// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter -- Deprecated no-op methods keep their signatures.

/**
 * Registry for sections that extensions add to existing WooCommerce settings pages.
 *
 * Registration no longer adds sections. The methods stay so callers don't fail.
 *
 * @since 10.9.0
 * @deprecated 11.4.0 The Settings UI was removed. Settings pages use the classic renderer.
 */
final class SettingsSectionRegistry {

	/**
	 * Singleton instance.
	 *
	 * @var SettingsSectionRegistry|null
	 */
	private static ?SettingsSectionRegistry $instance = null;

	/**
	 * Whether the registration action has fired.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get the registry instance.
	 *
	 * @return SettingsSectionRegistry
	 *
	 * @since 10.9.0
	 */
	public static function get_instance(): SettingsSectionRegistry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register a settings section.
	 *
	 * @param SettingsSectionInterface $section Section instance.
	 * @return bool Always false: the section is not added.
	 *
	 * @since 10.9.0
	 * @deprecated 11.4.0 Sections are no longer added. Add sections with the `woocommerce_get_sections_{$page_id}` filter.
	 */
	public function register( SettingsSectionInterface $section ): bool {
		\Automattic\WooCommerce\Internal\Admin\Settings\SettingsUIDeprecation::record_usage( __METHOD__ );
		return false;
	}

	/**
	 * Get a registered section.
	 *
	 * @param string $parent_page_id Parent settings page id.
	 * @param string $section_id Section id.
	 * @return SettingsSectionInterface|null Always null.
	 *
	 * @since 10.9.0
	 * @deprecated 11.4.0 No sections are registered.
	 */
	// @phpstan-ignore return.unusedType (The return type is kept for backward compatibility.)
	public function get_registered( string $parent_page_id, string $section_id ): ?SettingsSectionInterface {
		$this->initialize();
		return null;
	}

	/**
	 * Get registered section labels for a settings page.
	 *
	 * @param string $parent_page_id Parent settings page id.
	 * @return array<string, string> Always empty.
	 *
	 * @since 10.9.0
	 * @deprecated 11.4.0 No sections are registered.
	 */
	public function get_sections_for_page( string $parent_page_id ): array {
		$this->initialize();
		return array();
	}

	/**
	 * Clear all registered sections.
	 *
	 * @since 10.9.0
	 * @deprecated 11.4.0 No sections are registered.
	 */
	public function unregister_all(): void {
		$this->initialized = false;
	}

	/**
	 * Fire the deprecated registration action once. It only fires, with a deprecation notice, when something is hooked.
	 */
	private function initialize(): void {
		if ( $this->initialized ) {
			return;
		}
		$this->initialized = true;

		try {
			/**
			 * Fires when settings sections can be registered.
			 *
			 * @param SettingsSectionRegistry $registry Settings section registry.
			 *
			 * @since 10.9.0
			 * @deprecated 11.4.0 Registered sections are no longer added. Use the `woocommerce_get_sections_{$page_id}` filter.
			 */
			wc_do_deprecated_action(
				'woocommerce_settings_sections_registration',
				array( $this ),
				'11.4.0',
				'woocommerce_get_sections_{$page_id}',
				'The Settings UI was removed, so registered settings sections are no longer shown.'
			);
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				sprintf(
					'Settings section registration failed: %1$s: %2$s',
					get_class( $e ),
					$e->getMessage()
				),
				array( 'source' => 'settings-ui' )
			);
		}
	}
}
