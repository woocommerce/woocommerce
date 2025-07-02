<?php
/**
 * WooCommerce Point of Sale Settings
 *
 * @package WooCommerce\Admin
 */

declare(strict_types=1);

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Internal\Settings\PointOfSaleDefaultSettings;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_Point_Of_Sale', false ) ) {
	return new WC_Settings_Point_Of_Sale();
}

/**
 * WC_Settings_Point_Of_Sale.
 */
class WC_Settings_Point_Of_Sale extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'point-of-sale';
		$this->label = __( 'Point of Sale', 'woocommerce' );

		parent::__construct();

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
	}

	/**
	 * Setting page icon.
	 *
	 * @var string
	 */
	public $icon = 'store';

	/**
	 * Add Point of Sale page to settings if the feature is enabled.
	 *
	 * @param array $pages Existing pages.
	 * @return array|mixed
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function add_settings_page( $pages ) {
		if ( FeaturesUtil::feature_is_enabled( 'point_of_sale' ) ) {
			return parent::add_settings_page( $pages );
		} else {
			return $pages;
		}
	}

	/**
	 * Get settings for the default section.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {
		return array(
			array(
				'title' => __( 'Store details', 'woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Details about the store that are shown in email receipts.', 'woocommerce' ),
				'id'    => 'store_details',
			),

			array(
				'title'   => __( 'Store name', 'woocommerce' ),
				'desc'    => __( 'The name of your physical store.', 'woocommerce' ),
				'id'      => 'woocommerce_pos_store_name',
				'default' => PointOfSaleDefaultSettings::get_default_store_name(),
				'type'    => 'text',
				'css'     => 'min-width:300px;',
			),

			array(
				'title'    => __( 'Physical address', 'woocommerce' ),
				'id'       => 'woocommerce_pos_store_address',
				'default'  => PointOfSaleDefaultSettings::get_default_store_address(),
				'type'     => 'textarea',
				'css'      => 'min-width:300px; height: 100px;',
				'desc_tip' => true,
			),

			array(
				'title'   => __( 'Phone number', 'woocommerce' ),
				'id'      => 'woocommerce_pos_store_phone',
				'default' => '',
				'type'    => 'text',
				'css'     => 'min-width:300px;',
			),

			array(
				'title'   => __( 'Email', 'woocommerce' ),
				'desc'    => __( 'Your store contact email.', 'woocommerce' ),
				'id'      => 'woocommerce_pos_store_email',
				'default' => PointOfSaleDefaultSettings::get_default_store_email(),
				'type'    => 'email',
				'css'     => 'min-width:300px;',
			),

			array(
				'title'    => __( 'Refund & Returns Policy', 'woocommerce' ),
				'desc'     => __( 'Brief statement that will appear on the receipts.', 'woocommerce' ),
				'id'       => 'woocommerce_pos_refund_returns_policy',
				'default'  => '',
				'type'     => 'textarea',
				'css'      => 'min-width:300px; height: 100px;',
				'desc_tip' => true,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'store_details',
			),
		);
	}
}

return new WC_Settings_Point_Of_Sale();
