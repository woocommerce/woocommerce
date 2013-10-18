<?php
/**
 * Functions for the settings page in admin.
 *
 * The settings page contains options for the WooCommerce plugin - this file contains functions to display
 * and save the list of options.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Settings
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/** Store settings in this array */
global $woocommerce_settings;

/** Settings init */
include( 'settings/settings-init.php' );

if ( ! function_exists( 'woocommerce_settings' ) ) {

	/**
	 * Settings page.
	 *
	 * Handles the display of the main woocommerce settings page in admin.
	 *
	 * @access public
	 * @return void
	 */
	function woocommerce_settings() {
	    global $woocommerce, $woocommerce_settings, $current_section, $current_tab;

	    do_action( 'woocommerce_settings_start' );

	    // Get current tab/section
	    $current_tab 		= ( empty( $_GET['tab'] ) ) ? 'general' : sanitize_text_field( urldecode( $_GET['tab'] ) );
	    $current_section 	= ( empty( $_REQUEST['section'] ) ) ? '' : sanitize_text_field( urldecode( $_REQUEST['section'] ) );

	    // Save settings
	    if ( ! empty( $_POST ) ) {

	    	if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) )
	    		die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );

	    	if ( ! $current_section ) {

	    	 	$old_base_color = get_option('woocommerce_frontend_css_base_color');
	    	 	$old_base_color = get_option('woocommerce_frontend_css_base_color');

	    	 	include_once( 'settings/settings-save.php' );

		    	switch ( $current_tab ) {
					case "payment_gateways" :
						woocommerce_update_options( $woocommerce_settings[ $current_tab ] );
						$woocommerce->payment_gateways->process_admin_options();
					break;
					case "shipping" :
						woocommerce_update_options( $woocommerce_settings[ $current_tab ] );
						$woocommerce->shipping->process_admin_options();
					break;
					default :
						if ( isset( $woocommerce_settings[ $current_tab ] ) )
							woocommerce_update_options( $woocommerce_settings[ $current_tab ] );

						// Trigger action for tab
						do_action( 'woocommerce_update_options_' . $current_tab );
					break;
				}

				do_action( 'woocommerce_update_options' );

				// Handle Colour Settings
				if ( $current_tab == 'general' && get_option('woocommerce_frontend_css') == 'yes' ) {

					// Save settings
					$primary 		= ( ! empty( $_POST['woocommerce_frontend_css_primary'] ) ) ? woocommerce_format_hex( $_POST['woocommerce_frontend_css_primary'] ) : '';
					$secondary 		= ( ! empty( $_POST['woocommerce_frontend_css_secondary'] ) ) ? woocommerce_format_hex( $_POST['woocommerce_frontend_css_secondary'] ) : '';
					$highlight 		= ( ! empty( $_POST['woocommerce_frontend_css_highlight'] ) ) ? woocommerce_format_hex( $_POST['woocommerce_frontend_css_highlight'] ) : '';
					$content_bg 	= ( ! empty( $_POST['woocommerce_frontend_css_content_bg'] ) ) ? woocommerce_format_hex( $_POST['woocommerce_frontend_css_content_bg'] ) : '';
					$subtext 		= ( ! empty( $_POST['woocommerce_frontend_css_subtext'] ) ) ? woocommerce_format_hex( $_POST['woocommerce_frontend_css_subtext'] ) : '';

					$colors = array(
						'primary' 		=> $primary,
						'secondary' 	=> $secondary,
						'highlight' 	=> $highlight,
						'content_bg' 	=> $content_bg,
						'subtext' 		=> $subtext
					);

					$old_colors = get_option( 'woocommerce_frontend_css_colors' );
					update_option( 'woocommerce_frontend_css_colors', $colors );

					if ( $old_colors != $colors )
						woocommerce_compile_less_styles();
				}

			} else {

				// If saving a shipping methods options, load 'er up
				if ( ( $current_tab == 'shipping' || $current_tab == 'payment_gateways' && class_exists( $current_section ) ) ) {

					$current_section_class = new $current_section();
					do_action( 'woocommerce_update_options_' . $current_tab . '_' . $current_section_class->id );

				// If saving an email's options, load theme
				} elseif ( $current_tab == 'email' ) {

					// Load mailer
					$mailer = $woocommerce->mailer();

					if ( class_exists( $current_section ) ) {
						$current_section_class = new $current_section();
						do_action( 'woocommerce_update_options_' . $current_tab . '_' . $current_section_class->id );
					} else {
						do_action( 'woocommerce_update_options_' . $current_tab . '_' . $current_section );
					}

				// Save tax
				} elseif ( $current_tab == 'tax' ) {

					include_once('settings/settings-tax-rates.php');

					woocommerce_tax_rates_setting_save();

				} else {

					// Save section only
					do_action( 'woocommerce_update_options_' . $current_tab . '_' . $current_section );

				}

			}

			// Clear any unwanted data
			$woocommerce->clear_product_transients();

			delete_transient( 'woocommerce_cache_excluded_uris' );

			// Redirect back to the settings page
			$redirect = add_query_arg( 'saved', 'true' );

			if ( ! empty( $_POST['subtab'] ) ) $redirect = add_query_arg( 'subtab', esc_attr( str_replace( '#', '', $_POST['subtab'] ) ), $redirect );

			wp_safe_redirect( $redirect );
			exit;
		}

		// Get any returned messages
		$error 		= ( empty( $_GET['wc_error'] ) ) ? '' : urldecode( stripslashes( $_GET['wc_error'] ) );
		$message 	= ( empty( $_GET['wc_message'] ) ) ? '' : urldecode( stripslashes( $_GET['wc_message'] ) );

		if ( $error || $message ) {

			if ( $error ) {
				echo '<div id="message" class="error fade"><p><strong>' . esc_html( $error ) . '</strong></p></div>';
			} else {
				echo '<div id="message" class="updated fade"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
			}

		} elseif ( ! empty( $_GET['saved'] ) ) {

			echo '<div id="message" class="updated fade"><p><strong>' . __( 'Your settings have been saved.', 'woocommerce' ) . '</strong></p></div>';

		}

	    // Were the settings saved?
	    if ( ! empty( $_GET['saved'] ) ) {
	        do_action('woocommerce_settings_saved');
	    }

	    // Hide WC Link
	    if ( ! empty( $_GET['hide-wc-extensions-message'] ) )
	    	update_option( 'hide-wc-extensions-message', 1 );
	    ?>
		<div class="wrap woocommerce">
			<form method="post" id="mainform" action="" enctype="multipart/form-data">
				<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div><h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
					<?php
						$tabs = array(
							'general' => __( 'General', 'woocommerce' ),
							'catalog' => __( 'Catalog', 'woocommerce' ),
							'pages' => __( 'Pages', 'woocommerce' ),
							'inventory' => __( 'Inventory', 'woocommerce' ),
							'tax' => __( 'Tax', 'woocommerce'),
							'shipping' => __( 'Shipping', 'woocommerce' ),
							'payment_gateways' => __( 'Payment Gateways', 'woocommerce' ),
							'email' => __( 'Emails', 'woocommerce' ),
							'integration' => __( 'Integration', 'woocommerce' )
						);

						if ( ! $woocommerce->integrations->get_integrations() )
							unset( $tabs['integration'] );

						$tabs = apply_filters('woocommerce_settings_tabs_array', $tabs);

						foreach ( $tabs as $name => $label ) {
							echo '<a href="' . admin_url( 'admin.php?page=woocommerce_settings&tab=' . $name ) . '" class="nav-tab ';
							if( $current_tab == $name ) echo 'nav-tab-active';
							echo '">' . $label . '</a>';
						}

						do_action( 'woocommerce_settings_tabs' );
					?>
				</h2>
				<?php wp_nonce_field( 'woocommerce-settings', '_wpnonce', true, true ); ?>

				<?php if ( ! get_option('hide-wc-extensions-message') ) : ?>
					<div id="woocommerce_extensions"><a href="<?php echo esc_url( add_query_arg( 'hide-wc-extensions-message', 'true' ) ); ?>" class="hide">&times;</a><?php printf(__( 'More functionality and gateway options available via <a href="%s" target="_blank">WC official extensions</a>.', 'woocommerce' ), 'http://www.woothemes.com/extensions/woocommerce-extensions/'); ?></div>
				<?php endif; ?>

				<?php
					switch ($current_tab) :
						case "general" :
							include_once('settings/settings-frontend-styles.php');
							woocommerce_admin_fields( $woocommerce_settings[$current_tab] );
						break;
						case "tax" :

							$links = array(
								'<a href="' . admin_url( 'admin.php?page=woocommerce_settings&tab=tax' ) . '" class="' . ( $current_section == '' ? 'current' : '' ) . '">' . __( 'Tax Options', 'woocommerce' ) . '</a>'
							);

							// Get tax classes and display as links
							$tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option('woocommerce_tax_classes' ) ) ) );

							$links[] = __( 'Tax Rates', 'woocommerce' ) . ': <a href="' . admin_url( 'admin.php?page=woocommerce_settings&tab=tax&section=standard' ) . '" class="' . ( $current_section == 'standard' ? 'current' : '' ) . '">' . __( 'Standard', 'woocommerce' ) . '</a>';

							if ( $tax_classes )
								foreach ( $tax_classes as $class )
									$links[] = '<a href="' . admin_url( 'admin.php?page=woocommerce_settings&tab=tax&section=' . sanitize_title( $class ) ) . '" class="' . ( $current_section == sanitize_title( $class ) ? 'current' : '' ) . '">' . $class  . '</a>';

							echo '<ul class="subsubsub"><li>' . implode( ' | </li><li>', $links ) . '</li></ul><br class="clear" />';

							if ( $current_section == 'standard' || in_array( $current_section, array_map( 'sanitize_title', $tax_classes ) ) ) {

								include_once('settings/settings-tax-rates.php');

								woocommerce_tax_rates_setting();

							} else {
								woocommerce_admin_fields( $woocommerce_settings[ $current_tab ] );
							}
						break;
						case "pages" :
						case "catalog" :
						case "inventory" :
							woocommerce_admin_fields( $woocommerce_settings[$current_tab] );
						break;
						case "email" :

							$current = $current_section ? '' : 'class="current"';

							$links = array( '<a href="' . admin_url( 'admin.php?page=woocommerce_settings&tab=email' ) . '" ' . $current . '>' . __( 'Email Options', 'woocommerce' ) . '</a>' );

							// Define emails that can be customised here
							$mailer 			= $woocommerce->mailer();
							$email_templates 	= $mailer->get_emails();

							foreach ( $email_templates as $email ) {

								$title = empty( $email->title ) ? ucwords( $email->id ) : ucwords( $email->title );

								$current = ( get_class( $email ) == $current_section ) ? 'class="current"' : '';

								$links[] = '<a href="' . add_query_arg( 'section', get_class( $email ), admin_url('admin.php?page=woocommerce_settings&tab=email') ) . '"' . $current . '>' . esc_html( $title ) . '</a>';

							}

							echo '<ul class="subsubsub"><li>' . implode( ' | </li><li>', $links ) . '</li></ul><br class="clear" />';

							// Specific email options
							if ( $current_section ) {
								foreach ( $email_templates as $email ) {
									if ( get_class( $email ) == $current_section ) {
										$email->admin_options();
										break;
									}
								}
			            	} else {
			            		woocommerce_admin_fields( $woocommerce_settings[ $current_tab ] );
			            	}

						break;
						case "shipping" :

							include('settings/settings-shipping-methods.php');

							$current = $current_section ? '' : 'class="current"';

							$links = array( '<a href="' . admin_url('admin.php?page=woocommerce_settings&tab=shipping') . '" ' . $current . '>' . __( 'Shipping Options', 'woocommerce' ) . '</a>' );

							// Load shipping methods so we can show any global options they may have
							$shipping_methods = $woocommerce->shipping->load_shipping_methods();

							foreach ( $shipping_methods as $method ) {

								if ( ! $method->has_settings() ) continue;

								$title = empty( $method->method_title ) ? ucwords( $method->id ) : ucwords( $method->method_title );

								$current = ( get_class( $method ) == $current_section ) ? 'class="current"' : '';

								$links[] = '<a href="' . add_query_arg( 'section', get_class( $method ), admin_url('admin.php?page=woocommerce_settings&tab=shipping') ) . '"' . $current . '>' . esc_html( $title ) . '</a>';

							}

							echo '<ul class="subsubsub"><li>' . implode( ' | </li><li>', $links ) . '</li></ul><br class="clear" />';

							// Specific method options
							if ( $current_section ) {
								foreach ( $shipping_methods as $method ) {
									if ( get_class( $method ) == $current_section && $method->has_settings() ) {
										$method->admin_options();
										break;
									}
								}
			            	} else {
			            		woocommerce_admin_fields( $woocommerce_settings[ $current_tab ] );
			            	}

						break;
						case "payment_gateways" :
							include('settings/settings-payment-gateways.php');

							$current = $current_section ? '' : 'class="current"';

							$links = array( '<a href="' . admin_url('admin.php?page=woocommerce_settings&tab=payment_gateways') . '" ' . $current . '>' . __( 'Payment Gateways', 'woocommerce' ) . '</a>' );

							// Load shipping methods so we can show any global options they may have
							$payment_gateways = $woocommerce->payment_gateways->payment_gateways();

							foreach ( $payment_gateways as $gateway ) {

								$title = empty( $gateway->method_title ) ? ucwords( $gateway->id ) : ucwords( $gateway->method_title );

								$current = ( get_class( $gateway ) == $current_section ) ? 'class="current"' : '';

								$links[] = '<a href="' . add_query_arg( 'section', get_class( $gateway ), admin_url('admin.php?page=woocommerce_settings&tab=payment_gateways') ) . '"' . $current . '>' . esc_html( $title ) . '</a>';

							}

							echo '<ul class="subsubsub"><li>' . implode( ' | </li><li>', $links ) . '</li></ul><br class="clear" />';

							// Specific method options
							if ( $current_section ) {
								foreach ( $payment_gateways as $gateway ) {
									if ( get_class( $gateway ) == $current_section ) {
										$gateway->admin_options();
										break;
									}
								}
			            	} else {
			            		woocommerce_admin_fields( $woocommerce_settings[ $current_tab ] );
			            	}

						break;
						case "integration" :

							$integrations = $woocommerce->integrations->get_integrations();

							$current_section = empty( $current_section ) ? key( $integrations ) : $current_section;

							$links = array();

							foreach ( $integrations as $integration ) {
								$title = empty( $integration->method_title ) ? ucwords( $integration->id ) : ucwords( $integration->method_title );

								$current = ( $integration->id == $current_section ) ? 'class="current"' : '';

								$links[] = '<a href="' . add_query_arg( 'section', $integration->id, admin_url('admin.php?page=woocommerce_settings&tab=integration') ) . '"' . $current . '>' . esc_html( $title ) . '</a>';
							}

							echo '<ul class="subsubsub"><li>' . implode( ' | </li><li>', $links ) . '</li></ul><br class="clear" />';

							if ( isset( $integrations[ $current_section ] ) )
								$integrations[ $current_section ]->admin_options();

						break;
						default :
							do_action( 'woocommerce_settings_tabs_' . $current_tab );
						break;
					endswitch;
				?>
		        <p class="submit">
		        	<?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
		        		<input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'woocommerce' ); ?>" />
		        	<?php endif; ?>
		        	<input type="hidden" name="subtab" id="last_tab" />
		        </p>
			</form>

			<script type="text/javascript">
				jQuery(window).load(function(){

					// Countries
					jQuery('select#woocommerce_allowed_countries').change(function(){
						if (jQuery(this).val()=="specific") {
							jQuery(this).parent().parent().next('tr').show();
						} else {
							jQuery(this).parent().parent().next('tr').hide();
						}
					}).change();

					// Color picker
					jQuery('.colorpick').each(function(){
						jQuery('.colorpickdiv', jQuery(this).parent()).farbtastic(this);
						jQuery(this).click(function() {
							if ( jQuery(this).val() == "" ) jQuery(this).val('#');
							jQuery('.colorpickdiv', jQuery(this).parent() ).show();
						});
					});
					jQuery(document).mousedown(function(){
						jQuery('.colorpickdiv').hide();
					});

					// Edit prompt
					jQuery(function(){
						var changed = false;

						jQuery('input, textarea, select, checkbox').change(function(){
							changed = true;
						});

						jQuery('.woo-nav-tab-wrapper a').click(function(){
							if (changed) {
								window.onbeforeunload = function() {
								    return '<?php echo __( 'The changes you made will be lost if you navigate away from this page.', 'woocommerce' ); ?>';
								}
							} else {
								window.onbeforeunload = '';
							}
						});

						jQuery('.submit input').click(function(){
							window.onbeforeunload = '';
						});
					});

					// Sorting
					jQuery('table.wc_gateways tbody, table.wc_shipping tbody').sortable({
						items:'tr',
						cursor:'move',
						axis:'y',
						handle: 'td',
						scrollSensitivity:40,
						helper:function(e,ui){
							ui.children().each(function(){
								jQuery(this).width(jQuery(this).width());
							});
							ui.css('left', '0');
							return ui;
						},
						start:function(event,ui){
							ui.item.css('background-color','#f6f6f6');
						},
						stop:function(event,ui){
							ui.item.removeAttr('style');
						}
					});

					// Chosen selects
					jQuery("select.chosen_select").chosen();

					jQuery("select.chosen_select_nostd").chosen({
						allow_single_deselect: 'true'
					});
				});
			</script>
		</div>
		<?php
	}
}


