<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Onboarding;
use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Purchase Task
 */
class Purchase {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'update_option_woocommerce_onboarding_profile', array( __CLASS__, 'clear_dismissal' ), 10, 2 );
	}

	/**
	 * Clear dismissal on onboarding product type changes.
	 *
	 * @param array $old_value Old value.
	 * @param array $new_value New value.
	 */
	public static function clear_dismissal( $old_value, $new_value ) {
		$product_types          = isset( $new_value['product_types'] ) ? (array) $new_value['product_types'] : array();
		$previous_product_types = isset( $old_value['product_types'] ) ? (array) $old_value['product_types'] : array();

		if ( empty( array_diff( $product_types, $previous_product_types ) ) ) {
			return;
		}

		$task = new Task( self::get_task() );
		$task->undo_dismiss();
	}

	/**
	 * Get the task arguments.
	 *
	 * @return array
	 */
	public static function get_task() {
		$products = self::get_products();

		return array(
			'id'             => 'purchase',
			'title'          => count( $products['remaining'] ) === 1
				? sprintf(
					/* translators: %1$s: list of product names comma separated, %2%s the last product name */
					__(
						'Add %s to my store',
						'woocommerce-admin'
					),
					$products['remaining'][0]
				)
				: __(
					'Add paid extensions to my store',
					'woocommerce-admin'
				),
			'content'        => count( $products['remaining'] ) === 1
				? $products['purchaseable'][0]['description']
				: sprintf(
					/* translators: %1$s: list of product names comma separated, %2%s the last product name */
					__(
						'Good choice! You chose to add %1$s and %2$s to your store.',
						'woocommerce-admin'
					),
					implode( ', ', array_slice( $products['remaining'], 0, -1 ) ) . ( count( $products['remaining'] ) > 2 ? ',' : '' ),
					end( $products['remaining'] )
				),
			'action_label'   => __( 'Purchase & install now', 'woocommerce-admin' ),
			'is_complete'    => count( $products['remaining'] ) === 0,
			'can_view'       => count( $products['purchaseable'] ) > 0,
			'time'           => __( '2 minutes', 'woocommerce-admin' ),
			'is_dismissable' => true,
		);
	}

	/**
	 * Get purchaseable and remaining products.
	 *
	 * @return array
	 */
	public static function get_products() {
		$profiler_data = get_option( Onboarding::PROFILE_DATA_OPTION, array() );
		$installed     = PluginsHelper::get_installed_plugin_slugs();
		$product_types = isset( $profiler_data['product_types'] ) ? $profiler_data['product_types'] : array();
		$allowed       = Onboarding::get_allowed_product_types();
		$purchaseable  = array();
		$remaining     = array();
		foreach ( $product_types as $type ) {
			if ( ! isset( $allowed[ $type ]['slug'] ) ) {
				continue;
			}

			$purchaseable[] = $allowed[ $type ];

			if ( ! in_array( $allowed[ $type ]['slug'], $installed, true ) ) {
				$remaining[] = $allowed[ $type ]['label'];
			}
		}

		return array(
			'purchaseable' => $purchaseable,
			'remaining'    => $remaining,
		);
	}
}
