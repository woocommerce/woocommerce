<?php
/**
 * WooCommerce Shipping Settings
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Settings_Shipping' ) ) :

/**
 * WC_Settings_Shipping.
 */
class WC_Settings_Shipping extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'shipping';
		$this->label = __( 'Shipping', 'woocommerce' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_admin_field_shipping_methods', array( $this, 'shipping_methods_setting' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'' => __( 'Shipping Options', 'woocommerce' )
		);

		if ( ! defined( 'WC_INSTALLING' ) ) {
			// Load shipping methods so we can show any global options they may have
			$shipping_methods = WC()->shipping->load_shipping_methods();

			foreach ( $shipping_methods as $method ) {
				if ( ! $method->has_settings() ) {
					continue;
				}
				$title = empty( $method->method_title ) ? ucfirst( $method->id ) : $method->method_title;
				$sections[ strtolower( get_class( $method ) ) ] = esc_html( $title );
			}
		}

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {

		$settings = apply_filters('woocommerce_shipping_settings', array(

			array( 'title' => __( 'Shipping Options', 'woocommerce' ), 'type' => 'title', 'id' => 'shipping_options' ),

			array(
				'title'         => __( 'Shipping Calculations', 'woocommerce' ),
				'desc'          => __( 'Enable shipping', 'woocommerce' ),
				'id'            => 'woocommerce_calc_shipping',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start'
			),

			array(
				'desc'          => __( 'Enable the shipping calculator on the cart page', 'woocommerce' ),
				'id'            => 'woocommerce_enable_shipping_calc',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'autoload'      => false
			),

			array(
				'desc'          => __( 'Hide shipping costs until an address is entered', 'woocommerce' ),
				'id'            => 'woocommerce_shipping_cost_requires_address',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'autoload'      => false
			),

			array(
				'title'   => __( 'Shipping Destination', 'woocommerce' ),
				'desc'    => __( 'This controls which shipping address is used by default.', 'woocommerce' ),
				'id'      => 'woocommerce_ship_to_destination',
				'default' => 'billing',
				'type'    => 'radio',
				'options' => array(
					'shipping'     => __( 'Default to shipping address', 'woocommerce' ),
					'billing'      => __( 'Default to billing address', 'woocommerce' ),
					'billing_only' => __( 'Only ship to the customer\'s billing address', 'woocommerce' ),
				),
				'autoload'        => false,
				'desc_tip'        =>  true,
				'show_if_checked' => 'option',
			),

			array(
				'title'    => __( 'Restrict shipping to Location(s)', 'woocommerce' ),
				'desc'     => sprintf( __( 'Choose which countries you want to ship to, or choose to ship to all <a href="%s">locations you sell to</a>.', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=general' ) ),
				'id'       => 'woocommerce_ship_to_countries',
				'default'  => '',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'desc_tip' => false,
				'options'  => array(
					''         => __( 'Ship to all countries you sell to', 'woocommerce' ),
					'all'      => __( 'Ship to all countries', 'woocommerce' ),
					'specific' => __( 'Ship to specific countries only', 'woocommerce' )
				)
			),

			array(
				'title'   => __( 'Specific Countries', 'woocommerce' ),
				'desc'    => '',
				'id'      => 'woocommerce_specific_ship_to_countries',
				'css'     => '',
				'default' => '',
				'type'    => 'multi_select_countries'
			),

			array(
				'type' => 'shipping_methods',
			),

			array( 'type' => 'sectionend', 'id' => 'shipping_options' ),

		) );

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		// Load shipping methods so we can show any global options they may have
		$shipping_methods = WC()->shipping->load_shipping_methods();

		if ( $current_section ) {

 			foreach ( $shipping_methods as $method ) {

				if ( strtolower( get_class( $method ) ) == strtolower( $current_section ) && $method->has_settings() ) {
					$method->admin_options();
					break;
				}
			}
 		} else {
			$settings = $this->get_settings();

			WC_Admin_Settings::output_fields( $settings );
		}
	}

	/**
	 * Output shipping method settings.
	 */
	public function shipping_methods_setting() {
		$selection_priority = get_option( 'woocommerce_shipping_method_selection_priority', array() );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php _e( 'Shipping Methods', 'woocommerce' ) ?></th>
			<td class="forminp">
				<table class="wc_shipping widefat wp-list-table" cellspacing="0">
					<thead>
						<tr>
							<th class="sort">&nbsp;</th>
							<th class="name"><?php _e( 'Name', 'woocommerce' ); ?></th>
							<th class="id"><?php _e( 'ID', 'woocommerce' ); ?></th>
							<th class="status"><?php _e( 'Enabled', 'woocommerce' ); ?></th>
							<th class="priority"><?php _e( 'Selection Priority', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'Available methods will be chosen by default in this order. If multiple methods have the same priority, they will be sorted by cost.', 'woocommerce' ) ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( WC()->shipping->load_shipping_methods() as $key => $method ) : ?>
							<tr>
								<td width="1%" class="sort">
									<input type="hidden" name="method_order[<?php echo esc_attr( $method->id ); ?>]" value="<?php echo esc_attr( $method->id ); ?>" />
								</td>
								<td class="name">
									<?php if ( $method->has_settings ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=' . strtolower( get_class( $method ) ) ) ); ?>"><?php endif; ?>
									<?php echo esc_html( $method->get_title() ); ?>
									<?php if ( $method->has_settings ) : ?></a><?php endif; ?>
								</td>
								<td class="id">
									<?php echo esc_attr( $method->id ); ?>
								</td>
								<td class="status">
									<?php if ( 'yes' === $method->enabled ) : ?>
										<span class="status-enabled tips" data-tip="<?php esc_attr_e( 'Yes', 'woocommerce' ); ?>"><?php _e( 'Yes', 'woocommerce' ); ?></span>
									<?php else : ?>
										<span class="na">-</span>
									<?php endif; ?>
								</td>
								<td width="1%" class="priority">
									<input type="number" step="1" min="0" name="method_priority[<?php echo esc_attr( $method->id ); ?>]" value="<?php echo isset( $selection_priority[ $method->id ] ) ? absint( $selection_priority[ $method->id ] ) : 1; ?>" />
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
					<tfoot>
						<tr>
							<th>&nbsp;</th>
							<th colspan="4"><span class="description"><?php _e( 'Drag and drop the above shipping methods to control their display order.', 'woocommerce' ); ?></span></th>
						</tr>
					</tfoot>
				</table>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$wc_shipping = WC_Shipping::instance();

		if ( ! $current_section ) {
			WC_Admin_Settings::save_fields( $this->get_settings() );
			$wc_shipping->process_admin_options();

		} else {
			foreach ( $wc_shipping->load_shipping_methods() as $method_id => $method ) {
				if ( $current_section === sanitize_title( get_class( $method ) ) ) {
					do_action( 'woocommerce_update_options_' . $this->id . '_' . $method->id );
				}
			}
		}

		// Increments the transient version to invalidate cache
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}
}

endif;

return new WC_Settings_Shipping();
