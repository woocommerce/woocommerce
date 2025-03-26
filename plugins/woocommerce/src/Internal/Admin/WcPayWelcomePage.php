<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentExtensionSuggestionIncentives;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentExtensionSuggestions;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Class WCPayWelcomePage
 *
 * @package Automattic\WooCommerce\Admin\Features
 */
class WcPayWelcomePage {
	/**
	 * The incentive type for the WooPayments welcome page.
	 */
	const INCENTIVE_TYPE = 'welcome_page';

	/**
	 * The suggestion incentives instance.
	 *
	 * @var PaymentExtensionSuggestionIncentives
	 */
	private PaymentExtensionSuggestionIncentives $suggestion_incentives;

	/**
	 * Class instance.
	 *
	 * @var ?WcPayWelcomePage
	 */
	protected static ?WcPayWelcomePage $instance = null;

	/**
	 * Get class instance.
	 *
	 * @return ?WcPayWelcomePage
	 */
	public static function instance(): ?WcPayWelcomePage {
		self::$instance = is_null( self::$instance ) ? new self() : self::$instance;

		return self::$instance;
	}

	/**
	 * WCPayWelcomePage constructor.
	 */
	public function __construct() {
		$this->suggestion_incentives = wc_get_container()->get( PaymentExtensionSuggestionIncentives::class );
	}

	/**
	 * Register hooks.
	 */
	public function register() {
		// Because we gate the hooking based on a feature flag,
		// we need to delay the registration until the 'woocommerce_init' hook.
		// Otherwise, we end up in an infinite loop.
		add_action( 'woocommerce_init', array( $this, 'delayed_register' ) );
	}

