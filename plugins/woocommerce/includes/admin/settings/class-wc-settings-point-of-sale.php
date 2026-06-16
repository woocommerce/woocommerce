<?php
/**
 * WooCommerce Point of Sale Settings
 *
 * @package WooCommerce\Admin
 */

declare(strict_types=1);

use Automattic\WooCommerce\Internal\Inventory\InventoryController;
use Automattic\WooCommerce\Internal\Inventory\LocationStockService;
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
		add_action( 'woocommerce_admin_field_pos_location_stock_locations', array( $this, 'output_pos_location_stock_locations_field' ) );
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . LocationStockService::LOCATIONS_OPTION, array( $this, 'sanitize_pos_location_stock_locations' ), 10, 3 );
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
		$settings = array(
			array(
				'title' => __( 'Store details', 'woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Details about the store that are shown in email receipts.', 'woocommerce' ),
				'id'    => 'store_details',
			),

			array(
				'title'             => __( 'Store name', 'woocommerce' ),
				'desc'              => __( 'The name of your physical store.', 'woocommerce' ),
				'id'                => 'woocommerce_pos_store_name',
				'default'           => PointOfSaleDefaultSettings::get_default_store_name(),
				'type'              => 'text',
				'css'               => 'min-width:300px;',
				'skip_initial_save' => true,
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

		if ( ! FeaturesUtil::feature_is_enabled( InventoryController::FEATURE_ID ) ) {
			return $settings;
		}

		return array_merge(
			$settings,
			array(
				array(
					'title' => __( 'POS locations', 'woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'Configure up to 5 POS locations. Each configured location has separate POS stock quantities on stock-managed products.', 'woocommerce' ),
					'id'    => 'pos_location_stock_locations',
				),
				array(
					'title'    => __( 'Locations', 'woocommerce' ),
					'id'       => LocationStockService::LOCATIONS_OPTION,
					'type'     => 'pos_location_stock_locations',
					'autoload' => false,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'pos_location_stock_locations',
				),
			)
		);
	}

	/**
	 * Output POS location rows.
	 *
	 * @param array $value Field settings.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function output_pos_location_stock_locations_field( $value ): void {
		$field_name = $value['field_name'] ?? $value['id'];
		$locations  = array_values( $this->get_location_stock_service()->get_locations() );

		$location_count = count( $locations );
		while ( $location_count < LocationStockService::MAX_POS_LOCATIONS ) {
			$locations[] = array(
				'slug'      => '',
				'name'      => '',
				'address_1' => '',
				'address_2' => '',
				'city'      => '',
				'state'     => '',
				'postcode'  => '',
				'country'   => '',
			);
			++$location_count;
		}
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp forminp-pos-location-stock-locations">
				<?php foreach ( array_slice( $locations, 0, LocationStockService::MAX_POS_LOCATIONS ) as $index => $location ) : ?>
					<fieldset class="wc-pos-location-stock-location" style="margin: 0 0 1.5em;">
						<legend><strong>
							<?php
							printf(
								/* translators: %d: POS location number. */
								esc_html__( 'Location %d', 'woocommerce' ),
								(int) $index + 1
							);
							?>
						</strong></legend>
						<p>
							<label for="<?php echo esc_attr( $value['id'] . '_' . $index . '_name' ); ?>"><?php esc_html_e( 'Name', 'woocommerce' ); ?></label><br />
							<input
								type="text"
								class="regular-text"
								id="<?php echo esc_attr( $value['id'] . '_' . $index . '_name' ); ?>"
								name="<?php echo esc_attr( $field_name . '[' . $index . '][name]' ); ?>"
								value="<?php echo esc_attr( $location['name'] ); ?>"
								placeholder="<?php echo esc_attr( 0 === $index ? __( 'POS', 'woocommerce' ) : '' ); ?>"
							/>
						</p>
						<p>
							<label for="<?php echo esc_attr( $value['id'] . '_' . $index . '_slug' ); ?>"><?php esc_html_e( 'Slug', 'woocommerce' ); ?></label><br />
							<input
								type="text"
								class="regular-text"
								id="<?php echo esc_attr( $value['id'] . '_' . $index . '_slug' ); ?>"
								name="<?php echo esc_attr( $field_name . '[' . $index . '][slug]' ); ?>"
								value="<?php echo esc_attr( $location['slug'] ); ?>"
								placeholder="<?php echo esc_attr( 0 === $index ? LocationStockService::LOCATION_POS : '' ); ?>"
							/>
							<span class="description"><?php esc_html_e( 'Used by POS API requests.', 'woocommerce' ); ?></span>
						</p>
						<p>
							<label for="<?php echo esc_attr( $value['id'] . '_' . $index . '_address_1' ); ?>"><?php esc_html_e( 'Address line 1', 'woocommerce' ); ?></label><br />
							<input
								type="text"
								class="regular-text"
								id="<?php echo esc_attr( $value['id'] . '_' . $index . '_address_1' ); ?>"
								name="<?php echo esc_attr( $field_name . '[' . $index . '][address_1]' ); ?>"
								value="<?php echo esc_attr( $location['address_1'] ); ?>"
							/>
						</p>
						<p>
							<label for="<?php echo esc_attr( $value['id'] . '_' . $index . '_address_2' ); ?>"><?php esc_html_e( 'Address line 2', 'woocommerce' ); ?></label><br />
							<input
								type="text"
								class="regular-text"
								id="<?php echo esc_attr( $value['id'] . '_' . $index . '_address_2' ); ?>"
								name="<?php echo esc_attr( $field_name . '[' . $index . '][address_2]' ); ?>"
								value="<?php echo esc_attr( $location['address_2'] ); ?>"
							/>
						</p>
						<p>
							<label for="<?php echo esc_attr( $value['id'] . '_' . $index . '_city' ); ?>"><?php esc_html_e( 'City', 'woocommerce' ); ?></label><br />
							<input
								type="text"
								class="regular-text"
								id="<?php echo esc_attr( $value['id'] . '_' . $index . '_city' ); ?>"
								name="<?php echo esc_attr( $field_name . '[' . $index . '][city]' ); ?>"
								value="<?php echo esc_attr( $location['city'] ); ?>"
							/>
						</p>
						<p>
							<label for="<?php echo esc_attr( $value['id'] . '_' . $index . '_state' ); ?>"><?php esc_html_e( 'State/county', 'woocommerce' ); ?></label><br />
							<input
								type="text"
								class="regular-text"
								id="<?php echo esc_attr( $value['id'] . '_' . $index . '_state' ); ?>"
								name="<?php echo esc_attr( $field_name . '[' . $index . '][state]' ); ?>"
								value="<?php echo esc_attr( $location['state'] ); ?>"
							/>
						</p>
						<p>
							<label for="<?php echo esc_attr( $value['id'] . '_' . $index . '_postcode' ); ?>"><?php esc_html_e( 'Postcode/ZIP', 'woocommerce' ); ?></label><br />
							<input
								type="text"
								class="regular-text"
								id="<?php echo esc_attr( $value['id'] . '_' . $index . '_postcode' ); ?>"
								name="<?php echo esc_attr( $field_name . '[' . $index . '][postcode]' ); ?>"
								value="<?php echo esc_attr( $location['postcode'] ); ?>"
							/>
						</p>
						<p>
							<label for="<?php echo esc_attr( $value['id'] . '_' . $index . '_country' ); ?>"><?php esc_html_e( 'Country/region', 'woocommerce' ); ?></label><br />
							<select
								id="<?php echo esc_attr( $value['id'] . '_' . $index . '_country' ); ?>"
								name="<?php echo esc_attr( $field_name . '[' . $index . '][country]' ); ?>"
								class="wc-enhanced-select"
								style="min-width: 300px;"
							>
								<option value=""></option>
								<?php WC()->countries->country_dropdown_options( $location['country'] ); ?>
							</select>
						</p>
					</fieldset>
				<?php endforeach; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Sanitize POS location settings.
	 *
	 * @param mixed $value     Cleaned option value.
	 * @param array $option    Option settings.
	 * @param mixed $raw_value Raw option value.
	 * @return array
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function sanitize_pos_location_stock_locations( $value, $option, $raw_value ): array {
		$locations = is_array( $raw_value ) ? $raw_value : array();

		return $this->get_location_stock_service()->normalize_locations( $locations );
	}

	/**
	 * Get the location stock service.
	 */
	private function get_location_stock_service(): LocationStockService {
		return wc_get_container()->get( LocationStockService::class );
	}
}

return new WC_Settings_Point_Of_Sale();
