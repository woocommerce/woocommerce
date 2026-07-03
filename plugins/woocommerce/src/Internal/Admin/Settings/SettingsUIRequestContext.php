<?php
/**
 * Settings UI request context.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Admin\Settings\SettingsSectionRegistry;
use Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface;

/**
 * Resolves and caches Settings UI state for the active settings request.
 *
 * @since 10.9.0
 */
class SettingsUIRequestContext {

	/**
	 * Storage key for the default section in shared settings payloads.
	 *
	 * @var string
	 */
	private const DEFAULT_SECTION_KEY = 'default';

	/**
	 * Context instances keyed by settings page object and section.
	 *
	 * @var array<string, SettingsUIRequestContext>
	 */
	private static array $contexts = array();

	/**
	 * Settings page for this context.
	 *
	 * @var \WC_Settings_Page
	 */
	private \WC_Settings_Page $settings_page;

	/**
	 * Current settings section. Empty string means the default section.
	 *
	 * @var string
	 */
	private string $section;

	/**
	 * Resolved Settings UI page adapter.
	 *
	 * @var SettingsUIPageInterface|null
	 */
	private ?SettingsUIPageInterface $settings_ui_page;

	/**
	 * Whether script handles have been resolved.
	 *
	 * @var bool
	 */
	private bool $script_handles_resolved = false;

	/**
	 * Resolved script handles.
	 *
	 * @var string[]
	 */
	private array $script_handles = array();

	/**
	 * Whether script handle resolution failed.
	 *
	 * @var bool
	 */
	private bool $script_handles_failed = false;

	/**
	 * Developer-facing script handle failure reason.
	 *
	 * @var string
	 */
	private string $script_handles_failure_reason = '';

	/**
	 * Whether schema generation has been attempted.
	 *
	 * @var bool
	 */
	private bool $schema_resolved = false;

	/**
	 * Generated Settings UI schema.
	 *
	 * @var array|null
	 */
	private ?array $schema = null;

	/**
	 * Whether schema generation failed.
	 *
	 * @var bool
	 */
	private bool $schema_failed = false;

	/**
	 * Constructor.
	 *
	 * @param \WC_Settings_Page $settings_page Settings page.
	 * @param string            $section Current settings section. Empty string means the default section.
	 */
	private function __construct( \WC_Settings_Page $settings_page, string $section ) {
		$this->settings_page    = $settings_page;
		$this->section          = $section;
		$this->settings_ui_page = self::resolve_settings_ui_page( $settings_page, $section );
	}

	/**
	 * Get the context for the active settings request.
	 *
	 * @return SettingsUIRequestContext|null
	 */
	public static function get_current(): ?SettingsUIRequestContext {
		if ( ! PageController::is_settings_page() || ! Features::is_enabled( 'settings-ui' ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return null;
		}

		if ( ! class_exists( '\WC_Admin_Settings' ) ) {
			return null;
		}

		$current_tab = self::get_current_settings_tab();
		foreach ( \WC_Admin_Settings::get_settings_pages() as $settings_page ) {
			if ( ! $settings_page instanceof \WC_Settings_Page || $settings_page->get_id() !== $current_tab ) {
				continue;
			}

			$context = self::for_settings_page( $settings_page, self::get_current_settings_section() );
			return $context->get_settings_ui_page() ? $context : null;
		}

		return null;
	}

	/**
	 * Get a context for a known settings page and section.
	 *
	 * @param \WC_Settings_Page $settings_page Settings page.
	 * @param string            $section Current settings section. Empty string means the default section.
	 * @return SettingsUIRequestContext
	 */
	public static function for_settings_page( \WC_Settings_Page $settings_page, string $section ): SettingsUIRequestContext {
		$key = self::get_context_key( $settings_page, $section );

		if ( ! isset( self::$contexts[ $key ] ) ) {
			self::$contexts[ $key ] = new self( $settings_page, $section );
		}

		return self::$contexts[ $key ];
	}

	/**
	 * Reset cached request contexts.
	 */
	public static function reset(): void {
		self::$contexts = array();
	}

	/**
	 * Get the current WooCommerce settings tab.
	 *
	 * @return string
	 */
	private static function get_current_settings_tab(): string {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_GET['tab'] ) ) {
			return 'general';
		}

		$tab = wp_unslash( $_GET['tab'] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! is_string( $tab ) ) {
			return 'general';
		}

