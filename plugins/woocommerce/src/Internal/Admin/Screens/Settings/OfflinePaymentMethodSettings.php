<?php
/**
 * Form layouts for the experimental offline payment method settings screens.
 *
 * @package WooCommerce\Internal\Admin\Screens\Settings
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Screens\Settings;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Internal\Admin\Screens\AdminScreens;

defined( 'ABSPATH' ) || exit;

/**
 * Supplies the bank transfer, cheque and cash on delivery layouts through WordPress View Config.
 *
 * @internal
 */
final class OfflinePaymentMethodSettings {

	/**
	 * The fields every offline payment method shows, in order.
	 */
	private const GENERAL_FIELDS = array( 'enabled', 'title', 'description', 'instructions', 'enable_for_methods', 'enable_for_virtual' );

	/**
	 * Register the layouts.
	 *
	 * @internal
	 */
	public function __construct() {
		add_filter( 'get_entity_view_config_woo_settings_payments_bacs', array( $this, 'handle_get_entity_view_config_woo_settings_payments_bacs' ) );
		add_filter( 'get_entity_view_config_woo_settings_payments_cheque', array( $this, 'handle_get_entity_view_config_woo_settings_payments_cheque' ) );
		add_filter( 'get_entity_view_config_woo_settings_payments_cod', array( $this, 'handle_get_entity_view_config_woo_settings_payments_cod' ) );
	}

	/**
	 * Set the fields shown by the bank transfer form, with the bank accounts in their own card.
	 *
	 * @internal
	 *
	 * @since 11.4.0
	 * @param mixed $config View Config object supplied by WordPress.
	 * @return mixed
	 */
	public function handle_get_entity_view_config_woo_settings_payments_bacs( $config ) {
		return $this->merge_cards(
			$config,
			array(
				$this->get_general_card(),
				array(
					'id'       => 'account_details',
					'label'    => __( 'Account details', 'woocommerce' ),
					'layout'   => array(
						'type'          => 'card',
						'isCollapsible' => false,
					),
					'children' => array( 'accounts' ),
				),
			)
		);
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
		return $this->merge_cards( $config, array( $this->get_general_card() ) );
	}

	/**
	 * Set the fields shown by the cash on delivery form.
	 *
	 * @internal
	 *
	 * @since 11.4.0
	 * @param mixed $config View Config object supplied by WordPress.
	 * @return mixed
	 */
	public function handle_get_entity_view_config_woo_settings_payments_cod( $config ) {
		return $this->merge_cards( $config, array( $this->get_general_card() ) );
	}

	/**
	 * Get the card with the fields every offline payment method shows.
	 *
	 * @return array
	 */
	private function get_general_card(): array {
		return array(
			'id'       => 'general',
			'label'    => __( 'General', 'woocommerce' ),
			'layout'   => array(
				'type'          => 'card',
				'isCollapsible' => false,
			),
			'children' => self::GENERAL_FIELDS,
		);
	}

	/**
	 * Merge the cards into the form layout while the screen is enabled.
	 *
	 * @param mixed $config View Config object supplied by WordPress.
	 * @param array $cards  The form's cards, in order.
	 * @return mixed
	 */
	private function merge_cards( $config, array $cards ) {
		if ( ! Features::is_enabled( AdminScreens::FEATURE_FLAG ) || ! is_object( $config ) || ! is_callable( array( $config, 'merge' ) ) ) {
			return $config;
		}
		return $config->merge( array( 'form' => array( 'fields' => $cards ) ), 1 );
	}
}
