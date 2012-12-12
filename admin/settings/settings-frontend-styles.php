<?php
/**
 * Frontend styles/color picker settings.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Settings
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Output the frontend styles settings.
 *
 * @access public
 * @return void
 */
function woocommerce_frontend_styles_setting() {
	global $woocommerce;
	?><tr valign="top" class="woocommerce_frontend_css_colors">
		<th scope="row" class="titledesc">
			<label><?php _e( 'Styles', 'woocommerce' ); ?></label>
		</th>
	    <td class="forminp"><?php

			$base_file		= $woocommerce->plugin_path() . '/assets/css/woocommerce-base.less';
			$css_file		= $woocommerce->plugin_path() . '/assets/css/woocommerce.css';

			if ( is_writable( $base_file ) && is_writable( $css_file ) ) {

				// Get settings
				$colors = array_map( 'esc_attr', (array) get_option( 'woocommerce_frontend_css_colors' ) );

				// Defaults
				if ( empty( $colors['primary'] ) ) $colors['primary'] = '#ad74a2';
				if ( empty( $colors['secondary'] ) ) $colors['secondary'] = '#f7f6f7';
				if ( empty( $colors['highlight'] ) ) $colors['highlight'] = '#85ad74';
				if ( empty( $colors['content_bg'] ) ) $colors['content_bg'] = '#ffffff';
	            if ( empty( $colors['subtext'] ) ) $colors['subtext'] = '#777777';

				// Show inputs
	    		woocommerce_frontend_css_color_picker( __( 'Primary', 'woocommerce' ), 'woocommerce_frontend_css_primary', $colors['primary'], __( 'Call to action buttons/price slider/layered nav UI', 'woocommerce' ) );
	    		woocommerce_frontend_css_color_picker( __( 'Secondary', 'woocommerce' ), 'woocommerce_frontend_css_secondary', $colors['secondary'], __( 'Buttons and tabs', 'woocommerce' ) );
	    		woocommerce_frontend_css_color_picker( __( 'Highlight', 'woocommerce' ), 'woocommerce_frontend_css_highlight', $colors['highlight'], __( 'Price labels and Sale Flashes', 'woocommerce' ) );
	    		woocommerce_frontend_css_color_picker( __( 'Content', 'woocommerce' ), 'woocommerce_frontend_css_content_bg', $colors['content_bg'], __( 'Your themes page background - used for tab active states', 'woocommerce' ) );
	    		woocommerce_frontend_css_color_picker( __( 'Subtext', 'woocommerce' ), 'woocommerce_frontend_css_subtext', $colors['subtext'], __( 'Used for certain text and asides - breadcrumbs, small text etc.', 'woocommerce' ) );

	    	} else {

	    		echo '<span class="description">' . __( 'To edit colours <code>woocommerce/assets/css/woocommerce-base.less</code> and <code>woocommerce.css</code> need to be writable. See <a href="http://codex.wordpress.org/Changing_File_Permissions">the Codex</a> for more information.', 'woocommerce' ) . '</span>';

	    	}

	    ?></td>
		</tr>
		<script type="text/javascript">
			jQuery('input#woocommerce_frontend_css').change(function() {
				if (jQuery(this).is(':checked')) {
					jQuery('tr.woocommerce_frontend_css_colors').show();
				} else {
					jQuery('tr.woocommerce_frontend_css_colors').hide();
				}
			}).change();
		</script>
		<?php
}

add_action( 'woocommerce_admin_field_frontend_styles', 'woocommerce_frontend_styles_setting' );


/**
 * Output a colour picker input box.
 *
 * @access public
 * @param mixed $name
 * @param mixed $id
 * @param mixed $value
 * @param string $desc (default: '')
 * @return void
 */
function woocommerce_frontend_css_color_picker( $name, $id, $value, $desc = '' ) {
	global $woocommerce;

	echo '<div class="color_box"><strong><img class="help_tip" data-tip="' . esc_attr( $desc ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" /> ' . esc_html( $name ) . '</strong>
   		<input name="' . esc_attr( $id ). '" id="' . esc_attr( $id ) . '" type="text" value="' . esc_attr( $value ) . '" class="colorpick" /> <div id="colorPickerDiv_' . esc_attr( $id ) . '" class="colorpickdiv"></div>
    </div>';

}