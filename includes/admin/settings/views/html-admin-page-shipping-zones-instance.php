<h2><?php echo esc_html( $shipping_method->get_method_title() ); ?> <small class="wc-admin-breadcrumb"><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&zone_id=' . absint( $zone->get_zone_id() ) ) ); ?>" title="<?php echo esc_attr( __( 'Return to Shipping Methods', 'woocommerce' ) . ' (' . $zone->get_zone_name() . ')' ); ?>">&#x21a9;</a></small></h2>
<?php $shipping_method->admin_options(); ?>
<p class="submit"><input type="submit" class="button button-primary" name="save_method" value="<?php _e( 'Save changes', 'woocommerce' ); ?>" /></p>
<?php wp_nonce_field( 'woocommerce_save_method', 'woocommerce_save_method_nonce' ); ?>
