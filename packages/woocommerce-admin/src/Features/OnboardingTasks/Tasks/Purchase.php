<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Onboarding;
use Automattic\WooCommerce\Admin\PluginsHelper;

/**
 * Purchase Task
 */
class Purchase {
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
						'woocommerce'
					),
					$products['remaining'][0]
				)
				: __(
					'Add paid extensions to my store',
					'woocommerce'
				),
			'content'        => count( $products['remaining'] ) === 1
				? $products['purchaseable'][0]['description']
				: sprintf(
					/* translators: %1$s: list of product names comma separated, %2%s the last product name */
					__(
						'Good choice! You chose to add %1$s and %2$s to your store.',
						'woocommerce'
					),
					implode( ', ', array_slice( $products['remaining'], 0, -1 ) ) . ( count( $products['remaining'] ) > 2 ? ',' : '' ),
					end( $products['remaining'] )
				),
			'action_label'   => __( 'Purchase & install now', 'woocommerce' ),
			'is_complete'    => count( $products['remaining'] ) === 0,
			'can_view'       => count( $products['purchaseable'] ) > 0,
			'time'           => __( '2 minutes', 'woocommerce' ),
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
