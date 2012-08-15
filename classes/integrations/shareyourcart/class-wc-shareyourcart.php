<?php
/**
 * ShareYourCart Integration
 *
 * Enables ShareYourCart integration.
 *
 * @class 		WC_ShareYourCart
 * @extends		WC_Integration
 * @version		1.6.4
 * @package		WooCommerce/Classes/Integrations
 * @author 		WooThemes
 */
class WC_ShareYourCart extends WC_Integration {

	var $ShareYourCartWooCommerce;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
        $this->id					= 'shareyourcart';
        $this->method_title     	= __( 'ShareYourCart', 'woocommerce' );
        $this->method_description	= sprintf( __( 'Increase your social media exposure by 10 percent! ShareYourCart helps you get more customers by motivating satisfied customers to talk with their friends about your products. For help with ShareYourCart view the <a href="%s">documentation</a>.', 'woocommerce' ), 'http://www.woothemes.com/woocommerce-docs/user-guide/shareyourcart/' );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->enabled 		= $this->settings['enabled'];
		$this->client_id	= $this->settings['client_id'];
		$this->app_key		= $this->settings['app_key'];
		$this->email		= $this->settings['email'];

		// Actions
		add_action( 'woocommerce_update_options_integration_shareyourcart', array( &$this, 'process_admin_options') );
		add_action( 'woocommerce_update_options_integration_shareyourcart', array( &$this, 'process_forms') );

