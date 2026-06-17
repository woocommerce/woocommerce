<?php
/**
 * Settings section registry.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registry for sections that extensions add to existing WooCommerce settings pages.
 *
 * @since 10.9.0
 */
final class SettingsSectionRegistry {

	/**
	 * Singleton instance.
	 *
	 * @var SettingsSectionRegistry|null
	 */
	private static ?SettingsSectionRegistry $instance = null;

	/**
	 * Registered sections keyed by parent page id and section id.
	 *
	 * @var array<string, array<string, SettingsSectionInterface>>
	 */
	private array $sections = array();

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
	 * @return bool True when registered.
	 *
	 * @since 10.9.0
	 */
	public function register( SettingsSectionInterface $section ): bool {
		$parent_page_id = $this->normalize_id( $section->get_parent_page_id() );
		$section_id     = $this->normalize_id( $section->get_id() );

		if ( '' === $parent_page_id || '' === $section_id ) {
			wc_doing_it_wrong(
				__METHOD__,
				esc_html__( 'Settings sections must declare a non-empty parent page id and section id.', 'woocommerce' ),
				'10.9.0'
			);
			return false;
		}

		if ( isset( $this->sections[ $parent_page_id ][ $section_id ] ) ) {
			wc_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: 1: parent settings page id, 2: settings section id. */
					esc_html__( 'A settings section is already registered for "%1$s/%2$s".', 'woocommerce' ),
					esc_html( $parent_page_id ),
					esc_html( $section_id )
				),
				'10.9.0'
			);
			return false;
		}

		$this->sections[ $parent_page_id ][ $section_id ] = $section;
		return true;
	}

	/**
	 * Get a registered section.
	 *
	 * @param string $parent_page_id Parent settings page id.
	 * @param string $section_id Section id.
	 * @return SettingsSectionInterface|null
	 *
	 * @since 10.9.0
	 */
	public function get_registered( string $parent_page_id, string $section_id ): ?SettingsSectionInterface {
		$this->initialize();

		$parent_page_id = $this->normalize_id( $parent_page_id );
		$section_id     = $this->normalize_id( $section_id );

		return $this->sections[ $parent_page_id ][ $section_id ] ?? null;
	}

	/**
	 * Get registered section labels for a settings page.
	 *
	 * @param string $parent_page_id Parent settings page id.
	 * @return array<string, string>
	 *
	 * @since 10.9.0
	 */
	public function get_sections_for_page( string $parent_page_id ): array {
		$this->initialize();

		$parent_page_id = $this->normalize_id( $parent_page_id );
		$registered     = $this->sections[ $parent_page_id ] ?? array();

		$sections = array();
		foreach ( $registered as $section_id => $section ) {
			$sections[ $section_id ] = $section->get_label();
		}

		return $sections;
	}

	/**
	 * Clear all registered sections.
	 *
	 * @since 10.9.0
	 */
	public function unregister_all(): void {
		$this->sections    = array();
		$this->initialized = false;
	}

	/**
	 * Fire the section registration action once.
	 */
	private function initialize(): void {
		if ( $this->initialized ) {
			return;
		}

		$this->initialized = true;

		/**
		 * Fires when settings sections can be registered.
		 *
		 * @param SettingsSectionRegistry $registry Settings section registry.
		 *
		 * @since 10.9.0
		 */
		do_action( 'woocommerce_settings_sections_registration', $this );
	}

	/**
	 * Normalize page and section identifiers.
	 *
	 * @param string $id Identifier.
	 * @return string
	 */
	private function normalize_id( string $id ): string {
		return sanitize_title( $id );
	}
}
