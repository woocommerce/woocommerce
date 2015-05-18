<?php
/**
 * Add extra profile fields for users in admin.
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Admin_Profile' ) ) :

/**
 * WC_Admin_Profile Class
 */
class WC_Admin_Profile {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'show_user_profile', array( $this, 'add_customer_meta_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'add_customer_meta_fields' ) );

		add_action( 'personal_options_update', array( $this, 'save_customer_meta_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_customer_meta_fields' ) );
	}

	/**
	 * Get Address Fields for the edit user pages.
	 *
	 * @return array Fields to display which are filtered through woocommerce_customer_meta_fields before being returned
	 */
	public function get_customer_meta_fields() {
		$show_fields = apply_filters('woocommerce_customer_meta_fields', array(
			'billing' => array(
				'title' => __( 'Customer Billing Address', 'woocommerce' ),
				'fields' => array(
					'billing_first_name' => array(
						'label'       => __( 'First name', 'woocommerce' ),
						'description' => ''
					),
					'billing_last_name' => array(
						'label'       => __( 'Last name', 'woocommerce' ),
						'description' => ''
					),
					'billing_company' => array(
						'label'       => __( 'Company', 'woocommerce' ),
						'description' => ''
					),
					'billing_address_1' => array(
						'label'       => __( 'Address 1', 'woocommerce' ),
						'description' => ''
					),
					'billing_address_2' => array(
						'label'       => __( 'Address 2', 'woocommerce' ),
						'description' => ''
					),
					'billing_city' => array(
						'label'       => __( 'City', 'woocommerce' ),
						'description' => ''
					),
					'billing_postcode' => array(
						'label'       => __( 'Postcode', 'woocommerce' ),
						'description' => ''
					),
					'billing_country' => array(
						'label'       => __( 'Country', 'woocommerce' ),
						'description' => '',
						'class'       => 'js_field-country',
						'type'        => 'select',
						'options'     => array( '' => __( 'Select a country&hellip;', 'woocommerce' ) ) + WC()->countries->get_allowed_countries()
					),
					'billing_state' => array(
						'label'       => __( 'State/County', 'woocommerce' ),
						'description' => __( 'State/County or state code', 'woocommerce' ),
						'class'       => 'js_field-state'
					),
					'billing_phone' => array(
						'label'       => __( 'Telephone', 'woocommerce' ),
						'description' => ''
					),
					'billing_email' => array(
						'label'       => __( 'Email', 'woocommerce' ),
						'description' => ''
					)
				)
			),
			'shipping' => array(
				'title' => __( 'Customer Shipping Address', 'woocommerce' ),
				'fields' => array(
					'shipping_first_name' => array(
						'label'       => __( 'First name', 'woocommerce' ),
						'description' => ''
					),
					'shipping_last_name' => array(
						'label'       => __( 'Last name', 'woocommerce' ),
						'description' => ''
					),
					'shipping_company' => array(
						'label'       => __( 'Company', 'woocommerce' ),
						'description' => ''
					),
					'shipping_address_1' => array(
						'label'       => __( 'Address 1', 'woocommerce' ),
						'description' => ''
					),
					'shipping_address_2' => array(
						'label'       => __( 'Address 2', 'woocommerce' ),
						'description' => ''
					),
					'shipping_city' => array(
						'label'       => __( 'City', 'woocommerce' ),
						'description' => ''
					),
					'shipping_postcode' => array(
						'label'       => __( 'Postcode', 'woocommerce' ),
						'description' => ''
					),
					'shipping_country' => array(
						'label'       => __( 'Country', 'woocommerce' ),
						'description' => '',
						'class'       => 'js_field-country',
						'type'        => 'select',
						'options'     => array( '' => __( 'Select a country&hellip;', 'woocommerce' ) ) + WC()->countries->get_allowed_countries()
					),
					'shipping_state' => array(
						'label'       => __( 'State/County', 'woocommerce' ),
						'description' => __( 'State/County or state code', 'woocommerce' ),
						'class'       => 'js_field-state'
					)
				)
			)
		) );
		return $show_fields;
	}

	/**
	 * Show Address Fields on edit user pages.
	 *
	 * @param WP_User $user
	 */
	public function add_customer_meta_fields( $user ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$show_fields = $this->get_customer_meta_fields();

		foreach ( $show_fields as $fieldset ) :
			?>
			<h3><?php echo $fieldset['title']; ?></h3>
			<table class="form-table">
				<?php
				foreach ( $fieldset['fields'] as $key => $field ) :
					?>
					<tr>
						<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
						<td>
							<?php if ( ! empty( $field['type'] ) && 'select' == $field['type'] ) : ?>
								<select name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" class="<?php echo ( ! empty( $field['class'] ) ? $field['class'] : '' ); ?>" style="width: 25em;">
									<?php
										$selected = esc_attr( get_user_meta( $user->ID, $key, true ) );
										foreach ( $field['options'] as $option_key => $option_value ) : ?>
										<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $selected, $option_key, true ); ?>><?php echo esc_attr( $option_value ); ?></option>
									<?php endforeach; ?>
								</select>
							<?php else : ?>
							<input type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_user_meta( $user->ID, $key, true ) ); ?>" class="<?php echo ( ! empty( $field['class'] ) ? $field['class'] : 'regular-text' ); ?>" />
							<?php endif; ?>
							<br/>
							<span class="description"><?php echo wp_kses_post( $field['description'] ); ?></span>
						</td>
					</tr>
					<?php
				endforeach;
				?>
			</table>
			<?php
		endforeach;
	}

	/**
	 * Save Address Fields on edit user pages
	 *
	 * @param int $user_id User ID of the user being saved
	 */
	public function save_customer_meta_fields( $user_id ) {
		$save_fields = $this->get_customer_meta_fields();

		foreach ( $save_fields as $fieldset ) {

			foreach ( $fieldset['fields'] as $key => $field ) {

				if ( isset( $_POST[ $key ] ) ) {
					update_user_meta( $user_id, $key, wc_clean( $_POST[ $key ] ) );
				}
			}
		}
	}
}

endif;

return new WC_Admin_Profile();
