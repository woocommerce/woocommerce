<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Jetpack\JetpackConnection;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the WooCommerce > Settings > AI tools tab while the
 * agent_readiness feature is enabled, and names the options it saves.
 *
 * @since 11.4.0
 */
class AgenticCommerceSettings implements RegisterHooksInterface {

	public const FEATURE_ID = 'agent_readiness';

	public const TAB = 'agentic-commerce';

	/**
	 * The AI shopping section of the tab, which the landing page's Manage button opens.
	 */
	public const SHOPPING_SECTION = 'shopping';

	/**
	 * The admin-post action, and its nonce action, behind Connect to WordPress.com on the Overview.
	 */
	public const CONNECT_ACTION = 'woocommerce_ai_tools_connect';

	/**
	 * Query argument the Overview gets back after connecting: connected or error.
	 */
	public const CONNECT_RESULT = 'wc_ai_connect';

	public const PRIVACY_PAGE_OPTION = 'wp_page_for_privacy_policy';

	public const TERMS_PAGE_OPTION = 'woocommerce_terms_page_id';

	public const REFUND_RETURNS_PAGE_OPTION = 'woocommerce_refund_returns_page_id';

	public const SHIPPING_POLICY_PAGE_OPTION = 'woocommerce_shipping_policy_page_id';

	public const FAQ_PAGE_OPTION = 'woocommerce_faq_page_id';

	/**
	 * UCP Phase 1 sends it as the warranty_policy link. Not in the settings design yet.
	 */
	public const WARRANTY_PAGE_OPTION = 'woocommerce_warranty_page_id';

	/**
	 * Features controller.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Store conditions.
	 *
	 * @var StoreConditions
	 */
	private StoreConditions $store_conditions;

	/**
	 * Readiness service.
	 *
	 * @var AgentReadiness
	 */
	private AgentReadiness $agent_readiness;

	/**
	 * Sales readiness.
	 *
	 * @var ReadinessCheckPage
	 */
	private ReadinessCheckPage $readiness_page;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FeaturesController $features_controller Features controller.
	 * @param StoreConditions    $store_conditions    Store conditions.
	 * @param AgentReadiness     $agent_readiness     Readiness service.
	 * @param ReadinessCheckPage $readiness_page      Sales readiness.
	 */
	final public function init( FeaturesController $features_controller, StoreConditions $store_conditions, AgentReadiness $agent_readiness, ReadinessCheckPage $readiness_page ): void {
		$this->features_controller = $features_controller;
		$this->store_conditions    = $store_conditions;
		$this->agent_readiness     = $agent_readiness;
		$this->readiness_page      = $readiness_page;
	}

	/**
	 * Link to the AI tools tab: its landing page, or a section of it.
	 *
	 * @param string $section Section id, or '' for the landing page.
	 */
	public static function url( string $section = '' ): string {
		return admin_url( 'admin.php?page=wc-settings&tab=' . self::TAB . ( '' === $section ? '' : '&section=' . $section ) );
	}

