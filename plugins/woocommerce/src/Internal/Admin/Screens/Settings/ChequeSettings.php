<?php
/**
 * Form layout for the experimental cheque settings screen.
 *
 * @package WooCommerce\Internal\Admin\Screens\Settings
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Screens\Settings;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Internal\Admin\Screens\AdminScreens;

defined( 'ABSPATH' ) || exit;

/**
 * Supplies the cheque example's layout through WordPress View Config.
 *
 * @internal
 */
final class ChequeSettings {

	/**
	 * Register the fields and the layout.
	 *
	 * @internal
	 */
	public function __construct() {
		add_action( 'wp_entity_fields_api_init', array( $this, 'handle_wp_entity_fields_api_init' ) );
		add_filter( 'get_entity_view_config_woo_settings_payments_cheque', array( $this, 'handle_get_entity_view_config_woo_settings_payments_cheque' ) );
	}

	/**
	 * Register the cheque fields with the Entity fields API, from the collection wp-build copies
	 * out of `admin-screens/packages/entity-fields`.
	 *
	 * @internal
	 *
	 * @since 11.4.0
	 */
	public function handle_wp_entity_fields_api_init(): void {
		$directory = $this->get_fields_directory();
		if ( ! Features::is_enabled( AdminScreens::FEATURE_FLAG ) || ! function_exists( 'wp_register_entity_field_collection' ) || ! is_dir( $directory ) ) {
			return;
		}
		wp_register_entity_field_collection( $directory );
	}

	/**
	 * Path to the built cheque field collection.
	 *
	 * @return string
	 */
	private function get_fields_directory(): string {
		return WC_ABSPATH . 'assets/client/routes/scripts/entity-fields/cheque';
	}

	/**
	 * Set the fields shown by the cheque form.
	 *
	 * @internal
	 *
	 * @since 11.3.0
	 * @param mixed $config View Config object supplied by WordPress.
	 * @return mixed
	 */
	public function handle_get_entity_view_config_woo_settings_payments_cheque( $config ) {
		if ( ! Features::is_enabled( AdminScreens::FEATURE_FLAG ) || ! is_object( $config ) || ! is_callable( array( $config, 'merge' ) ) ) {
			return $config;
		}
		return $config->merge(
			array(
				'form' => array(
					'fields' => array(
						array(
							'id'       => 'general',
							'label'    => __( 'General', 'woocommerce' ),
							'layout'   => array(
								'type'          => 'card',
								'isCollapsible' => false,
							),
							'children' => array( 'enabled', 'title', 'description', 'instructions' ),
						),
					),
				),
			),
			1
		);
	}
}
