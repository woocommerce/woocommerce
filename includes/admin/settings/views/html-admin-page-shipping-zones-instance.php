<h2><?php echo esc_html( $shipping_method->get_method_title() ); ?> <small class="wc-admin-breadcrumb">&lt; <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&zone_id=' . absint( $zone->get_zone_id() ) ) ); ?>"><?php _e( 'Shipping Methods', 'woocommerce' ); ?> (<?php echo esc_html( $zone->get_zone_name() ); ?>)</a> &lt; <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>"><?php _e( 'Shipping Zones', 'woocommerce' ); ?></a></small></h2>
<?php $shipping_method->admin_options(); ?>
<p class="submit"><input type="submit" class="button button-primary" name="save_method" value="<?php _e( 'Save changes', 'woocommerce' ); ?>" /></p>
<?php wp_nonce_field( 'woocommerce_save_method', 'woocommerce_save_method_nonce' ); ?>