		// Actions if enabled
		if ( $this->enabled == 'yes' ) {
			add_action( 'wp_enqueue_scripts', array(&$this, 'styles') );

			$this->init_share_your_cart();
		}
    }

	/**
	 * styles function.
	 *
	 * @access public
	 * @return void
	 */
	public function styles() {
		wp_enqueue_style( 'shareyourcart', plugins_url( 'css/style.css', __FILE__ ) );
	}

	/**
	 * init_share_your_cart function.
	 *
	 * @access public
	 * @return void
	 */
	function init_share_your_cart() {

		if ( empty( $this->shareYourCartWooCommerce ) ) {
			// Share your cart api class
			include_once('class-shareyourcart-woocommerce.php');

			// Init the class
			$this->shareYourCartWooCommerce = new ShareYourCartWooCommerce( $this->settings );
		}

	}

	/**
	 * process_forms function.
	 *
	 * @access public
	 */
	function process_forms() {

		if ( ! empty( $_REQUEST['syc-account'] ) && $_REQUEST['syc-account'] == 'create' ) {

			$this->init_share_your_cart();

			$error = $message = '';

			$redirect = remove_query_arg( 'saved' );
			$redirect = remove_query_arg( 'wc_error', $redirect );
			$redirect = remove_query_arg( 'wc_message', $redirect );

			if ( ! empty( $_POST['domain'] ) && ! empty( $_POST['email'] ) && ! empty( $_POST['syc-terms-agreement'] ) ) {
				if ( ! ( ( $register = $this->shareYourCartWooCommerce->register( $this->shareYourCartWooCommerce->getSecretKey(), $_POST['domain'], $_POST['email'], $message ) ) === false) ) {

					$this->shareYourCartWooCommerce->setConfigValue('app_key', $register['app_key']);
					$this->shareYourCartWooCommerce->setConfigValue('client_id', $register['client_id']);

					$redirect = remove_query_arg( 'syc-account', $redirect );
				} else {

					$error = $message;

					if ( json_decode($error) ) {
						$error = json_decode($error);
						$error = $error->message;
					}

					$message = '';

				}

			} else {

				$error = __( 'Please complete all fields.', 'woocommerce' );

			}

			if ( $error ) $redirect = add_query_arg( 'wc_error', urlencode( esc_attr( $error ) ), $redirect );
			if ( $message ) $redirect = add_query_arg( 'wc_message', urlencode( esc_attr( $message ) ), $redirect );

			wp_safe_redirect( $redirect );
			exit;

		} elseif ( ! empty( $_REQUEST['syc-account'] ) && $_REQUEST['syc-account'] == 'recover' ) {

			$this->init_share_your_cart();

			$error = $message = '';

			$redirect = remove_query_arg( 'saved' );
			$redirect = remove_query_arg( 'wc_error', $redirect );
			$redirect = remove_query_arg( 'wc_message', $redirect );

			if ( ! empty( $_POST['domain'] ) && ! empty( $_POST['email'] ) ) {

				if ( ! $this->shareYourCartWooCommerce->recover( $this->shareYourCartWooCommerce->getSecretKey(), $_POST['domain'], $_POST['email'], $message ) ) {

					$error = $message;

					if ( json_decode($error) ) {
						$error = json_decode($error);
						$error = $error->message;
					}

					$message = '';

				} else {
					$redirect = remove_query_arg( 'syc-account', $redirect );
				}

			} else {

				$error = __( 'Please complete all fields.', 'woocommerce' );

			}

			if ( $error ) $redirect = add_query_arg( 'wc_error', urlencode( esc_attr( $error ) ), $redirect );
			if ( $message ) $redirect = add_query_arg( 'wc_message', urlencode( esc_attr( $message ) ), $redirect );

			wp_safe_redirect( $redirect );
			exit;

		}

	}

	/**
	 * Admin Options
	 *
	 * Setup the gateway settings screen.
	 * Override this in your gateway.
	 *
	 * @since 1.0.0
	 */
	function admin_options() {

		if ( $this->enabled == 'yes' ) {
			// Installation
			$this->shareYourCartWooCommerce->install();

			// Check status
			$this->shareYourCartWooCommerce->admin_settings_page();
		}
		?>

		<?php
		if ( ! $this->client_id && ! $this->app_key ) {

			if ( ! empty( $_REQUEST['syc-account'] ) && $_REQUEST['syc-account'] == 'create' ) {

				?>
				<div class="updated">
					<h3><?php _e('Create a ShareYourCart account', 'woocommerce') ?></h3>
					<table class="form-table">
						<tr>
							<th><?php _e('Domain', 'woocommerce'); ?></th>
							<td><input type="text" name="domain" id="domain"  class="regular-text" value="<?php echo (isset($_POST['domain']) ? $_POST['domain'] : site_url()); ?>" /></td>
						</tr>
						<tr>
							<th><?php _e('Email', 'woocommerce'); ?></th>
							<td><input type="text" name="email" id="email" class="regular-text" value="<?php echo (isset($_POST['email']) ? $_POST['email'] : get_option('admin_email')); ?>"/></td>
						</tr>
						<tr>
							<th>&nbsp;</th>
							<td><input class="buttonCheckbox" name="syc-terms-agreement" id="syc-terms-agreement" type="checkbox" /> <label for="syc-terms-agreement"><?php printf(__('I agree to the <a href="%s">ShareYourCart terms and conditions</a>', 'woocommerce'), 'http://shareyourcart.com/terms'); ?></label></td>
						</tr>
					</table>
					<p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Create Account', 'woocommerce') ?>" /></p>
				</div>
				<?php

			} elseif ( ! empty( $_REQUEST['syc-account'] ) && $_REQUEST['syc-account'] == 'recover' ) {

				?>
				<div class="updated">
					<h3><?php _e('Recover your ShareYourCart account', 'woocommerce') ?></h3>
					<table class="form-table">
						<tr>
							<th><?php _e('Domain', 'woocommerce'); ?></th>
							<td><input type="text" name="domain" id="domain"  class="regular-text" value="<?php echo (isset($_POST['domain']) ? $_POST['domain'] : site_url()); ?>" /></td>
						</tr>
						<tr>
							<th><?php _e('Email', 'woocommerce'); ?></th>
							<td><input type="text" name="email" id="email" class="regular-text" value="<?php echo (isset($_POST['email']) ? $_POST['email'] : get_option('admin_email')); ?>"/></td>
						</tr>
					</table>
					<p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Email me my details', 'woocommerce') ?>" /></p>
				</div>
				<?php

			} else {

				?>
				<div id="wc_get_started">
					<span class="main"><?php _e('Setup your ShareYourCart account', 'woocommerce'); ?></span>
					<span><?php echo $this->method_description; ?></span>
					<p><a href="<?php echo add_query_arg( 'syc-account', 'create', admin_url( 'admin.php?page=woocommerce_settings&tab=integration&section=shareyourcart' ) ); ?>" class="button button-primary api-link"><?php _e('Create an account', 'woocommerce'); ?></a> <a href="<?php echo add_query_arg( 'syc-account', 'recover', admin_url( 'admin.php?page=woocommerce_settings&tab=integration&section=shareyourcart' ) ); ?>" class="button api-link"><?php _e('Can\'t access your account?', 'woocommerce'); ?></a></p>
				</div>
				<?php

			}
		} else {
			echo '<h3><a href="http://shareyourcart.com/"><img src="' . plugins_url( 'sdk/img/shareyourcart-logo.png', __FILE__ ) . '" alt="ShareYourCart" /></a></h3>';
			echo wpautop( $this->method_description );
		}
		?>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
			<?php if ( $this->client_id && $this->app_key ) : ?>
				<tr>
					<th><?php _e('Configure ShareYourCart', 'woocommerce'); ?></th>
					<td>
						<p><?php _e('You can choose how much of a discount to give (in fixed amount, percentage, or free shipping) and to which social media channels it should it be applied. You can also define what the advertisement should say, so that it fully benefits your sales.', 'woocommerce'); ?></p>
						<p><a href="http://www.shareyourcart.com/configure/?app_key=<?php echo $this->app_key; ?>&amp;client_id=<?php echo $this->client_id; ?>&amp;email=<?php echo $this->email; ?>" class="button" target="_blank"><?php _e('Configure', 'woocommerce'); ?></a></p>
					</td>
				</tr>
			<?php endif; ?>
		</table>
		<script type="text/javascript">

			jQuery('#woocommerce_shareyourcart_button_style').change(function(){

				var value = jQuery(this).val();

				if ( value == 'standard_button' ) {
					jQuery('.standard_button').closest('tr').show();
					jQuery('.image_button').closest('tr').hide();
					jQuery('.custom_html').closest('tr').hide();
				}
				if ( value == 'image_button' ) {
					jQuery('.standard_button').closest('tr').hide();
					jQuery('.image_button').closest('tr').show();
					jQuery('.custom_html').closest('tr').hide();
				}
				if ( value == 'custom_html' ) {
					jQuery('.standard_button').closest('tr').hide();
					jQuery('.image_button').closest('tr').hide();
					jQuery('.custom_html').closest('tr').show();
				}

			}).change();

		</script>

		<!-- Section -->
		<div><input type="hidden" name="section" value="<?php echo $this->id; ?>" /></div>

		<?php
	}


	/**
     * Initialise Settings Form Fields
     */
    function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
				'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
				'label'		 	=> __( 'Enable ShareYourCart integration', 'woocommerce' ),
				'type' 			=> 'checkbox',
				'default' 		=> 'no',
			),
			'client_id' => array(
				'title' 		=> __( 'Client ID', 'woocommerce' ),
				'description'	=> __( 'Get your client ID by creating a ShareYourCart account.', 'woocommerce' ),
				'type' 			=> 'text',
				'default' 		=> '',
				'css'			=> 'width: 300px'
			),
			'app_key' => array(
				'title' 		=> __( 'App Key', 'woocommerce' ),
				'description'	=> __( 'Get your app key by creating a ShareYourCart account.', 'woocommerce' ),
				'type' 			=> 'text',
				'default' 		=> '',
				'css'			=> 'width: 300px'
			),
			'email' => array(
				'title' 		=> __( 'Email address', 'woocommerce' ),
				'description'	=> __( 'The email address you used to sign up for ShareYourCart.', 'woocommerce' ),
				'type' 			=> 'text',
				'default' 		=> get_option('admin_email'),
				'css'			=> 'width: 300px'
			),
			'show_on_product' => array(
				'title' 		=> __( 'Show button by default on:', 'woocommerce' ),
				'label'		 	=> __( 'Product page', 'woocommerce' ),
				'type' 			=> 'checkbox',
				'default' 		=> 'yes',
			),
			'show_on_cart' => array(
				'label'		 	=> __( 'Cart page', 'woocommerce' ),
				'type' 			=> 'checkbox',
				'default' 		=> 'yes',
			),
			'button_style' => array(
				'title' 		=> __( 'Button style', 'woocommerce' ),
				'description' 	=> __( 'Select a style for your share buttons', 'woocommerce' ),
				'default' 		=> 'standard_button',
				'type' 			=> 'select',
				'options' => array(
					'standard_button' => __( 'Standard Button', 'woocommerce' ),
					'custom_html' => __( 'Custom HTML', 'woocommerce' )
				)
			),
			'button_skin' => array(
				'title' 		=> __( 'Button skin', 'woocommerce' ),
				'description' 	=> __( 'Select a skin for your share buttons', 'woocommerce' ),
				'default' 		=> 'orange',
				'type' 			=> 'select',
				'options' => array(
					'orange' => __( 'Orange', 'woocommerce' ),
					'blue' => __( 'Blue', 'woocommerce' ),
					'light' => __( 'Light', 'woocommerce' ),
					'dark' => __( 'Dark', 'woocommerce' )
				),
				'class'			=> 'standard_button'
			),
			'button_position' => array(
				'title' 		=> __( 'Button position', 'woocommerce' ),
				'description' 	=> __( 'Where should the button be positioned?', 'woocommerce' ),
				'default' 		=> 'normal',
				'type' 			=> 'select',
				'options' => array(
					'normal' 	=> __( 'Normal', 'woocommerce' ),
					'floating' 	=> __( 'Floating', 'woocommerce' )
				),
				'class'			=> 'standard_button'
			),
			'button_html' => array(
				'title' 		=> __( 'HTML for the button', 'woocommerce' ),
				'description' 	=> __( 'Enter the HTML code for your custom button.', 'woocommerce' ),
				'default' 		=> '<button>Get a <div class="shareyourcart-discount"></div> discount</button>',
				'type' 			=> 'textarea',
				'class'			=> 'custom_html'
			)
		);

    } // End init_form_fields()

}


/**
 * Add the integration to WooCommerce
 *
 * @package		WooCommerce/Classes/Integrations
 * @access public
 * @param mixed $integrations
 * @return void
 */
function add_shareyourcart_integration( $integrations ) {
	if ( ! class_exists('ShareYourCartAPI') ) // Only allow this integration if we're not already using shareyourcart via another plugin
		$integrations[] = 'WC_ShareYourCart';
	return $integrations;
}
add_filter('woocommerce_integrations', 'add_shareyourcart_integration' );
