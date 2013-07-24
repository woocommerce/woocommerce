<?php
/**
 * Admin profile functions
 *
 * Functions used for modifying the users panel in admin.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Users
 * @version     2.1.0
 */

/**
 * Get Address Fields for the edit user pages.
 *
 * @access public
 * @return array Fields to display which are filtered through woocommerce_customer_meta_fields before being returned
 */
function woocommerce_get_customer_meta_fields() {
	$show_fields = apply_filters('woocommerce_customer_meta_fields', array(
		'billing' => array(
			'title' => __( 'Customer Billing Address', 'woocommerce' ),
			'fields' => array(
				'billing_first_name' => array(
						'label' => __( 'First name', 'woocommerce' ),
						'description' => ''
					),
				'billing_last_name' => array(
						'label' => __( 'Last name', 'woocommerce' ),
						'description' => ''
					),
				'billing_company' => array(
						'label' => __( 'Company', 'woocommerce' ),
						'description' => ''
					),
				'billing_address_1' => array(
						'label' => __( 'Address 1', 'woocommerce' ),
						'description' => ''
					),
				'billing_address_2' => array(
						'label' => __( 'Address 2', 'woocommerce' ),
						'description' => ''
					),
				'billing_city' => array(
						'label' => __( 'City', 'woocommerce' ),
						'description' => ''
					),
				'billing_postcode' => array(
						'label' => __( 'Postcode', 'woocommerce' ),
						'description' => ''
					),
				'billing_state' => array(
						'label' => __( 'State/County', 'woocommerce' ),
						'description' => __( 'Country or state code', 'woocommerce' ),
					),
				'billing_country' => array(
						'label' => __( 'Country', 'woocommerce' ),
						'description' => __( '2 letter Country code', 'woocommerce' ),
					),
				'billing_phone' => array(
						'label' => __( 'Telephone', 'woocommerce' ),
						'description' => ''
					),
				'billing_email' => array(
						'label' => __( 'Email', 'woocommerce' ),
						'description' => ''
					)
			)
		),
		'shipping' => array(
			'title' => __( 'Customer Shipping Address', 'woocommerce' ),
			'fields' => array(
				'shipping_first_name' => array(
						'label' => __( 'First name', 'woocommerce' ),
						'description' => ''
					),
				'shipping_last_name' => array(
						'label' => __( 'Last name', 'woocommerce' ),
						'description' => ''
					),
				'shipping_company' => array(
						'label' => __( 'Company', 'woocommerce' ),
						'description' => ''
					),
				'shipping_address_1' => array(
						'label' => __( 'Address 1', 'woocommerce' ),
						'description' => ''
					),
				'shipping_address_2' => array(
						'label' => __( 'Address 2', 'woocommerce' ),
						'description' => ''
					),
				'shipping_city' => array(
						'label' => __( 'City', 'woocommerce' ),
						'description' => ''
					),
				'shipping_postcode' => array(
						'label' => __( 'Postcode', 'woocommerce' ),
						'description' => ''
					),
				'shipping_state' => array(
						'label' => __( 'State/County', 'woocommerce' ),
						'description' => __( 'State/County or state code', 'woocommerce' )
					),
				'shipping_country' => array(
						'label' => __( 'Country', 'woocommerce' ),
						'description' => __( '2 letter Country code', 'woocommerce' )
					)
			)
		)
	));
	return $show_fields;
}


/**
 * Show Address Fields on edit user pages.
 *
 * @access public
 * @param mixed $user User (object) being displayed
 * @return void
 */
function woocommerce_customer_meta_fields( $user ) {
	if ( ! current_user_can( 'manage_woocommerce' ) )
		return;

	$show_fields = woocommerce_get_customer_meta_fields();

	foreach( $show_fields as $fieldset ) :
		?>
		<h3><?php echo $fieldset['title']; ?></h3>
		<table class="form-table">
			<?php
			foreach( $fieldset['fields'] as $key => $field ) :
				?>
				<tr>
					<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
					<td>
						<input type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_user_meta( $user->ID, $key, true ) ); ?>" class="regular-text" /><br/>
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

add_action( 'show_user_profile', 'woocommerce_customer_meta_fields' );
add_action( 'edit_user_profile', 'woocommerce_customer_meta_fields' );


/**
 * Save Address Fields on edit user pages
 *
 * @access public
 * @param mixed $user_id User ID of the user being saved
 * @return void
 */
function woocommerce_save_customer_meta_fields( $user_id ) {
	if ( ! current_user_can( 'manage_woocommerce' ) )
		return $columns;

 	$save_fields = woocommerce_get_customer_meta_fields();

 	foreach( $save_fields as $fieldset )
 		foreach( $fieldset['fields'] as $key => $field )
 			if ( isset( $_POST[ $key ] ) )
 				update_user_meta( $user_id, $key, woocommerce_clean( $_POST[ $key ] ) );
}

add_action( 'personal_options_update', 'woocommerce_save_customer_meta_fields' );
add_action( 'edit_user_profile_update', 'woocommerce_save_customer_meta_fields' );