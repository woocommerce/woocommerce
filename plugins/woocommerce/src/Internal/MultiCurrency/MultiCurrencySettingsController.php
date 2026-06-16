<?php
/**
 * MultiCurrencySettingsController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;
use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyAccountInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySettingsProjectionService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\MultiCurrency\WooPaymentsLegacyAccountAdapter;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Registers native multi-currency settings hooks when core owns multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencySettingsController implements RegisterHooksInterface {

	/**
	 * Runtime owner arbiter.
	 *
	 * @var MultiCurrencyRuntimeArbiter
	 */
	private MultiCurrencyRuntimeArbiter $arbiter;

	/**
	 * Provider account boundary.
	 *
	 * @var MultiCurrencyAccountInterface
	 */
	private MultiCurrencyAccountInterface $account;

	/**
	 * Provider connection resolver.
	 *
	 * @var callable|null
	 */
	private $provider_connected_resolver = null;

	/**
	 * Provider onboarding URL resolver.
	 *
	 * @var callable|null
	 */
	private $onboarding_url_resolver = null;

	/**
	 * Admin request resolver.
	 *
	 * @var callable|null
	 */
	private $admin_request_resolver = null;

	/**
	 * Current WooCommerce settings tab resolver.
	 *
	 * @var callable|null
	 */
	private $current_tab_resolver = null;

	/**
	 * Current admin screen base resolver.
	 *
	 * @var callable|null
	 */
	private $current_screen_base_resolver = null;

	/**
	 * Admin asset availability resolver.
	 *
	 * @var callable|null
	 */
	private $asset_available_resolver = null;

	/**
	 * Admin asset registrar.
	 *
	 * @var callable|null
	 */
	private $asset_registrar = null;

	/**
	 * Admin asset enqueuer.
	 *
	 * @var callable|null
	 */
	private $asset_enqueuer = null;

	/**
	 * Emoji detection script printer.
	 *
	 * @var callable|null
	 */
	private $emoji_detection_script_printer = null;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyRuntimeArbiter     $arbiter Runtime owner arbiter.
	 * @param WooPaymentsLegacyAccountAdapter $account Provider account boundary.
	 */
	final public function init( MultiCurrencyRuntimeArbiter $arbiter, WooPaymentsLegacyAccountAdapter $account ): void {
		$this->arbiter = $arbiter;
		$this->account = $account;
	}

	/**
	 * Set the provider connection resolver.
	 *
	 * @internal Used by tests and future explicit provider bootstrap.
	 *
	 * @param callable $provider_connected_resolver Provider connection resolver.
	 */
	public function set_provider_connected_resolver( callable $provider_connected_resolver ): void {
		$this->provider_connected_resolver = $provider_connected_resolver;
	}

	/**
	 * Set the provider onboarding URL resolver.
	 *
	 * @internal Used by tests and future explicit provider bootstrap.
	 *
	 * @param callable $onboarding_url_resolver Provider onboarding URL resolver.
	 */
	public function set_onboarding_url_resolver( callable $onboarding_url_resolver ): void {
		$this->onboarding_url_resolver = $onboarding_url_resolver;
	}

	/**
	 * Set the admin request resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $admin_request_resolver Admin request resolver.
	 */
	public function set_admin_request_resolver( callable $admin_request_resolver ): void {
		$this->admin_request_resolver = $admin_request_resolver;
	}

	/**
	 * Set the current settings tab resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $current_tab_resolver Current settings tab resolver.
	 */
	public function set_current_tab_resolver( callable $current_tab_resolver ): void {
		$this->current_tab_resolver = $current_tab_resolver;
	}

	/**
	 * Set the current screen base resolver.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $current_screen_base_resolver Current screen base resolver.
	 */
	public function set_current_screen_base_resolver( callable $current_screen_base_resolver ): void {
		$this->current_screen_base_resolver = $current_screen_base_resolver;
	}

	/**
	 * Set the admin asset availability resolver.
	 *
	 * @internal Used by tests and future explicit asset bootstrap.
	 *
	 * @param callable $asset_available_resolver Admin asset availability resolver.
	 */
	public function set_asset_available_resolver( callable $asset_available_resolver ): void {
		$this->asset_available_resolver = $asset_available_resolver;
	}

	/**
	 * Set the admin asset registrar.
	 *
	 * @internal Used by tests and future explicit asset bootstrap.
	 *
	 * @param callable $asset_registrar Admin asset registrar.
	 */
	public function set_asset_registrar( callable $asset_registrar ): void {
		$this->asset_registrar = $asset_registrar;
	}

	/**
	 * Set the admin asset enqueuer.
	 *
	 * @internal Used by tests and future explicit asset bootstrap.
	 *
	 * @param callable $asset_enqueuer Admin asset enqueuer.
	 */
	public function set_asset_enqueuer( callable $asset_enqueuer ): void {
		$this->asset_enqueuer = $asset_enqueuer;
	}

	/**
	 * Set the emoji detection script printer.
	 *
	 * @internal Used by tests.
	 *
	 * @param callable $emoji_detection_script_printer Emoji detection script printer.
	 */
	public function set_emoji_detection_script_printer( callable $emoji_detection_script_printer ): void {
		$this->emoji_detection_script_printer = $emoji_detection_script_printer;
	}

	/**
	 * Register multi-currency settings hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_core_register() ) {
			return;
		}

		$this->add_filter_once( 'woocommerce_get_settings_pages', array( $this, 'handle_woocommerce_get_settings_pages' ) );
		$this->add_filter_once( 'wcpay_js_settings', array( $this, 'add_multi_currency_settings_config' ) );
		$this->add_action_once( 'admin_print_scripts', array( $this, 'handle_admin_print_scripts' ) );
		$this->add_action_once( 'woocommerce_admin_field_wcpay_multi_currency_settings_page', array( $this, 'render_settings_container' ) );
		$this->add_action_once( 'woocommerce_admin_field_wcpay_currencies_settings_onboarding_cta', array( $this, 'render_onboarding_cta' ) );
		$this->add_action_once( 'admin_enqueue_scripts', array( $this, 'handle_admin_enqueue_scripts' ) );
	}

	/**
	 * Add the native multi-currency settings page.
	 *
	 * @param array<int,mixed> $settings_pages Existing settings pages.
	 * @return array<int,mixed>
	 */
	public function handle_woocommerce_get_settings_pages( array $settings_pages ): array {
		$manifest = MultiCurrencySettingsProjectionService::get_settings_page_manifest(
			$this->is_provider_connected(),
			$this->is_cli_request(),
			$this->is_wpcom_jobs_request(),
			did_action( 'upgrader_process_complete' ) > 0
		);

		if ( empty( $manifest ) ) {
			return $settings_pages;
		}

		$settings_pages[] = new MultiCurrencySettingsPage( $manifest );

		return $settings_pages;
	}

	/**
	 * Render the React mount point for connected multi-currency settings.
	 *
	 * @internal
	 */
	public function render_settings_container(): void {
		$GLOBALS['hide_save_button'] = true;

		echo wp_kses_post( MultiCurrencySettingsProjectionService::get_settings_container_markup() );
	}

	/**
	 * Render the onboarding CTA settings field.
	 *
	 * @internal
	 */
	public function render_onboarding_cta(): void {
		echo wp_kses_post( MultiCurrencySettingsProjectionService::get_onboarding_cta_markup( $this->get_onboarding_url() ) );
	}

	/**
	 * Print the emoji detection script on the multi-currency settings page.
	 *
	 * @internal
	 */
	public function handle_admin_print_scripts(): void {
		if ( ! $this->is_multi_currency_settings_page() ) {
			return;
		}

		if ( null !== $this->emoji_detection_script_printer ) {
			call_user_func( $this->emoji_detection_script_printer );
			return;
		}

		if ( function_exists( 'print_emoji_detection_script' ) ) {
			print_emoji_detection_script();
		}
	}

	/**
	 * Enqueue multi-currency settings assets when the migrated bundle is available.
	 *
	 * @internal
	 */
	public function handle_admin_enqueue_scripts(): void {
		if ( ! MultiCurrencySettingsProjectionService::should_enqueue_admin_assets( $this->get_current_tab() ) ) {
			return;
		}

		if ( ! $this->is_settings_asset_available() ) {
			return;
		}

		$manifest = MultiCurrencySettingsProjectionService::get_admin_asset_manifest();
		$this->register_admin_assets( $manifest );

		$script = is_array( $manifest['script'] ?? null ) ? $manifest['script'] : array();
		$this->enqueue_admin_asset( (string) ( $script['handle'] ?? '' ) );
	}

	/**
	 * Add the multi-currency flag to the WCPay JS config.
	 *
	 * @param array<string,mixed> $config Existing config.
	 * @return array<string,mixed>
	 */
	public function add_multi_currency_settings_config( array $config ): array {
		return MultiCurrencySettingsProjectionService::add_props_to_wcpay_js_config( $config );
	}

	/**
	 * Tell whether the provider account is connected.
	 *
	 * @return bool
	 */
	private function is_provider_connected(): bool {
		if ( null !== $this->provider_connected_resolver ) {
			return (bool) call_user_func( $this->provider_connected_resolver );
		}

		try {
			return $this->account->is_provider_connected();
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * Get the provider onboarding URL.
	 *
	 * @return string
	 */
	private function get_onboarding_url(): string {
		if ( null !== $this->onboarding_url_resolver ) {
			return (string) call_user_func( $this->onboarding_url_resolver );
		}

		try {
			$onboarding_url = $this->account->get_provider_onboarding_page_url();
			if ( '' !== $onboarding_url ) {
				return $onboarding_url;
			}
		} catch ( \Throwable $e ) {
			return admin_url( 'admin.php?page=wc-admin&path=/payments/onboarding' );
		}

		return admin_url( 'admin.php?page=wc-admin&path=/payments/onboarding' );
	}

	/**
	 * Tell whether the current request is an admin request.
	 *
	 * @return bool
	 */
	private function is_admin_request(): bool {
		if ( null !== $this->admin_request_resolver ) {
			return (bool) call_user_func( $this->admin_request_resolver );
		}

		return function_exists( 'is_admin' ) && is_admin();
	}

	/**
	 * Get the current WooCommerce settings tab.
	 *
	 * @return string|null
	 */
	private function get_current_tab(): ?string {
		if ( null !== $this->current_tab_resolver ) {
			$current_tab = call_user_func( $this->current_tab_resolver );

			return is_string( $current_tab ) ? $current_tab : null;
		}

		global $current_tab;

		return is_string( $current_tab ) ? $current_tab : null;
	}

	/**
	 * Get the current admin screen base.
	 *
	 * @return string|null
	 */
	private function get_current_screen_base(): ?string {
		if ( null !== $this->current_screen_base_resolver ) {
			$screen_base = call_user_func( $this->current_screen_base_resolver );

			return is_string( $screen_base ) ? $screen_base : null;
		}

		global $current_screen;

		if ( is_object( $current_screen ) && isset( $current_screen->base ) && is_string( $current_screen->base ) ) {
			return $current_screen->base;
		}

		return null;
	}

	/**
	 * Tell whether the current admin screen is the multi-currency settings page.
	 *
	 * @return bool
	 */
	private function is_multi_currency_settings_page(): bool {
		return MultiCurrencySettingsProjectionService::is_multi_currency_settings_page(
			$this->is_admin_request(),
			$this->get_current_tab(),
			$this->get_current_screen_base()
		);
	}

	/**
	 * Tell whether the current request is WP-CLI.
	 *
	 * @return bool
	 */
	private function is_cli_request(): bool {
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	/**
	 * Tell whether the current request is a WPCOM jobs request.
	 *
	 * @return bool
	 */
	private function is_wpcom_jobs_request(): bool {
		return defined( 'WPCOM_JOBS' ) && WPCOM_JOBS;
	}

	/**
	 * Tell whether the migrated settings asset bundle exists.
	 *
	 * @return bool
	 */
	private function is_settings_asset_available(): bool {
		if ( null !== $this->asset_available_resolver ) {
			return (bool) call_user_func( $this->asset_available_resolver );
		}

		$manifest = MultiCurrencySettingsProjectionService::get_admin_asset_manifest();
		$script   = is_array( $manifest['script'] ?? null ) ? $manifest['script'] : array();
		$entry    = (string) ( $script['entry'] ?? '' );

		return '' !== $entry && file_exists( WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/' . $entry . '.asset.php' );
	}

	/**
	 * Register admin assets.
	 *
	 * @param array<string,mixed> $manifest Admin asset manifest.
	 */
	private function register_admin_assets( array $manifest ): void {
		if ( null !== $this->asset_registrar ) {
			call_user_func( $this->asset_registrar, $manifest );
			return;
		}

		$script = is_array( $manifest['script'] ?? null ) ? $manifest['script'] : array();
		$entry  = (string) ( $script['entry'] ?? '' );
		if ( '' === $entry ) {
			return;
		}

		WCAdminAssets::register_script( 'wp-admin-scripts', $entry, true );
	}

	/**
	 * Enqueue an admin asset handle.
	 *
	 * @param string $handle Asset handle.
	 */
	private function enqueue_admin_asset( string $handle ): void {
		if ( '' === $handle ) {
			return;
		}

		if ( null !== $this->asset_enqueuer ) {
			call_user_func( $this->asset_enqueuer, $handle );
			return;
		}

		// WCAdminAssets::register_script() enqueues production assets directly.
	}

	/**
	 * Register a filter only once for this controller instance.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Hook callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 */
	private function add_filter_once( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		if ( false === has_filter( $hook, $callback ) ) {
			add_filter( $hook, $callback, $priority, $accepted_args );
		}
	}

	/**
	 * Register an action only once for this controller instance.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Hook callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 */
	private function add_action_once( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		if ( false === has_action( $hook, $callback ) ) {
			add_action( $hook, $callback, $priority, $accepted_args );
		}
	}
}
