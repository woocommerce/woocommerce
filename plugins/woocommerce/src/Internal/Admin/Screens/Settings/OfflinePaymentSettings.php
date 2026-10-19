<?php
/**
 * Form layouts for the experimental offline payment settings screens.
 *
 * @package WooCommerce\Internal\Admin\Screens\Settings
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Screens\Settings;

use Automattic\WooCommerce\Admin\Features\Features;

defined( 'ABSPATH' ) || exit;

/**
 * Supplies Core offline payment layouts through WordPress View Config.
 *
 * @internal
 */
final class OfflinePaymentSettings {

	/**
	 * Register the three Core layouts.
	 *
	 * @internal
	 */
	public function __construct() {
		foreach ( array( 'bacs', 'cheque', 'cod' ) as $gateway_id ) {
			add_filter(
				'get_entity_view_config_woo_settings_payments_' . $gateway_id,
				function ( $config ) use ( $gateway_id ) {
					return $this->get_view_config( $config, $gateway_id );
				}
			);
		}
	}

	/**
	 * Set the fields shown by the Core offline payment forms.
	 *
	 * @since 11.3.0
	 * @param mixed  $config     View Config object supplied by WordPress.
	 * @param string $gateway_id Offline gateway ID.
	 * @return mixed
	 */
	public function get_view_config( $config, string $gateway_id ) {
		if ( ! Features::is_enabled( 'payment-settings-screen' ) || ! is_object( $config ) || ! is_callable( array( $config, 'merge' ) ) ) {
			return $config;
		}
		$fields = array( 'enabled', 'title', 'description', 'instructions', 'enable_for_methods', 'enable_for_virtual' );
		if ( 'bacs' === $gateway_id ) {
			$fields[] = 'account_details';
		}
		return $config->merge(
			array(
				'form' => array(
					'layout' => array( 'type' => 'regular' ),
					'fields' => $fields,
				),
			),
			1
		);
	}
}
