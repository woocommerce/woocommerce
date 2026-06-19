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

	private const PATH_ONBOARDING = '/woopayments/onboarding';

	private const PATH_OVERVIEW = '/woopayments/overview';

	private const PATH_PAYOUTS = '/woopayments/payouts';

	private const PATH_TRANSACTIONS = '/woopayments/transactions';

	private const PATH_TRANSACTION_DETAILS = '/woopayments/transactions/details';

	private const PATH_DISPUTES = '/woopayments/disputes';

	private const PATH_DISPUTE_DETAILS = '/woopayments/disputes/details';

	private const PATH_DISPUTE_CHALLENGE = '/woopayments/disputes/challenge';

	private const PATH_CARD_READERS = '/woopayments/card-readers';

	private const PATH_LOANS = '/woopayments/loans';

	private const PATH_SETTINGS = '/woopayments/settings';

	private const PATH_PAYOUT_DETAILS = '/woopayments/payouts/details';

	private const LEGACY_ROUTE_REDIRECTS = array(
		'/payments/overview'             => self::PATH_OVERVIEW,
		'/payments/deposits'             => self::PATH_PAYOUTS,
		'/payments/deposits/details'     => self::PATH_PAYOUT_DETAILS,
		'/payments/payouts'              => self::PATH_PAYOUTS,
		'/payments/payouts/details'      => self::PATH_PAYOUT_DETAILS,
		'/payments/transactions'         => self::PATH_TRANSACTIONS,
		'/payments/transactions/details' => self::PATH_TRANSACTION_DETAILS,
		'/payments/disputes'             => self::PATH_DISPUTES,
		'/payments/disputes/details'     => self::PATH_DISPUTE_DETAILS,
		'/payments/disputes/challenge'   => self::PATH_DISPUTE_CHALLENGE,
		'/payments/card-readers'         => self::PATH_CARD_READERS,
		'/payments/loans'                => self::PATH_LOANS,
		'/payments/settings'             => self::PATH_SETTINGS,
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
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter $arbiter         Runtime owner arbiter.
	 * @param WooPaymentsAccountService    $account_service WooPayments account service.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsAccountService $account_service ): void {
		$this->arbiter         = $arbiter;
		$this->account_service = $account_service;
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

		$query = array();
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

		return Utils::wc_payments_settings_url( $target_path, $query );
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
	 * Add native WooPayments submenu items under the Core Payments parent menu.
	 *
	 * @return void
	 */
	public function add_menu_items(): void {
		if ( ! current_user_can( self::CAPABILITY ) || ! $this->arbiter->should_native_register() ) {
			return;
		}

		foreach ( $this->get_menu_items() as $menu_item ) {
			$this->append_menu_item( $menu_item['title'], $menu_item['path'] );
		}
	}

	/**
	 * Get the menu items that should be visible for the cached account state.
	 *
	 * @return array<int,array{title:string,path:string}>
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
	 * @return array<int,array{title:string,path:string}>
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
			array(
				'title' => __( 'Transactions', 'woocommerce' ),
				'path'  => self::PATH_TRANSACTIONS,
			),
			array(
				'title' => __( 'Disputes', 'woocommerce' ),
				'path'  => self::PATH_DISPUTES,
			),
		);

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

		$menu_items[] = array(
			'title' => __( 'Settings', 'woocommerce' ),
			'path'  => self::PATH_SETTINGS,
		);

		return $menu_items;
	}

	/**
	 * Get the reduced native WooPayments submenu for restricted accounts.
	 *
	 * @return array<int,array{title:string,path:string}>
	 */
	private function get_reduced_menu_items(): array {
		return array(
			array(
				'title' => __( 'Overview', 'woocommerce' ),
				'path'  => self::PATH_OVERVIEW,
			),
			array(
				'title' => __( 'Transactions', 'woocommerce' ),
				'path'  => self::PATH_TRANSACTIONS,
			),
			array(
				'title' => __( 'Disputes', 'woocommerce' ),
				'path'  => self::PATH_DISPUTES,
			),
		);
	}

	/**
	 * Append one submenu item under the Core Payments parent.
	 *
	 * @param string $title Menu title.
	 * @param string $path  Native WooPayments settings route path.
	 * @return void
	 */
	private function append_menu_item( string $title, string $path ): void {
		global $submenu;

		$parent_slug = $this->get_parent_slug();

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- WordPress admin menus are registered through global arrays.
		if ( ! isset( $submenu[ $parent_slug ] ) || ! is_array( $submenu[ $parent_slug ] ) ) {
			$submenu[ $parent_slug ] = array();
		}

		$submenu[ $parent_slug ][] = array(
			$title,
			self::CAPABILITY,
			Utils::wc_payments_settings_url( $path, array( 'from' => Payments::FROM_PAYMENTS_MENU_ITEM ) ),
		);
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
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
