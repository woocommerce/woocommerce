<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Onboarding;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\PluginsHelper;

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
		$products = $this->get_products();

		return count( $products['remaining'] ) === 1
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
			);
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		$products = $this->get_products();

		return count( $products['remaining'] ) === 1
			? $products['purchaseable'][0]['description']
			: sprintf(
				/* translators: %1$s: list of product names comma separated, %2%s the last product name */
				__(
					'Good choice! You chose to add %1$s and %2$s to your store.',
					'woocommerce-admin'
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
		return __( 'Purchase & install now', 'woocommerce-admin' );
	}


	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '2 minutes', 'woocommerce-admin' );
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		$products = $this->get_products();
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
		$products = $this->get_products();
		return count( $products['purchaseable'] ) > 0;
	}

	/**
	 * Get puchasable theme by slug.
	 *
	 * @param string $price_string string of price.
	 * @return float|null
	 */
	private function get_price_from_string( $price_string ) {
		$price_match = null;
		// Parse price from string as it includes the currency symbol.
		preg_match( '/\\d+\.\d{2}\s*/', $price_string, $price_match );
		if ( count( $price_match ) > 0 ) {
			return (float) $price_match[0];
		}
		return null;
	}

	/**
	 * Get puchasable theme by slug.
	 *
	 * @param string $slug from theme.
	 * @return array|null
	 */
	private function get_paid_theme_by_slug( $slug ) {
		$themes    = Onboarding::get_themes();
		$theme_key = array_search( $slug, array_column( $themes, 'slug' ), true );
		$theme     = false !== $theme_key ? $themes[ $theme_key ] : null;
		if ( $theme && isset( $theme['id'] ) && isset( $theme['price'] ) && ( ! isset( $theme['is_installed'] ) || ! $theme['is_installed'] ) ) {
			$price = $this->get_price_from_string( $theme['price'] );
			if ( $price && $price > 0 ) {
				return $themes[ $theme_key ];
			}
		}
		return null;
	}

	/**
	 * Get purchaseable and remaining products.
	 *
	 * @return array
	 */
	private function get_products() {
		$profiler_data = get_option( Onboarding::PROFILE_DATA_OPTION, array() );
		$installed     = PluginsHelper::get_installed_plugin_slugs();
		$product_types = isset( $profiler_data['product_types'] ) ? $profiler_data['product_types'] : array();
		$product_data  = Onboarding::get_product_data( Onboarding::get_allowed_product_types() );

		$theme      = isset( $profiler_data['theme'] ) ? $profiler_data['theme'] : null;
		$paid_theme = $theme ? $this->get_paid_theme_by_slug( $theme ) : null;
		if ( $paid_theme ) {
			$product_types[]        = 'themes';
			$product_data['themes'] = $paid_theme;
		}

		$purchaseable = array();
		$remaining    = array();
		foreach ( $product_types as $type ) {
			if ( ! isset( $product_data[ $type ]['slug'] ) ) {
				continue;
			}

			$purchaseable[] = $product_data[ $type ];

			if ( ! in_array( $product_data[ $type ]['slug'], $installed, true ) ) {
				if ( isset( $product_data[ $type ]['label'] ) ) {
					$remaining[] = $product_data[ $type ]['label'];
				} else {
					$remaining[] = $product_data[ $type ]['title'];
				}
			}
		}

		return array(
			'purchaseable' => $purchaseable,
			'remaining'    => $remaining,
		);
	}
}
