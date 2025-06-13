<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentsExtensionSuggestionIncentives;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentsExtensionSuggestions;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Class WCPayWelcomePage
 *
 * @deprecated 9.9.0 The WooPayments welcome page is deprecated and will be removed in a future version of WooCommerce.
 */
class WcPayWelcomePage {
	/**
	 * The incentive type for the WooPayments welcome page.
	 */
	const INCENTIVE_TYPE = 'welcome_page';

	/**
	 * The suggestion incentives instance.
	 *
	 * @var PaymentsExtensionSuggestionIncentives
	 */
	private PaymentsExtensionSuggestionIncentives $suggestion_incentives;

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
		$this->suggestion_incentives = wc_get_container()->get( PaymentsExtensionSuggestionIncentives::class );
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
			PaymentsExtensionSuggestions::WOOPAYMENTS,
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
			PaymentsExtensionSuggestions::WOOPAYMENTS,
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
			PaymentsExtensionSuggestions::WOOPAYMENTS,
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
