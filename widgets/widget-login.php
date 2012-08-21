<?php
/**
 * Login Widget
 *
 * @author 		WooThemes
 * @category 	Widgets
 * @package 	WooCommerce/Widgets
 * @version 	1.6.4
 * @extends 	WP_Widget
 */
class WooCommerce_Widget_Login extends WP_Widget {

	var $woo_widget_cssclass;
	var $woo_widget_description;
	var $woo_widget_idbase;
	var $woo_widget_name;

	/**
	 * constructor
	 *
	 * @access public
	 * @return void
	 */
	function WooCommerce_Widget_Login() {

		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'widget_login';
		$this->woo_widget_description = __( 'Display a login area and "My Account" links in the sidebar.', 'woocommerce' );
		$this->woo_widget_idbase = 'woocommerce_login';
		$this->woo_widget_name = __('WooCommerce Login', 'woocommerce' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

		/* Create the widget. */
		$this->WP_Widget('woocommerce_login', $this->woo_widget_name, $widget_ops);
	}

	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	function widget( $args, $instance ) {
		global $woocommerce;

		extract($args);

		// Don't show if on the account page since that has a login
		if (is_account_page() && !is_user_logged_in()) return;

		$logged_out_title = (!empty($instance['logged_out_title'])) ? $instance['logged_out_title'] : __('Customer Login', 'woocommerce');
		$logged_in_title = (!empty($instance['logged_in_title'])) ? $instance['logged_in_title'] : __('Welcome %s', 'woocommerce');

		echo $before_widget;

		if (is_user_logged_in()) {

			$user = get_user_by('id', get_current_user_id());

			if ( $logged_in_title ) echo $before_title . sprintf( $logged_in_title, ucwords($user->display_name) ) . $after_title;

			do_action('woocommerce_login_widget_logged_in_before_links');

			$links = apply_filters( 'woocommerce_login_widget_logged_in_links', array(
				__('My account', 'woocommerce') 	=> get_permalink(woocommerce_get_page_id('myaccount')),
				__('Change my password', 'woocommerce') => get_permalink(woocommerce_get_page_id('change_password')),
				__('Logout', 'woocommerce')		=> wp_logout_url(home_url())
			));

			if (sizeof($links>0)) :

				echo '<ul class="pagenav">';

				foreach ($links as $name => $link) :
					echo '<li><a href="'.$link.'">'.$name.'</a></li>';
				endforeach;

				echo '</ul>';

			endif;

			do_action('woocommerce_login_widget_logged_in_after_links');

		} else {

			if ( $logged_out_title ) echo $before_title . $logged_out_title . $after_title;

			do_action('woocommerce_login_widget_logged_out_before_form');

			global $login_errors;

			if ( is_wp_error($login_errors) && $login_errors->get_error_code() ) foreach ($login_errors->get_error_messages() as $error) :
				echo '<div class="woocommerce_error">' . $error . "</div>\n";
				break;
			endforeach;

			// Get redirect URL
			$redirect_to = apply_filters( 'woocommerce_login_widget_redirect', get_permalink(woocommerce_get_page_id('myaccount')) );
			?>
			<form method="post">

				<p><label for="user_login"><?php _e('Username or email', 'woocommerce'); ?></label> <input name="log" value="<?php if (isset($_POST['log'])) echo esc_attr(stripslashes($_POST['log'])); ?>" class="input-text" id="user_login" type="text" /></p>

				<p><label for="user_pass"><?php _e('Password', 'woocommerce'); ?></label> <input name="pwd" class="input-text" id="user_pass" type="password" /></p>

				<p><input type="submit" class="submitbutton" name="wp-submit" id="wp-submit" value="<?php _e('Login &rarr;', 'woocommerce'); ?>" /> <a href="<?php echo wp_lostpassword_url(); ?>"><?php echo __('Lost password?', 'woocommerce'); ?></a></p>

				<div>
					<input type="hidden" name="redirect_to" class="redirect_to" value="<?php echo $redirect_to; ?>" />
					<input type="hidden" name="testcookie" value="1" />
					<input type="hidden" name="woocommerce_login" value="1" />
					<input type="hidden" name="rememberme" value="forever" />
				</div>

				<?php do_action('woocommerce_login_widget_logged_out_form'); ?>

			</form>
			<?php
			do_action('woocommerce_login_widget_logged_out_after_form');

			$woocommerce->add_inline_js("
				// Ajax Login
				jQuery('.widget_login form').submit(function(){

					var thisform = this;

					jQuery(thisform).block({ message: null, overlayCSS: {
				        backgroundColor: '#fff',
				        opacity:         0.6
				    } });

				    var data = {
						action: 		'woocommerce_sidebar_login_process',
						security: 		'".wp_create_nonce("woocommerce-sidebar-login-action")."',
						user_login: 	jQuery('input[name=\"log\"]', thisform).val(),
						user_password: 	jQuery('input[name=\"pwd\"]', thisform).val(),
						redirect_to:	jQuery('.redirect_to:eq(0)', thisform).val()
					};

					// Ajax action
					jQuery.ajax({
						url: '" . ( ( is_ssl() || force_ssl_admin() || force_ssl_login() ) ? str_replace( 'http:', 'https:', admin_url( 'admin-ajax.php' ) ) : str_replace( 'https:', 'http:', admin_url( 'admin-ajax.php' ) ) ) . "',
						data: data,
						type: 'GET',
						dataType: 'jsonp',
						success: function( result ) {
							jQuery('.woocommerce_error').remove();

							if (result.success==1) {
								window.location = result.redirect;
							} else {
								jQuery(thisform).prepend('<div class=\"woocommerce_error\">' + result.error + '</div>');
								jQuery(thisform).unblock();
							}
						}

					});

					return false;
				});
			");

			$links = apply_filters( 'woocommerce_login_widget_logged_out_links', array());

			if (sizeof($links>0)) :

				echo '<ul class="pagenav">';

				foreach ($links as $name => $link) :
					echo '<li><a href="'.$link.'">'.$name.'</a></li>';
				endforeach;

				echo '</ul>';

			endif;

		}

		echo $after_widget;
	}

	/**
	 * update function.
	 *
	 * @see WP_Widget->update
	 * @access public
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	function update( $new_instance, $old_instance ) {
		$instance['logged_out_title'] = strip_tags(stripslashes($new_instance['logged_out_title']));
		$instance['logged_in_title'] = strip_tags(stripslashes($new_instance['logged_in_title']));
		return $instance;
	}

	/**
	 * form function.
	 *
	 * @see WP_Widget->form
	 * @access public
	 * @param array $instance
	 * @return void
	 */
	function form( $instance ) {
		?>

		<p><label for="<?php echo $this->get_field_id('logged_out_title'); ?>"><?php _e('Logged out title:', 'woocommerce') ?></label>
		<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('logged_out_title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('logged_out_title') ); ?>" value="<?php if (isset ( $instance['logged_out_title'])) echo esc_attr( $instance['logged_out_title'] ); else echo __('Customer Login', 'woocommerce'); ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('logged_in_title'); ?>"><?php _e('Logged in title:', 'woocommerce') ?></label>
		<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('logged_in_title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('logged_in_title') ); ?>" value="<?php if (isset ( $instance['logged_in_title'])) echo esc_attr( $instance['logged_in_title'] ); else echo __('Welcome %s', 'woocommerce'); ?>" /></p>

		<?php
	}

}


/**
 * Process the login.
 *
 * @access public
 * @package 	WooCommerce/Widgets
 * @return void
 */
function woocommerce_sidebar_login_process() {

	if (isset($_POST['woocommerce_login'])) {

		global $login_errors;

		// Get redirect URL
		$redirect_to = apply_filters( 'woocommerce_login_widget_redirect', get_permalink(woocommerce_get_page_id('myaccount')) );

		// Check for Secure Cookie
		$secure_cookie = '';

		// If the user wants ssl but the session is not ssl, force a secure cookie.
		if ( !empty($_POST['log']) && !force_ssl_admin() ) {
			$user_name = sanitize_user($_POST['log']);
			if ( $user = get_user_by('login', $user_name) ) {
				if ( get_user_option('use_ssl', $user->ID) ) {
					$secure_cookie = true;
					force_ssl_admin(true);
				}
			}
		}

		if ( force_ssl_admin() ) $secure_cookie = true;
		if ( $secure_cookie == '' && force_ssl_login() ) $secure_cookie = false;

		// Login
		$user = wp_signon( '', $secure_cookie );

		// Redirect filter
		if ( $secure_cookie && strstr($redirect_to, 'wp-admin') ) $redirect_to = str_replace('http:', 'https:', $redirect_to);

		// Check the username
		if ( !$_POST['log'] ) :
			$user = new WP_Error();
			$user->add('empty_username', '<strong>' . __('ERROR', 'woocommerce') . '</strong>: ' . __('Please enter a username.', 'woocommerce'));
		elseif ( !$_POST['pwd'] ) :
			$user = new WP_Error();
			$user->add('empty_username', '<strong>' . __('ERROR', 'woocommerce') . '</strong>: ' . __('Please enter your password.', 'woocommerce'));
		endif;

		// Redirect if successful
		if ( !is_wp_error($user) ) :
			wp_safe_redirect( $redirect_to );
			exit;
		endif;

		$login_errors = $user;

	}
}

add_action( 'init', 'woocommerce_sidebar_login_process', 0 );