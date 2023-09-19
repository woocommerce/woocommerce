<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Internal\Admin\Loader;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Products;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * Appearance Task
 */
class Appearance extends Task {

	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'appearance';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Customize your store', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return __(
			"Choose a theme that best fits your brand's look and feel, then make it your own. Change the colors, add your logo, and create pages.",
			'woocommerce'
		);
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '2 minutes', 'woocommerce' );
	}

	/**
	 * Action label.
	 *
	 * @return string
	 */
	public function get_action_label() {
		return __( 'Choose theme', 'woocommerce' );
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return 'twentytwentythree' !== get_stylesheet();
	}
}