	/**
	 * Delayed hook registration.
	 */
	public function delayed_register() {
		// Don't do anything if the feature is enabled.
		if ( FeaturesUtil::feature_is_enabled( 'reactify-classic-payments-settings' ) ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'register_menu_and_page' ) );
		add_filter( 'woocommerce_admin_shared_settings', array( $this, 'shared_settings' ) );
		add_filter( 'woocommerce_admin_allowed_promo_notes', array( $this, 'allowed_promo_notes' ) );
		add_filter( 'woocommerce_admin_woopayments_onboarding_task_badge', array( $this, 'onboarding_task_badge' ) );
		add_filter( 'woocommerce_admin_woopayments_onboarding_task_additional_data', array( $this, 'onboarding_task_additional_data' ) );
	}

	/**
	 * Registers the WooPayments welcome page.
	 */
	public function register_menu_and_page() {
		global $menu;

		// The WooPayments plugin must not be active.
		if ( $this->is_wcpay_active() ) {
			return;
		}

		$menu_title = esc_html__( 'Payments', 'woocommerce' );
		$menu_icon  = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4NTIiIGhlaWdodD0iNjg0Ij48cGF0aCBmaWxsPSIjYTJhYWIyIiBkPSJNODIgODZ2NTEyaDY4NFY4NlptMCA1OThjLTQ4IDAtODQtMzgtODQtODZWODZDLTIgMzggMzQgMCA4MiAwaDY4NGM0OCAwIDg0IDM4IDg0IDg2djUxMmMwIDQ4LTM2IDg2LTg0IDg2em0zODQtNTU2djQ0aDg2djg0SDM4MnY0NGgxMjhjMjQgMCA0MiAxOCA0MiA0MnYxMjhjMCAyNC0xOCA0Mi00MiA0MmgtNDR2NDRoLTg0di00NGgtODZ2LTg0aDE3MHYtNDRIMzM4Yy0yNCAwLTQyLTE4LTQyLTQyVjIxNGMwLTI0IDE4LTQyIDQyLTQyaDQ0di00NHoiLz48L3N2Zz4=';

		// If we have an incentive to show, we register the WooPayments welcome/incentives page.
		// Otherwise, we register a menu item that links to the Payments task page.
		if ( $this->has_incentive() ) {
			$page_id      = 'wc-calypso-bridge-payments-welcome-page';
			$page_options = array(
				'id'         => $page_id,
				'title'      => $menu_title,
				'capability' => 'manage_woocommerce',
				'path'       => '/wc-pay-welcome-page',
				'position'   => '56',
				'icon'       => $menu_icon,
			);

			wc_admin_register_page( $page_options );

			$menu_path = PageController::get_instance()->get_path_from_id( $page_id );

			// Registering a top level menu via wc_admin_register_page doesn't work when the new
			// nav is enabled. The new nav disabled everything, except the 'WooCommerce' menu.
			// We need to register this menu via add_menu_page so that it doesn't become a child of
			// WooCommerce menu.
			if ( get_option( 'woocommerce_navigation_enabled', 'no' ) === 'yes' ) {
				$menu_path          = 'admin.php?page=wc-admin&path=/wc-pay-welcome-page';
				$menu_with_nav_data = array(
					$menu_title,
					$menu_title,
					'manage_woocommerce',
					$menu_path,
					null,
					$menu_icon,
					56,
				);

				call_user_func_array( 'add_menu_page', $menu_with_nav_data );
			}

			// Add a badge to the Payments menu item when an incentive is active (available and not dismissed).
			$badge = ' <span class="wcpay-menu-badge awaiting-mod count-1"><span class="plugin-count">1</span></span>';
			foreach ( $menu as $index => $menu_item ) {
				// Only add the badge markup if not already present and the menu item is the Payments menu item.
				if ( 0 === strpos( $menu_item[0], $menu_title )
					&& $menu_path === $menu_item[2]
					&& false === strpos( $menu_item[0], $badge ) ) {

					$menu[ $index ][0] .= $badge; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

					// One menu item with a badge is more than enough.
					break;
				}
			}
		} else {
			// Default to linking to the Payments settings page.
			$menu_path = 'admin.php?page=wc-settings&tab=checkout';

			// Determine the path to the active Payments task page, if any.
			$task_slug = $this->get_active_payments_task_slug();
			if ( ! empty( $task_slug ) ) {
				$menu_path = 'admin.php?page=wc-admin&task=' . $task_slug;
			}

			add_menu_page(
				$menu_title,
				$menu_title,
				'manage_woocommerce',
				$menu_path,
				null,
				$menu_icon,
				56,
			);
		}
	}

	/**
	 * Adds shared settings for the WooPayments incentive.
	 *
	 * @param array $settings Shared settings.
	 * @return array The updated shared settings.
	 */
	public function shared_settings( array $settings ): array {
		// Return early if not on a wc-admin powered page.
		if ( ! PageController::is_admin_page() ) {
			return $settings;
		}

		// Return early if there is no incentive to show.
		if ( ! $this->has_incentive() ) {
			return $settings;
		}

		$settings['wcpayWelcomePageIncentive'] = $this->get_incentive();

		return $settings;
	}

	/**
	 * Adds allowed promo notes for the WooPayments incentives.
	 *
	 * @param array $promo_notes Allowed promo notes.
	 *
	 * @return array
	 */
	public function allowed_promo_notes( array $promo_notes = array() ): array {
		// Note: We need to disregard if WooPayments is active when adding the promo note to the list of
		// allowed promo notes. The AJAX call that adds the promo note happens after WooPayments is installed and activated.
		// Return early if the incentive page must not be visible, without checking if WooPayments is active.
		if ( ! $this->has_incentive( true ) ) {
			return $promo_notes;
		}

		// Add our incentive ID to the allowed promo notes so it can be added to the store.
		$promo_notes[] = $this->get_incentive()['id'];

		return $promo_notes;
	}

	/**
	 * Adds the WooPayments incentive badge to the onboarding task.
	 *
	 * @param string $badge Current badge.
	 *
	 * @return string
	 */
	public function onboarding_task_badge( string $badge ): string {
		// Return early if there is no incentive to show.
		if ( ! $this->has_incentive() ) {
			return $badge;
		}

		return $this->get_incentive()['task_badge'] ?? $badge;
	}

	/**
	 * Filter the onboarding task additional data to add the WooPayments incentive data to it.
	 *
	 * @param ?array $additional_data The current task additional data.
	 *
	 * @return ?array The filtered task additional data.
	 */
	public function onboarding_task_additional_data( ?array $additional_data ): ?array {
		// Return early if there is no incentive to show.
		if ( ! $this->has_incentive() ) {
			return $additional_data;
		}

		// If we have an incentive, add the incentive ID to the additional data.
		if ( $this->get_incentive()['id'] ) {
			if ( empty( $additional_data ) ) {
				$additional_data = array();
			}
			$additional_data['wooPaymentsIncentiveId'] = $this->get_incentive()['id'];
		}

		return $additional_data;
	}

	/**
	 * Check if we have an incentive available to show.
	 *
	 * @param bool $skip_wcpay_active Whether to skip the check for the WooPayments plugin being active.
	 *
	 * @return bool Whether we have an incentive available to show.
	 */
	public function has_incentive( bool $skip_wcpay_active = false ): bool {
		// The WooPayments plugin must not be active.
		if ( ! $skip_wcpay_active && $this->is_wcpay_active() ) {
			return false;
		}

		// Suggestions not disabled via a setting.
		if ( get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) === 'no' ) {
			return false;
		}

		/**
		 * Filter allow marketplace suggestions.
		 *
		 * User can disable all suggestions via filter.
		 *
		 * @since 3.6.0
		 */
		if ( ! apply_filters( 'woocommerce_allow_marketplace_suggestions', true ) ) {
			return false;
		}

		$incentive = $this->get_incentive();
		if ( empty( $incentive ) ) {
			return false;
		}

		if ( $this->is_incentive_dismissed( $incentive ) ) {
			return false;
		}

		return $this->suggestion_incentives->is_incentive_visible(
			$incentive['id'],
			PaymentExtensionSuggestions::WOOPAYMENTS,
			WC()->countries->get_base_country(),
			$skip_wcpay_active
		);
	}

	/**
	 * Get the WooPayments incentive details, if available.
	 *
	 * @return array|null The incentive details. Null if there is no incentive available.
	 */
	private function get_incentive(): ?array {
		return $this->suggestion_incentives->get_incentive(
			PaymentExtensionSuggestions::WOOPAYMENTS,
			WC()->countries->get_base_country(),
			self::INCENTIVE_TYPE,
			true
		);
	}

	/**
	 * Check if the WooPayments plugin is active.
	 *
	 * @return boolean
	 */
	private function is_wcpay_active(): bool {
		return class_exists( '\WC_Payments' );
	}

	/**
	 * Check if the current incentive has been manually dismissed.
	 *
	 * @param array $incentive The incentive details.
	 *
	 * @return boolean
	 */
	private function is_incentive_dismissed( array $incentive ): bool {
		/*
		 * First, check the legacy option.
		 */
		$dismissed_incentives = get_option( 'wcpay_welcome_page_incentives_dismissed', array() );
		if ( ! empty( $dismissed_incentives ) ) {
			// Search the incentive ID in the dismissed incentives list.
			if ( in_array( $incentive['id'], $dismissed_incentives, true ) ) {
				return true;
			}
		}

		/*
		 * Second, use the new logic.
		 */
		return $this->suggestion_incentives->is_incentive_dismissed(
			$incentive['id'],
			PaymentExtensionSuggestions::WOOPAYMENTS,
			'wc_payments_task'
		);
	}

	/**
	 * Get the slug of the active payments task.
	 *
	 * It can be either 'woocommerce-payments' or 'payments'.
	 *
	 * @return string Either 'woocommerce-payments' or 'payments'. Empty string if no task is found.
	 */
	private function get_active_payments_task_slug(): string {
		$setup_task_list    = TaskLists::get_list( 'setup' );
		$extended_task_list = TaskLists::get_list( 'extended' );

		// The task pages are not available if the task lists don't exist or are not visible.
		// Bail early if we have no task to work with.
		if (
			( empty( $setup_task_list ) || ! $setup_task_list->is_visible() ) &&
			( empty( $extended_task_list ) || ! $extended_task_list->is_visible() )
		) {
			return '';
		}

		// The Payments task in the setup task list.
		if ( ! empty( $setup_task_list ) && $setup_task_list->is_visible() ) {
			$payments_task = $setup_task_list->get_task( 'payments' );
			if ( ! empty( $payments_task ) && $payments_task->can_view() ) {
				return 'payments';
			}
		}

		// The Additional Payments task in the extended task list.
		if ( ! empty( $extended_task_list ) && $extended_task_list->is_visible() ) {
			$payments_task = $extended_task_list->get_task( 'payments' );
			if ( ! empty( $payments_task ) && $payments_task->can_view() ) {
				return 'payments';
			}
		}

		// The WooPayments task in the setup task list.
		if ( ! empty( $setup_task_list ) && $setup_task_list->is_visible() ) {
			$payments_task = $setup_task_list->get_task( 'woocommerce-payments' );
			if ( ! empty( $payments_task ) && $payments_task->can_view() ) {
				return 'woocommerce-payments';
			}
		}

		return '';
	}

	/**
	 * Get the WooCommerce setup task list Payments task instance.
	 *
	 * @return Task|null The Payments task instance. null if the task is not found.
	 */
	private function get_payments_task(): ?Task {
		$task_list = TaskLists::get_list( 'setup' );
		if ( empty( $task_list ) ) {
			return null;
		}

		$payments_task = $task_list->get_task( 'payments' );
		if ( empty( $payments_task ) ) {
			return null;
		}

		return $payments_task;
	}

	/**
	 * Determine if the WooCommerce setup task list Payments task is complete.
	 *
	 * @return bool True if the Payments task is complete, false otherwise.
	 */
	private function is_payments_task_complete(): bool {
		$payments_task = $this->get_payments_task();

		return ! empty( $payments_task ) && $payments_task->is_complete();
	}
}
