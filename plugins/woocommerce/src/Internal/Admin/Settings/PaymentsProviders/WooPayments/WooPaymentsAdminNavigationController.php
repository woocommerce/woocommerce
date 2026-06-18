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

	private const PATH_DISPUTES = '/woopayments/disputes';

	private const PATH_CARD_READERS = '/woopayments/card-readers';

	private const PATH_LOANS = '/woopayments/loans';

	private const PATH_SETTINGS = '/woopayments/settings';

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
