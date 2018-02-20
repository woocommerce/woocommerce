<?php
/**
 * Setup Wizard Class
 *
 * Takes new users through some basic steps to setup their store.
 *
 * @package     WooCommerce/Admin
 * @version     2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Setup_Wizard class.
 */
class WC_Admin_Setup_Wizard {

	/**
	 * Current step
	 *
	 * @var string
	 */
	private $step = '';

	/**
	 * Steps for the setup wizard
	 *
	 * @var array
	 */
	private $steps = array();

	/**
	 * Actions to be executed after the HTTP response has completed
	 *
	 * @var array
	 */
	private $deferred_actions = array();

	/**
	 * Tweets user can optionally send after install
	 *
	 * @var array
	 */
	private $tweets = array(
		'Someone give me woo-t, I just set up a new store with #WordPress and @WooCommerce!',
		'Someone give me high five, I just set up a new store with #WordPress and @WooCommerce!',
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
	 * The theme "extra" should only be shown if the current user can modify themes
	 * and the store doesn't already have a WooCommerce theme.
	 *
	 * @return boolean
	 */
	protected function should_show_theme_extra() {
		$support_woocommerce = current_theme_supports( 'woocommerce' ) && ! $this->is_default_theme();

		return (
			current_user_can( 'install_themes' ) &&
			current_user_can( 'switch_themes' ) &&
			! is_multisite() &&
			! $support_woocommerce
		);
	}

	/**
	 * Is the user using a default WP theme?
	 *
	 * @return boolean
	 */
	protected function is_default_theme() {
		return wc_is_active_theme( array(
			'twentyseventeen',
			'twentysixteen',
			'twentyfifteen',
			'twentyfourteen',
			'twentythirteen',
			'twentyeleven',
			'twentytwelve',
			'twentyten',
		) );
	}

	/**
	 * The "automated tax" extra should only be shown if the current user can
	 * install plugins and the store is in a supported country.
	 */
	protected function should_show_automated_tax_extra() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		$country_code = WC()->countries->get_base_country();
		// https://developers.taxjar.com/api/reference/#countries .
		$tax_supported_countries = array_merge(
			array( 'US', 'CA', 'AU' ),
			WC()->countries->get_european_union_countries()
		);

		return in_array( $country_code, $tax_supported_countries, true );
	}

