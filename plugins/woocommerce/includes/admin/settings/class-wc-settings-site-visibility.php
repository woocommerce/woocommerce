<?php
/**
 * WooCommerce site visibility settings
 *
 * @package WooCommerce\Admin
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_Site_Visibility', false ) ) {
	return new WC_Settings_Site_Visibility();
}

/**
 * WC_Settings_Site_Visibility.
 */
class WC_Settings_Site_Visibility extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'site-visibility';
		$this->label = __( 'Site visibility', 'woocommerce' );

		parent::__construct();
	}

	/**
	 * Get settings for the default section.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {
		return array(
			array(
				'title' => __( 'Site visibility', 'woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Manage how your site appears to visitors.', 'woocommerce' ),
				'id'    => 'site_visibility_options',
			),
			array(
				'title'   => __( 'Visibility', 'woocommerce' ),
				'id'      => 'woocommerce_coming_soon',
				'default' => 'no',
				'type'    => 'radio',
				'options' => array(
					'yes' => __( 'Coming soon', 'woocommerce' ),
					'no'  => __( 'Live', 'woocommerce' ),
				),
			),
			array(
				'title'    => __( 'Store pages only', 'woocommerce' ),
				'desc'     => __( 'Display a coming soon message on your store pages while the rest of your site remains visible.', 'woocommerce' ),
				'id'       => 'woocommerce_store_pages_only',
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Private link', 'woocommerce' ),
				'desc'     => __( 'Allow visitors with a private link to view your site while it is in coming soon mode.', 'woocommerce' ),
				'id'       => 'woocommerce_private_link',
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc_tip' => true,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'site_visibility_options',
			),
		);
	}
}

return new WC_Settings_Site_Visibility();
