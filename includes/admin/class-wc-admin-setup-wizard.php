<?php
/**
 * Setup Wizard Class
 *
 * Takes new users through some basic steps to setup their store.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.4.0
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Setup_Wizard class
 */
class WC_Admin_Setup_Wizard {

	/** @var string Currenct Step */
	private $step   = '';

	/** @var array Steps for the setup wizard */
	private $steps  = array();

	/** @var array Tweets user can optionally send after install */
	private $tweets = array(
		'Someone give me woo-t, I just set up a new store with #WordPress and @WooCommerce!',
		'Someone give me high five, I just set up a new store with #WordPress and @WooCommerce!'
	);

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		if ( apply_filters( 'woocommerce_enable_setup_wizard', true ) && current_user_can( 'manage_woocommerce' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menus' ) );
			add_action( 'admin_init', array( $this, 'setup_wizard' ) );
		}
	}

	/**
	 * Add admin menus/screens.
	 */
	public function admin_menus() {
		add_dashboard_page( '', '', 'manage_options', 'wc-setup', '' );
	}

	/**
	 * Show the setup wizard
	 */
	public function setup_wizard() {
		if ( empty( $_GET['page'] ) || 'wc-setup' !== $_GET['page'] ) {
			return;
		}
		$this->steps = array(
			'introduction' => array(
				'name'    =>  __( 'Introduction', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_introduction' ),
				'handler' => ''
			),
			'pages' => array(
				'name'    =>  __( 'Page Setup', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_pages' ),
				'handler' => array( $this, 'wc_setup_pages_save' )
			),
			'locale' => array(
				'name'    =>  __( 'Store Locale', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_locale' ),
				'handler' => array( $this, 'wc_setup_locale_save' )
			),
			'shipping_taxes' => array(
				'name'    =>  __( 'Shipping &amp; Tax', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_shipping_taxes' ),
				'handler' => array( $this, 'wc_setup_shipping_taxes_save' ),
			),
			'payments' => array(
				'name'    =>  __( 'Payments', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_payments' ),
				'handler' => array( $this, 'wc_setup_payments_save' ),
			),
			'next_steps' => array(
				'name'    =>  __( 'Ready!', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_ready' ),
				'handler' => ''
			)
		);
		$this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );
		$suffix     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'select2', WC()->plugin_url() . '/assets/js/select2/select2' . $suffix . '.js', array( 'jquery' ), '3.5.2' );
		wp_register_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js', array( 'jquery', 'select2' ), WC_VERSION );
		wp_localize_script( 'wc-enhanced-select', 'wc_enhanced_select_params', array(
			'i18n_matches_1'            => _x( 'One result is available, press enter to select it.', 'enhanced select', 'woocommerce' ),
			'i18n_matches_n'            => _x( '%qty% results are available, use up and down arrow keys to navigate.', 'enhanced select', 'woocommerce' ),
			'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
			'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
			'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
			'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
			'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
			'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
			'ajax_url'                  => admin_url( 'admin-ajax.php' ),
			'search_products_nonce'     => wp_create_nonce( 'search-products' ),
			'search_customers_nonce'    => wp_create_nonce( 'search-customers' )
		) );
		wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
		wp_enqueue_style( 'wc-setup', WC()->plugin_url() . '/assets/css/wc-setup.css', array( 'dashicons', 'install' ), WC_VERSION );

		wp_register_script( 'wc-setup', WC()->plugin_url() . '/assets/js/admin/wc-setup.min.js', array( 'jquery', 'wc-enhanced-select', 'jquery-blockui' ), WC_VERSION );
		wp_localize_script( 'wc-setup', 'wc_setup_params', array(
			'locale_info' => json_encode( include( WC()->plugin_path() . '/i18n/locale-info.php' ) )
		) );

		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'] );
		}

		ob_start();
		$this->setup_wizard_header();
		$this->setup_wizard_steps();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	}

	public function get_next_step_link() {
		$keys = array_keys( $this->steps );
		return add_query_arg( 'step', $keys[ array_search( $this->step, array_keys( $this->steps ) ) + 1 ], remove_query_arg( 'translation_updated' ) );
	}

	/**
	 * Setup Wizard Header
	 */
	public function setup_wizard_header() {
		?>
		<!DOCTYPE html>
		<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php _e( 'WooCommerce &rsaquo; Setup Wizard', 'woocommerce' ); ?></title>
			<?php wp_print_scripts( 'wc-setup' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="wc-setup wp-core-ui">
			<h1 id="wc-logo"><a href="http://woothemes.com/woocommerce"><img src="<?php echo WC()->plugin_url(); ?>/assets/images/woocommerce_logo.png" alt="WooCommerce" /></a></h1>
		<?php
	}

	/**
	 * Setup Wizard Footer
	 */
	public function setup_wizard_footer() {
		?>
			<?php if ( 'next_steps' === $this->step ) : ?>
				<a class="wc-return-to-dashboard" href="<?php echo esc_url( admin_url() ); ?>"><?php _e( 'Return to the WordPress Dashboard', 'woocommerce' ); ?></a>
			<?php endif; ?>
			</body>
		</html>
		<?php
	}

	/**
	 * Output the steps
	 */
	public function setup_wizard_steps() {
		$ouput_steps = $this->steps;
		array_shift( $ouput_steps );
		?>
		<ol class="wc-setup-steps">
			<?php foreach ( $ouput_steps as $step_key => $step ) : ?>
				<li class="<?php
					if ( $step_key === $this->step ) {
						echo 'active';
					} elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
						echo 'done';
					}
				?>"><?php echo esc_html( $step['name'] ); ?></li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	/**
	 * Output the content for the current step
	 */
	public function setup_wizard_content() {
		echo '<div class="wc-setup-content">';
		call_user_func( $this->steps[ $this->step ]['view'] );
		echo '</div>';
	}

	/**
	 * Introduction step
	 */
	public function wc_setup_introduction() {
		$locale = get_locale();

		if ( isset( $_GET['translation_updated'] ) ) {
			WC_Language_Pack_Upgrader::language_update_messages();
		} elseif( 'en_US' !== $locale && WC_Language_Pack_Upgrader::has_available_update( $locale ) ) {
			?>
			<div class="woocommerce-message woocommerce-language-pack">
				<p><?php printf( esc_html__( 'WooCommerce is available in %s. Would you like to use this translation?', 'woocommerce' ), $locale ); ?></p>
				<p class="submit">
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'index.php?page=wc-setup&action=translation_upgrade' ), 'setup_language' ) ); ?>" class="button-primary"><?php _e( 'Install Translation', 'woocommerce' ); ?></a>
				</p>
			</div>
			<?php
		}
		?>
		<h1><?php _e( 'Welcome to the world of WooCommerce!', 'woocommerce' ); ?></h1>
		<p><?php _e( 'Thank you for choosing WooCommerce to power your online store! This quick setup wizard will help you configure the basic settings. <strong>It’s completely optional and shouldn’t take longer than five minutes.</strong>', 'woocommerce' ); ?></p>
		<p><?php _e( 'No time right now? If you don’t want to go through the wizard, you can skip and return to the WordPress dashboard. Come back anytime if you change your mind!', 'woocommerce' ); ?></p>
		<p class="wc-setup-actions step">
			<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large button-next"><?php _e( 'Let\'s Go!', 'woocommerce' ); ?></a>
			<a href="<?php echo esc_url( wp_get_referer() ? wp_get_referer() : admin_url( 'plugins.php' ) ); ?>" class="button button-large"><?php _e( 'Not right now', 'woocommerce' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Page setup
	 */
	public function wc_setup_pages() {
		?>
		<h1><?php _e( 'Page Setup', 'woocommerce' ); ?></h1>
		<form method="post">
			<p><?php printf( __( 'Your store needs a few essential %spages%s. The following will be created automatically (if they do not already exist):', 'woocommerce' ), '<a href="' . esc_url( admin_url( 'edit.php?post_type=page' ) ) . '" target="_blank">', '</a>' ); ?></p>
			<table class="wc-setup-pages" cellspacing="0">
				<thead>
					<tr>
						<th class="page-name"><?php _e( 'Page Name', 'woocommerce' ); ?></th>
						<th class="page-description"><?php _e( 'Description', 'woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="page-name"><?php echo _x( 'Shop', 'Page title', 'woocommerce' ); ?></td>
						<td><?php _e( 'The shop page will display your products.', 'woocommerce' ); ?></td>
					</tr>
					<tr>
						<td class="page-name"><?php echo _x( 'Cart', 'Page title', 'woocommerce' ); ?></td>
						<td><?php _e( 'The cart page will be where the customers go to view their cart and begin checkout.', 'woocommerce' ); ?></td>
					</tr>
					<tr>
						<td class="page-name"><?php echo _x( 'Checkout', 'Page title', 'woocommerce' ); ?></td>
						<td>
							<?php _e( 'The checkout page will be where the customers go to pay for their items.', 'woocommerce' ); ?>
						</td>
					</tr>
					<tr>
						<td class="page-name"><?php echo _x( 'My Account', 'Page title', 'woocommerce' ); ?></td>
						<td>
							<?php _e( 'Registered customers will be able to manage their account details and view past orders on this page.', 'woocommerce' ); ?>
						</td>
					</tr>
				</tbody>
			</table>

			<p><?php printf( __( 'Once created, these pages can be managed from your admin dashboard on the %sPages screen%s. You can control which pages are shown on your website via %sAppearance > Menus%s.', 'woocommerce' ), '<a href="' . esc_url( admin_url( 'edit.php?post_type=page' ) ) . '" target="_blank">', '</a>', '<a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '" target="_blank">', '</a>' ); ?></p>

			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'woocommerce' ); ?>" name="save_step" />
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'woocommerce' ); ?></a>
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Save Page Settings
	 */
	public function wc_setup_pages_save() {
		check_admin_referer( 'wc-setup' );

		WC_Install::create_pages();
		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Locale settings
	 */
	public function wc_setup_locale() {
		$user_location  = WC_Geolocation::geolocate_ip();
		$country        = ! empty( $user_location['country'] ) ? $user_location['country'] : 'US';
		$state          = ! empty( $user_location['state'] ) ? $user_location['state'] : '*';
		$state          = 'US' === $country && '*' === $state ? 'AL' : $state;

		// Defaults
		$currency       = get_option( 'woocommerce_currency', 'GBP' );
		$currency_pos   = get_option( 'woocommerce_currency_pos', 'left' );
		$decimal_sep    = get_option( 'woocommerce_price_decimal_sep', '.' );
		$thousand_sep   = get_option( 'woocommerce_price_thousand_sep', ',' );
		$dimension_unit = get_option( 'woocommerce_dimension_unit', 'cm' );
		$weight_unit    = get_option( 'woocommerce_weight_unit', 'kg' );
		?>
		<h1><?php _e( 'Store Locale Setup', 'woocommerce' ); ?></h1>
		<form method="post">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="store_location"><?php _e( 'Where is your store based?', 'woocommerce' ); ?></label></th>
					<td>
					<select id="store_location" name="store_location" style="width:100%;" required data-placeholder="<?php esc_attr_e( 'Choose a country&hellip;', 'woocommerce' ); ?>" class="wc-enhanced-select">
							<?php WC()->countries->country_dropdown_options( $country, $state ); ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="currency_code"><?php _e( 'Which currency will your store use?', 'woocommerce' ); ?></label></th>
					<td>
						<select id="currency_code" name="currency_code" required style="width:100%;" data-placeholder="<?php esc_attr_e( 'Choose a currency&hellip;', 'woocommerce' ); ?>" class="wc-enhanced-select">
							<option value=""><?php _e( 'Choose a currency&hellip;', 'woocommerce' ); ?></option>
							<?php
							foreach ( get_woocommerce_currencies() as $code => $name ) {
								echo '<option value="' . esc_attr( $code ) . '" ' . checked( $currency, $code, false ) . '>' . esc_html( $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')' ) . '</option>';
							}
							?>
						</select>
						<span class="description"><?php printf( __( 'If your currency is not listed you can %sadd it later%s.', 'woocommerce' ), '<a href="http://docs.woothemes.com/document/add-a-custom-currency-symbol/" target="_blank">', '</a>' ); ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="currency_pos"><?php _e( 'Currency Position', 'woocommerce' ); ?></label></th>
					<td>
						<select id="currency_pos" name="currency_pos" class="wc-enhanced-select">
							<option value="left" <?php selected( $currency_pos, 'left' ); ?>><?php echo __( 'Left', 'woocommerce' ); ?></option>
							<option value="right" <?php selected( $currency_pos, 'right' ); ?>><?php echo __( 'Right', 'woocommerce' ); ?></option>
							<option value="left_space" <?php selected( $currency_pos, 'left_space' ); ?>><?php echo __( 'Left with space', 'woocommerce' ); ?></option>
							<option value="right_space" <?php selected( $currency_pos, 'right_space' ); ?>><?php echo __( 'Right with space', 'woocommerce' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="thousand_sep"><?php _e( 'Thousand Separator', 'woocommerce' ); ?></label></th>
					<td>
						<input type="text" id="thousand_sep" name="thousand_sep" size="2" value="<?php echo esc_attr( $thousand_sep ) ; ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="decimal_sep"><?php _e( 'Decimal Separator', 'woocommerce' ); ?></label></th>
					<td>
						<input type="text" id="decimal_sep" name="decimal_sep" size="2" value="<?php echo esc_attr( $decimal_sep ) ; ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="weight_unit"><?php _e( 'Which unit should be used for product weights?', 'woocommerce' ); ?></label></th>
					<td>
						<select id="weight_unit" name="weight_unit" class="wc-enhanced-select">
							<option value="kg" <?php selected( $weight_unit, 'kg' ); ?>><?php echo __( 'kg', 'woocommerce' ); ?></option>
							<option value="g" <?php selected( $weight_unit, 'g' ); ?>><?php echo __( 'g', 'woocommerce' ); ?></option>
							<option value="lbs" <?php selected( $weight_unit, 'lbs' ); ?>><?php echo __( 'lbs', 'woocommerce' ); ?></option>
							<option value="oz" <?php selected( $weight_unit, 'oz' ); ?>><?php echo __( 'oz', 'woocommerce' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dimension_unit"><?php _e( 'Which unit should be used for product dimensions?', 'woocommerce' ); ?></label></th>
					<td>
						<select id="dimension_unit" name="dimension_unit" class="wc-enhanced-select">
							<option value="m" <?php selected( $dimension_unit, 'm' ); ?>><?php echo __( 'm', 'woocommerce' ); ?></option>
							<option value="cm" <?php selected( $dimension_unit, 'cm' ); ?>><?php echo __( 'cm', 'woocommerce' ); ?></option>
							<option value="mm" <?php selected( $dimension_unit, 'mm' ); ?>><?php echo __( 'mm', 'woocommerce' ); ?></option>
							<option value="in" <?php selected( $dimension_unit, 'in' ); ?>><?php echo __( 'in', 'woocommerce' ); ?></option>
							<option value="yd" <?php selected( $dimension_unit, 'yd' ); ?>><?php echo __( 'yd', 'woocommerce' ); ?></option>
						</select>
					</td>
				</tr>
			</table>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'woocommerce' ); ?>" name="save_step" />
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'woocommerce' ); ?></a>
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Save Locale Settings
	 */
	public function wc_setup_locale_save() {
		check_admin_referer( 'wc-setup' );

		$store_location = sanitize_text_field( $_POST['store_location'] );
		$currency_code  = sanitize_text_field( $_POST['currency_code'] );
		$currency_pos   = sanitize_text_field( $_POST['currency_pos'] );
		$decimal_sep    = sanitize_text_field( $_POST['decimal_sep'] );
		$thousand_sep   = sanitize_text_field( $_POST['thousand_sep'] );
		$weight_unit    = sanitize_text_field( $_POST['weight_unit'] );
		$dimension_unit = sanitize_text_field( $_POST['dimension_unit'] );

		update_option( 'woocommerce_default_country', $store_location );
		update_option( 'woocommerce_currency', $currency_code );
		update_option( 'woocommerce_currency_pos', $currency_pos );
		update_option( 'woocommerce_price_decimal_sep', $decimal_sep );
		update_option( 'woocommerce_price_thousand_sep', $thousand_sep );
		update_option( 'woocommerce_weight_unit', $weight_unit );
		update_option( 'woocommerce_dimension_unit', $dimension_unit );

		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Shipping and taxes
	 */
	public function wc_setup_shipping_taxes() {
		$domestic                         = new WC_Shipping_Flat_Rate();
		$international                    = new WC_Shipping_International_Delivery();
		$shipping_cost_domestic           = '';
		$shipping_cost_international      = '';

		if ( 'yes' === $domestic->get_option( 'enabled' ) ) {
			$shipping_cost_domestic      = $domestic->get_option( 'cost' );
		}

		if ( 'yes' === $international->get_option( 'enabled' ) ) {
			$shipping_cost_international      = $international->get_option( 'cost' );
		}
		?>
		<h1><?php _e( 'Shipping &amp; Tax Setup', 'woocommerce' ); ?></h1>
		<form method="post">
			<p><?php printf( __( 'If you will be charging sales tax, or shipping physical goods to customers, you can configure the basic options below. This is optional and can be changed later via %1$sWooCommerce > Settings > Tax%3$s and %2$sWooCommerce > Settings > Shipping%3$s.', 'woocommerce' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=tax' ) . '" target="_blank">', '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping' ) . '" target="_blank">', '</a>' ); ?></p>
			<table class="form-table">
				<tr class="section_title">
					<td colspan="2">
						<h2><?php _e( 'Basic Shipping Setup', 'woocommerce' ); ?></h2>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="woocommerce_calc_shipping"><?php _e( 'Will you be shipping products?', 'woocommerce' ); ?></label></th>
					<td>
						<input type="checkbox" id="woocommerce_calc_shipping" <?php checked( get_option( 'woocommerce_calc_shipping', 'no' ), 'yes' ); ?> name="woocommerce_calc_shipping" class="input-checkbox" value="1" />
						<label for="woocommerce_calc_shipping"><?php _e( 'Yes, I will be shipping physical goods to customers', 'woocommerce' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="shipping_cost_domestic"><?php _e( '<strong>Domestic</strong> shipping costs:', 'woocommerce' ); ?></label></th>
					<td>
						<?php printf( __( 'A total of %s per order and/or %s per item', 'woocommerce' ), get_woocommerce_currency_symbol() . ' <input type="text" id="shipping_cost_domestic" name="shipping_cost_domestic" size="5" value="' . esc_attr( $shipping_cost_domestic ) . '" />', get_woocommerce_currency_symbol() . ' <input type="text" id="shipping_cost_domestic_item" name="shipping_cost_domestic_item" size="5" />' ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="shipping_cost_international"><?php _e( '<strong>International</strong> shipping costs:', 'woocommerce' ); ?></label></th>
					<td>
						<?php printf( __( 'A total of %s per order and/or %s per item', 'woocommerce' ), get_woocommerce_currency_symbol() . ' <input type="text" id="shipping_cost_international" name="shipping_cost_international" size="5" value="' . esc_attr( $shipping_cost_international ) . '" />', get_woocommerce_currency_symbol() . ' <input type="text" id="shipping_cost_international_item" name="shipping_cost_international_item" size="5" />' ); ?>
					</td>
				</tr>
				<tr class="section_title">
					<td colspan="2">
						<h2><?php _e( 'Basic Tax Setup', 'woocommerce' ); ?></h2>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="woocommerce_calc_taxes"><?php _e( 'Will you be charging sales tax?', 'woocommerce' ); ?></label></th>
					<td>
						<input type="checkbox" <?php checked( get_option( 'woocommerce_calc_taxes', 'no' ), 'yes' ); ?> id="woocommerce_calc_taxes" name="woocommerce_calc_taxes" class="input-checkbox" value="1" />
						<label for="woocommerce_calc_taxes"><?php _e( 'Yes, I will be charging sales tax', 'woocommerce' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="woocommerce_prices_include_tax"><?php _e( 'How will you enter product prices?', 'woocommerce' ); ?></label></th>
					<td>
						<label><input type="radio" <?php checked( get_option( 'woocommerce_prices_include_tax', 'no' ), 'yes' ); ?> id="woocommerce_prices_include_tax" name="woocommerce_prices_include_tax" class="input-radio" value="yes" /> <?php _e( 'I will enter prices inclusive of tax', 'woocommerce' ); ?></label><br/>
						<label><input type="radio" <?php checked( get_option( 'woocommerce_prices_include_tax', 'no' ), 'no' ); ?> id="woocommerce_prices_include_tax" name="woocommerce_prices_include_tax" class="input-radio" value="no" /> <?php _e( 'I will enter prices exclusive of tax', 'woocommerce' ); ?></label>
					</td>
				</tr>
				<?php
					$locale_info = include( WC()->plugin_path() . '/i18n/locale-info.php' );
					$tax_rates   = array();
					$country     = WC()->countries->get_base_country();
					$state       = WC()->countries->get_base_state();

					if ( isset( $locale_info[ $country ] ) ) {
						if ( isset( $locale_info[ $country ]['tax_rates'][ $state ] ) ) {
							$tax_rates = $locale_info[ $country ]['tax_rates'][ $state ];
						} elseif ( isset( $locale_info[ $country ]['tax_rates'][''] ) ) {
							$tax_rates = $locale_info[ $country ]['tax_rates'][''];
						}
						if ( isset( $locale_info[ $country ]['tax_rates']['*'] ) ) {
							$tax_rates = array_merge( $locale_info[ $country ]['tax_rates']['*'], $tax_rates );
						}
					}
					if ( $tax_rates ) {
						?>
						<tr>
							<th scope="row"><label for="woocommerce_import_tax_rates"><?php _e( 'Import Tax Rates?', 'woocommerce' ); ?></label></th>
							<td>
								<label><input type="checkbox" id="woocommerce_import_tax_rates" name="woocommerce_import_tax_rates" class="input-checkbox" value="yes" /> <?php _e( 'Yes, please import some starter tax rates', 'woocommerce' ); ?></label>
								<div class="importing-tax-rates">
									<table class="tax-rates">
										<thead>
											<tr>
												<th><?php _e( 'Country', 'woocommerce' ); ?></th>
												<th><?php _e( 'State', 'woocommerce' ); ?></th>
												<th><?php _e( 'Rate (%)', 'woocommerce' ); ?></th>
												<th><?php _e( 'Name', 'woocommerce' ); ?></th>
												<th><?php _e( 'Tax Shipping', 'woocommerce' ); ?></th>
											</tr>
										</thead>
										<tbody>
											<?php
												foreach ( $tax_rates as $rate ) {
													?>
													<tr>
														<td><?php echo esc_attr( $rate['country'] ); ?></td>
														<td><?php echo esc_attr( $rate['state'] ? $rate['state'] : '*' ); ?></td>
														<td><?php echo esc_attr( $rate['rate'] ); ?></td>
														<td><?php echo esc_attr( $rate['name'] ); ?></td>
														<td><?php echo empty( $rate['shipping'] ) ? '-' : '&#10004;'; ?></td>
													</tr>
													<?php
												}
											?>
										</tbody>
									</table>
									<p class="description"><?php _e( 'Please note: you may still need to add local and product specific tax rates depending on your business location. If in doubt, speak to an accountant.', 'woocommerce' ); ?></p>
								</div>
								<p class="description"><?php printf( __( 'You can edit tax rates later from the %1$stax settings%3$s screen and read more about taxes in %2$sour documentation%3$s.', 'woocommerce' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=tax' ) . '" target="_blank">', '<a href="http://docs.woothemes.com/document/setting-up-taxes-in-woocommerce/" target="_blank">', '</a>' ); ?></p>
							</td>
						</tr>
						<?php
					}
				?>
			</table>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'woocommerce' ); ?>" name="save_step" />
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'woocommerce' ); ?></a>
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Save shipping and tax options
	 */
	public function wc_setup_shipping_taxes_save() {
		check_admin_referer( 'wc-setup' );

		$woocommerce_calc_shipping = isset( $_POST['woocommerce_calc_shipping'] ) ? 'yes' : 'no';
		$woocommerce_calc_taxes    = isset( $_POST['woocommerce_calc_taxes'] ) ? 'yes' : 'no';

		update_option( 'woocommerce_calc_shipping', $woocommerce_calc_shipping );
		update_option( 'woocommerce_calc_taxes', $woocommerce_calc_taxes );
		update_option( 'woocommerce_prices_include_tax', sanitize_text_field( $_POST['woocommerce_prices_include_tax'] ) );

		if ( 'yes' === $woocommerce_calc_shipping && ! empty( $_POST['shipping_cost_domestic'] ) ) {
			// Delete existing settings if they exist
			delete_option( 'woocommerce_flat_rate_settings' );

			// Init rate and settings
			$shipping_method = new WC_Shipping_Flat_Rate();
			$costs           = array();
			$costs[]         = wc_format_decimal( sanitize_text_field( $_POST['shipping_cost_domestic'] ) );
			if ( $item_cost = sanitize_text_field( $_POST['shipping_cost_domestic_item'] ) ) {
				$costs[] = $item_cost . ' * [qty]';
			}
			$shipping_method->settings['cost']         = implode( ' + ', array_filter( $costs ) );
			$shipping_method->settings['enabled']      = 'yes';
			$shipping_method->settings['type']         = 'order';
			$shipping_method->settings['availability'] = 'specific';
			$shipping_method->settings['countries']    = array( WC()->countries->get_base_country() );

			update_option( $shipping_method->plugin_id . $shipping_method->id . '_settings', $shipping_method->settings );
		}

		if ( 'yes' === $woocommerce_calc_shipping && ! empty( $_POST['shipping_cost_international'] ) ) {
			// Delete existing settings if they exist
			delete_option( 'woocommerce_international_delivery_settings' );

			// Init rate and settings
			$shipping_method = new WC_Shipping_International_Delivery();
			$costs           = array();
			$costs[]         = wc_format_decimal( sanitize_text_field( $_POST['shipping_cost_international'] ) );
			if ( $item_cost = sanitize_text_field( $_POST['shipping_cost_international_item'] ) ) {
				$costs[] = $item_cost . ' * [qty]';
			}
			$shipping_method->settings['cost']         = implode( ' + ', array_filter( $costs ) );
			$shipping_method->settings['enabled']      = 'yes';
			$shipping_method->settings['type']         = 'order';
			if ( ! empty( $_POST['shipping_cost_domestic'] ) ) {
				$shipping_method->settings['availability'] = 'excluding';
				$shipping_method->settings['countries']    = array( WC()->countries->get_base_country() );
			}

			update_option( $shipping_method->plugin_id . $shipping_method->id . '_settings', $shipping_method->settings );
		}

		if ( 'yes' === $woocommerce_calc_taxes && ! empty( $_POST['woocommerce_import_tax_rates'] ) ) {
			$locale_info = include( WC()->plugin_path() . '/i18n/locale-info.php' );
			$tax_rates   = array();
			$country     = WC()->countries->get_base_country();
			$state       = WC()->countries->get_base_state();

			if ( isset( $locale_info[ $country ] ) ) {
				if ( isset( $locale_info[ $country ]['tax_rates'][ $state ] ) ) {
					$tax_rates = $locale_info[ $country ]['tax_rates'][ $state ];
				} elseif ( isset( $locale_info[ $country ]['tax_rates'][''] ) ) {
					$tax_rates = $locale_info[ $country ]['tax_rates'][''];
				}
				if ( isset( $locale_info[ $country ]['tax_rates']['*'] ) ) {
					$tax_rates = array_merge( $locale_info[ $country ]['tax_rates']['*'], $tax_rates );
				}
			}
			if ( $tax_rates ) {
				$loop = 0;
				foreach ( $tax_rates as $rate ) {
					$tax_rate = array(
						'tax_rate_country'  => $rate['country'],
						'tax_rate_state'    => $rate['state'],
						'tax_rate'          => $rate['rate'],
						'tax_rate_name'     => $rate['name'],
						'tax_rate_priority' => isset( $rate['priority'] ) ? absint( $rate['priority'] ) : 1,
						'tax_rate_compound' => 0,
						'tax_rate_shipping' => $rate['shipping'] ? 1 : 0,
						'tax_rate_order'    => $loop ++,
						'tax_rate_class'    => ''
					);
					WC_Tax::_insert_tax_rate( $tax_rate );
				}
			}
		}

		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Payments Step
	 */
	public function wc_setup_payments() {
		$paypal_settings = array_filter( (array) get_option( 'woocommerce_paypal_settings', array() ) );
		$cheque_settings = array_filter( (array) get_option( 'woocommerce_cheque_settings', array() ) );
		$cod_settings    = array_filter( (array) get_option( 'woocommerce_cod_settings', array() ) );
		$bacs_settings   = array_filter( (array) get_option( 'woocommerce_bacs_settings', array() ) );
		?>
		<h1><?php _e( 'Payments', 'woocommerce' ); ?></h1>
		<form method="post">
			<p><?php printf( __( 'WooCommerce can accept both online and offline payments. %2$sAdditional payment methods%3$s can be installed later and managed from the %1$scheckout settings%3$s screen.', 'woocommerce' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '" target="_blank">', '<a href="' . admin_url( 'admin.php?page=wc-addons&view=payment-gateways' ) . '" target="_blank">', '</a>' ); ?></p>
			<table class="form-table">
				<tr class="section_title">
					<td colspan="2">
						<h2><?php _e( 'PayPal Standard', 'woocommerce' ); ?></h2>
						<p><?php _e( 'To accept payments via PayPal on your store, simply enter your PayPal email address below.', 'woocommerce' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="woocommerce_paypal_email"><?php _e( 'PayPal Email Address:', 'woocommerce' ); ?></label></th>
					<td>
						<input type="email" id="woocommerce_paypal_email" name="woocommerce_paypal_email" class="input-text" value="<?php echo esc_attr( isset( $paypal_settings['email'] ) ? $paypal_settings['email'] : '' ); ?>" />
					</td>
				</tr>
				<tr class="section_title">
					<td colspan="2">
						<h2><?php _e( 'Offline Payments', 'woocommerce' ); ?></h2>
						<p><?php _e( 'Offline gateways require manual processing, but can be useful in certain circumstances or for testing payments.', 'woocommerce' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="woocommerce_enable_cheque"><?php _e( 'Cheque Payments', 'woocommerce' ); ?></label></th>
					<td>
						<label><input type="checkbox" id="woocommerce_enable_cheque" name="woocommerce_enable_cheque" class="input-checkbox" value="yes" <?php checked( ( isset( $cheque_settings['enabled'] ) && 'yes' === $cheque_settings['enabled'] ), true ); ?> /> <?php _e( 'Enable payment via Cheques', 'woocommerce' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="woocommerce_enable_cod"><?php _e( 'Cash on Delivery', 'woocommerce' ); ?></label></th>
					<td>
						<label><input type="checkbox" id="woocommerce_enable_cod" name="woocommerce_enable_cod" class="input-checkbox" value="yes" <?php checked( ( isset( $cod_settings['enabled'] ) && 'yes' === $cod_settings['enabled'] ), true ); ?> /> <?php _e( 'Enable cash on delivery', 'woocommerce' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="woocommerce_enable_bacs"><?php _e( 'Bank Transfer (BACS)', 'woocommerce' ); ?></label></th>
					<td>
						<label><input type="checkbox" id="woocommerce_enable_bacs" name="woocommerce_enable_bacs" class="input-checkbox" value="yes" <?php checked( ( isset( $bacs_settings['enabled'] ) && 'yes' === $bacs_settings['enabled'] ), true ); ?> /> <?php _e( 'Enable BACS payments', 'woocommerce' ); ?></label>
					</td>
				</tr>
			</table>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'woocommerce' ); ?>" name="save_step" />
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'woocommerce' ); ?></a>
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Payments Step save
	 */
	public function wc_setup_payments_save() {
		check_admin_referer( 'wc-setup' );

		$paypal_settings            = array_filter( (array) get_option( 'woocommerce_paypal_settings', array() ) );
		$cheque_settings            = array_filter( (array) get_option( 'woocommerce_cheque_settings', array() ) );
		$cod_settings               = array_filter( (array) get_option( 'woocommerce_cod_settings', array() ) );
		$bacs_settings              = array_filter( (array) get_option( 'woocommerce_bacs_settings', array() ) );

		$paypal_settings['enabled'] = ! empty( $_POST['woocommerce_paypal_email'] ) ? 'yes' : 'no';
		$cheque_settings['enabled'] = isset( $_POST['woocommerce_enable_cheque'] ) ? 'yes' : 'no';
		$cod_settings['enabled']    = isset( $_POST['woocommerce_enable_cod'] ) ? 'yes' : 'no';
		$bacs_settings['enabled']   = isset( $_POST['woocommerce_enable_bacs'] ) ? 'yes' : 'no';

		if ( ! empty( $_POST['woocommerce_paypal_email'] ) ) {
			$paypal_settings['email'] = wc_clean( $_POST['woocommerce_paypal_email'] );
		}

		update_option( 'woocommerce_paypal_settings', $paypal_settings );
		update_option( 'woocommerce_cheque_settings', $cheque_settings );
		update_option( 'woocommerce_cod_settings', $cod_settings );
		update_option( 'woocommerce_bacs_settings', $bacs_settings );

		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Actions on the final step
	 */
	private function wc_setup_ready_actions() {
		WC_Admin_Notices::remove_notice( 'install' );

		if ( isset( $_GET['wc_tracker_optin'] ) && isset( $_GET['wc_tracker_nonce'] ) && wp_verify_nonce( $_GET['wc_tracker_nonce'], 'wc_tracker_optin' ) ) {
			update_option( 'woocommerce_allow_tracking', 'yes' );
			WC_Tracker::send_tracking_data( true );

		} elseif ( isset( $_GET['wc_tracker_optout'] ) && isset( $_GET['wc_tracker_nonce'] ) && wp_verify_nonce( $_GET['wc_tracker_nonce'], 'wc_tracker_optout' ) ) {
			update_option( 'woocommerce_allow_tracking', 'no' );
		}
	}

	/**
	 * Final step
	 */
	public function wc_setup_ready() {
		$this->wc_setup_ready_actions();
		shuffle( $this->tweets );
		?>
		<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/woocommerce/" data-text="<?php echo esc_attr( $this->tweets[0] ); ?>" data-via="WooThemes" data-size="large">Tweet</a>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>

		<h1><?php _e( 'Your Store is Ready!', 'woocommerce' ); ?></h1>

		<?php if ( 'unknown' === get_option( 'woocommerce_allow_tracking', 'unknown' ) ) : ?>
			<div class="woocommerce-message woocommerce-tracker">
				<p><?php printf( __( 'Want to help make WooCommerce even more awesome? Allow WooThemes to collect non-sensitive diagnostic data and usage information, and get %s discount on your next WooThemes purchase. %sFind out more%s.', 'woocommerce' ), '20%', '<a href="http://www.woothemes.com/woocommerce/usage-tracking/" target="_blank">', '</a>' ); ?></p>
				<p class="submit">
					<a class="button-primary button button-large" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc_tracker_optin', 'true' ), 'wc_tracker_optin', 'wc_tracker_nonce' ) ); ?>"><?php _e( 'Allow', 'woocommerce' ); ?></a>
					<a class="button-secondary button button-large skip"  href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc_tracker_optout', 'true' ), 'wc_tracker_optout', 'wc_tracker_nonce' ) ); ?>"><?php _e( 'No thanks', 'woocommerce' ); ?></a>
				</p>
			</div>
		<?php endif; ?>

		<div class="wc-setup-next-steps">
			<div class="wc-setup-next-steps-first">
				<h2><?php _e( 'Next Steps', 'woocommerce' ); ?></h2>
				<ul>
					<li class="setup-product"><a class="button button-primary button-large" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product&tutorial=true' ) ); ?>"><?php _e( 'Create your first product!', 'woocommerce' ); ?></a></li>
				</ul>
			</div>
			<div class="wc-setup-next-steps-last">
				<h2><?php _e( 'Learn More', 'woocommerce' ); ?></h2>
				<ul>
					<li class="video-walkthrough"><a href="http://docs.woothemes.com/document/woocommerce-101-video-series/?utm_source=WooCommerce&amp;utm_medium=Wizard&amp;utm_content=Videos&amp;utm_campaign=Onboarding"><?php _e( 'Watch the WC 101 video walkthroughs', 'woocommerce' ); ?></a></li>
					<li class="newsletter"><a href="http://www.woothemes.com/woocommerce-onboarding-email/?utm_source=WooCommerce&amp;utm_medium=Wizard&amp;utm_content=Newsletter&amp;utm_campaign=Onboarding"><?php _e( 'Get eCommerce advice in your inbox', 'woocommerce' ); ?></a></li>
					<li class="sidekick"><a href="http://www.woothemes.com/sidekick/"><?php _e( 'Follow Sidekick interactive walkthroughs', 'woocommerce' ); ?></a></li>
					<li class="learn-more"><a href="http://docs.woothemes.com/documentation/plugins/woocommerce/getting-started/?utm_source=WooCommerce&amp;utm_medium=Wizard&amp;utm_content=Docs&amp;utm_campaign=Onboarding"><?php _e( 'Read more about getting started', 'woocommerce' ); ?></a></li>
				</ul>
			</div>
		</div>
		<?php
	}
}

new WC_Admin_Setup_Wizard();
