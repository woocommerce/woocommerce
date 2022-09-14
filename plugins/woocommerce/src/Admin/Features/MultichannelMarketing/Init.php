<?php
/**
 * Multichannel Marketing Experience
 *
 * @package Woocommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Features\MultichannelMarketing;

use Automattic\WooCommerce\Internal\Admin\Survey;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * Contains logic for Multichannel Marketing.
 */
class Init {
	/**
	 * Option name used to toggle this feature.
	 */
	const TOGGLE_OPTION_NAME = 'woocommerce_multichannel_marketing_enabled';

	/**
	 * Determines if the feature has been toggled on or off.
	 *
	 * @var boolean
	 */
	protected static $is_updated = false;

	/**
	 * Hook into WooCommerce.
	 */
	public function __construct() {
		add_filter( 'woocommerce_settings_features', array( $this, 'add_feature_toggle' ) );
		add_action( 'update_option_' . self::TOGGLE_OPTION_NAME, array( $this, 'reload_page_on_toggle' ), 10, 2 );
		add_action( 'woocommerce_settings_saved', array( $this, 'maybe_reload_page' ) );
	}

	/**
	 * Add the feature toggle to the features settings.
	 *
	 * @param array $features Feature sections.
	 * @return array
	 */
	public static function add_feature_toggle( $features ) {
		$description = __(
			'Enables the new WooCommerce Multichannel Marketing experience in the Marketing page',
			'woocommerce'
		);

		$features[] = array(
			'title' => __( 'Marketing', 'woocommerce' ),
			'desc'  => $description,
			'id'    => self::TOGGLE_OPTION_NAME,
			'type'  => 'checkbox',
			'class' => '',
		);

		return $features;
	}

	/**
	 * Reloads the page when the option is toggled to make sure all Multichannel Marketing features are loaded.
	 *
	 * @param string $old_value Old value.
	 * @param string $value     New value.
	 */
	public static function reload_page_on_toggle( $old_value, $value ) {
		if ( $old_value === $value ) {
			return;
		}

		self::$is_updated = true;
	}

	/**
	 * Reload the page if the setting has been updated.
	 */
	public static function maybe_reload_page() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) || ! self::$is_updated ) {
			return;
		}

		wp_safe_redirect( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		exit();
	}
}
