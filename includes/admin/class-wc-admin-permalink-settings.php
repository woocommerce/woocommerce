<?php
/**
 * Adds settings to the permalinks admin settings page
 *
 * @class       WC_Admin_Permalink_Settings
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Admin_Permalink_Settings' ) ) :

/**
 * WC_Admin_Permalink_Settings Class.
 */
class WC_Admin_Permalink_Settings {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		$this->settings_init();
		$this->settings_save();
	}

	/**
	 * Init our settings.
	 */
	public function settings_init() {
		// Add a section to the permalinks page
		add_settings_section( 'woocommerce-permalink', __( 'Product Permalinks', 'woocommerce' ), array( $this, 'settings' ), 'permalink' );

		// Add our settings
		add_settings_field(
			'woocommerce_product_category_slug',            // id
			__( 'Product category base', 'woocommerce' ),   // setting title
			array( $this, 'product_category_slug_input' ),  // display callback
			'permalink',                                    // settings page
			'optional'                                      // settings section
		);
		add_settings_field(
			'woocommerce_product_tag_slug',                 // id
			__( 'Product tag base', 'woocommerce' ),        // setting title
			array( $this, 'product_tag_slug_input' ),       // display callback
			'permalink',                                    // settings page
			'optional'                                      // settings section
		);
		add_settings_field(
			'woocommerce_product_attribute_slug',           // id
			__( 'Product attribute base', 'woocommerce' ),  // setting title
			array( $this, 'product_attribute_slug_input' ), // display callback
			'permalink',                                    // settings page
			'optional'                                      // settings section
		);
	}

	/**
	 * Show a slug input box.
	 */
	public function product_category_slug_input() {
		$permalinks = get_option( 'woocommerce_permalinks' );
		?>
		<input name="woocommerce_product_category_slug" type="text" class="regular-text code" value="<?php if ( isset( $permalinks['category_base'] ) ) echo esc_attr( $permalinks['category_base'] ); ?>" placeholder="<?php echo esc_attr_x('product-category', 'slug', 'woocommerce') ?>" />
		<?php
	}

	/**
	 * Show a slug input box.
	 */
	public function product_tag_slug_input() {
		$permalinks = get_option( 'woocommerce_permalinks' );
		?>
		<input name="woocommerce_product_tag_slug" type="text" class="regular-text code" value="<?php if ( isset( $permalinks['tag_base'] ) ) echo esc_attr( $permalinks['tag_base'] ); ?>" placeholder="<?php echo esc_attr_x('product-tag', 'slug', 'woocommerce') ?>" />
		<?php
	}

	/**
	 * Show a slug input box.
	 */
	public function product_attribute_slug_input() {
		$permalinks = get_option( 'woocommerce_permalinks' );
		?>
		<input name="woocommerce_product_attribute_slug" type="text" class="regular-text code" value="<?php if ( isset( $permalinks['attribute_base'] ) ) echo esc_attr( $permalinks['attribute_base'] ); ?>" /><code>/attribute-name/attribute/</code>
		<?php
	}

	/**
	 * Show the settings.
	 */
	public function settings() {
		echo wpautop( __( 'These settings control the permalinks used specifically for products.', 'woocommerce' ) );

		$permalinks        = get_option( 'woocommerce_permalinks' );
		$product_permalink = isset( $permalinks['product_base'] ) ? $permalinks['product_base'] : '';

		// Get shop page
		$shop_page_id   = wc_get_page_id( 'shop' );
		$base_slug      = urldecode( ( $shop_page_id > 0 && get_post( $shop_page_id ) ) ? get_page_uri( $shop_page_id ) : _x( 'shop', 'default-slug', 'woocommerce' ) );
		$product_base   = _x( 'product', 'default-slug', 'woocommerce' );

		$structures = array(
			0 => '',
			1 => '/' . trailingslashit( $base_slug ),
			2 => '/' . trailingslashit( $base_slug ) . trailingslashit( '%product_cat%' )
		);
		?>
		<table class="form-table wc-permalink-structure">
			<tbody>
				<tr>
					<th><label><input name="product_permalink" type="radio" value="<?php echo esc_attr( $structures[0] ); ?>" class="wctog" <?php checked( $structures[0], $product_permalink ); ?> /> <?php _e( 'Default', 'woocommerce' ); ?></label></th>
					<td><code class="default-example"><?php echo esc_html( home_url() ); ?>/?product=sample-product</code> <code class="non-default-example"><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $product_base ); ?>/sample-product/</code></td>
				</tr>
				<?php if ( $shop_page_id ) : ?>
					<tr>
						<th><label><input name="product_permalink" type="radio" value="<?php echo esc_attr( $structures[1] ); ?>" class="wctog" <?php checked( $structures[1], $product_permalink ); ?> /> <?php _e( 'Shop base', 'woocommerce' ); ?></label></th>
						<td><code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $base_slug ); ?>/sample-product/</code></td>
					</tr>
					<tr>
						<th><label><input name="product_permalink" type="radio" value="<?php echo esc_attr( $structures[2] ); ?>" class="wctog" <?php checked( $structures[2], $product_permalink ); ?> /> <?php _e( 'Shop base with category', 'woocommerce' ); ?></label></th>
						<td><code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $base_slug ); ?>/product-category/sample-product/</code></td>
					</tr>
				<?php endif; ?>
				<tr>
					<th><label><input name="product_permalink" id="woocommerce_custom_selection" type="radio" value="custom" class="tog" <?php checked( in_array( $product_permalink, $structures ), false ); ?> />
						<?php _e( 'Custom Base', 'woocommerce' ); ?></label></th>
					<td>
						<input name="product_permalink_structure" id="woocommerce_permalink_structure" type="text" value="<?php echo esc_attr( $product_permalink ); ?>" class="regular-text code"> <span class="description"><?php _e( 'Enter a custom base to use. A base <strong>must</strong> be set or WordPress will use default instead.', 'woocommerce' ); ?></span>
					</td>
				</tr>
			</tbody>
		</table>
		<script type="text/javascript">
			jQuery( function() {
				jQuery('input.wctog').change(function() {
					jQuery('#woocommerce_permalink_structure').val( jQuery( this ).val() );
				});
				jQuery('.permalink-structure input').change(function() {
					jQuery('.wc-permalink-structure').find('code.non-default-example, code.default-example').hide();
					if ( jQuery(this).val() ) {
						jQuery('.wc-permalink-structure code.non-default-example').show();
						jQuery('.wc-permalink-structure input').removeAttr('disabled');
					} else {
						jQuery('.wc-permalink-structure code.default-example').show();
						jQuery('.wc-permalink-structure input:eq(0)').click();
						jQuery('.wc-permalink-structure input').attr('disabled', 'disabled');
					}
				});
				jQuery('.permalink-structure input:checked').change();
				jQuery('#woocommerce_permalink_structure').focus( function(){
					jQuery('#woocommerce_custom_selection').click();
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Save the settings.
	 */
	public function settings_save() {
		if ( ! is_admin() ) {
			return;
		}

		// We need to save the options ourselves; settings api does not trigger save for the permalinks page.
		if ( isset( $_POST['permalink_structure'] ) ) {
			$permalinks = get_option( 'woocommerce_permalinks' );

			if ( ! $permalinks ) {
				$permalinks = array();
			}

			$permalinks['category_base']    = wc_sanitize_permalink( trim( $_POST['woocommerce_product_category_slug'] ) );
			$permalinks['tag_base']         = wc_sanitize_permalink( trim( $_POST['woocommerce_product_tag_slug'] ) );
			$permalinks['attribute_base']   = wc_sanitize_permalink( trim( $_POST['woocommerce_product_attribute_slug'] ) );

			// Product base.
			$product_permalink = isset( $_POST['product_permalink'] ) ? wc_clean( $_POST['product_permalink'] ) : '';

			if ( 'custom' === $product_permalink ) {
				if ( isset( $_POST['product_permalink_structure'] ) ) {
					$product_permalink = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', trim( $_POST['product_permalink_structure'] ) ) );
				} else {
					$product_permalink = '/';
				}

				// This is an invalid base structure and breaks pages.
				if ( '%product_cat%' == $product_permalink ) {
					$product_permalink = '/' . _x( 'product', 'slug', 'woocommerce' ) . '/' . $product_permalink;
				}
			} elseif ( empty( $product_permalink ) ) {
				$product_permalink = false;
			}

			$permalinks['product_base'] = wc_sanitize_permalink( $product_permalink );

			// Shop base may require verbose page rules if nesting pages.
			$shop_page_id   = wc_get_page_id( 'shop' );
			$shop_permalink = ( $shop_page_id > 0 && get_post( $shop_page_id ) ) ? get_page_uri( $shop_page_id ) : _x( 'shop', 'default-slug', 'woocommerce' );

			if ( $shop_page_id && trim( $permalinks['product_base'], '/' ) === $shop_permalink ) {
				$permalinks['use_verbose_page_rules'] = true;
			}

			update_option( 'woocommerce_permalinks', $permalinks );
		}
	}
}

endif;

return new WC_Admin_Permalink_Settings();