	/**
	 * Register hooks. The feature check waits for init, since feature definitions load translations.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'handle_init' ) );
	}

	/**
	 * Add the settings page when the feature is enabled.
	 *
	 * @internal
	 */
	public function handle_init(): void {
		if ( ! is_admin() || ! $this->features_controller->feature_is_enabled( self::FEATURE_ID ) ) {
			return;
		}
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'handle_woocommerce_get_settings_pages' ) );
		add_action( 'admin_post_' . self::CONNECT_ACTION, array( $this, 'handle_connect' ) );
		add_filter( 'admin_body_class', array( $this, 'handle_admin_body_class' ), 20 );
		// After WooCommerce's rating request (priority 1) and version (20).
		add_filter( 'admin_footer_text', array( $this, 'handle_admin_footer' ), 99 );
		add_filter( 'update_footer', array( $this, 'handle_admin_footer' ), 99 );
	}

	/**
	 * Connect to WordPress.com: registers the site if needed, then sends the user to the Jetpack
	 * connection screen, which returns to the Overview.
	 *
	 * @since 11.4.0
	 *
	 * @internal
	 */
	public function handle_connect(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to connect this store.', 'woocommerce' ), 403 );
		}
		check_admin_referer( self::CONNECT_ACTION );
		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- wordpress_com_https_url() allows only https on wordpress.com.
		wp_redirect( self::connect_redirect() );
		exit;
	}

	/**
	 * Where Connect to WordPress.com goes: the authorization URL, or back to the Overview with an error
	 * when the site can't be registered or that URL is not https on WordPress.com.
	 *
	 * @since 11.4.0
	 */
	public static function connect_redirect(): string {
		$result    = JetpackConnection::get_authorization_url( add_query_arg( self::CONNECT_RESULT, 'connected', self::url() ), 'woocommerce-core-profiler' );
		$candidate = ! empty( $result['success'] ) && is_string( $result['url'] ?? null ) ? $result['url'] : '';
		$url       = self::wordpress_com_https_url( $candidate );
		return '' !== $url ? $url : add_query_arg( self::CONNECT_RESULT, 'error', self::url() );
	}

	/**
	 * An https WordPress.com URL with a lowercased scheme, or an empty string.
	 *
	 * @since 11.4.0
	 *
	 * @param string $url Candidate redirect.
	 * @return string
	 */
	public static function wordpress_com_https_url( string $url ): string {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return '';
		}
		$raw_scheme = isset( $parts['scheme'] ) ? $parts['scheme'] : '';
		$scheme     = strtolower( $raw_scheme );
		$host       = strtolower( (string) ( $parts['host'] ?? '' ) );
		$suffix     = '.wordpress.com';
		$on_wp      = 'wordpress.com' === $host || ( strlen( $host ) > strlen( $suffix ) && substr( $host, -strlen( $suffix ) ) === $suffix );
		if ( 'https' !== $scheme || ! $on_wp ) {
			return '';
		}
		return 'https' . substr( $url, strlen( $raw_scheme ) );
	}

	/**
	 * The Overview's connection card: whether this store and user are connected to WordPress.com, the
	 * notice to show after a connection attempt, and the POST that starts one.
	 *
	 * @since 11.4.0
	 *
	 * @return array{connected: bool, notice: string, url: string, action: string, nonce: string}
	 */
	public static function connect_view(): array {
		$manager = JetpackConnection::get_manager();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only picks the notice.
		$result    = isset( $_GET[ self::CONNECT_RESULT ] ) && is_string( $_GET[ self::CONNECT_RESULT ] ) ? sanitize_key( wp_unslash( $_GET[ self::CONNECT_RESULT ] ) ) : '';
		$connected = $manager->is_connected() && $manager->is_user_connected();
		if ( 'connected' === $result ) {
			$notice = $connected ? 'connected' : 'error';
		} elseif ( 'error' === $result && ! $connected ) {
			$notice = 'error';
		} else {
			$notice = '';
		}
		return array(
			'connected' => $connected,
			'notice'    => $notice,
			'url'       => $connected ? '' : admin_url( 'admin-post.php' ),
			'action'    => $connected ? '' : self::CONNECT_ACTION,
			'nonce'     => $connected ? '' : wp_create_nonce( self::CONNECT_ACTION ),
		);
	}

	/**
	 * No admin footer on the AI tools tab and on Sales readiness, as in the design: the rating request
	 * and the version would sit under, or over, the last card.
	 *
	 * @internal
	 *
	 * @param mixed $text Footer text.
	 * @return mixed
	 */
	public function handle_admin_footer( $text ) {
		return self::is_own_screen() ? '' : $text;
	}

	/**
	 * Whether this request is the AI tools tab or Sales readiness.
	 */
	private static function is_own_screen(): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Only picks what the footer shows.
		$page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$tab  = isset( $_GET['tab'] ) && is_string( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		$path = isset( $_GET['path'] ) && is_string( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		return ( 'wc-settings' === $page && self::TAB === $tab ) || ( 'wc-admin' === $page && ReadinessCheckPage::PATH === $path );
	}

	/**
	 * Mark the AI tools tab on the body, so its panel can be white like the Payments tab.
	 *
	 * @internal
	 *
	 * @param mixed $classes Body classes.
	 * @return mixed
	 */
	public function handle_admin_body_class( $classes ) {
		global $current_tab;

		if ( ! is_string( $classes ) || self::TAB !== $current_tab ) {
			return $classes;
		}
		return "$classes woocommerce-settings-agentic-commerce-tab";
	}

	/**
	 * Add the AI tools tab to the settings pages.
	 *
	 * @internal
	 *
	 * @param mixed $pages Settings pages.
	 * @return mixed
	 */
	public function handle_woocommerce_get_settings_pages( $pages ) {
		if ( ! is_array( $pages ) || ! class_exists( \WC_Settings_Page::class, false ) ) {
			return $pages;
		}
		$pages[] = new AgenticCommerceSettingsPage( $this->store_conditions, $this->agent_readiness, $this->readiness_page );
		return $pages;
	}
}
