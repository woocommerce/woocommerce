<?php
/**
 * WooPaymentsAdminNavigationController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAdminMenuBadgeService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Registers persistent admin navigation for native WooPayments surfaces.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsAdminNavigationController implements RegisterHooksInterface {

	private const CAPABILITY = 'manage_woocommerce';

	private const MENU_HOOK_PRIORITY = 70;

	private const UNRESOLVED_NOTIFICATION_BADGE_FORMAT = ' <span class="wcpay-menu-badge awaiting-mod count-%1$d"><span class="plugin-count">%1$d</span></span>';

	private const PATH_ONBOARDING = '/woopayments/onboarding';

	private const PATH_OVERVIEW = '/woopayments/overview';

	private const PATH_PAYOUTS = '/woopayments/payouts';

	private const PATH_TRANSACTIONS = '/woopayments/transactions';

	private const PATH_TRANSACTION_DETAILS = '/woopayments/transactions/details';

	private const PATH_REPORTS = '/woopayments/reports';

	private const PATH_DISPUTES = '/woopayments/disputes';

	private const PATH_DISPUTE_DETAILS = '/woopayments/disputes/details';

	private const PATH_DISPUTE_CHALLENGE = '/woopayments/disputes/challenge';

	private const PATH_CARD_READERS = '/woopayments/card-readers';

	private const PATH_LOANS = '/woopayments/loans';

	private const PATH_DOCUMENTS = '/woopayments/documents';

	private const PATH_SETTINGS = '/woopayments/settings';

	private const PATH_FRAUD_PROTECTION_SETTINGS = '/woopayments/settings/fraud-protection';

	private const PATH_PAYOUT_DETAILS = '/woopayments/payouts/details';

	private const SETTINGS_FRAGMENT_ADVANCED = 'advanced';

	private const SETTINGS_FRAGMENT_PAYMENT_METHODS = 'payment-methods';

	private const VAT_DETAILS_REDIRECT_QUERY_ARG = 'woopayments-vat-details-redirect';

	private const VAT_DETAILS_MODAL_QUERY_ARG = 'woopayments-vat-details-modal';

	private const LEGACY_DOCUMENTS_ROUTE = '/payments/documents';

	private const LEGACY_REPORTS_ROUTE = '/payments/reports';

	private const LEGACY_CARD_READERS_ROUTE = '/payments/card-readers';

	private const LEGACY_LOANS_ROUTE = '/payments/loans';

	private const LEGACY_ROUTE_REDIRECTS = array(
		'/payments/connect'                    => self::PATH_ONBOARDING,
		'/payments/onboarding'                 => self::PATH_ONBOARDING,
		'/payments/onboarding/kyc'             => self::PATH_ONBOARDING,
		'/payments/overview'                   => self::PATH_OVERVIEW,
		'/payments/deposits'                   => self::PATH_PAYOUTS,
		'/payments/deposits/details'           => self::PATH_PAYOUT_DETAILS,
		'/payments/payouts'                    => self::PATH_PAYOUTS,
		'/payments/payouts/details'            => self::PATH_PAYOUT_DETAILS,
		'/payments/transactions'               => self::PATH_TRANSACTIONS,
		'/payments/transactions/details'       => self::PATH_TRANSACTION_DETAILS,
		self::LEGACY_REPORTS_ROUTE             => self::PATH_REPORTS,
		'/payments/disputes'                   => self::PATH_DISPUTES,
		'/payments/disputes/details'           => self::PATH_DISPUTE_DETAILS,
		'/payments/disputes/challenge'         => self::PATH_DISPUTE_CHALLENGE,
		self::LEGACY_CARD_READERS_ROUTE        => self::PATH_CARD_READERS,
		self::LEGACY_LOANS_ROUTE               => self::PATH_LOANS,
		self::LEGACY_DOCUMENTS_ROUTE           => self::PATH_DOCUMENTS,
		'/payments/settings'                   => self::PATH_SETTINGS,
		'/payments/fraud-protection'           => self::PATH_FRAUD_PROTECTION_SETTINGS,
		'/payments/multi-currency-setup'       => self::PATH_SETTINGS,
		'/payments/additional-payment-methods' => self::PATH_SETTINGS,
	);

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * WooPayments admin menu badge service.
	 *
	 * @var WooPaymentsAdminMenuBadgeService
	 */
	private WooPaymentsAdminMenuBadgeService $badge_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter     $arbiter         Runtime owner arbiter.
	 * @param WooPaymentsAccountService        $account_service WooPayments account service.
	 * @param WooPaymentsAdminMenuBadgeService $badge_service   WooPayments admin menu badge service.
	 */
	final public function init(
		NativePaymentsRuntimeArbiter $arbiter,
		WooPaymentsAccountService $account_service,
		WooPaymentsAdminMenuBadgeService $badge_service
	): void {
		$this->arbiter         = $arbiter;
		$this->account_service = $account_service;
		$this->badge_service   = $badge_service;
	}

	/**
	 * Register admin navigation hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() ) {
			return;
		}

		if ( false === has_action( 'admin_menu', array( $this, 'add_menu_items' ) ) ) {
			add_action( 'admin_menu', array( $this, 'add_menu_items' ), self::MENU_HOOK_PRIORITY );
		}

		if ( false === has_action( 'admin_init', array( $this, 'redirect_legacy_payment_paths' ) ) ) {
			add_action( 'admin_init', array( $this, 'redirect_legacy_payment_paths' ) );
		}

		if ( false === has_action( 'template_redirect', array( $this, 'redirect_vat_details_request' ) ) ) {
			add_action( 'template_redirect', array( $this, 'redirect_vat_details_request' ) );
		}

		if ( false === has_filter( 'woocommerce_admin_shared_settings', array( $this, 'preload_shared_settings' ) ) ) {
			add_filter( 'woocommerce_admin_shared_settings', array( $this, 'preload_shared_settings' ) );
		}
	}

	/**
	 * Preload native WooPayments settings for the Payments settings page frontend.
	 *
	 * @param mixed $settings Shared admin settings.
	 * @return array<mixed>
	 */
	public function preload_shared_settings( $settings = array() ): array {
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		if ( ! $this->is_payments_settings_request() ) {
			return $settings;
		}

		if ( ! isset( $settings['woopaymentsSettings'] ) || ! is_array( $settings['woopaymentsSettings'] ) ) {
			$settings['woopaymentsSettings'] = array();
		}

		$feature_flags = $settings['woopaymentsSettings']['featureFlags'] ?? array();
		if ( ! is_array( $feature_flags ) ) {
			$feature_flags = array();
		}

		$feature_flags['reportsArea']                    = $this->account_service->is_reports_enabled();
		$settings['woopaymentsSettings']['featureFlags'] = $feature_flags;

		$settings['woopaymentsSettings']['adminRouteAvailability'] = $this->get_admin_route_availability();

		return $settings;
	}

	/**
	 * Redirect legacy WooPayments WC Admin paths to their native Settings > Payments routes.
	 *
	 * @return void
	 */
	public function redirect_legacy_payment_paths(): void {
		if ( ! current_user_can( self::CAPABILITY ) || ! $this->arbiter->should_native_register() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect for legacy admin deep links.
		$redirect_url = $this->get_legacy_payment_path_redirect_url( $_GET );
		if ( '' === $redirect_url ) {
			return;
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Redirect temporary VAT details requests to the native provider settings route.
	 *
	 * @return void
	 */
	public function redirect_vat_details_request(): void {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect for a temporary legacy compatibility URL.
		$redirect_url = $this->get_vat_details_redirect_url( $_GET );
		if ( '' === $redirect_url ) {
			return;
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Get the native provider settings URL for temporary VAT details requests.
	 *
	 * @param array<string,mixed> $request Query request.
	 * @return string
	 */
	public function get_vat_details_redirect_url( array $request ): string {
		if ( ! isset( $request[ self::VAT_DETAILS_REDIRECT_QUERY_ARG ] ) ) {
			return '';
		}

		return Utils::wc_payments_settings_url(
			self::PATH_SETTINGS,
			array(
				self::VAT_DETAILS_MODAL_QUERY_ARG => 'true',
			)
		);
	}

	/**
	 * Get the native redirect URL for a legacy WooPayments WC Admin request.
	 *
	 * @param array<string,mixed> $request Query request.
	 * @return string
	 */
	public function get_legacy_payment_path_redirect_url( array $request ): string {
		if ( 'wc-admin' !== $this->get_request_scalar( $request, 'page' ) ) {
			return '';
		}

		$legacy_path = sanitize_text_field( rawurldecode( $this->get_raw_request_scalar( $request, 'path' ) ) );
		$target_path = self::LEGACY_ROUTE_REDIRECTS[ $legacy_path ] ?? '';
		if ( '' === $target_path ) {
			return '';
		}

		$route_availability = $this->get_admin_route_availability();
		if ( $this->is_legacy_setup_route( $legacy_path ) ) {
			$target_path = $this->get_legacy_setup_route_redirect_path( $route_availability );
		}

		if ( true !== ( $route_availability['allowedRoutes'][ $target_path ] ?? false ) ) {
			return '';
		}

		$query    = array();
		$fragment = '';
		foreach ( $request as $key => $value ) {
			if ( in_array( $key, array( 'page', 'path' ), true ) || ! is_scalar( $value ) ) {
				continue;
			}

			$sanitized_key = sanitize_key( (string) $key );
			if ( '' === $sanitized_key ) {
				continue;
			}

			$query[ $sanitized_key ] = sanitize_text_field( wp_unslash( (string) $value ) );
		}

		if ( self::LEGACY_REPORTS_ROUTE === $legacy_path && isset( $query['tab'] ) ) {
			$query['report_tab'] = $query['tab'];
			unset( $query['tab'] );
		} else {
			unset( $query['tab'] );
		}

		if ( '/payments/multi-currency-setup' === $legacy_path ) {
			$fragment = self::SETTINGS_FRAGMENT_ADVANCED;
		}

		if ( '/payments/additional-payment-methods' === $legacy_path ) {
			$fragment = self::SETTINGS_FRAGMENT_PAYMENT_METHODS;
		}

		return Utils::wc_payments_settings_url( $target_path, $query, $fragment );
	}

	/**
	 * Tell whether a legacy route is part of the setup/onboarding flow.
	 *
	 * @param string $legacy_path Legacy WooPayments WC Admin route path.
	 * @return bool
	 */
	private function is_legacy_setup_route( string $legacy_path ): bool {
		return in_array(
			$legacy_path,
			array(
				'/payments/connect',
				'/payments/onboarding',
				'/payments/onboarding/kyc',
			),
			true
		);
	}

	/**
	 * Resolve setup-era deep links to the best available native account route.
	 *
	 * @param array{gatewayEnabled:bool,accountState:string,allowedRoutes:array<string,bool>} $route_availability Route availability.
	 * @return string
	 */
	private function get_legacy_setup_route_redirect_path( array $route_availability ): string {
		if ( true === ( $route_availability['allowedRoutes'][ self::PATH_ONBOARDING ] ?? false ) ) {
			return self::PATH_ONBOARDING;
		}

		if ( true === ( $route_availability['allowedRoutes'][ self::PATH_OVERVIEW ] ?? false ) ) {
			return self::PATH_OVERVIEW;
		}

		return self::PATH_SETTINGS;
	}

	/**
	 * Get a scalar request value.
	 *
	 * @param array<string,mixed> $request Query request.
	 * @param string              $key     Query key.
	 * @return string
	 */
	private function get_request_scalar( array $request, string $key ): string {
		if ( ! isset( $request[ $key ] ) || ! is_scalar( $request[ $key ] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( (string) $request[ $key ] ) );
	}

	/**
	 * Get a raw scalar request value.
	 *
	 * @param array<string,mixed> $request Query request.
	 * @param string              $key     Query key.
	 * @return string
	 */
	private function get_raw_request_scalar( array $request, string $key ): string {
		if ( ! isset( $request[ $key ] ) || ! is_scalar( $request[ $key ] ) ) {
			return '';
		}

		return wp_unslash( (string) $request[ $key ] );
	}

	/**
	 * Tell whether the current admin request is the Core Payments settings page.
	 *
	 * @return bool
	 */
	private function is_payments_settings_request(): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only request routing for admin shared settings.
		$page = $this->get_request_scalar( $_GET, 'page' );
		$tab  = $this->get_request_scalar( $_GET, 'tab' );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return 'wc-settings' === $page && 'checkout' === $tab;
	}

	/**
	 * Get native WooPayments admin route availability for protected routes.
	 *
	 * @return array{gatewayEnabled:bool,accountState:string,allowedRoutes:array<string,bool>}
	 */
	private function get_admin_route_availability(): array {
		$gateway_enabled  = $this->account_service->is_gateway_enabled();
		$restricted       = $this->account_service->is_account_rejected() || $this->account_service->is_account_under_review();
		$valid_account    = $this->account_service->has_valid_account_for_admin_navigation();
		$onboarding       = ! $restricted && ! $valid_account && ( ! $this->account_service->has_account() || ! $this->account_service->is_details_submitted() );
		$full_access      = $valid_account && ! $restricted;
		$reduced_access   = $restricted;
		$protected_access = $full_access || $reduced_access;

		return array(
			'gatewayEnabled' => $gateway_enabled,
			'accountState'   => $this->get_admin_route_account_state( $restricted, $valid_account, $onboarding ),
			'allowedRoutes'  => array(
				self::PATH_SETTINGS                  => true,
				self::PATH_FRAUD_PROTECTION_SETTINGS => true,
				self::PATH_ONBOARDING                => $onboarding,
				self::PATH_OVERVIEW                  => $protected_access,
				self::PATH_PAYOUTS                   => $full_access,
				self::PATH_PAYOUT_DETAILS            => $full_access,
				self::PATH_TRANSACTIONS              => $protected_access,
				self::PATH_TRANSACTION_DETAILS       => $protected_access,
				self::PATH_DISPUTES                  => $protected_access,
				self::PATH_DISPUTE_DETAILS           => $protected_access,
				self::PATH_DISPUTE_CHALLENGE         => $protected_access,
				self::PATH_REPORTS                   => $full_access && $this->account_service->is_reports_enabled(),
				self::PATH_CARD_READERS              => $full_access && $this->account_service->is_card_present_eligible() && $this->account_service->has_card_readers_available(),
				self::PATH_LOANS                     => $full_access && $this->account_service->has_previous_capital_loans(),
				self::PATH_DOCUMENTS                 => $full_access && $this->account_service->is_documents_enabled(),
			),
		);
	}

	/**
	 * Get a coarse account state label for native WooPayments admin route availability.
	 *
	 * @param bool $restricted      Whether the account is rejected or under review.
	 * @param bool $valid_account   Whether the account can use admin navigation.
	 * @param bool $onboarding      Whether the account should use the onboarding route.
	 * @return string
	 */
	private function get_admin_route_account_state( bool $restricted, bool $valid_account, bool $onboarding ): string {
		if ( $restricted ) {
			return 'restricted';
		}

		if ( $onboarding ) {
			return 'onboarding';
		}

		if ( $valid_account ) {
			return 'full';
		}

		return 'unavailable';
	}

	/**
	 * Add native WooPayments submenu items under the Core Payments parent menu.
	 *
	 * @return void
	 */
	public function add_menu_items(): void {
		if (
			! current_user_can( self::CAPABILITY )
			|| ! $this->arbiter->should_native_register()
		) {
			return;
		}

		foreach ( $this->get_menu_items() as $menu_item ) {
			$this->append_menu_item(
				$menu_item['title'],
				$menu_item['path'],
				$menu_item['query'] ?? array(),
				$menu_item['badge_count'] ?? 0
			);
		}
	}

	/**
	 * Get the menu items that should be visible for the cached account state.
	 *
	 * @return array<int,array{title:string,path:string,query?:array<string,string>,badge_count?:int}>
	 */
	private function get_menu_items(): array {
		if ( $this->account_service->is_account_rejected() || $this->account_service->is_account_under_review() ) {
			return $this->get_reduced_menu_items();
		}

		if ( ! $this->account_service->has_valid_account_for_admin_navigation() ) {
			if ( ! $this->account_service->has_account() ) {
				return array(
					array(
						'title' => __( 'Onboarding', 'woocommerce' ),
						'path'  => self::PATH_ONBOARDING,
					),
				);
			}

			if ( ! $this->account_service->is_details_submitted() ) {
				return array(
					array(
						'title' => __( 'Continue onboarding', 'woocommerce' ),
						'path'  => self::PATH_ONBOARDING,
					),
				);
			}

			return array();
		}

		return $this->get_full_menu_items();
	}

	/**
	 * Get the full native WooPayments submenu for a valid account.
	 *
	 * @return array<int,array{title:string,path:string,query?:array<string,string>,badge_count?:int}>
	 */
	private function get_full_menu_items(): array {
		$menu_items = array(
			array(
				'title' => __( 'Overview', 'woocommerce' ),
				'path'  => self::PATH_OVERVIEW,
			),
			array(
				'title' => __( 'Payouts', 'woocommerce' ),
				'path'  => self::PATH_PAYOUTS,
			),
			$this->get_transactions_menu_item(),
		);

		if ( $this->account_service->is_reports_enabled() ) {
			$menu_items[] = array(
				'title' => __( 'Reports', 'woocommerce' ),
				'path'  => self::PATH_REPORTS,
			);
		}

		$menu_items[] = $this->get_disputes_menu_item();

		if ( $this->account_service->is_card_present_eligible() && $this->account_service->has_card_readers_available() ) {
			$menu_items[] = array(
				'title' => __( 'Card Readers', 'woocommerce' ),
				'path'  => self::PATH_CARD_READERS,
			);
		}

		if ( $this->account_service->has_previous_capital_loans() ) {
			$menu_items[] = array(
				'title' => __( 'Capital Loans', 'woocommerce' ),
				'path'  => self::PATH_LOANS,
			);
		}

		if ( $this->account_service->is_documents_enabled() ) {
			$menu_items[] = array(
				'title' => __( 'Documents', 'woocommerce' ),
				'path'  => self::PATH_DOCUMENTS,
			);
		}

		$menu_items[] = array(
			'title' => __( 'Settings', 'woocommerce' ),
			'path'  => self::PATH_SETTINGS,
		);

		return $menu_items;
	}

	/**
	 * Get the reduced native WooPayments submenu for restricted accounts.
	 *
	 * @return array<int,array{title:string,path:string,query?:array<string,string>,badge_count?:int}>
	 */
	private function get_reduced_menu_items(): array {
		return array(
			array(
				'title' => __( 'Overview', 'woocommerce' ),
				'path'  => self::PATH_OVERVIEW,
			),
			$this->get_transactions_menu_item(),
			$this->get_disputes_menu_item(),
		);
	}

	/**
	 * Get the Transactions menu item with the uncaptured authorization badge when needed.
	 *
	 * @return array{title:string,path:string,badge_count?:int}
	 */
	private function get_transactions_menu_item(): array {
		$menu_item = array(
			'title' => __( 'Transactions', 'woocommerce' ),
			'path'  => self::PATH_TRANSACTIONS,
		);

		$count = $this->badge_service->get_uncaptured_transactions_count();
		if ( $count > 0 ) {
			$menu_item['badge_count'] = $count;
		}

		return $menu_item;
	}

	/**
	 * Get the Disputes menu item with the awaiting-response badge and filter when needed.
	 *
	 * @return array{title:string,path:string,query?:array<string,string>,badge_count?:int}
	 */
	private function get_disputes_menu_item(): array {
		$menu_item = array(
			'title' => __( 'Disputes', 'woocommerce' ),
			'path'  => self::PATH_DISPUTES,
		);

		$count = $this->badge_service->get_disputes_awaiting_response_count();
		if ( $count > 0 ) {
			$menu_item['query']       = array( 'filter' => 'awaiting_response' );
			$menu_item['badge_count'] = $count;
		}

		return $menu_item;
	}

	/**
	 * Append one submenu item under the Core Payments parent.
	 *
	 * @param string               $title       Menu title.
	 * @param string               $path        Native WooPayments settings route path.
	 * @param array<string,string> $query       Extra query args.
	 * @param int                  $badge_count Badge count.
	 * @return void
	 */
	private function append_menu_item( string $title, string $path, array $query = array(), int $badge_count = 0 ): void {
		global $submenu;

		$parent_slug = $this->get_parent_slug();
		$query       = array_merge( array( 'from' => Payments::FROM_PAYMENTS_MENU_ITEM ), $query );

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- WordPress admin menus are registered through global arrays.
		if ( ! isset( $submenu[ $parent_slug ] ) || ! is_array( $submenu[ $parent_slug ] ) ) {
			$submenu[ $parent_slug ] = array();
		}

		$submenu[ $parent_slug ][] = array(
			esc_html( $title ) . $this->get_notification_badge( $badge_count ),
			self::CAPABILITY,
			Utils::wc_payments_settings_url( $path, $query ),
		);
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Get a menu notification badge.
	 *
	 * @param int $count Badge count.
	 * @return string
	 */
	private function get_notification_badge( int $count ): string {
		if ( $count <= 0 ) {
			return '';
		}

		return sprintf( self::UNRESOLVED_NOTIFICATION_BADGE_FORMAT, $count );
	}

	/**
	 * Get the Core Payments parent menu slug.
	 *
	 * @return string
	 */
	private function get_parent_slug(): string {
		return 'admin.php?page=wc-settings&tab=checkout&from=' . Payments::FROM_PAYMENTS_MENU_ITEM;
	}
}
