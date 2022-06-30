<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Review Shipping Options Task
 */
class ReviewShippingOptions extends Task {
	/**
	 * Constructor
	 *
	 * @param TaskList $task_list Parent task list.
	 */
	public function __construct( $task_list ) {
		parent::__construct( $task_list );
		add_action( 'current_screen', array( $this, 'possibly_mark_task_as_completed' ) );
	}

	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'shipping';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Review Shipping Options', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return '';
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return '';
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return 'yes' === get_option( 'woocommerce_admin_reviewed_default_shipping_zones' );
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		return 'yes' === get_option( 'woocommerce_admin_created_default_shipping_zones' );
	}

	/**
	 * Action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=shipping' );
	}

	/**
	 * Mark task as completed when the user visits the shipping setting page.
	 */
	public function possibly_mark_task_as_completed() {
		$screen = get_current_screen();

		if (
			! $screen ||
			'woocommerce_page_wc-settings' !== $screen->id ||
			! isset( $_GET['tab'] ) || 'shipping' !== $_GET['tab'] // phpcs:ignore CSRF okok.
		) {
			// It's not the shipping settings page.
			return;
		}

		if ( self::is_complete() || ! self::can_view() ) {
			return;
		}

		update_option( 'woocommerce_admin_reviewed_default_shipping_zones', 'yes' );
	}
}