		$tab = sanitize_title( $tab );
		return '' !== $tab ? $tab : 'general';
	}

	/**
	 * Get the current WooCommerce settings section.
	 *
	 * @return string
	 */
	private static function get_current_settings_section(): string {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_GET['section'] ) ) {
			return '';
		}

		$section = wp_unslash( $_GET['section'] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return is_string( $section ) ? sanitize_title( $section ) : '';
	}

	/**
	 * Get the shared settings payload key for a section.
	 *
	 * @param string $section Section id. Empty string means the default section.
	 * @return string
	 */
	private static function get_section_key( string $section ): string {
		return '' === $section ? self::DEFAULT_SECTION_KEY : $section;
	}

	/**
	 * Get the current section's shared settings payload key.
	 *
	 * @return string
	 */
	public function get_current_section_key(): string {
		return self::get_section_key( $this->section );
	}

	/**
	 * Get the Settings UI page adapter.
	 *
	 * @return SettingsUIPageInterface|null
	 */
	public function get_settings_ui_page(): ?SettingsUIPageInterface {
		return $this->settings_ui_page;
	}

	/**
	 * Get the Settings UI page id.
	 *
	 * @return string
	 */
	public function get_page_id(): string {
		return $this->settings_ui_page ? $this->settings_ui_page->get_page_id() : $this->settings_page->get_id();
	}

	/**
	 * Whether this context can render through the Settings UI.
	 *
	 * @return bool
	 */
	public function is_rendering_enabled(): bool {
		return Features::is_enabled( 'settings-ui' ) && $this->settings_ui_page instanceof SettingsUIPageInterface;
	}

	/**
	 * Get extension script handles for this context.
	 *
	 * @return string[]
	 */
	public function get_script_handles(): array {
		if ( ! $this->script_handles_resolved ) {
			$this->resolve_script_handles();
		}

		return $this->script_handles;
	}

	/**
	 * Whether script handle resolution failed.
	 *
	 * @return bool
	 */
	public function has_script_handles_failed(): bool {
		if ( ! $this->script_handles_resolved ) {
			$this->resolve_script_handles();
		}

		return $this->script_handles_failed;
	}

	/**
	 * Get the script handle failure reason.
	 *
	 * @return string
	 */
	public function get_script_handles_failure_reason(): string {
		if ( ! $this->script_handles_resolved ) {
			$this->resolve_script_handles();
		}

		return '' !== $this->script_handles_failure_reason
			? $this->script_handles_failure_reason
			: __( 'Settings UI script handles could not be resolved.', 'woocommerce' );
	}

	/**
	 * Get the Settings UI schema for this context.
	 *
	 * @return array|null
	 */
	public function get_schema(): ?array {
		if ( ! $this->schema_resolved ) {
			$this->resolve_schema();
		}

		return $this->schema;
	}

	/**
	 * Whether schema generation failed.
	 *
	 * @return bool
	 */
	public function has_schema_failed(): bool {
		if ( ! $this->schema_resolved ) {
			$this->resolve_schema();
		}

		return $this->schema_failed;
	}

	/**
	 * Get the context cache key.
	 *
	 * @param \WC_Settings_Page $settings_page Settings page.
	 * @param string            $section Section id. Empty string means the default section.
	 * @return string
	 */
	private static function get_context_key( \WC_Settings_Page $settings_page, string $section ): string {
		return implode(
			'::',
			array(
				(string) spl_object_id( $settings_page ),
				$settings_page->get_id(),
				self::get_section_key( $section ),
			)
		);
	}

	/**
	 * Resolve the Settings UI adapter for a settings page and section.
	 *
	 * @param \WC_Settings_Page $settings_page Settings page.
	 * @param string            $section Section id. Empty string means the default section.
	 * @return SettingsUIPageInterface|null
	 */
	private static function resolve_settings_ui_page( \WC_Settings_Page $settings_page, string $section ): ?SettingsUIPageInterface {
		try {
			$registered_section = SettingsSectionRegistry::get_instance()->get_registered( $settings_page->get_id(), $section );
		} catch ( \Throwable $e ) {
			$registered_section = null;
		}

		if ( $registered_section ) {
			return new RegisteredSettingsSectionAdapter( $settings_page, $registered_section );
		}

		$settings_ui_page = $settings_page->get_settings_ui_page();
		return $settings_ui_page instanceof SettingsUIPageInterface ? $settings_ui_page : null;
	}

	/**
	 * Resolve extension script handles.
	 */
	private function resolve_script_handles(): void {
		$this->script_handles_resolved = true;
		$this->script_handles          = array();

		if ( ! $this->settings_ui_page ) {
			return;
		}

		try {
			$this->script_handles = self::filter_script_handles( $this->settings_ui_page->get_script_handles( $this->section ) );
		} catch ( \Throwable $e ) {
			$this->script_handles_failed = true;

			wc_get_logger()->debug(
				sprintf(
					'Settings UI script handles could not be resolved for page "%1$s" section "%2$s": %3$s: %4$s',
					$this->get_page_id(),
					'' === $this->section ? self::DEFAULT_SECTION_KEY : $this->section,
					get_class( $e ),
					$e->getMessage()
				),
				array( 'source' => 'settings-ui' )
			);

			if ( $e instanceof \Exception ) {
				$this->script_handles_failure_reason = sprintf(
					/* translators: %s: exception message. */
					__( 'Settings UI script handles could not be resolved: %s', 'woocommerce' ),
					$e->getMessage()
				);
				wc_caught_exception( $e, __CLASS__ . '::' . __FUNCTION__ );
			}
		}
	}

	/**
	 * Resolve the Settings UI schema.
	 */
	private function resolve_schema(): void {
		$this->schema_resolved = true;
		$this->schema          = null;

		if ( ! $this->settings_ui_page ) {
			return;
		}

		try {
			$this->schema = $this->settings_ui_page->get_schema( $this->section );
		} catch ( \Throwable $e ) {
			$this->schema_failed = true;

			wc_get_logger()->debug(
				sprintf(
					'Settings UI schema could not be resolved for page "%1$s" section "%2$s": %3$s: %4$s',
					$this->get_page_id(),
					'' === $this->section ? self::DEFAULT_SECTION_KEY : $this->section,
					get_class( $e ),
					$e->getMessage()
				),
				array( 'source' => 'settings-ui' )
			);

			if ( $e instanceof \Exception ) {
				wc_caught_exception( $e, __CLASS__ . '::' . __FUNCTION__ );
			}
		}
	}

	/**
	 * Filter extension-provided script handles to valid WordPress script handle strings.
	 *
	 * @param array $script_handles Raw script handles.
	 * @return string[]
	 */
	private static function filter_script_handles( array $script_handles ): array {
		return array_values(
			array_filter(
				$script_handles,
				static function ( $script_handle ): bool {
					return is_string( $script_handle ) && '' !== $script_handle;
				}
			)
		);
	}
}