/**
 * Get a setting from the settings API.
 *
 * @access public
 * @param mixed $option
 * @return void
 */
function woocommerce_settings_get_option( $option_name, $default = '' ) {
	// Array value
	if ( strstr( $option_name, '[' ) ) {

		parse_str( $option_name, $option_array );

		// Option name is first key
		$option_keys = array_keys( $option_array );
		$option_name = current( $option_keys );

		// Get value
		$option_values = get_option( $option_name, '' );

		$key = key( $option_array[ $option_name ] );

		if ( isset( $option_values[ $key ] ) )
			$option_value = $option_values[ $key ];
		else
			$option_value = null;

	// Single value
	} else {
		$option_value = get_option( $option_name, null );
	}

	if ( is_array( $option_value ) )
		$option_value = array_map( 'stripslashes', $option_value );
	elseif ( ! is_null( $option_value ) )
		$option_value = stripslashes( $option_value );

	return $option_value === null ? $default : $option_value;
}

/**
 * Output admin fields.
 *
 * Loops though the woocommerce options array and outputs each field.
 *
 * @access public
 * @param array $options Opens array to output
 * @return void
 */
function woocommerce_admin_fields( $options ) {
	global $woocommerce;

    foreach ( $options as $value ) {
    	if ( ! isset( $value['type'] ) ) continue;
    	if ( ! isset( $value['id'] ) ) $value['id'] = '';
    	if ( ! isset( $value['title'] ) ) $value['title'] = isset( $value['name'] ) ? $value['name'] : '';
    	if ( ! isset( $value['class'] ) ) $value['class'] = '';
    	if ( ! isset( $value['css'] ) ) $value['css'] = '';
    	if ( ! isset( $value['default'] ) ) $value['default'] = '';
    	if ( ! isset( $value['desc'] ) ) $value['desc'] = '';
    	if ( ! isset( $value['desc_tip'] ) ) $value['desc_tip'] = false;

    	// Custom attribute handling
		$custom_attributes = array();

		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) )
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value )
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

		// Description handling
		if ( $value['desc_tip'] === true ) {
			$description = '';
			$tip = $value['desc'];
		} elseif ( ! empty( $value['desc_tip'] ) ) {
			$description = $value['desc'];
			$tip = $value['desc_tip'];
		} elseif ( ! empty( $value['desc'] ) ) {
			$description = $value['desc'];
			$tip = '';
		} else {
			$description = $tip = '';
		}

		if ( $description && in_array( $value['type'], array( 'textarea', 'radio' ) ) ) {
			$description = '<p style="margin-top:0">' . wp_kses_post( $description ) . '</p>';
		} elseif ( $description ) {
			$description = '<span class="description">' . wp_kses_post( $description ) . '</span>';
		}

		if ( $tip && in_array( $value['type'], array( 'checkbox' ) ) ) {

			$tip = '<p class="description">' . $tip . '</p>';

		} elseif ( $tip ) {

			$tip = '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

		}

		// Switch based on type
        switch( $value['type'] ) {

        	// Section Titles
            case 'title':
            	if ( ! empty( $value['title'] ) ) echo '<h3>' . esc_html( $value['title'] ) . '</h3>';
            	if ( ! empty( $value['desc'] ) ) echo wpautop( wptexturize( wp_kses_post( $value['desc'] ) ) );
            	echo '<table class="form-table">'. "\n\n";
            	if ( ! empty( $value['id'] ) ) do_action( 'woocommerce_settings_' . sanitize_title( $value['id'] ) );
            break;

            // Section Ends
            case 'sectionend':
            	if ( ! empty( $value['id'] ) ) do_action( 'woocommerce_settings_' . sanitize_title( $value['id'] ) . '_end' );
            	echo '</table>';
            	if ( ! empty( $value['id'] ) ) do_action( 'woocommerce_settings_' . sanitize_title( $value['id'] ) . '_after' );
            break;

            // Standard text inputs and subtypes like 'number'
            case 'text':
            case 'email':
            case 'number':
            case 'color' :
            case 'password' :

            	$type 			= $value['type'];
            	$class 			= '';
            	$option_value 	= woocommerce_settings_get_option( $value['id'], $value['default'] );

            	if ( $value['type'] == 'color' ) {
            		$type = 'text';
            		$value['class'] .= 'colorpick';
	            	$description .= '<div id="colorPickerDiv_' . esc_attr( $value['id'] ) . '" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div>';
            	}

            	?><tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
						<?php echo $tip; ?>
					</th>
                    <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
                    	<input
                    		name="<?php echo esc_attr( $value['id'] ); ?>"
                    		id="<?php echo esc_attr( $value['id'] ); ?>"
                    		type="<?php echo esc_attr( $type ); ?>"
                    		style="<?php echo esc_attr( $value['css'] ); ?>"
                    		value="<?php echo esc_attr( $option_value ); ?>"
                    		class="<?php echo esc_attr( $value['class'] ); ?>"
                    		<?php echo implode( ' ', $custom_attributes ); ?>
                    		/> <?php echo $description; ?>
                    </td>
                </tr><?php
            break;

            // Textarea
            case 'textarea':

            	$option_value 	= woocommerce_settings_get_option( $value['id'], $value['default'] );

            	?><tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
						<?php echo $tip; ?>
					</th>
                    <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
                    	<?php echo $description; ?>

                        <textarea
                        	name="<?php echo esc_attr( $value['id'] ); ?>"
                        	id="<?php echo esc_attr( $value['id'] ); ?>"
                        	style="<?php echo esc_attr( $value['css'] ); ?>"
                        	class="<?php echo esc_attr( $value['class'] ); ?>"
                        	<?php echo implode( ' ', $custom_attributes ); ?>
                        	><?php echo esc_textarea( $option_value );  ?></textarea>
                    </td>
                </tr><?php
            break;

            // Select boxes
            case 'select' :
            case 'multiselect' :

            	$option_value 	= woocommerce_settings_get_option( $value['id'], $value['default'] );

            	?><tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
						<?php echo $tip; ?>
					</th>
                    <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
                    	<select
                    		name="<?php echo esc_attr( $value['id'] ); ?><?php if ( $value['type'] == 'multiselect' ) echo '[]'; ?>"
                    		id="<?php echo esc_attr( $value['id'] ); ?>"
                    		style="<?php echo esc_attr( $value['css'] ); ?>"
                    		class="<?php echo esc_attr( $value['class'] ); ?>"
                    		<?php echo implode( ' ', $custom_attributes ); ?>
                    		<?php if ( $value['type'] == 'multiselect' ) echo 'multiple="multiple"'; ?>
                    		>
	                    	<?php
		                        foreach ( $value['options'] as $key => $val ) {
		                        	?>
		                        	<option value="<?php echo esc_attr( $key ); ?>" <?php

			                        	if ( is_array( $option_value ) )
			                        		selected( in_array( $key, $option_value ), true );
			                        	else
			                        		selected( $option_value, $key );

		                        	?>><?php echo $val ?></option>
		                        	<?php
		                        }
		                    ?>
                       </select> <?php echo $description; ?>
                    </td>
                </tr><?php
            break;

            // Radio inputs
            case 'radio' :

            	$option_value 	= woocommerce_settings_get_option( $value['id'], $value['default'] );

            	?><tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
						<?php echo $tip; ?>
					</th>
                    <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
                    	<fieldset>
                    		<?php echo $description; ?>
                    		<ul>
                    		<?php
                    			foreach ( $value['options'] as $key => $val ) {
		                        	?>
		                        	<li>
		                        		<label><input
			                        		name="<?php echo esc_attr( $value['id'] ); ?>"
			                        		value="<?php echo $key; ?>"
			                        		type="radio"
				                    		style="<?php echo esc_attr( $value['css'] ); ?>"
				                    		class="<?php echo esc_attr( $value['class'] ); ?>"
				                    		<?php echo implode( ' ', $custom_attributes ); ?>
				                    		<?php checked( $key, $option_value ); ?>
			                        		/> <?php echo $val ?></label>
		                        	</li>
		                        	<?php
		                        }
                    		?>
                    		</ul>
                    	</fieldset>
                    </td>
                </tr><?php
            break;

            // Checkbox input
            case 'checkbox' :

            	$option_value 	= woocommerce_settings_get_option( $value['id'], $value['default'] );

            	if ( ! isset( $value['hide_if_checked'] ) ) $value['hide_if_checked'] = false;
            	if ( ! isset( $value['show_if_checked'] ) ) $value['show_if_checked'] = false;

            	if ( ! isset( $value['checkboxgroup'] ) || ( isset( $value['checkboxgroup'] ) && $value['checkboxgroup'] == 'start' ) ) {
            		?>
            		<tr valign="top" class="<?php
            			if ( $value['hide_if_checked'] == 'yes' || $value['show_if_checked']=='yes') echo 'hidden_option';
            			if ( $value['hide_if_checked'] == 'option' ) echo 'hide_options_if_checked';
            			if ( $value['show_if_checked'] == 'option' ) echo 'show_options_if_checked';
            		?>">
					<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ) ?></th>
					<td class="forminp forminp-checkbox">
						<fieldset>
					<?php
            	} else {
            		?>
            		<fieldset class="<?php
            			if ( $value['hide_if_checked'] == 'yes' || $value['show_if_checked'] == 'yes') echo 'hidden_option';
            			if ( $value['hide_if_checked'] == 'option') echo 'hide_options_if_checked';
            			if ( $value['show_if_checked'] == 'option') echo 'show_options_if_checked';
            		?>">
            		<?php
            	}

            	?>
		            <legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ) ?></span></legend>

					<label for="<?php echo $value['id'] ?>">
					<input
						name="<?php echo esc_attr( $value['id'] ); ?>"
						id="<?php echo esc_attr( $value['id'] ); ?>"
						type="checkbox"
						value="1"
						<?php checked( $option_value, 'yes'); ?>
						<?php echo implode( ' ', $custom_attributes ); ?>
					/> <?php echo wp_kses_post( $value['desc'] ) ?></label> <?php echo $tip; ?>
				<?php

				if ( ! isset( $value['checkboxgroup'] ) || ( isset( $value['checkboxgroup'] ) && $value['checkboxgroup'] == 'end' ) ) {
					?>
						</fieldset>
					</td>
					</tr>
					<?php
				} else {
					?>
					</fieldset>
					<?php
				}

            break;

            // Image width settings
            case 'image_width' :

            	$width 	= woocommerce_settings_get_option( $value['id'] . '[width]', $value['default']['width'] );
            	$height = woocommerce_settings_get_option( $value['id'] . '[height]', $value['default']['height'] );
            	$crop 	= checked( 1, woocommerce_settings_get_option( $value['id'] . '[crop]', $value['default']['crop'] ), false );

            	?><tr valign="top">
					<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ) ?> <?php echo $tip; ?></th>
                    <td class="forminp">

                    	<?php _e( 'Width', 'woocommerce' ); ?> <input name="<?php echo esc_attr( $value['id'] ); ?>[width]" id="<?php echo esc_attr( $value['id'] ); ?>-width" type="text" size="3" value="<?php echo $width; ?>" />

                    	<?php _e( 'Height', 'woocommerce' ); ?> <input name="<?php echo esc_attr( $value['id'] ); ?>[height]" id="<?php echo esc_attr( $value['id'] ); ?>-height" type="text" size="3" value="<?php echo $height; ?>" />

                    	<label><?php _e( 'Hard Crop', 'woocommerce' ); ?> <input name="<?php echo esc_attr( $value['id'] ); ?>[crop]" id="<?php echo esc_attr( $value['id'] ); ?>-crop" type="checkbox" <?php echo $crop; ?> /></label>

                    	</td>
                </tr><?php
            break;

            // Single page selects
            case 'single_select_page' :

            	$args = array( 'name'				=> $value['id'],
            				   'id'					=> $value['id'],
            				   'sort_column' 		=> 'menu_order',
            				   'sort_order'			=> 'ASC',
            				   'show_option_none' 	=> ' ',
            				   'class'				=> $value['class'],
            				   'echo' 				=> false,
            				   'selected'			=> absint( woocommerce_settings_get_option( $value['id'] ) )
            				   );

            	if( isset( $value['args'] ) )
            		$args = wp_parse_args( $value['args'], $args );

            	?><tr valign="top" class="single_select_page">
                    <th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ) ?> <?php echo $tip; ?></th>
                    <td class="forminp">
			        	<?php echo str_replace(' id=', " data-placeholder='" . __( 'Select a page&hellip;', 'woocommerce' ) .  "' style='" . $value['css'] . "' class='" . $value['class'] . "' id=", wp_dropdown_pages( $args ) ); ?> <?php echo $description; ?>
			        </td>
               	</tr><?php
            break;

            // Single country selects
            case 'single_select_country' :

            	$country_setting = (string) woocommerce_settings_get_option( $value['id'] );

            	$countries = $woocommerce->countries->countries;

            	if ( strstr( $country_setting, ':' ) ) {
            		$exploded_settings = explode( ':', $country_setting );
            		$country = current( $exploded_settings );
            		$state = end( $exploded_settings );
            	} else {
            		$country = $country_setting;
            		$state = '*';
            	}

            	?><tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
						<?php echo $tip; ?>
					</th>
                    <td class="forminp"><select name="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>" data-placeholder="<?php _e( 'Choose a country&hellip;', 'woocommerce' ); ?>" title="Country" class="chosen_select">
			        	<?php echo $woocommerce->countries->country_dropdown_options( $country, $state ); ?>
			        </select> <?php echo $description; ?>
               		</td>
               	</tr><?php
            break;

            // Country multiselects
            case 'multi_select_countries' :

            	$selections = (array) woocommerce_settings_get_option( $value['id'] );

            	$countries = $woocommerce->countries->countries;
            	asort( $countries );
            	?><tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
						<?php echo $tip; ?>
					</th>
                    <td class="forminp">
	                    <select multiple="multiple" name="<?php echo esc_attr( $value['id'] ); ?>[]" style="width:450px;" data-placeholder="<?php _e( 'Choose countries&hellip;', 'woocommerce' ); ?>" title="Country" class="chosen_select">
				        	<?php
				        		if ( $countries )
				        			foreach ( $countries as $key => $val )
	                    				echo '<option value="'.$key.'" ' . selected( in_array( $key, $selections ), true, false ).'>' . $val . '</option>';
	                    	?>
				        </select> <?php echo $description; ?>
               		</td>
               	</tr><?php
            break;

            // Default: run an action
            default:
            	do_action( 'woocommerce_admin_field_' . $value['type'], $value );
            break;
    	}
	}
}
