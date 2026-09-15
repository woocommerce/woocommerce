<?php
/**
 * Settings UI request context.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Admin\Settings\SettingsSectionRegistry;
use Automattic\WooCommerce\Admin\Settings\SettingsSectionUIPageProviderInterface;
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
	 * Settings tabs whose sections render as drill-down pages.
	 *
	 * @var string[]
	 */
	private const DRILL_DOWN_TABS = array( 'checkout' );

	/**
	 * Query argument used to request classic settings for the current request.
	 *
	 * @var string
	 */
	private const CLASSIC_REQUEST_QUERY_ARG = 'wc_settings_ui';

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
	 * Whether the Settings UI page id has been resolved.
	 *
	 * @var bool
	 */
	private bool $page_id_resolved = false;

	/**
	 * Resolved Settings UI page id.
	 *
	 * @var string
	 */
	private string $page_id = '';

	/**
	 * Failure raised while resolving the Settings UI page id.
	 *
	 * @var \Throwable|null
	 */
	private ?\Throwable $page_id_failure = null;

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
	 * Whether script handle registrations have been checked.
	 *
	 * @var bool
	 */
	private bool $script_handle_registrations_checked = false;

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
	 * Developer-facing schema failure reason.
	 *
	 * @var string
	 */
	private string $schema_failure_reason = '';

	/**
	 * Constructor.
	 *
	 * @param \WC_Settings_Page $settings_page Settings page.
	 * @param string            $section Current settings section. Empty string means the default section.
	 */
	private function __construct( \WC_Settings_Page $settings_page, string $section ) {
		$this->settings_page    = $settings_page;
		$this->section          = $section;
		$this->settings_ui_page = self::is_classic_request() ? null : self::resolve_settings_ui_page( $settings_page, $section );
	}

	/**
	 * Get the context for the active settings request.
	 *
	 * @return SettingsUIRequestContext|null
	 */
	public static function get_current(): ?SettingsUIRequestContext {
		if ( self::is_classic_request() || ! PageController::is_settings_page() || ! Features::is_enabled( 'settings-ui' ) || ! current_user_can( 'manage_woocommerce' ) ) {
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
	 * Reads $_REQUEST to match how the legacy $current_section global is derived,
	 * so context resolution and legacy settings rendering agree on the section.
	 *
	 * @return string
	 */
	private static function get_current_settings_section(): string {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_REQUEST['section'] ) ) {
			return '';
		}

		$section = wp_unslash( $_REQUEST['section'] );
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
	 * Get the legacy settings page this context was resolved for.
	 *
	 * @since 11.0.0
	 *
	 * @return \WC_Settings_Page
	 */
	public function get_settings_page(): \WC_Settings_Page {
		return $this->settings_page;
	}

	/**
	 * Get the Settings UI page id.
	 *
	 * @return string
	 * @throws \Throwable When the Settings UI page adapter cannot resolve its page id.
	 */
	public function get_page_id(): string {
		if ( ! $this->page_id_resolved ) {
			try {
				$this->page_id = $this->settings_ui_page ? $this->settings_ui_page->get_page_id() : $this->settings_page->get_id();
			} catch ( \Throwable $e ) {
				$this->page_id_failure = $e;
			}

			$this->page_id_resolved = true;
		}

		if ( $this->page_id_failure ) {
			throw $this->page_id_failure;
		}

		return $this->page_id;
	}

	/**
	 * Whether this context renders a drill-down page.
	 *
	 * A drill-down page is a section of a settings tab whose sections are
	 * presented as standalone pages, following the Payments pattern: the shell
	 * header (title, breadcrumbs, top save button) replaces the top-level
	 * settings tabs. Pages registered at the top level of settings are not
	 * drill-downs: they hide the header and keep the tabs.
	 *
	 * @since 11.0.0
	 *
	 * @return bool
	 */
	public function is_drill_down(): bool {
		return '' !== $this->section && in_array( $this->settings_page->get_id(), self::DRILL_DOWN_TABS, true );
	}

	/**
	 * Whether this context can render through the Settings UI.
	 *
	 * True when the settings UI feature is enabled and a Settings UI page resolved
	 * for the page and section. The page can come from a registered section (native
	 * or adapted from its legacy settings) or from the settings page itself, and
	 * callers replacing legacy rendering should treat all three the same.
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
	 * Validate and enqueue extension script handles for this context.
	 *
	 * Handle names are collected separately so extensions can register their
	 * scripts after WooCommerce builds the settings embed dependency list.
	 *
	 * @since 11.2.0
	 *
	 * @return string[] Enqueued script handles, or an empty array on failure.
	 */
	public function enqueue_script_handles(): array {
		$this->validate_script_handle_registrations();

		if ( $this->script_handles_failed ) {
			return array();
		}

		try {
			foreach ( $this->script_handles as $script_handle ) {
				wp_enqueue_script( $script_handle );

				if ( ! wp_script_is( $script_handle, 'enqueued' ) ) {
					$this->record_script_handles_failure(
						new \RuntimeException(
							sprintf(
								/* translators: %s: script handle. */
								__( 'Settings UI script handle "%s" could not be enqueued.', 'woocommerce' ),
								sanitize_text_field( $script_handle )
							)
						),
						__METHOD__
					);
					return array();
				}
			}
		} catch ( \Throwable $e ) {
			$this->record_script_handles_failure( $e, __METHOD__ );
			return array();
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
	 * Whether resolving, validating, or enqueueing declared scripts failed.
	 *
	 * @return bool
	 *
	 * @since 11.2.0
	 */
	public function has_script_handle_loading_failed(): bool {
		$this->validate_script_handle_registrations();

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
		if ( $this->has_script_handles_failed() ) {
			$this->schema_resolved = true;

			return null;
		}

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
			$this->get_schema();
		}

		return $this->schema_failed;
	}

	/**
	 * Get the schema failure reason.
	 *
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function get_schema_failure_reason(): string {
		if ( ! $this->schema_resolved ) {
			$this->get_schema();
		}

		return '' !== $this->schema_failure_reason
			? $this->schema_failure_reason
			: __( 'Settings UI schema could not be resolved.', 'woocommerce' );
	}

	/**
	 * Whether the current request explicitly asks for classic settings.
	 *
	 * @return bool
	 */
	private static function is_classic_request(): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only request override that changes rendering only.
		if ( ! isset( $_GET[ self::CLASSIC_REQUEST_QUERY_ARG ] ) ) {
			return false;
		}

		$rendering_mode = wp_unslash( $_GET[ self::CLASSIC_REQUEST_QUERY_ARG ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Type checked and sanitized below.
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return is_string( $rendering_mode ) && 'classic' === sanitize_key( $rendering_mode );
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
			self::log_resolution_failure( 'Registered settings section', $settings_page->get_id(), $section, $e, __METHOD__ );
			$registered_section = null;
		}

		if ( $registered_section ) {
			if ( $registered_section instanceof SettingsSectionUIPageProviderInterface ) {
				try {
					$settings_ui_page = $registered_section->get_settings_ui_page( $settings_page );
					if ( $settings_ui_page instanceof SettingsUIPageInterface ) {
						return $settings_ui_page;
					}
				} catch ( \Throwable $e ) {
					self::log_resolution_failure( 'Native Settings UI page', $settings_page->get_id(), $section, $e, __METHOD__ );

					// Raise a developer notice here only: this failure still
					// renders through the registered-section adapter, so
					// nothing downstream reports it. Registry lookup failures
					// are environmental, and schema/script-handle failures
					// surface through log_settings_ui_fallback() at render time.
					wc_doing_it_wrong(
						__METHOD__,
						sprintf(
							/* translators: 1: settings page id, 2: settings section id, 3: failure reason. */
							esc_html__( 'The native Settings UI page for page "%1$s" section "%2$s" could not be resolved. Falling back to the default settings adapter. Reason: %3$s', 'woocommerce' ),
							esc_html( $settings_page->get_id() ),
							esc_html( self::get_section_key( $section ) ),
							esc_html( get_class( $e ) . ': ' . $e->getMessage() )
						),
						'11.0.0'
					);
				}
			}

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
			$this->script_handles = self::validate_script_handles( $this->settings_ui_page->get_script_handles( $this->section ) );
		} catch ( \Throwable $e ) {
			$this->record_script_handles_failure( $e, __METHOD__ );
		}
	}

	/**
	 * Validate extension script handle declarations.
	 *
	 * @param array $script_handles Declared script handles.
	 * @return string[] Validated script handles.
	 * @throws \InvalidArgumentException When a handle is not a non-empty string.
	 */
	private static function validate_script_handles( array $script_handles ): array {
		// Exception messages are cached diagnostics rather than HTML output. Dynamic
		// handles are sanitized before the exception crosses this boundary.
		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		foreach ( $script_handles as $script_handle ) {
			if ( ! is_string( $script_handle ) || '' === trim( $script_handle ) ) {
				throw new \InvalidArgumentException( __( 'Settings UI script handles must be non-empty strings.', 'woocommerce' ) );
			}
		}
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped

		return array_values( array_unique( array_map( 'trim', $script_handles ) ) );
	}

	/**
	 * Validate extension script handle registrations.
	 */
	private function validate_script_handle_registrations(): void {
		if ( ! $this->script_handles_resolved ) {
			$this->resolve_script_handles();
		}

		if ( $this->script_handle_registrations_checked || $this->script_handles_failed ) {
			return;
		}

		$this->script_handle_registrations_checked = true;

		try {
			foreach ( $this->script_handles as $script_handle ) {
				if ( wp_script_is( $script_handle, 'registered' ) ) {
					continue;
				}

				$this->record_script_handles_failure(
					new \RuntimeException(
						sprintf(
							/* translators: %s: script handle. */
							__( 'Settings UI script handle "%s" is not registered.', 'woocommerce' ),
							sanitize_text_field( $script_handle )
						)
					),
					__METHOD__
				);
				return;
			}
		} catch ( \Throwable $e ) {
			$this->record_script_handles_failure( $e, __METHOD__ );
		}
	}

	/**
	 * Cache and report a script handle failure.
	 *
	 * @param \Throwable $e Resolution failure.
	 * @param string     $caller Calling method, for exception tracking.
	 */
	private function record_script_handles_failure( \Throwable $e, string $caller ): void {
		$this->script_handles_failed = true;

		self::log_resolution_failure( 'Settings UI script handles', $this->settings_page->get_id(), $this->section, $e, $caller );

		$this->script_handles_failure_reason = sprintf(
			/* translators: %s: failure reason. */
			__( 'Settings UI script handles could not be resolved: %s', 'woocommerce' ),
			self::sanitize_failure_reason( $e )
		);
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
			$schema = $this->settings_ui_page->get_schema( $this->section );
			$schema = SettingsUISchema::canonicalize_schema_values( $schema );

			$schema = $this->apply_section_navigation( $schema );
			$schema = $this->apply_shell_header_visibility( $schema );
			$schema = $this->ensure_drill_down_breadcrumbs( $schema );

			SettingsUISchema::assert_valid_schema( $schema );
			$this->schema = $schema;
		} catch ( \Throwable $e ) {
			$this->schema_failed         = true;
			$this->schema_failure_reason = self::sanitize_failure_reason( $e );

			self::log_resolution_failure( 'Settings UI schema', $this->settings_page->get_id(), $this->section, $e, __METHOD__ );
		}
	}

	/**
	 * Sanitize a failure reason before it reaches developer-facing output.
	 *
	 * @param \Throwable $e Resolution failure.
	 * @return string
	 */
	private static function sanitize_failure_reason( \Throwable $e ): string {
		$reason = sanitize_text_field( $e->getMessage() );

		return '' !== $reason ? $reason : get_class( $e );
	}

	/**
	 * Log a Settings UI resolution failure for developers.
	 *
	 * @param string     $subject What failed to resolve, e.g. 'Settings UI schema'.
	 * @param string     $page_id Settings page id.
	 * @param string     $section Section id. Empty string means the default section.
	 * @param \Throwable $e The resolution failure.
	 * @param string     $caller Calling method, for exception tracking.
	 */
	private static function log_resolution_failure( string $subject, string $page_id, string $section, \Throwable $e, string $caller ): void {
		wc_get_logger()->error(
			sprintf(
				'%1$s could not be resolved for page "%2$s" section "%3$s": %4$s: %5$s',
				$subject,
				$page_id,
				self::get_section_key( $section ),
				get_class( $e ),
				$e->getMessage()
			),
			array( 'source' => 'settings-ui' )
		);

		if ( $e instanceof \Exception ) {
			wc_caught_exception( $e, $caller );
		}
	}

	/**
	 * Set the shell section navigation from the page registration.
	 *
	 * Top-level pages never carry section navigation: the classic section
	 * links render with the settings header instead. Drill-down pages keep
	 * schema-provided navigation and default to none, since the header
	 * breadcrumbs replace it.
	 *
	 * @param array $schema Resolved settings UI schema.
	 * @return array
	 */
	private function apply_section_navigation( array $schema ): array {
		if ( ! isset( $schema['shell'] ) || ! is_array( $schema['shell'] ) ) {
			$schema['shell'] = array();
		}

		if ( ! $this->is_drill_down() || ! isset( $schema['shell']['sectionNavigation'] ) ) {
			$schema['shell']['sectionNavigation'] = array();
		}

		return $schema;
	}

	/**
	 * Set the shell header visibility from the page registration.
	 *
	 * The header is reserved for drill-down pages. Pages registered at the top
	 * level of settings always hide it, regardless of what their schema asks
	 * for.
	 *
	 * @param array $schema Resolved settings UI schema.
	 * @return array
	 */
	private function apply_shell_header_visibility( array $schema ): array {
		if ( ! isset( $schema['shell'] ) || ! is_array( $schema['shell'] ) ) {
			$schema['shell'] = array();
		}

		$schema['shell']['header'] = $this->is_drill_down() ? 'visible' : 'hidden';

		return $schema;
	}

	/**
	 * Ensure a drill-down schema carries breadcrumbs back to its parent tab.
	 *
	 * Schemas that omit `shell.breadcrumbs` get a single crumb linking to the
	 * parent settings tab, since the header breadcrumbs replace the top-level
	 * settings tabs on drill-down pages.
	 *
	 * @param array $schema Resolved settings UI schema.
	 * @return array
	 */
	private function ensure_drill_down_breadcrumbs( array $schema ): array {
		if ( ! $this->is_drill_down() || isset( $schema['shell']['breadcrumbs'] ) ) {
			return $schema;
		}

		$schema['shell']['breadcrumbs'] = array(
			array(
				'label' => wp_strip_all_tags( html_entity_decode( $this->settings_page->get_label(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ),
				'href'  => add_query_arg(
					array(
						'page' => 'wc-settings',
						'tab'  => sanitize_title( $this->settings_page->get_id() ),
					),
					admin_url( 'admin.php' )
				),
			),
		);

		return $schema;
	}
}