	/**
	 * Show the setup wizard.
	 */
	public function setup_wizard() {
		if ( empty( $_GET['page'] ) || 'wc-setup' !== $_GET['page'] ) { // WPCS: CSRF ok, input var ok.
			return;
		}
		$default_steps = array(
			'store_setup' => array(
				'name'    => __( 'Store setup', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_store_setup' ),
				'handler' => array( $this, 'wc_setup_store_setup_save' ),
			),
			'payment'     => array(
				'name'    => __( 'Payment', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_payment' ),
				'handler' => array( $this, 'wc_setup_payment_save' ),
			),
			'shipping'    => array(
				'name'    => __( 'Shipping', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_shipping' ),
				'handler' => array( $this, 'wc_setup_shipping_save' ),
			),
			'extras'      => array(
				'name'    => __( 'Extras', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_extras' ),
				'handler' => array( $this, 'wc_setup_extras_save' ),
			),
			'activate'    => array(
				'name'    => __( 'Activate', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_activate' ),
				'handler' => array( $this, 'wc_setup_activate_save' ),
			),
			'next_steps'  => array(
				'name'    => __( 'Ready!', 'woocommerce' ),
				'view'    => array( $this, 'wc_setup_ready' ),
				'handler' => '',
			),
		);

		// Hide the extras step if this store/user isn't eligible for them.
		if ( ! $this->should_show_theme_extra() && ! $this->should_show_automated_tax_extra() ) {
			unset( $default_steps['extras'] );
		}

		// Hide shipping step if the store is selling digital products only.
		if ( 'virtual' === get_option( 'woocommerce_product_type' ) ) {
			unset( $default_steps['shipping'] );
		}

		// Hide the activate step if Jetpack is already active, but not
		// if we're returning from connecting Jetpack on WordPress.com.
		if ( class_exists( 'Jetpack' ) && Jetpack::is_active() && ! isset( $_GET['from'] ) && ! isset( $_GET['activate_error'] ) ) { // WPCS: CSRF ok, input var ok.
			unset( $default_steps['activate'] );
		}

		// Whether or not there is a pending background install of Jetpack.
		$pending_jetpack = ! class_exists( 'Jetpack' ) && get_option( 'woocommerce_setup_background_installing_jetpack' );

		$this->steps = apply_filters( 'woocommerce_setup_wizard_steps', $default_steps );
		$this->step  = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) ); // WPCS: CSRF ok, input var ok.
		$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.0' );
		wp_register_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js', array( 'jquery', 'selectWoo' ), WC_VERSION );
		wp_localize_script( 'wc-enhanced-select', 'wc_enhanced_select_params', array(
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
			'search_customers_nonce'    => wp_create_nonce( 'search-customers' ),
		) );
		wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
		wp_enqueue_style( 'wc-setup', WC()->plugin_url() . '/assets/css/wc-setup.css', array( 'dashicons', 'install' ), WC_VERSION );

		wp_register_script( 'wc-setup', WC()->plugin_url() . '/assets/js/admin/wc-setup' . $suffix . '.js', array( 'jquery', 'wc-enhanced-select', 'jquery-blockui', 'wp-util' ), WC_VERSION );
		wp_localize_script( 'wc-setup', 'wc_setup_params', array(
			'pending_jetpack_install' => $pending_jetpack ? 'yes' : 'no',
		) );

		// @codingStandardsIgnoreStart
		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'], $this );
		}
		// @codingStandardsIgnoreEnd

		ob_start();
		$this->setup_wizard_header();
		$this->setup_wizard_steps();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	}

	/**
	 * Get the URL for the next step's screen.
	 *
	 * @param string $step  slug (default: current step).
	 * @return string       URL for next step if a next step exists.
	 *                      Admin URL if it's the last step.
	 *                      Empty string on failure.
	 * @since 3.0.0
	 */
	public function get_next_step_link( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}

		$keys = array_keys( $this->steps );
		if ( end( $keys ) === $step ) {
			return admin_url();
		}

		$step_index = array_search( $step, $keys, true );
		if ( false === $step_index ) {
			return '';
		}

		return add_query_arg( 'step', $keys[ $step_index + 1 ], remove_query_arg( 'activate_error' ) );
	}

	/**
	 * Setup Wizard Header.
	 */
	public function setup_wizard_header() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php esc_html_e( 'WooCommerce &rsaquo; Setup Wizard', 'woocommerce' ); ?></title>
			<?php wp_print_scripts( 'wc-setup' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="wc-setup wp-core-ui">
			<h1 id="wc-logo"><a href="https://woocommerce.com/"><img src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/woocommerce_logo.png" alt="WooCommerce" /></a></h1>
		<?php
	}

	/**
	 * Setup Wizard Footer.
	 */
	public function setup_wizard_footer() {
		?>
			<?php if ( 'store_setup' === $this->step ) : ?>
				<a class="wc-return-to-dashboard" href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Not right now', 'woocommerce' ); ?></a>
			<?php elseif ( 'next_steps' === $this->step ) : ?>
				<a class="wc-return-to-dashboard" href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Return to your dashboard', 'woocommerce' ); ?></a>
			<?php elseif ( 'activate' === $this->step || 'extras' === $this->step ) : ?>
				<a class="wc-return-to-dashboard" href="<?php echo esc_url( $this->get_next_step_link() ); ?>"><?php esc_html_e( 'Skip this step', 'woocommerce' ); ?></a>
			<?php endif; ?>
			</body>
		</html>
		<?php
	}

	/**
	 * Output the steps.
	 */
	public function setup_wizard_steps() {
		$output_steps = $this->steps;
		?>
		<ol class="wc-setup-steps">
			<?php foreach ( $output_steps as $step_key => $step ) : ?>
				<li class="
					<?php
					if ( $step_key === $this->step ) {
						echo 'active';
					} elseif ( array_search( $this->step, array_keys( $this->steps ), true ) > array_search( $step_key, array_keys( $this->steps ), true ) ) {
						echo 'done';
					}
					?>
				"><?php echo esc_html( $step['name'] ); ?></li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	/**
	 * Output the content for the current step.
	 */
	public function setup_wizard_content() {
		echo '<div class="wc-setup-content">';
		if ( ! empty( $this->steps[ $this->step ]['view'] ) ) {
			call_user_func( $this->steps[ $this->step ]['view'], $this );
		}
		echo '</div>';
	}

	/**
	 * Initial "store setup" step.
	 * Location, product type, page setup, and tracking opt-in.
	 */
	public function wc_setup_store_setup() {
		$address        = WC()->countries->get_base_address();
		$address_2      = WC()->countries->get_base_address_2();
		$city           = WC()->countries->get_base_city();
		$state          = WC()->countries->get_base_state();
		$country        = WC()->countries->get_base_country();
		$postcode       = WC()->countries->get_base_postcode();
		$currency       = get_option( 'woocommerce_currency', 'GBP' );
		$product_type   = get_option( 'woocommerce_product_type', 'both' );
		$sell_in_person = get_option( 'woocommerce_sell_in_person', 'none_selected' );

		if ( empty( $country ) ) {
			$user_location = WC_Geolocation::geolocate_ip();
			$country       = $user_location['country'];
			$state         = $user_location['state'];
		} elseif ( empty( $state ) ) {
			$state = '*';
		}

		$locale_info         = include WC()->plugin_path() . '/i18n/locale-info.php';
		$currency_by_country = wp_list_pluck( $locale_info, 'currency_code' );
		?>
		<form method="post" class="address-step">
			<?php wp_nonce_field( 'wc-setup' ); ?>
			<p class="store-setup"><?php esc_html_e( 'The following wizard will help you configure your store and get you started quickly.', 'woocommerce' ); ?></p>
			<label for="store_country_state" class="location-prompt">
				<?php esc_html_e( 'Where is your store based?', 'woocommerce' ); ?>
			</label>
			<select
				id="store_country_state"
				name="store_country_state"
				required
				data-placeholder="<?php esc_attr_e( 'Choose a country&hellip;', 'woocommerce' ); ?>"
				aria-label="<?php esc_attr_e( 'Country', 'woocommerce' ); ?>"
				class="location-input wc-enhanced-select dropdown"
			>
					<?php WC()->countries->country_dropdown_options( $country, $state ); ?>
			</select>

			<div class="store-address-container">
			<label class="location-prompt" for="store_address">
				<?php esc_html_e( 'Address', 'woocommerce' ); ?>
			</label>
			<input
				type="text"
				id="store_address"
				class="location-input"
				name="store_address"
				required
				value="<?php echo esc_attr( $address ); ?>"
			/>
			<label class="location-prompt" for="store_address_2">
				<?php esc_html_e( 'Address line 2', 'woocommerce' ); ?>
			</label>
			<input
				type="text"
				id="store_address_2"
				class="location-input"
				name="store_address_2"
				value="<?php echo esc_attr( $address_2 ); ?>"
			/>
			<div class="city-and-postcode">
				<div>
					<label class="location-prompt" for="store_city">
						<?php esc_html_e( 'City', 'woocommerce' ); ?>
					</label>
					<input
						type="text"
						id="store_city"
						class="location-input"
						name="store_city"
						required
						value="<?php echo esc_attr( $city ); ?>"
					/>
				</div>
				<div>
					<label class="location-prompt" for="store_postcode">
						<?php esc_html_e( 'Postcode / ZIP', 'woocommerce' ); ?>
					</label>
					<input
						type="text"
						id="store_postcode"
						class="location-input"
						name="store_postcode"
						required
						value="<?php echo esc_attr( $postcode ); ?>"
					/>
				</div>
			</div>
		</div>

			<div class="store-currency-container">
			<label class="location-prompt" for="currency_code">
				<?php esc_html_e( 'What currency do you use?', 'woocommerce' ); ?>
			</label>
			<select
				id="currency_code"
				name="currency_code"
				required
				data-placeholder="<?php esc_attr_e( 'Choose a currency&hellip;', 'woocommerce' ); ?>"
				class="location-input wc-enhanced-select dropdown"
			>
				<option value=""><?php esc_html_e( 'Choose a currency&hellip;', 'woocommerce' ); ?></option>
				<?php foreach ( get_woocommerce_currencies() as $code => $name ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $currency, $code ); ?>><?php printf( esc_html__( '%1$s (%2$s)', 'woocommerce' ), $name, get_woocommerce_currency_symbol( $code ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<script type="text/javascript">
				var wc_setup_currencies = <?php echo wp_json_encode( $currency_by_country ); ?>;
			</script>
			</div>

			<div class="product-type-container">
			<label class="location-prompt" for="product_type">
				<?php esc_html_e( 'What type of product do you plan to sell?', 'woocommerce' ); ?>
			</label>
			<select
				id="product_type"
				name="product_type"
				required
				class="location-input wc-enhanced-select dropdown"
			>
				<option value="both" <?php selected( $product_type, 'both' ); ?>><?php esc_html_e( 'I plan to sell both physical and digital products', 'woocommerce' ); ?></option>
				<option value="physical" <?php selected( $product_type, 'physical' ); ?>><?php esc_html_e( 'I plan to sell physical products', 'woocommerce' ); ?></option>
				<option value="virtual" <?php selected( $product_type, 'virtual' ); ?>><?php esc_html_e( 'I plan to sell digital products', 'woocommerce' ); ?></option>
			</select>
			</div>

			<input
				type="checkbox"
				id="woocommerce_sell_in_person"
				name="sell_in_person"
				value="yes"
				<?php checked( $sell_in_person, true ); ?>
			/>
			<label class="location-prompt" for="woocommerce_sell_in_person">
				<?php esc_html_e( 'I will also be selling products or services in person.', 'woocommerce' ); ?>
			</label>

			<?php if ( 'unknown' === get_option( 'woocommerce_allow_tracking', 'unknown' ) ) : ?>
				<div class="allow-tracking">
					<input type="checkbox" id="wc_tracker_optin" name="wc_tracker_optin" value="yes" checked />
					<label for="wc_tracker_optin"><?php esc_html_e( 'Allow WooCommerce to collect non-sensitive diagnostic data and usage information.', 'woocommerce' ); ?></label>
				</div>
			<?php endif; ?>
			<p class="wc-setup-actions step">
				<button type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( "Let's go!", 'woocommerce' ); ?>" name="save_step"><?php esc_html_e( "Let's go!", 'woocommerce' ); ?></button>
			</p>
		</form>
		<?php
	}

	/**
	 * Save initial store settings.
	 */
	public function wc_setup_store_setup_save() {
		check_admin_referer( 'wc-setup' );
		// @codingStandardsIgnoreStart
		$address        = sanitize_text_field( $_POST['store_address'] );
		$address_2      = sanitize_text_field( $_POST['store_address_2'] );
		$city           = sanitize_text_field( $_POST['store_city'] );
		$country_state  = sanitize_text_field( $_POST['store_country_state'] );
		$postcode       = sanitize_text_field( $_POST['store_postcode'] );
		$currency_code  = sanitize_text_field( $_POST['currency_code'] );
		$product_type   = sanitize_text_field( $_POST['product_type'] );
		$sell_in_person = isset( $_POST['sell_in_person'] ) && ( 'yes' === sanitize_text_field( $_POST['sell_in_person'] ) );
		$tracking       = isset( $_POST['wc_tracker_optin'] ) && ( 'yes' === sanitize_text_field( $_POST['wc_tracker_optin'] ) );
		// @codingStandardsIgnoreEnd
		update_option( 'woocommerce_store_address', $address );
		update_option( 'woocommerce_store_address_2', $address_2 );
		update_option( 'woocommerce_store_city', $city );
		update_option( 'woocommerce_default_country', $country_state );
		update_option( 'woocommerce_store_postcode', $postcode );
		update_option( 'woocommerce_currency', $currency_code );
		update_option( 'woocommerce_product_type', $product_type );
		update_option( 'woocommerce_sell_in_person', $sell_in_person );

		$locale_info = include WC()->plugin_path() . '/i18n/locale-info.php';
		$country     = WC()->countries->get_base_country();

		// Set currency formatting options based on chosen location and currency.
		if (
			isset( $locale_info[ $country ] ) &&
			$locale_info[ $country ]['currency_code'] === $currency_code
		) {
			update_option( 'woocommerce_currency_pos', $locale_info[ $country ]['currency_pos'] );
			update_option( 'woocommerce_price_decimal_sep', $locale_info[ $country ]['decimal_sep'] );
			update_option( 'woocommerce_price_num_decimals', $locale_info[ $country ]['num_decimals'] );
			update_option( 'woocommerce_price_thousand_sep', $locale_info[ $country ]['thousand_sep'] );
		}

		if ( $tracking ) {
			update_option( 'woocommerce_allow_tracking', 'yes' );
			WC_Tracker::send_tracking_data( true );
		} else {
			update_option( 'woocommerce_allow_tracking', 'no' );
		}

		WC_Install::create_pages();
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Finishes replying to the client, but keeps the process running for further (async) code execution.
	 *
	 * @see https://core.trac.wordpress.org/ticket/41358 .
	 */
	protected function close_http_connection() {
		// Only 1 PHP process can access a session object at a time, close this so the next request isn't kept waiting.
		// @codingStandardsIgnoreStart
		if ( session_id() ) {
			session_write_close();
		}
		// @codingStandardsIgnoreEnd

		wc_set_time_limit( 0 );

		// fastcgi_finish_request is the cleanest way to send the response and keep the script running, but not every server has it.
		if ( is_callable( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		} else {
			// Fallback: send headers and flush buffers.
			if ( ! headers_sent() ) {
				header( 'Connection: close' );
			}
			@ob_end_flush(); // @codingStandardsIgnoreLine.
			flush();
		}
	}

	/**
	 * Function called after the HTTP request is finished, so it's executed without the client having to wait for it.
	 *
	 * @see WC_Admin_Setup_Wizard::install_plugin
	 * @see WC_Admin_Setup_Wizard::install_theme
	 */
	public function run_deferred_actions() {
		$this->close_http_connection();
		foreach ( $this->deferred_actions as $action ) {
			call_user_func_array( $action['func'], $action['args'] );

			// Clear the background installation flag if this is a plugin.
			if (
				isset( $action['func'][1] ) &&
				'background_installer' === $action['func'][1] &&
				isset( $action['args'][0] )
			) {
				delete_option( 'woocommerce_setup_background_installing_' . $action['args'][0] );
			}
		}
	}

	/**
	 * Helper method to queue the background install of a plugin.
	 *
	 * @param string $plugin_id  Plugin id used for background install.
	 * @param array  $plugin_info Plugin info array containing at least main file and repo slug.
	 */
	protected function install_plugin( $plugin_id, $plugin_info ) {
		// Make sure we don't trigger multiple simultaneous installs.
		if ( get_option( 'woocommerce_setup_background_installing_' . $plugin_id ) ) {
			return;
		}

		if ( ! empty( $plugin_info['file'] ) && is_plugin_active( $plugin_info['file'] ) ) {
			return;
		}

		if ( empty( $this->deferred_actions ) ) {
			add_action( 'shutdown', array( $this, 'run_deferred_actions' ) );
		}

		array_push( $this->deferred_actions, array(
			'func' => array( 'WC_Install', 'background_installer' ),
			'args' => array( $plugin_id, $plugin_info ),
		) );

		// Set the background installation flag for this plugin.
		update_option( 'woocommerce_setup_background_installing_' . $plugin_id, true );
	}


	/**
	 * Helper method to queue the background install of a theme.
	 *
	 * @param string $theme_id  Theme id used for background install.
	 */
	protected function install_theme( $theme_id ) {
		if ( empty( $this->deferred_actions ) ) {
			add_action( 'shutdown', array( $this, 'run_deferred_actions' ) );
		}
		array_push( $this->deferred_actions, array(
			'func' => array( 'WC_Install', 'theme_background_installer' ),
			'args' => array( $theme_id ),
		) );
	}

	/**
	 * Helper method to install Jetpack.
	 */
	protected function install_jetpack() {
		$this->install_plugin( 'jetpack', array(
			'file'      => 'jetpack/jetpack.php',
			'name'      => __( 'Jetpack', 'woocommerce' ),
			'repo-slug' => 'jetpack',
		) );
	}

	/**
	 * Helper method to install WooCommerce Services and its Jetpack dependency.
	 */
	protected function install_woocommerce_services() {
		$this->install_jetpack();
		$this->install_plugin( 'woocommerce-services', array(
			'file'      => 'woocommerce-services/woocommerce-services.php',
			'name'      => __( 'WooCommerce Services', 'woocommerce' ),
			'repo-slug' => 'woocommerce-services',
		) );
	}

	/**
	 * Get the WCS shipping carrier for a given country code.
	 *
	 * Can also be used to determine if WCS supports a given country.
	 *
	 * @param string $country_code Country Code.
	 * @param string $currency_code Currecy Code.
	 * @return bool|string Carrier name if supported, boolean False otherwise.
	 */
	protected function get_wcs_shipping_carrier( $country_code, $currency_code ) {
		switch ( array( $country_code, $currency_code ) ) {
			case array( 'US', 'USD' ):
				return 'USPS';
			case array( 'CA', 'CAD' ):
				return 'Canada Post';
			default:
				return false;
		}
	}

	/**
	 * Get shipping methods based on country code.
	 *
	 * @param string $country_code Country code.
	 * @param string $currency_code Currency code.
	 * @return array
	 */
	protected function get_wizard_shipping_methods( $country_code, $currency_code ) {
		$shipping_methods = array(
			'live_rates'    => array(
				'name'        => __( 'Live Rates', 'woocommerce' ),
				'description' => __( 'WooCommerce Services and Jetpack will be installed and activated for you.', 'woocommerce' ),
			),
			'flat_rate'     => array(
				'name'        => __( 'Flat Rate', 'woocommerce' ),
				'description' => __( 'Set a fixed price to cover shipping costs.', 'woocommerce' ),
				'settings'    => array(
					'cost' => array(
						'type'          => 'text',
						'default_value' => __( 'Cost', 'woocommerce' ),
						'description'   => __( 'What would you like to charge for flat rate shipping?', 'woocommerce' ),
						'required'      => true,
					),
				),
			),
			'free_shipping' => array(
				'name'        => __( 'Free Shipping', 'woocommerce' ),
				'description' => __( "Don't charge for shipping.", 'woocommerce' ),
			),
		);

		$live_rate_carrier = $this->get_wcs_shipping_carrier( $country_code, $currency_code );

		if ( false === $live_rate_carrier || ! current_user_can( 'install_plugins' ) ) {
			unset( $shipping_methods['live_rates'] );
		}

		return $shipping_methods;
	}

	/**
	 * Render the available shipping methods for a given country code.
	 *
	 * @param string $country_code Country code.
	 * @param string $currency_code Currency code.
	 * @param string $input_prefix Input prefix.
	 */
	protected function shipping_method_selection_form( $country_code, $currency_code, $input_prefix ) {
		$live_rate_carrier = $this->get_wcs_shipping_carrier( $country_code, $currency_code );
		$selected          = $live_rate_carrier ? 'live_rates' : 'flat_rate';
		$shipping_methods  = $this->get_wizard_shipping_methods( $country_code, $currency_code );
		?>
		<div class="wc-wizard-shipping-method-select">
			<div class="wc-wizard-shipping-method-dropdown">
				<select id="<?php echo esc_attr( "{$input_prefix}[method]" ); ?>" name="<?php echo esc_attr( "{$input_prefix}[method]" ); ?>" class="method wc-enhanced-select">
				<?php foreach ( $shipping_methods as $method_id => $method ) : ?>
					<option value="<?php echo esc_attr( $method_id ); ?>" <?php selected( $selected, $method_id ); ?>><?php echo esc_html( $method['name'] ); ?></option>
				<?php endforeach; ?>
				</select>
			</div>
			<div class="shipping-method-descriptions">
				<?php foreach ( $shipping_methods as $method_id => $method ) : ?>
					<p class="shipping-method-description <?php echo esc_attr( $method_id ); ?> <?php echo $method_id !== $selected ? 'hide' : ''; ?>">
						<?php echo esc_html( $method['description'] ); ?>
					</p>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="shipping-method-settings">
		<?php foreach ( $shipping_methods as $method_id => $method ) : ?>
			<?php
			if ( empty( $method['settings'] ) ) {
				continue;
			}
			?>
			<div class="shipping-method-setting <?php echo esc_attr( $method_id ); ?> <?php echo $method_id !== $selected ? 'hide' : ''; ?>">
			<?php foreach ( $method['settings'] as $setting_id => $setting ) : ?>
				<?php $method_setting_id = "{$input_prefix}[{$method_id}][{$setting_id}]"; ?>
				<input
					type="<?php echo esc_attr( $setting['type'] ); ?>"
					placeholder="<?php echo esc_attr( $setting['default_value'] ); ?>"
					id="<?php echo esc_attr( $method_setting_id ); ?>"
					name="<?php echo esc_attr( $method_setting_id ); ?>"
					class="<?php echo esc_attr( $setting['required'] ? 'shipping-method-required-field' : '' ); ?>"
					<?php echo ( $method_id === $selected && $setting['required'] ) ? 'required' : ''; ?>
				/>
				<p class="description">
					<?php echo esc_html( $setting['description'] ); ?>
				</p>
			<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Shipping.
	 */
	public function wc_setup_shipping() {
		$country_code          = WC()->countries->get_base_country();
		$country_name          = WC()->countries->countries[ $country_code ];
		$prefixed_country_name = WC()->countries->estimated_for_prefix( $country_code ) . $country_name;
		$currency_code         = get_woocommerce_currency();
		$wcs_carrier           = $this->get_wcs_shipping_carrier( $country_code, $currency_code );
		$existing_zones        = WC_Shipping_Zones::get_zones();

		$locale_info = include WC()->plugin_path() . '/i18n/locale-info.php';
		if ( isset( $locale_info[ $country_code ] ) ) {
			$dimension_unit = $locale_info[ $country_code ]['dimension_unit'];
			$weight_unit    = $locale_info[ $country_code ]['weight_unit'];
		} else {
			$dimension_unit = 'cm';
			$weight_unit    = 'kg';
		}

		if ( ! empty( $existing_zones ) ) {
			$intro_text = __( 'How would you like units on your store displayed?', 'woocommerce' );
		} elseif ( $wcs_carrier ) {
			$intro_text = sprintf(
				/* translators: %1$s: country name including the 'the' prefix, %2$s: shipping carrier name */
				__( "You're all set up to ship anywhere in %1\$s, and outside of it. We recommend using <strong>live rates</strong> (which are powered by our WooCommerce Services plugin and Jetpack) to get accurate %2\$s shipping prices to cover the cost of order fulfillment.", 'woocommerce' ),
				$prefixed_country_name,
				$wcs_carrier
			);
		} else {
			$intro_text = sprintf(
				/* translators: %s: country name including the 'the' prefix if needed */
				__( "You can choose which countries you'll be shipping to and with which methods. To get started, we've set you up with shipping inside and outside of %s.", 'woocommerce' ),
				$prefixed_country_name
			);
		}

		?>
		<h1><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></h1>
		<p><?php echo wp_kses_post( $intro_text ); ?></p>
		<form method="post">
			<?php if ( empty( $existing_zones ) ) : ?>
				<ul class="wc-wizard-services shipping">
					<li class="wc-wizard-service-item">
						<div class="wc-wizard-service-name">
							<p><?php echo esc_html_e( 'Shipping Zone', 'woocommerce' ); ?></p>
						</div>
						<div class="wc-wizard-service-description">
							<p><?php echo esc_html_e( 'Shipping Method', 'woocommerce' ); ?></p>
						</div>
					</li>
					<li class="wc-wizard-service-item">
						<div class="wc-wizard-service-name">
							<p><?php echo esc_html( $country_name ); ?></p>
						</div>
						<div class="wc-wizard-service-description">
							<?php $this->shipping_method_selection_form( $country_code, $currency_code, 'shipping_zones[domestic]' ); ?>
						</div>
						<div class="wc-wizard-service-enable">
							<span class="wc-wizard-service-toggle">
								<input id="shipping_zones[domestic][enabled]" type="checkbox" name="shipping_zones[domestic][enabled]" value="yes" checked="checked" class="wc-wizard-shipping-method-enable" />
								<label for="shipping_zones[domestic][enabled]">
							</span>
						</div>
					</li>
					<li class="wc-wizard-service-item">
						<div class="wc-wizard-service-name">
							<p><?php echo esc_html_e( 'Locations not covered by your other zones', 'woocommerce' ); ?></p>
						</div>
						<div class="wc-wizard-service-description">
							<?php $this->shipping_method_selection_form( $country_code, $currency_code, 'shipping_zones[intl]' ); ?>
						</div>
						<div class="wc-wizard-service-enable">
							<span class="wc-wizard-service-toggle">
								<input id="shipping_zones[intl][enabled]" type="checkbox" name="shipping_zones[intl][enabled]" value="yes" checked="checked" class="wc-wizard-shipping-method-enable"/>
								<label for="shipping_zones[intl][enabled]">
							</span>
						</div>
					</li>
				</ul>
			<?php endif; ?>

			<div class="wc-setup-shipping-units">
				<div class="wc-setup-shipping-unit">
					<p>
						<label for="weight_unit">
							<?php
								printf( wp_kses(
									__( '<strong>Weight unit</strong>—used to calculate shipping rates, and more.', 'woocommerce' ),
									array( 'strong' => array() )
								) );
							?>
						</label>
					</p>
					<select id="weight_unit" name="weight_unit" class="wc-enhanced-select">
						<option value="kg" <?php selected( $weight_unit, 'kg' ); ?>><?php esc_html_e( 'kg', 'woocommerce' ); ?></option>
						<option value="g" <?php selected( $weight_unit, 'g' ); ?>><?php esc_html_e( 'g', 'woocommerce' ); ?></option>
						<option value="lbs" <?php selected( $weight_unit, 'lbs' ); ?>><?php esc_html_e( 'lbs', 'woocommerce' ); ?></option>
						<option value="oz" <?php selected( $weight_unit, 'oz' ); ?>><?php esc_html_e( 'oz', 'woocommerce' ); ?></option>
					</select>
				</div>
				<div class="wc-setup-shipping-unit">
					<p>
						<label for="dimension_unit">
							<?php
								printf( wp_kses(
									__( '<strong>Dimension unit</strong>—helps for accurate package selection.', 'woocommerce' ),
									array( 'strong' => array() )
								) );
							?>
						</label>
					</p>
					<select id="dimension_unit" name="dimension_unit" class="wc-enhanced-select">
						<option value="m" <?php selected( $dimension_unit, 'm' ); ?>><?php esc_html_e( 'm', 'woocommerce' ); ?></option>
						<option value="cm" <?php selected( $dimension_unit, 'cm' ); ?>><?php esc_html_e( 'cm', 'woocommerce' ); ?></option>
						<option value="mm" <?php selected( $dimension_unit, 'mm' ); ?>><?php esc_html_e( 'mm', 'woocommerce' ); ?></option>
						<option value="in" <?php selected( $dimension_unit, 'in' ); ?>><?php esc_html_e( 'in', 'woocommerce' ); ?></option>
						<option value="yd" <?php selected( $dimension_unit, 'yd' ); ?>><?php esc_html_e( 'yd', 'woocommerce' ); ?></option>
					</select>
				</div>
			</div>

			<p class="wc-setup-actions step">
				<button type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'woocommerce' ); ?>" name="save_step"><?php esc_html_e( 'Continue', 'woocommerce' ); ?></button>
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Save shipping options.
	 */
	public function wc_setup_shipping_save() {
		check_admin_referer( 'wc-setup' );

		// If going through this step again, remove the live rates options.
		// in case the user saved different settings this time.
		delete_option( 'woocommerce_setup_domestic_live_rates_zone' );
		delete_option( 'woocommerce_setup_intl_live_rates_zone' );

		// @codingStandardsIgnoreStart
		$setup_domestic   = isset( $_POST['shipping_zones']['domestic']['enabled'] ) && ( 'yes' === $_POST['shipping_zones']['domestic']['enabled'] );
		$domestic_method  = isset( $_POST['shipping_zones']['domestic']['method'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_zones']['domestic']['method'] ) ) : '';
		$setup_intl       = isset( $_POST['shipping_zones']['intl']['enabled'] ) && ( 'yes' === $_POST['shipping_zones']['intl']['enabled'] );
		$intl_method      = isset( $_POST['shipping_zones']['intl']['method'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_zones']['intl']['method'] ) ) : '';
		$weight_unit      = sanitize_text_field( wp_unslash( $_POST['weight_unit'] ) );
		$dimension_unit   = sanitize_text_field( wp_unslash( $_POST['dimension_unit'] ) );
		$existing_zones   = WC_Shipping_Zones::get_zones();
		// @codingStandardsIgnoreEnd

		update_option( 'woocommerce_ship_to_countries', '' );
		update_option( 'woocommerce_weight_unit', $weight_unit );
		update_option( 'woocommerce_dimension_unit', $dimension_unit );

		// For now, limit this setup to the first run.
		if ( ! empty( $existing_zones ) ) {
			wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
			exit;
		}

		// Install WooCommerce Services if live rates were selected.
		if (
			( $setup_domestic && 'live_rates' === $domestic_method ) ||
			( $setup_intl && 'live_rates' === $intl_method )
		) {
			$this->install_woocommerce_services();
		}

		/*
		 * If enabled, create a shipping zone containing the country the
		 * store is located in, with the selected method preconfigured.
		 */
		if ( $setup_domestic ) {
			$country = WC()->countries->get_base_country();

			$zone = new WC_Shipping_Zone( null );
			$zone->set_zone_order( 0 );
			$zone->add_location( $country, 'country' );

			if ( 'live_rates' === $domestic_method ) {
				// Signal WooCommerce Services to setup the domestic zone.
				update_option( 'woocommerce_setup_domestic_live_rates_zone', true, 'no' );
			} else {
				$instance_id = $zone->add_shipping_method( $domestic_method );
			}

			$zone->save();

			// Save chosen shipping method settings (using REST controller for convenience).
			if ( isset( $instance_id ) && ! empty( $_POST['shipping_zones']['domestic'][ $domestic_method ] ) ) { // WPCS: input var ok.
				$method_controller = new WC_REST_Shipping_Zone_Methods_Controller();
				// @codingStandardsIgnoreStart
				$method_controller->update_item( array(
					'zone_id'     => $zone->get_id(),
					'instance_id' => $instance_id,
					'settings'    => wp_unslash( $_POST['shipping_zones']['domestic'][ $domestic_method ] ),
				) );
				// @codingStandardsIgnoreEnd
			}
		}

		// If enabled, set the selected method for the "rest of world" zone.
		if ( $setup_intl ) {
			if ( 'live_rates' === $intl_method ) {
				// Signal WooCommerce Services to setup the international zone.
				update_option( 'woocommerce_setup_intl_live_rates_zone', true, 'no' );
			} else {
				$zone        = new WC_Shipping_Zone( 0 );
				$instance_id = $zone->add_shipping_method( $intl_method );

				$zone->save();
			}

			// Save chosen shipping method settings (using REST controller for convenience).
			if ( isset( $instance_id ) && ! empty( $_POST['shipping_zones']['intl'][ $intl_method ] ) ) { // WPCS: input var ok.
				$method_controller = new WC_REST_Shipping_Zone_Methods_Controller();
				// @codingStandardsIgnoreStart
				$method_controller->update_item( array(
					'zone_id'     => $zone->get_id(),
					'instance_id' => $instance_id,
					'settings'    => wp_unslash( $_POST['shipping_zones']['intl'][ $intl_method ] ),
				) );
				// @codingStandardsIgnoreEnd
			}
		}

		// Notify the user that no shipping methods are configured.
		if ( ! $setup_domestic && ! $setup_intl ) {
			WC_Admin_Notices::add_notice( 'no_shipping_methods' );
		}

		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Is Stripe country supported
	 * https://stripe.com/global .
	 *
	 * @param string $country_code Country code.
	 */
	protected function is_stripe_supported_country( $country_code ) {
		$stripe_supported_countries = array(
			'AU',
			'AT',
			'BE',
			'CA',
			'DK',
			'FI',
			'FR',
			'DE',
			'HK',
			'IE',
			'JP',
			'LU',
			'NL',
			'NZ',
			'NO',
			'SG',
			'ES',
			'SE',
			'CH',
			'GB',
			'US',
		);

		return in_array( $country_code, $stripe_supported_countries, true );
	}

	/**
	 * Is Klarna Checkout country supported
	 *
	 * @param string $country_code Country code.
	 */
	protected function is_klarna_checkout_supported_country( $country_code ) {
		$supported_countries = array(
			'SE', // Sweden.
			'FI', // Finland.
			'NO', // Norway.
			'NL', // Netherlands.
		);
		return in_array( $country_code, $supported_countries, true );
	}

	/**
	 * Is Klarna Payments country supported
	 *
	 * @param string $country_code Country code.
	 */
	protected function is_klarna_payments_supported_country( $country_code ) {
		$supported_countries = array(
			'DK', // Denmark.
			'DE', // Germany.
			'AT', // Austria.
		);
		return in_array( $country_code, $supported_countries, true );
	}

	/**
	 * Is Square country supported
	 *
	 * @param string $country_code Country code.
	 */
	protected function is_square_supported_country( $country_code ) {
		$square_supported_countries = array(
			'US',
			'CA',
			'JP',
			'GB',
			'AU',
		);
		return in_array( $country_code, $square_supported_countries, true );
	}

	/**
	 * Helper method to retrieve the current user's email address.
	 *
	 * @return string Email address
	 */
	protected function get_current_user_email() {
		$current_user = wp_get_current_user();
		$user_email   = $current_user->user_email;

		return $user_email;
	}

	/**
	 * Array of all possible "in cart" gateways that can be offered.
	 *
	 * @return array
	 */
	protected function get_wizard_available_in_cart_payment_gateways() {
		$user_email = $this->get_current_user_email();

		$stripe_description = '<p>' . sprintf(
			/* translators: %s: URL */
			__( 'Accept debit and credit cards in 135+ currencies, methods such as Alipay, and one-touch checkout with Apple Pay. <a href="%s" target="_blank">Learn more</a>.', 'woocommerce' ),
			'https://woocommerce.com/products/stripe/'
		) . '</p>';
		$paypal_ec_description = '<p>' . sprintf(
			/* translators: %s: URL */
			__( 'Safe and secure payments using credit cards or your customer\'s PayPal account. <a href="%s" target="_blank">Learn more</a>.', 'woocommerce' ),
			'https://woocommerce.com/products/woocommerce-gateway-paypal-express-checkout/'
		) . '</p>';
		$klarna_checkout_description = '<p>' . sprintf(
			/* translators: %s: URL */
			__( 'Full checkout experience with pay now, pay later and slice it. No credit card numbers, no passwords, no worries. <a href="%s" target="_blank">Learn more about Klarna</a>.', 'woocommerce' ),
			'https://woocommerce.com/products/klarna-checkout/'
		) . '</p>';
		$klarna_payments_description = '<p>' . sprintf(
			/* translators: %s: URL */
			__( 'Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries. <a href="%s" target="_blank">Learn more about Klarna</a>.', 'woocommerce' ),
			'https://woocommerce.com/products/klarna-payments/ '
		) . '</p>';
		$square_description = '<p>' . sprintf(
			/* translators: %s: URL */
			__( 'Securely accept credit and debit cards with one low rate, no surprise fees (custom rates available). Sell online and in store and track sales and inventory in one place. <a href="%s" target="_blank">Learn more about Square</a>.', 'woocommerce' ),
			'https://woocommerce.com/products/square/'
		) . '</p>';

		return array(
			'stripe'          => array(
				'name'        => __( 'Stripe', 'woocommerce' ),
				'image'       => WC()->plugin_url() . '/assets/images/stripe.png',
				'description' => $stripe_description,
				'class'       => 'checked stripe-logo',
				'repo-slug'   => 'woocommerce-gateway-stripe',
				'settings'    => array(
					'create_account' => array(
						'label'       => __( 'Create a new Stripe account for me', 'woocommerce' ),
						'type'        => 'checkbox',
						'value'       => 'yes',
						'placeholder' => '',
						'required'    => false,
					),
					'email'          => array(
						'label'       => __( 'Stripe email address:', 'woocommerce' ),
						'type'        => 'email',
						'value'       => $user_email,
						'placeholder' => __( 'Stripe email address', 'woocommerce' ),
						'description' => __( "Enter your email address and we'll handle account creation. WooCommerce Services and Jetpack will be installed and activated for you.", 'woocommerce' ),
						'required'    => true,
					),
				),
			),
			'ppec_paypal'     => array(
				'name'        => __( 'PayPal Express Checkout', 'woocommerce' ),
				'image'       => WC()->plugin_url() . '/assets/images/paypal.png',
				'description' => $paypal_ec_description,
				'enabled'     => true,
				'class'       => 'checked paypal-logo',
				'repo-slug'   => 'woocommerce-gateway-paypal-express-checkout',
				'settings'    => array(
					'reroute_requests' => array(
						'label'       => __( 'Accept payments without linking a PayPal account', 'woocommerce' ),
						'type'        => 'checkbox',
						'value'       => 'yes',
						'placeholder' => '',
						'required'    => false,
					),
					'email'            => array(
						'label'       => __( 'Direct payments to email address:', 'woocommerce' ),
						'type'        => 'email',
						'value'       => $user_email,
						'placeholder' => __( 'Email address to receive payments', 'woocommerce' ),
						'description' => __( "Enter your email address and we'll authenticate payments for you. WooCommerce Services and Jetpack will be installed and activated for you.", 'woocommerce' ),
						'required'    => true,
					),
				),
			),
			'paypal'          => array(
				'name'        => __( 'PayPal Standard', 'woocommerce' ),
				'description' => __( 'Accept payments via PayPal using account balance or credit card.', 'woocommerce' ),
				'image'       => '',
				'settings'    => array(
					'email' => array(
						'label'       => __( 'PayPal email address:', 'woocommerce' ),
						'type'        => 'email',
						'value'       => $user_email,
						'placeholder' => __( 'PayPal email address', 'woocommerce' ),
						'required'    => true,
					),
				),
			),
			'klarna_checkout' => array(
				'name'        => __( 'Klarna Checkout', 'woocommerce' ),
				'description' => $klarna_checkout_description,
				'image'       => WC()->plugin_url() . '/assets/images/klarna-white.png',
				'enabled'     => true,
				'class'       => 'klarna-logo',
				'repo-slug'   => 'klarna-checkout-for-woocommerce',
			),
			'klarna_payments' => array(
				'name'        => __( 'Klarna Payments', 'woocommerce' ),
				'description' => $klarna_payments_description,
				'image'       => WC()->plugin_url() . '/assets/images/klarna-white.png',
				'enabled'     => true,
				'class'       => 'klarna-logo',
				'repo-slug'   => 'klarna-payments-for-woocommerce',
			),
			'square'          => array(
				'name'        => __( 'Square', 'woocommerce' ),
				'description' => $square_description,
				'image'       => WC()->plugin_url() . '/assets/images/square-white.png',
				'class'       => 'square-logo',
				'enabled'     => true,
				'repo-slug'   => 'woocommerce-square',
			),
		);
	}

	/**
	 * Simple array of "in cart" gateways to show in wizard.
	 *
	 * @return array
	 */
	public function get_wizard_in_cart_payment_gateways() {
		$gateways = $this->get_wizard_available_in_cart_payment_gateways();

		if ( ! current_user_can( 'install_plugins' ) ) {
			return array( 'paypal' => $gateways['paypal'] );
		}

		$country    = WC()->countries->get_base_country();
		$can_stripe = $this->is_stripe_supported_country( $country );

		if ( $this->is_klarna_checkout_supported_country( $country ) ) {
			$spotlight = 'klarna_checkout';
		} elseif ( $this->is_klarna_payments_supported_country( $country ) ) {
			$spotlight = 'klarna_payments';
		} elseif ( $this->is_square_supported_country( $country ) && get_option( 'woocommerce_sell_in_person' ) ) {
			$spotlight = 'square';
		}

		if ( isset( $spotlight ) ) {
			$offered_gateways = array(
				$spotlight    => $gateways[ $spotlight ],
				'ppec_paypal' => $gateways['ppec_paypal'],
			);
			if ( $can_stripe ) {
				$offered_gateways += array( 'stripe' => $gateways['stripe'] );
			}
			return $offered_gateways;
		}

		$offered_gateways = array();
		if ( $can_stripe ) {
			$gateways['stripe']['enabled']  = true;
			$gateways['stripe']['featured'] = true;
			$offered_gateways              += array( 'stripe' => $gateways['stripe'] );
		}
		$offered_gateways += array( 'ppec_paypal' => $gateways['ppec_paypal'] );
		return $offered_gateways;
	}

	/**
	 * Simple array of "manual" gateways to show in wizard.
	 *
	 * @return array
	 */
	protected function get_wizard_manual_payment_gateways() {
		$gateways = array(
			'cheque' => array(
				'name'        => _x( 'Check payments', 'Check payment method', 'woocommerce' ),
				'description' => __( 'A simple offline gateway that lets you accept a check as method of payment.', 'woocommerce' ),
				'image'       => '',
				'class'       => '',
			),
			'bacs'   => array(
				'name'        => __( 'Bank transfer (BACS) payments', 'woocommerce' ),
				'description' => __( 'A simple offline gateway that lets you accept BACS payment.', 'woocommerce' ),
				'image'       => '',
				'class'       => '',
			),
			'cod'    => array(
				'name'        => __( 'Cash on delivery', 'woocommerce' ),
				'description' => __( 'A simple offline gateway that lets you accept cash on delivery.', 'woocommerce' ),
				'image'       => '',
				'class'       => '',
			),
		);

		return $gateways;
	}

	/**
	 * Display service item in list.
	 *
	 * @param int   $item_id Item ID.
	 * @param array $item_info Item info array.
	 */
	public function display_service_item( $item_id, $item_info ) {
		$item_class = 'wc-wizard-service-item';
		if ( isset( $item_info['class'] ) ) {
			$item_class .= ' ' . $item_info['class'];
		}

		$previously_saved_settings = get_option( 'woocommerce_' . $item_id . '_settings' );

		// Show the user-saved state if it was previously saved.
		// Otherwise, rely on the item info.
		if ( is_array( $previously_saved_settings ) ) {
			$should_enable_toggle = 'yes' === $previously_saved_settings['enabled'];
		} else {
			$should_enable_toggle = isset( $item_info['enabled'] ) && $item_info['enabled'];
		}

		?>
		<li class="<?php echo esc_attr( $item_class ); ?>">
			<div class="wc-wizard-service-name">
				<?php if ( ! empty( $item_info['image'] ) ) : ?>
					<img src="<?php echo esc_attr( $item_info['image'] ); ?>" alt="<?php echo esc_attr( $item_info['name'] ); ?>" />
				<?php else : ?>
					<p><?php echo esc_html( $item_info['name'] ); ?></p>
				<?php endif; ?>
			</div>
			<div class="wc-wizard-service-description">
				<?php echo wp_kses_post( wpautop( $item_info['description'] ) ); ?>
				<?php if ( ! empty( $item_info['settings'] ) ) : ?>
					<div class="wc-wizard-service-settings <?php echo $should_enable_toggle ? '' : 'hide'; ?>">
						<?php foreach ( $item_info['settings'] as $setting_id => $setting ) : ?>
							<?php
							$is_checkbox = 'checkbox' === $setting['type'];

							if ( $is_checkbox ) {
								$checked = false;
								if ( isset( $previously_saved_settings[ $setting_id ] ) ) {
									$checked = 'yes' === $previously_saved_settings[ $setting_id ];
								}
							}
							if ( 'email' === $setting['type'] ) {
								$value = empty( $previously_saved_settings[ $setting_id ] )
									? $setting['value']
									: $previously_saved_settings[ $setting_id ];
							}
							?>
							<?php $input_id = $item_id . '_' . $setting_id; ?>
							<div class="<?php echo esc_attr( 'wc-wizard-service-setting-' . $input_id ); ?>">
								<label
									for="<?php echo esc_attr( $input_id ); ?>"
									class="<?php echo esc_attr( $input_id ); ?>"
								>
									<?php echo esc_html( $setting['label'] ); ?>
								</label>
								<input
									type="<?php echo esc_attr( $setting['type'] ); ?>"
									id="<?php echo esc_attr( $input_id ); ?>"
									class="<?php echo esc_attr( 'payment-' . $setting['type'] . '-input' ); ?>"
									name="<?php echo esc_attr( $input_id ); ?>"
									value="<?php echo esc_attr( isset( $value ) ? $value : $setting['value'] ); ?>"
									placeholder="<?php echo esc_attr( $setting['placeholder'] ); ?>"
									<?php echo ( $setting['required'] ) ? 'required' : ''; ?>
									<?php echo $is_checkbox ? checked( isset( $checked ) && $checked, true, false ) : ''; ?>
								/>
								<?php if ( ! empty( $setting['description'] ) ) : ?>
									<span class="wc-wizard-service-settings-description"><?php echo esc_html( $setting['description'] ); ?></span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="wc-wizard-service-enable">
				<span class="wc-wizard-service-toggle <?php echo esc_attr( $should_enable_toggle ? '' : 'disabled' ); ?>">
					<input
						id="wc-wizard-service-<?php echo esc_attr( $item_id ); ?>"
						type="checkbox"
						name="wc-wizard-service-<?php echo esc_attr( $item_id ); ?>-enabled"
						value="yes" <?php checked( $should_enable_toggle ); ?>
					/>
					<label for="wc-wizard-service-<?php echo esc_attr( $item_id ); ?>">
				</span>
			</div>
		</li>
		<?php
	}

	/**
	 * Is it a featured service?
	 *
	 * @param array $service Service info array.
	 * @return boolean
	 */
	public function is_featured_service( $service ) {
		return ! empty( $service['featured'] );
	}

	/**
	 * Is this a non featured service?
	 *
	 * @param array $service Service info array.
	 * @return boolean
	 */
	public function is_not_featured_service( $service ) {
		return ! $this->is_featured_service( $service );
	}

	/**
	 * Payment Step.
	 */
	public function wc_setup_payment() {
		$featured_gateways = array_filter( $this->get_wizard_in_cart_payment_gateways(), array( $this, 'is_featured_service' ) );
		$in_cart_gateways  = array_filter( $this->get_wizard_in_cart_payment_gateways(), array( $this, 'is_not_featured_service' ) );
		$manual_gateways   = $this->get_wizard_manual_payment_gateways();
		?>
		<h1><?php esc_html_e( 'Payment', 'woocommerce' ); ?></h1>
		<form method="post" class="wc-wizard-payment-gateway-form">
			<p>
				<?php
				printf(
					wp_kses(
						/* translators: %s: Link */
						__( 'WooCommerce can accept both online and offline payments. <a href="%s" target="_blank">Additional payment methods</a> can be installed later.', 'woocommerce' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
							),
						)
					),
					esc_url( admin_url( 'admin.php?page=wc-addons&section=payment-gateways' ) )
				);
				?>
			</p>
			<?php if ( $featured_gateways ) : ?>
			<ul class="wc-wizard-services featured">
				<?php
				foreach ( $featured_gateways as $gateway_id => $gateway ) {
					$this->display_service_item( $gateway_id, $gateway );
				}
				?>
			</ul>
			<?php endif; ?>
			<?php if ( $in_cart_gateways ) : ?>
			<ul class="wc-wizard-services in-cart">
				<?php
				foreach ( $in_cart_gateways as $gateway_id => $gateway ) {
					$this->display_service_item( $gateway_id, $gateway );
				}
				?>
			</ul>
			<?php endif; ?>
			<ul class="wc-wizard-services manual">
				<li class="wc-wizard-services-list-toggle closed">
					<div class="wc-wizard-service-name">
						<?php esc_html_e( 'Offline Payments', 'woocommerce' ); ?>
					</div>
					<div class="wc-wizard-service-description">
						<?php esc_html_e( 'Collect payments from customers offline.', 'woocommerce' ); ?>
					</div>
					<div class="wc-wizard-service-enable">
						<input class="wc-wizard-service-list-toggle" id="wc-wizard-service-list-toggle" type="checkbox">
						<label for="wc-wizard-service-list-toggle"></label>
					</div>
				</li>
				<?php
				foreach ( $manual_gateways as $gateway_id => $gateway ) {
					$this->display_service_item( $gateway_id, $gateway );
				}
				?>
			</ul>
			<p class="wc-setup-actions step">
				<button type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'woocommerce' ); ?>" name="save_step"><?php esc_html_e( 'Continue', 'woocommerce' ); ?></button>
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Payment Step save.
	 */
	public function wc_setup_payment_save() {
		check_admin_referer( 'wc-setup' );

		if (
			(
				// Install WooCommerce Services with Stripe to enable deferred account creation.
				! empty( $_POST['wc-wizard-service-stripe-enabled'] ) && // WPCS: CSRF ok, input var ok.
				! empty( $_POST['stripe_create_account'] ) // WPCS: CSRF ok, input var ok.
			) || (
				// Install WooCommerce Services with PayPal EC to enable proxied payments.
				! empty( $_POST['wc-wizard-service-ppec_paypal-enabled'] ) && // WPCS: CSRF ok, input var ok.
				! empty( $_POST['ppec_paypal_reroute_requests'] ) // WPCS: CSRF ok, input var ok.
			)
		) {
			$this->install_woocommerce_services();
		}

		$gateways = array_merge( $this->get_wizard_in_cart_payment_gateways(), $this->get_wizard_manual_payment_gateways() );

		foreach ( $gateways as $gateway_id => $gateway ) {
			// If repo-slug is defined, download and install plugin from .org.
			if ( ! empty( $gateway['repo-slug'] ) && ! empty( $_POST[ 'wc-wizard-service-' . $gateway_id . '-enabled' ] ) ) { // WPCS: CSRF ok, input var ok.
				$this->install_plugin( $gateway_id, $gateway );
			}

			$settings = array( 'enabled' => ! empty( $_POST[ 'wc-wizard-service-' . $gateway_id . '-enabled' ] ) ? 'yes' : 'no' );  // WPCS: CSRF ok, input var ok.

			// @codingStandardsIgnoreStart
			if ( ! empty( $gateway['settings'] ) ) {
				foreach ( $gateway['settings'] as $setting_id => $setting ) {
					$settings[ $setting_id ] = 'yes' === $settings['enabled'] && isset( $_POST[ $gateway_id . '_' . $setting_id ] )
						? wc_clean( wp_unslash( $_POST[ $gateway_id . '_' . $setting_id ] ) )
						: false;
				}
			}
			// @codingStandardsIgnoreSEnd

			if ( 'ppec_paypal' === $gateway_id && empty( $settings['reroute_requests'] ) ) {
				unset( $settings['enabled'] );
			}

			$settings_key = 'woocommerce_' . $gateway_id . '_settings';
			$previously_saved_settings = array_filter( (array) get_option( $settings_key, array() ) );
			update_option( $settings_key, array_merge( $previously_saved_settings, $settings ) );
		}

		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Extras.
	 */
	public function wc_setup_extras() {
		?>
		<h1><?php esc_html_e( 'Recommended Extras', 'woocommerce' ); ?></h1>
		<form method="post">
			<?php if ( $this->should_show_theme_extra() ) : ?>
			<ul class="wc-wizard-services featured">
				<li class="wc-wizard-service-item">
					<div class="wc-wizard-service-description">
						<h3><?php esc_html_e( 'Storefront Theme', 'woocommerce' ); ?></h3>
						<p>
							<?php
							$theme      = wp_get_theme();
							$theme_name = $theme['Name'];

							if ( $this->is_default_theme() ) {
								echo wp_kses_post( sprintf( __( 'The theme you are currently using is not optimized for WooCommerce. We recommend you switch to <a href="%s" title="Learn more about Storefront" target="_blank">Storefront</a>; our official, free, WooCommerce theme.', 'woocommerce' ), esc_url( 'https://woocommerce.com/storefront/' ) ) );
							} else {
								echo wp_kses_post( sprintf( __( 'The theme you are currently using does not fully support WooCommerce. We recommend you switch to <a href="%s" title="Learn more about Storefront" target="_blank">Storefront</a>; our official, free, WooCommerce theme.', 'woocommerce' ), esc_url( 'https://woocommerce.com/storefront/' ) ) );
							}
							?>
						</p>
						<p>
							<?php echo wp_kses_post( sprintf( __( 'If toggled on, Storefront will be installed for you, and <em>%s</em> theme will be deactivated.', 'woocommerce' ), esc_html( $theme_name ) ) ); ?>
						</p>
					</div>

					<div class="wc-wizard-service-enable">
						<span class="wc-wizard-service-toggle">
							<input id="setup_storefront_theme" type="checkbox" name="setup_storefront_theme" value="yes" checked="checked" />
							<label for="setup_storefront_theme">
						</span>
					</div>
				</li>
			</ul>
			<?php endif; ?>
			<?php if ( $this->should_show_automated_tax_extra() ) : ?>
				<ul class="wc-wizard-services featured">
					<li class="wc-wizard-service-item <?php echo get_option( 'woocommerce_setup_automated_taxes' ) ? 'checked' : ''; ?>">
						<div class="wc-wizard-service-description">
							<h3><?php esc_html_e( 'Automated Taxes (powered by WooCommerce Services)', 'woocommerce' ); ?></h3>
							<p>
								<?php esc_html_e( 'Automatically calculate and charge the correct rate of tax for each time a customer checks out. If toggled on, WooCommerce Services and Jetpack will be installed and activated for you.', 'woocommerce' ); ?>
							</p>
							<p class="wc-wizard-service-learn-more">
								<a href="<?php echo esc_url( 'https://wordpress.org/plugins/woocommerce-services/' ); ?>" target="_blank">
									<?php esc_html_e( 'Learn more about WooCommerce Services', 'woocommerce' ); ?>
								</a>
							</p>
						</div>

						<div class="wc-wizard-service-enable">
						<span class="wc-wizard-service-toggle <?php echo get_option( 'woocommerce_setup_automated_taxes' ) ? '' : 'disabled'; ?>">
							<input
								id="setup_automated_taxes"
								type="checkbox"
								name="setup_automated_taxes"
								value="yes"
								<?php checked( get_option( 'woocommerce_setup_automated_taxes', 'no' ), 'yes' ); ?>
							/>
							<label for="setup_automated_taxes">
						</span>
						</div>
					</li>
				</ul>
			<?php endif; ?>
			<p class="wc-setup-actions step">
				<button type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'woocommerce' ); ?>" name="save_step"><?php esc_html_e( 'Continue', 'woocommerce' ); ?></button>
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Extras step save.
	 */
	public function wc_setup_extras_save() {
		check_admin_referer( 'wc-setup' );

		$setup_automated_tax = isset( $_POST['setup_automated_taxes'] ) && 'yes' === $_POST['setup_automated_taxes'];
		$install_storefront  = isset( $_POST['setup_storefront_theme'] ) && 'yes' === $_POST['setup_storefront_theme'];

		update_option( 'woocommerce_calc_taxes', $setup_automated_tax ? 'yes' : 'no' );
		update_option( 'woocommerce_setup_automated_taxes', $setup_automated_tax );

		if ( $setup_automated_tax ) {
			$this->install_woocommerce_services();
		}

		if ( $install_storefront ) {
			$this->install_theme( 'storefront' );
		}

		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Go to the next step if Jetpack was connected.
	 */
	protected function wc_setup_activate_actions() {
		if (
			isset( $_GET['from'] ) &&
			'wpcom' === $_GET['from'] &&
			class_exists( 'Jetpack' ) &&
			Jetpack::is_active()
		) {
			wp_redirect( esc_url_raw( remove_query_arg( 'from', $this->get_next_step_link() ) ) );
			exit;
		}
	}

	protected function wc_setup_activate_get_description() {
		$description     = false;
		$stripe_settings = get_option( 'woocommerce_stripe_settings', false );
		$stripe_enabled  = is_array( $stripe_settings )
			&& isset( $stripe_settings['create_account'] ) && 'yes' === $stripe_settings['create_account']
			&& isset( $stripe_settings['enabled'] ) && 'yes' === $stripe_settings['enabled'];
		$ppec_settings   = get_option( 'woocommerce_ppec_paypal_settings', false );
		$ppec_enabled    = is_array( $ppec_settings )
			&& isset( $ppec_settings['reroute_requests'] ) && 'yes' === $ppec_settings['reroute_requests']
			&& isset( $ppec_settings['enabled'] ) && 'yes' === $ppec_settings['enabled'];
		$payment_enabled = $stripe_enabled || $ppec_enabled;
		$taxes_enabled   = (bool) get_option( 'woocommerce_setup_automated_taxes', false );
		$domestic_rates  = (bool) get_option( 'woocommerce_setup_domestic_live_rates_zone', false );
		$intl_rates      = (bool) get_option( 'woocommerce_setup_intl_live_rates_zone', false );
		$rates_enabled   = $domestic_rates || $intl_rates;

		/* translators: %s: list of features, potentially comma separated */
		$description_base = __( 'Your store is almost ready! To activate services like %s, just connect with Jetpack.', 'woocommerce' );

		if ( $payment_enabled && $taxes_enabled && $rates_enabled ) {
			$description = sprintf( $description_base, __( 'payment setup, automated taxes, live rates and discounted shipping labels', 'woocommerce' ) );
		} else if ( $payment_enabled && $taxes_enabled ) {
			$description = sprintf( $description_base, __( 'payment setup and automated taxes', 'woocommerce' ) );
		} else if ( $payment_enabled && $rates_enabled ) {
			$description = sprintf( $description_base, __( 'payment setup, live rates and discounted shipping labels', 'woocommerce' ) );
		} else if ( $payment_enabled ) {
			$description = sprintf( $description_base, __( 'payment setup', 'woocommerce' ) );
		} else if ( $taxes_enabled && $rates_enabled ) {
			$description = sprintf( $description_base, __( 'automated taxes, live rates and discounted shipping labels', 'woocommerce' ) );
		} else if ( $taxes_enabled ) {
			$description = sprintf( $description_base, __( 'automated taxes', 'woocommerce' ) );
		} else if ( $rates_enabled ) {
			$description = sprintf( $description_base, __( 'live rates and discounted shipping labels', 'woocommerce' ) );
		}

		return $description;
	}

	/**
	 * Activate step.
	 */
	public function wc_setup_activate() {
		$this->wc_setup_activate_actions();

		$has_jetpack_error = false;
		if ( isset( $_GET['activate_error'] ) ) {
			$has_jetpack_error = true;

			$title = __( "Sorry, we couldn't connect your store to Jetpack", 'woocommerce' );

			$error_message = $this->get_activate_error_message( sanitize_text_field( wp_unslash( $_GET['activate_error'] ) ) );
			$description = $error_message;
		} else {
			$description = $this->wc_setup_activate_get_description();
			$title = $description ?
				__( 'Connect your store to Jetpack', 'woocommerce' ) :
				__( 'Connect your store to Jetpack to enable extra features', 'woocommerce' );
		}
		?>
		<h1><?php echo esc_html( $title ); ?></h1>
		<p><?php echo esc_html( $description ); ?></p>
		<img
			class="jetpack-logo"
			src="<?php echo esc_url( WC()->plugin_url() . '/assets/images/jetpack_vertical_logo.png' ); ?>"
			alt="Jetpack logo"
		/>
		<?php if ( $has_jetpack_error ) : ?>
			<p class="wc-setup-actions step">
				<a
					href="<?php echo esc_url( $this->get_next_step_link() ); ?>"
					class="button-primary button button-large"
				>
					<?php esc_html_e( 'Finish setting up your store', 'woocommerce' ); ?>
				</a>
			</p>
		<?php else : ?>
			<p class="jetpack-terms">
				<?php
					printf(
						wp_kses_post( __( 'By connecting your site you agree to our fascinating <a href="%1$s" target="_blank">Terms of Service</a> and to <a href="%2$s" target="_blank">share details</a> with WordPress.com', 'woocommerce' ) ),
						'https://wordpress.com/tos',
						'https://jetpack.com/support/what-data-does-jetpack-sync'
					);
				?>
			</p>
			<form method="post" class="activate-jetpack">
				<p class="wc-setup-actions step">
					<button type="submit" class="button-primary button button-large" value="<?php esc_attr_e( 'Connect with Jetpack', 'woocommerce' ); ?>"><?php esc_html_e( 'Continue with Jetpack', 'woocommerce' ); ?></button>
				</p>
				<input type="hidden" name="save_step" value="activate" />
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</form>
			<h3 class="jetpack-reasons">
				<?php
					echo esc_html( $description ?
						__( "Bonus reasons you'll love Jetpack", 'woocommerce' ) :
						__( "Reasons you'll love Jetpack", 'woocommerce' )
					);
				?>
			</h3>
			<ul class="wc-wizard-features">
				<li class="wc-wizard-feature-item">
					<p class="wc-wizard-feature-name">
						<strong><?php esc_html_e( 'Better security', 'woocommerce' ); ?></strong>
					</p>
					<p class="wc-wizard-feature-description">
						<?php esc_html_e( 'Protect your store from unauthorized access.', 'woocommerce' ); ?>
					</p>
				</li>
				<li class="wc-wizard-feature-item">
					<p class="wc-wizard-feature-name">
						<strong><?php esc_html_e( 'Store stats', 'woocommerce' ); ?></strong>
					</p>
					<p class="wc-wizard-feature-description">
						<?php esc_html_e( 'Get insights on how your store is doing, including total sales, top products, and more.', 'woocommerce' ); ?>
					</p>
				</li>
				<li class="wc-wizard-feature-item">
					<p class="wc-wizard-feature-name">
						<strong><?php esc_html_e( 'Store monitoring', 'woocommerce' ); ?></strong>
					</p>
					<p class="wc-wizard-feature-description">
						<?php esc_html_e( 'Get an alert if your store is down for even a few minutes.', 'woocommerce' ); ?>
					</p>
				</li>
				<li class="wc-wizard-feature-item">
					<p class="wc-wizard-feature-name">
						<strong><?php esc_html_e( 'Product promotion', 'woocommerce' ); ?></strong>
					</p>
					<p class="wc-wizard-feature-description">
						<?php esc_html_e( "Share new items on social media the moment they're live in your store.", 'woocommerce' ); ?>
					</p>
				</li>
			</ul>
		<?php endif; ?>
	<?php
	}

	protected function get_all_activate_errors() {
		return array(
			'default' => __( "Sorry! We tried, but we couldn't connect Jetpack just now 😭. Please go to the Plugins tab to connect Jetpack, so that you can finish setting up your store.", 'woocommerce' ),
			'jetpack_cant_be_installed' => __( "Sorry! We tried, but we couldn't install Jetpack for you 😭. Please go to the Plugins tab to install it, and finish setting up your store.", 'woocommerce' ),
			'register_http_request_failed' => __( "Sorry! We couldn't contact Jetpack just now 😭. Please make sure that your site is visible over the internet, and that it accepts incoming and outgoing requests via curl. You can also try to connect to Jetpack again, and if you run into any more issues, please contact support.", 'woocommerce' ),
			'siteurl_private_ip_dev' => __( "Your site might be on a private network. Jetpack can only connect to public sites. Please make sure your site is visible over the internet, and then try connecting again 🙏." , 'woocommerce' ),
		);
	}

	protected function get_activate_error_message( $code = '' ) {
		$errors = $this->get_all_activate_errors();
		return array_key_exists( $code, $errors ) ? $errors[ $code ] : $errors['default'];
	}

	/**
	 * Activate step save.
	 *
	 * Install, activate, and launch connection flow for Jetpack.
	 */
	public function wc_setup_activate_save() {
		check_admin_referer( 'wc-setup' );

		// Leave a note for WooCommerce Services that Jetpack has been opted into.
		update_option( 'woocommerce_setup_jetpack_opted_in', true );

		WC_Install::background_installer( 'jetpack', array(
			'file'      => 'jetpack/jetpack.php',
			'name'      => __( 'Jetpack', 'woocommerce' ),
			'repo-slug' => 'jetpack',
		) );

		// Did Jetpack get successfully installed?
		if ( ! class_exists( 'Jetpack' ) ) {
			wp_redirect( esc_url_raw( add_query_arg( 'activate_error', 'jetpack_cant_be_installed' ) ) );
			exit;
		}

		Jetpack::maybe_set_version_option();
		$register_result = Jetpack::try_registration();

		if ( is_wp_error( $register_result ) ) {
			$result_error_code = $register_result->get_error_code();
			$jetpack_error_code = array_key_exists( $result_error_code, $this->get_all_activate_errors() ) ? $result_error_code : 'register';
			wp_redirect( esc_url_raw( add_query_arg( 'activate_error', $jetpack_error_code ) ) );
			exit;
		}

		$redirect_url = esc_url_raw( add_query_arg( array(
			'page'           => 'wc-setup',
			'step'           => 'activate',
			'from'           => 'wpcom',
			'activate_error' => false,
		), admin_url() ) );
		$connection_url = Jetpack::init()->build_connect_url( true, $redirect_url, 'woocommerce-setup-wizard' );

		wp_redirect( esc_url_raw( $connection_url ) );
		exit;
	}

	/**
	 * Final step.
	 */
	public function wc_setup_ready() {
		// We've made it! Don't prompt the user to run the wizard again.
		WC_Admin_Notices::remove_notice( 'install' );

		$user_email   = $this->get_current_user_email();
		$videos_url   = 'https://docs.woocommerce.com/document/woocommerce-guided-tour-videos/?utm_source=setupwizard&utm_medium=product&utm_content=videos&utm_campaign=woocommerceplugin';
		$docs_url     = 'https://docs.woocommerce.com/documentation/plugins/woocommerce/getting-started/?utm_source=setupwizard&utm_medium=product&utm_content=docs&utm_campaign=woocommerceplugin';
		$help_text    = sprintf(
			/* translators: %1$s: link to videos, %2$s: link to docs */
			__( 'Watch our <a href="%1$s" target="_blank">guided tour videos</a> to learn more about WooCommerce, and visit WooCommerce.com to learn more about <a href="%2$s" target="_blank">getting started</a>.', 'woocommerce' ),
			$videos_url,
			$docs_url
		);
		?>
		<h1><?php esc_html_e( "You're ready to start selling!", 'woocommerce' ); ?></h1>

		<div class="woocommerce-message woocommerce-newsletter">
			<p><?php esc_html_e( "We're here for you — get tips, product updates, and inspiration straight to your mailbox.", 'woocommerce' ); ?></p>
			<form action="//woocommerce.us8.list-manage.com/subscribe/post?u=2c1434dc56f9506bf3c3ecd21&amp;id=13860df971" method="post" target="_blank" novalidate>
				<div class="newsletter-form-container">
					<input
						class="newsletter-form-email"
						type="email"
						value="<?php echo esc_attr( $user_email ); ?>"
						name="EMAIL"
						placeholder="<?php esc_attr_e( 'Email address', 'woocommerce' ); ?>"
						required
					>
					<p class="wc-setup-actions step newsletter-form-button-container">
						<button
							type="submit"
							value="<?php esc_html_e( 'Yes please!', 'woocommerce' ); ?>"
							name="subscribe"
							id="mc-embedded-subscribe"
							class="button-primary button newsletter-form-button"
						><?php esc_html_e( 'Yes please!', 'woocommerce' ); ?></button>
					</p>
				</div>
			</form>
		</div>

		<ul class="wc-wizard-next-steps">
			<li class="wc-wizard-next-step-item">
				<div class="wc-wizard-next-step-description">
					<p class="next-step-heading"><?php esc_html_e( 'Next step', 'woocommerce' ); ?></p>
					<h3 class="next-step-description"><?php esc_html_e( 'Create your first product', 'woocommerce' ); ?></h3>
					<p class="next-step-extra-info"><?php esc_html_e( "You're ready to add your first product.", 'woocommerce' ); ?></p>
				</div>
				<div class="wc-wizard-next-step-action">
					<p class="wc-setup-actions step">
						<a class="button button-primary button-large" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product&tutorial=true' ) ); ?>">
							<?php esc_html_e( 'Create a product', 'woocommerce' ); ?>
						</a>
					</p>
				</div>
			</li>
			<li class="wc-wizard-next-step-item">
				<div class="wc-wizard-next-step-description">
					<p class="next-step-heading"><?php esc_html_e( 'Have an existing store?', 'woocommerce' ); ?></p>
					<h3 class="next-step-description"><?php esc_html_e( 'Import products', 'woocommerce' ); ?></h3>
					<p class="next-step-extra-info"><?php esc_html_e( 'Transfer existing products to your new store — just import a CSV file.', 'woocommerce' ); ?></p>
				</div>
				<div class="wc-wizard-next-step-action">
					<p class="wc-setup-actions step">
						<a class="button button-large" href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=product_importer' ) ); ?>">
							<?php esc_html_e( 'Import products', 'woocommerce' ); ?>
						</a>
					</p>
				</div>
			</li>
		</ul>
		<p class="next-steps-help-text"><?php echo wp_kses_post( $help_text ); ?></p>
		<?php
	}
}

new WC_Admin_Setup_Wizard();
