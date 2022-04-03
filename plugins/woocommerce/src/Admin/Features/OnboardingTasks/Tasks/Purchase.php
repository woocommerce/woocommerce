<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProducts;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingThemes;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Purchase Task
 */
class Purchase extends Task {
	/**
	 * Constructor
	 *
	 * @param TaskList $task_list Parent task list.
	 */
	public function __construct( $task_list ) {
		parent::__construct( $task_list );
		add_action( 'update_option_woocommerce_onboarding_profile', array( $this, 'clear_dismissal' ), 10, 2 );
	}

	/**
	 * Clear dismissal on onboarding product type changes.
	 *
	 * @param array $old_value Old value.
	 * @param array $new_value New value.
	 */
	public function clear_dismissal( $old_value, $new_value ) {
		$product_types          = isset( $new_value['product_types'] ) ? (array) $new_value['product_types'] : array();
		$previous_product_types = isset( $old_value['product_types'] ) ? (array) $old_value['product_types'] : array();

		if ( empty( array_diff( $product_types, $previous_product_types ) ) ) {
			return;
		}

		$this->undo_dismiss();
	}

	/**
	 * Get the task arguments.
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'purchase';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		$products = $this->get_paid_products_and_themes();

		return count( $products['remaining'] ) === 1
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
			);
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		$products = $this->get_paid_products_and_themes();

		if ( count( $products['remaining'] ) === 1 ) {
			return isset( $products['purchaseable'][0]['description'] ) ? $products['purchaseable'][0]['description'] : $products['purchaseable'][0]['excerpt'];
		}
		return sprintf(
		/* translators: %1$s: list of product names comma separated, %2%s the last product name */
			__(
				'Good choice! You chose to add %1$s and %2$s to your store.',
				'woocommerce'
			),
			implode( ', ', array_slice( $products['remaining'], 0, -1 ) ) . ( count( $products['remaining'] ) > 2 ? ',' : '' ),
			end( $products['remaining'] )
		);
	}

	/**
	 * Action label.
	 *
	 * @return string
	 */
	public function get_action_label() {
		return __( 'Purchase & install now', 'woocommerce' );
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
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		$products = $this->get_paid_products_and_themes();
		return count( $products['remaining'] ) === 0;
	}

	/**
	 * Dismissable.
	 *
	 * @return bool
	 */
	public function is_dismissable() {
		return true;
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		$products = $this->get_paid_products_and_themes();
		return count( $products['purchaseable'] ) > 0;
	}

	/**
	 * Get purchaseable and remaining products.
	 *
	 * @return array purchaseable and remaining products and themes.
	 */
	public static function get_paid_products_and_themes() {
		$relevant_products = OnboardingProducts::get_relevant_products();

		$profiler_data = get_option( OnboardingProfile::DATA_OPTION, array() );
		$theme         = isset( $profiler_data['theme'] ) ? $profiler_data['theme'] : null;
		$paid_theme    = $theme ? OnboardingThemes::get_paid_theme_by_slug( $theme ) : null;
		if ( $paid_theme ) {

			$relevant_products['purchaseable'][] = $paid_theme;

			if ( isset( $paid_theme['is_installed'] ) && false === $paid_theme['is_installed'] ) {
				$relevant_products['remaining'][] = $paid_theme['title'];
			}
		}
		return $relevant_products;
	}
}
