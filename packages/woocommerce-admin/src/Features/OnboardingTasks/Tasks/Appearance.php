<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

/**
 * Appearance Task
 */
class Appearance {
	/**
	 * Get the task arguments.
	 *
	 * @return array
	 */
	public static function get_task() {
		return array(
			'id'           => 'appearance',
			'title'        => __( 'Personalize my store', 'woocommerce' ),
			'content'      => __(
				'Add your logo, create a homepage, and start designing your store.',
				'woocommerce'
			),
			'action_label' => __( "Let's go", 'woocommerce' ),
			'is_complete'  => get_option( 'woocommerce_task_list_appearance_complete' ),
			'can_view'     => true,
			'time'         => __( '2 minutes', 'woocommerce' ),
		);
	}
}
