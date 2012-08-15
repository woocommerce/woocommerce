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
	    global $woocommerce, $woocommerce_settings;

	    // Get current tab/section
	    $current_tab 		= ( empty( $_GET['tab'] ) ) ? 'general' : urldecode( $_GET['tab'] );
	    $current_section 	= ( empty( $_REQUEST['section'] ) ) ? '' : urldecode( $_REQUEST['section'] );

	    // Save settings
	    if ( ! empty( $_POST ) ) {

	    	if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) )
	    		die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );

	    	if ( ! $current_section ) {

	    	 	$old_base_color = get_option('woocommerce_frontend_css_base_color');
	    	 	$old_base_color = get_option('woocommerce_frontend_css_base_color');

	    	 	include_once( 'settings/settings-save.php' );

		    	switch ( $current_tab ) {
					case "general" :
					case "pages" :
					case "catalog" :
					case "inventory" :
					case "shipping" :
					case "tax" :
					case "email" :
						woocommerce_update_options( $woocommerce_settings[$current_tab] );
					break;
				}

				do_action( 'woocommerce_update_options' );
				do_action( 'woocommerce_update_options_' . $current_tab );

				// Handle Colour Settings
				if ( $current_tab == 'general' && get_option('woocommerce_frontend_css') == 'yes' ) {

					// Save settings
					$primary 		= ( ! empty( $_POST['woocommerce_frontend_css_primary'] ) ) ? woocommerce_format_hex( $_POST['woocommerce_frontend_css_primary'] ) : '';
					$secondary 		= ( ! empty( $_POST['woocommerce_frontend_css_secondary'] ) ) ? woocommerce_format_hex( $_POST['woocommerce_frontend_css_secondary'] ) : '';
					$highlight 		= ( ! empty( $_POST['woocommerce_frontend_css_highlight'] ) ) ? woocommerce_format_hex( $_POST['woocommerce_frontend_css_highlight'] ) : '';
					$content_bg 	= ( ! empty( $_POST['woocommerce_frontend_css_content_bg'] ) ) ? woocommerce_format_hex( $_POST['woocommerce_frontend_css_content_bg'] ) : '';
					$subtext 		= ( ! empty( $_POST['woocommerce_frontend_css_subtext'] ) ) ? woocommerce_format_hex( $_POST['woocommerce_frontend_css_subtext'] ) : '';

					$colors = array(
						'primary' 	=> $primary,
						'secondary' => $secondary,
						'highlight' => $highlight,
						'content_bg' => $content_bg,
						'subtext' => $subtext
					);

					$old_colors = get_option( 'woocommerce_frontend_css_colors' );
					update_option( 'woocommerce_frontend_css_colors', $colors );

					if ( $old_colors != $colors )
						woocommerce_compile_less_styles();

				}

			} else {

				// If saving a shipping methods options, load 'er up
				if ( $current_tab == 'shipping' && class_exists( $current_section ) ) {
					$current_section_class = new $current_section();
					do_action( 'woocommerce_update_options_' . $current_tab . '_' . $current_section_class->id );
				} else {

					// Save section only
					do_action( 'woocommerce_update_options_' . $current_tab . '_' . $current_section );

				}

			}

			// Flush rules and clear any unwanted data
			flush_rewrite_rules( false );
			unset($_SESSION['orderby']);
			$woocommerce->clear_product_transients();

			// Redirect back to the settings page
			$redirect = add_query_arg( 'saved', 'true' );

			if ( ! empty( $_POST['subtab'] ) ) $redirect = add_query_arg( 'subtab', esc_attr( str_replace( '#', '', $_POST['subtab'] ) ), $redirect );

			wp_redirect( $redirect );
			exit;
		}

		// Get any returned messages
		$error 		= ( empty( $_GET['wc_error'] ) ) ? '' : urldecode( stripslashes( $_GET['wc_error'] ) );
		$message 	= ( empty( $_GET['wc_message'] ) ) ? '' : urldecode( stripslashes( $_GET['wc_message'] ) );

		if ( $error || $message ) {

			if ( $error ) {
				echo '<div id="message" class="error fade"><p><strong>' . wptexturize( $error ) . '</strong></p></div>';
			} else {
				echo '<div id="message" class="updated fade"><p><strong>' . wptexturize( $message ) . '</strong></p></div>';
			}

		} elseif ( ! empty( $_GET['saved'] ) ) {

			echo '<div id="message" class="updated fade"><p><strong>' . __( 'Your settings have been saved.', 'woocommerce' ) . '</strong></p></div>';

		}

	    // Were the settings saved?
	    if ( ! empty( $_GET['saved'] ) ) {
	        flush_rewrite_rules( false );
	        do_action('woocommerce_settings_saved');
	    }

	    // Hide WC Link
	    if (isset($_GET['hide-wc-extensions-message']))
	    	update_option('hide-wc-extensions-message', 1);

	    // Install/page installer
	    $install_complete = false;

	    // Add pages button
	    if (isset($_GET['install_woocommerce_pages']) && $_GET['install_woocommerce_pages']) {

			require_once( 'woocommerce-admin-install.php' );
	    	woocommerce_create_pages();
	    	update_option('skip_install_woocommerce_pages', 1);
	    	$install_complete = true;

		// Skip button
	    } elseif (isset($_GET['skip_install_woocommerce_pages']) && $_GET['skip_install_woocommerce_pages']) {

	    	update_option('skip_install_woocommerce_pages', 1);
	    	$install_complete = true;

	    }

		if ($install_complete) {
			?>
	    	<div id="message" class="updated woocommerce-message wc-connect">
				<div class="squeezer">
					<h4><?php _e( '<strong>Congratulations!</strong> &#8211; WooCommerce has been installed and setup. Enjoy :)', 'woocommerce' ); ?></h4>
					<p><a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/woocommerce/" data-text="A open-source (free) #ecommerce plugin for #WordPress that helps you sell anything. Beautifully." data-via="WooThemes" data-size="large" data-hashtags="WooCommerce">Tweet</a>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></p>
				</div>
			</div>
			<?php

			// Flush rules after install
			flush_rewrite_rules( false );

			// Set installed option
			update_option('woocommerce_installed', 0);
		}
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
					<div id="woocommerce_extensions"><a href="<?php echo add_query_arg('hide-wc-extensions-message', 'true') ?>" class="hide">&times;</a><?php printf(__('More functionality and gateway options available via <a href="%s" target="_blank">WC official extensions</a>.', 'woocommerce'), 'http://www.woothemes.com/extensions/woocommerce-extensions/'); ?></div>
				<?php endif; ?>

				<?php
					switch ($current_tab) :
						case "general" :
							include_once('settings/settings-frontend-styles.php');
							woocommerce_admin_fields( $woocommerce_settings[$current_tab] );
						break;
						case "tax" :
							include('settings/settings-tax-rates.php');
							woocommerce_admin_fields( $woocommerce_settings[$current_tab] );
						break;
						case "pages" :
						case "catalog" :
						case "inventory" :
						case "email" :
							woocommerce_admin_fields( $woocommerce_settings[$current_tab] );
						break;
						case "shipping" :

							include('settings/settings-shipping-methods.php');

							$current = $current_section ? '' : 'class="current"';

							$links = array( '<a href="' . admin_url('admin.php?page=woocommerce_settings&tab=shipping') . '" ' . $current . '>' . __('Shipping Options', 'woocommerce') . '</a>' );

							// Load shipping methods so we can show any global options they may have
							$shipping_methods = $woocommerce->shipping->load_shipping_methods();

							foreach ( $shipping_methods as $method ) {

								if ( ! $method->has_settings() ) continue;

								$title = empty( $method->method_title ) ? ucwords( $method->id ) : ucwords( $method->method_title );

								$current = ( get_class( $method ) == $current_section ) ? 'class="current"' : '';

								$links[] = '<a href="' . add_query_arg( 'section', get_class( $method ), admin_url('admin.php?page=woocommerce_settings&tab=shipping') ) . '"' . $current . '>' . $title . '</a>';

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
			            		woocommerce_admin_fields( $woocommerce_settings[$current_tab] );
			            	}

						break;
						case "payment_gateways" :
							include('settings/settings-payment-gateways.php');

							$links = array( '<a href="#payment-gateways">'.__('Payment Gateways', 'woocommerce').'</a>' );

			            	foreach ( $woocommerce->payment_gateways->payment_gateways() as $gateway ) :
			            		$title = empty( $gateway->method_title ) ? ucwords( $gateway->id ) : ucwords( $gateway->method_title );

			            		$links[] = '<a href="#gateway-'.$gateway->id.'">'.$title.'</a>';
							endforeach;

							echo '<div class="subsubsub_section"><ul class="subsubsub"><li>' . implode( ' | </li><li>', $links ) . '</li></ul><br class="clear" />';

							echo '<div class="section" id="payment-gateways">';

							woocommerce_admin_fields( $woocommerce_settings[$current_tab] );

							echo '</div>';

							// Specific method options
			            	foreach ( $woocommerce->payment_gateways->payment_gateways() as $gateway ) {
			            		echo '<div class="section" id="gateway-'.$gateway->id.'">';
		            			$gateway->admin_options();
		            			echo '</div>';
			            	}

							echo '</div>';

						break;
						case "integration" :

							$integrations = $woocommerce->integrations->get_integrations();

							$current_section = empty( $current_section ) ? key( $integrations ) : $current_section;

							foreach ( $integrations as $integration ) {
								$title = empty( $integration->method_title ) ? ucwords( $integration->id ) : ucwords( $integration->method_title );

								$current = ( $integration->id == $current_section ) ? 'class="current"' : '';

								$links[] = '<a href="' . add_query_arg( 'section', $integration->id, admin_url('admin.php?page=woocommerce_settings&tab=integration') ) . '"' . $current . '>' . $title . '</a>';
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
		        	<input name="save" class="button-primary" type="submit" value="<?php _e( 'Save changes', 'woocommerce' ); ?>" />
		        	<input type="hidden" name="subtab" id="last_tab" />
		        </p>
			</form>

			<script type="text/javascript">
				jQuery(window).load(function(){
					// Subsubsub tabs
					jQuery('div.subsubsub_section ul.subsubsub li a:eq(0)').addClass('current');
					jQuery('div.subsubsub_section .section:gt(0)').hide();

					jQuery('div.subsubsub_section ul.subsubsub li a').click(function(){
						var $clicked = jQuery(this);
						var $section = $clicked.closest('.subsubsub_section');
						var $target  = $clicked.attr('href');

						$section.find('a').removeClass('current');

						if ( $section.find('.section:visible').size() > 0 ) {
							$section.find('.section:visible').fadeOut( 100, function() {
								$section.find( $target ).fadeIn('fast');
							});
						} else {
							$section.find( $target ).fadeIn('fast');
						}

						$clicked.addClass('current');
						jQuery('#last_tab').val( $target );

						return false;
					});

					<?php if (isset($_GET['subtab']) && $_GET['subtab']) echo 'jQuery("div.subsubsub_section ul.subsubsub li a[href=#'.$_GET['subtab'].']").click();'; ?>

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
    	if ( ! isset( $value['name'] ) ) $value['name'] = '';
    	if ( ! isset( $value['class'] ) ) $value['class'] = '';
    	if ( ! isset( $value['css'] ) ) $value['css'] = '';
    	if ( ! isset( $value['std'] ) ) $value['std'] = '';
    	if ( ! isset( $value['desc'] ) ) $value['desc'] = '';
    	if ( ! isset( $value['desc_tip'] ) ) $value['desc_tip'] = false;

    	if ( $value['desc_tip'] === true ) {
    		$description = '<img class="help_tip" data-tip="' . esc_attr( $value['desc'] ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" />';
    	} elseif ( $value['desc_tip'] ) {
    		$description = '<img class="help_tip" data-tip="' . esc_attr( $value['desc_tip'] ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" />';
    	} else {
    		$description = '<span class="description">' . $value['desc'] . '</span>';
    	}

        switch( $value['type'] ) {
            case 'title':
            	if ( isset($value['name'] ) && $value['name'] ) echo '<h3>' . $value['name'] . '</h3>';
            	if ( isset($value['desc'] ) && $value['desc'] ) echo wpautop( wptexturize( $value['desc'] ) );
            	echo '<table class="form-table">'. "\n\n";
            	if ( isset($value['id'] ) && $value['id'] ) do_action( 'woocommerce_settings_' . sanitize_title($value['id'] ) );
            break;
            case 'sectionend':
            	if ( isset($value['id'] ) && $value['id'] ) do_action( 'woocommerce_settings_' . sanitize_title( $value['id'] ) . '_end' );
            	echo '</table>';
            	if ( isset($value['id'] ) && $value['id'] ) do_action( 'woocommerce_settings_' . sanitize_title( $value['id'] ) . '_after' );
            break;
            case 'text':
            	?><tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo $value['name']; ?></label>
					</th>
                    <td class="forminp"><input name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" type="<?php echo esc_attr( $value['type'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>" value="<?php if ( get_option( $value['id'] ) !== false && get_option( $value['id'] ) !== null ) { echo esc_attr( stripslashes( get_option($value['id'] ) ) ); } else { echo esc_attr( $value['std'] ); } ?>" /> <?php echo $description; ?></td>
                </tr><?php
            break;
            case 'color' :
            	?><tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo $value['name']; ?></label>
					</th>
                    <td class="forminp"><input name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" type="text" style="<?php echo esc_attr( $value['css'] ); ?>" value="<?php if ( get_option( $value['id'] ) !== false && get_option( $value['id'] ) !== null ) { echo esc_attr( stripslashes( get_option($value['id'] ) ) ); } else { echo esc_attr( $value['std'] ); } ?>" class="colorpick" /> <?php echo $description; ?> <div id="colorPickerDiv_<?php echo esc_attr( $value['id'] ); ?>" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div></td>
                </tr><?php
            break;
            case 'image_width' :
            	?><tr valign="top">
					<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
                    <td class="forminp">

                    	<?php _e('Width', 'woocommerce'); ?> <input name="<?php echo esc_attr( $value['id'] ); ?>_width" id="<?php echo esc_attr( $value['id'] ); ?>_width" type="text" size="3" value="<?php if ( $size = get_option( $value['id'].'_width') ) echo stripslashes($size); else echo $value['std']; ?>" />

                    	<?php _e('Height', 'woocommerce'); ?> <input name="<?php echo esc_attr( $value['id'] ); ?>_height" id="<?php echo esc_attr( $value['id'] ); ?>_height" type="text" size="3" value="<?php if ( $size = get_option( $value['id'].'_height') ) echo stripslashes($size); else echo $value['std']; ?>" />

                    	<label><?php _e('Hard Crop', 'woocommerce'); ?> <input name="<?php echo esc_attr( $value['id'] ); ?>_crop" id="<?php echo esc_attr( $value['id'] ); ?>_crop" type="checkbox" <?php if (get_option( $value['id'].'_crop')!='') checked(get_option( $value['id'].'_crop'), 1); else checked(1); ?> /></label>

                    	<?php echo $description; ?></td>
                </tr><?php
            break;
            case 'select':
            	?><tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo $value['name']; ?></label>
					</th>
                    <td class="forminp"><select name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>" class="<?php if (isset($value['class'])) echo $value['class']; ?>">
                        <?php
                        foreach ($value['options'] as $key => $val) {
                        	$_current = get_option( $value['id'] );
							if ( ! $_current ) {
								$_current = $value['std'];
							}
                        	?>
                        	<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $_current, $key ); ?>><?php echo $val ?></option>
                        	<?php
                        }
                        ?>
                       </select> <?php echo $description; ?>
                    </td>
                </tr><?php
            break;
            case 'checkbox' :

            	if (!isset($value['hide_if_checked'])) $value['hide_if_checked'] = false;
            	if (!isset($value['show_if_checked'])) $value['show_if_checked'] = false;

            	if (!isset($value['checkboxgroup']) || (isset($value['checkboxgroup']) && $value['checkboxgroup']=='start')) :
            		?>
            		<tr valign="top" class="<?php
            			if ($value['hide_if_checked']=='yes' || $value['show_if_checked']=='yes') echo 'hidden_option';
            			if ($value['hide_if_checked']=='option') echo 'hide_options_if_checked';
            			if ($value['show_if_checked']=='option') echo 'show_options_if_checked';
            		?>">
					<th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
					<td class="forminp">
						<fieldset>
					<?php
            	else :
            		?>
            		<fieldset class="<?php
            			if ($value['hide_if_checked']=='yes' || $value['show_if_checked']=='yes') echo 'hidden_option';
            			if ($value['hide_if_checked']=='option') echo 'hide_options_if_checked';
            			if ($value['show_if_checked']=='option') echo 'show_options_if_checked';
            		?>">
            		<?php
            	endif;

            	?>
	            <legend class="screen-reader-text"><span><?php echo $value['name'] ?></span></legend>
					<label for="<?php echo $value['id'] ?>">
					<input name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" type="checkbox" value="1" <?php checked(get_option($value['id']), 'yes'); ?> />
					<?php echo $value['desc'] ?></label> <?php if ( $value['desc_tip'] ) echo $description; ?><br />
				<?php

				if (!isset($value['checkboxgroup']) || (isset($value['checkboxgroup']) && $value['checkboxgroup']=='end')) :
					?>
						</fieldset>
					</td>
					</tr>
					<?php
				else :
					?>
					</fieldset>
					<?php
				endif;

            break;
            case 'textarea':
            	?><tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo $value['name']; ?></label>
					</th>
                    <td class="forminp">
                        <textarea <?php if ( isset($value['args']) ) echo $value['args'] . ' '; ?>name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>"><?php if (false !== get_option($value['id'])) echo esc_textarea(stripslashes(get_option($value['id']))); else echo esc_textarea( $value['std'] ); ?></textarea> <?php echo $description; ?>
                    </td>
                </tr><?php
            break;
            case 'single_select_page' :
            	$page_setting = (int) get_option($value['id']);

            	$args = array( 'name'				=> $value['id'],
            				   'id'					=> $value['id'],
            				   'sort_column' 		=> 'menu_order',
            				   'sort_order'			=> 'ASC',
            				   'show_option_none' 	=> ' ',
            				   'class'				=> $value['class'],
            				   'echo' 				=> false,
            				   'selected'			=> $page_setting);

            	if( isset($value['args']) ) $args = wp_parse_args($value['args'], $args);

            	?><tr valign="top" class="single_select_page">
                    <th scope="row" class="titledesc"><?php echo $value['name'] ?></th>
                    <td class="forminp">
			        	<?php echo str_replace(' id=', " data-placeholder='".__('Select a page&hellip;', 'woocommerce')."' style='".$value['css']."' class='".$value['class']."' id=", wp_dropdown_pages($args)); ?> <?php echo $description; ?>
			        </td>
               	</tr><?php
            break;
            case 'single_select_country' :
            	$countries = $woocommerce->countries->countries;
            	$country_setting = (string) get_option($value['id']);
            	if (strstr($country_setting, ':')) :
            		$country = current(explode(':', $country_setting));
            		$state = end(explode(':', $country_setting));
            	else :
            		$country = $country_setting;
            		$state = '*';
            	endif;
            	?><tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo $value['name']; ?></label>
					</th>
                    <td class="forminp"><select name="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>" data-placeholder="<?php _e('Choose a country&hellip;', 'woocommerce'); ?>" title="Country" class="chosen_select">
			        	<?php echo $woocommerce->countries->country_dropdown_options($country, $state); ?>
			        </select> <?php echo $description; ?>
               		</td>
               	</tr><?php
            break;
            case 'multi_select_countries' :
            	$countries = $woocommerce->countries->countries;
            	asort($countries);
            	$selections = (array) get_option($value['id']);
            	?><tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo $value['name']; ?></label>
					</th>
                    <td class="forminp">
	                    <select multiple="multiple" name="<?php echo esc_attr( $value['id'] ); ?>[]" style="width:450px;" data-placeholder="<?php _e('Choose countries&hellip;', 'woocommerce'); ?>" title="Country" class="chosen_select">
				        	<?php
				        		if ($countries) foreach ($countries as $key=>$val) :
	                    			echo '<option value="'.$key.'" '.selected( in_array($key, $selections), true, false ).'>'.$val.'</option>';
	                    		endforeach;
	                    	?>
				        </select>
               		</td>
               	</tr><?php
            break;
            default:
            	do_action( 'woocommerce_admin_field_' . $value['type'], $value );
            break;
    	}
	}
}