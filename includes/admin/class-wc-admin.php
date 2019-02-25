<?php
/**
 * WooCommerce Admin
 *
 * @package  WooCommerce/Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class WC_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var WC_Admin|null
	 */
	protected static $instance = null;

	/**
	 * Return singleston instance.
	 *
	 * @static
	 * @return WC_Admin
	 */
	final public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Singleton. Prevent clone.
	 */
	final public function __clone() {
		trigger_error( 'Singleton. No cloning allowed!', E_USER_ERROR ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
	}

	/**
	 * Singleton. Prevent serialization.
	 */
	final public function __wakeup() {
		trigger_error( 'Singleton. No serialization allowed!', E_USER_ERROR ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
	}

	/**
	 * Singleton. Prevent construct.
	 */
	final private function __construct() {}

	/**
	 * Hook into WP actions/filters.
	 */
	public function init() {
		add_action( 'plugins_loaded', array( 'WC_Helper_File_Headers', 'load' ) );
		add_action( 'init', array( 'WC_Helper', 'load' ) );
		add_action( 'init', array( WC_Admin_Importers::instance(), 'init' ) );
		add_action( 'init', array( WC_Admin_Post_Types::instance(), 'init' ) );
		add_action( 'init', array( WC_Admin_Taxonomies::instance(), 'init' ) );
		add_action( 'init', array( WC_Admin_Menus::instance(), 'init' ) );
		add_action( 'init', array( WC_Admin_Customize::instance(), 'init' ) );
		add_action( 'init', array( WC_Admin_Assets::instance(), 'init' ) );
		add_action( 'init', array( WC_Admin_Notices::instance(), 'init' ) );

		add_action( 'admin_init', array( $this, 'buffer' ), 1 );
		add_action( 'admin_init', array( $this, 'maybe_show_setup_wizard' ) );
		add_action( 'admin_init', array( $this, 'preview_emails' ) );
		add_action( 'admin_init', array( $this, 'prevent_admin_access' ) );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );

		add_action( 'current_screen', array( $this, 'setup_current_screen' ) );

		add_action( 'admin_footer', 'wc_print_js', 25 );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );

		add_action( 'wp_ajax_setup_wizard_check_jetpack', array( $this, 'setup_wizard_check_jetpack' ) );
	}

	/**
	 * Load WC admin classes on screens where they are used.
	 */
	public function setup_current_screen() {
		$current_screen = get_current_screen();

		if ( in_array( $current_screen->id, array( 'dashboard', 'dashboard-network' ), true ) ) {
			WC_Admin_Dashboard::instance()->init();
		}

		if ( 'options-permalink' === $current_screen->id ) {
			WC_Admin_Permalink_Settings::instance()->init();
		}

		if ( 'plugins' === $current_screen->id ) {
			WC_Plugins_Screen_Updates::instance()->init();
		}

		if ( 'update-core' === $current_screen->id ) {
			WC_Updates_Screen_Updates::instance()->init();
		}

		if ( in_array( $current_screen->id, array( 'users', 'user', 'profile', 'user-edit' ), true ) ) {
			WC_Admin_Profile::instance()->init();
		}

		if ( in_array( $current_screen->id, wc_get_screen_ids(), true ) ) {
			WC_Admin_Help::instance()->init();
		}
	}

	/**
	 * Output buffering allows admin screens to make redirects later on.
	 */
	public function buffer() {
		ob_start();
	}

	/**
	 * Show the setup wizard if the query string is present.
	 */
	public function maybe_show_setup_wizard() {
		if ( ! empty( $_GET['wc-setup'] ) && apply_filters( 'woocommerce_enable_setup_wizard', true ) && current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			WC_Admin_Setup_Wizard::instance()->setup_wizard();
		}
	}

	/**
	 * Handle redirects to setup/welcome page after install and updates.
	 *
	 * For setup wizard, transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
	 */
	public function admin_redirects() {
		// Nonced plugin install redirects (whitelisted)
		if ( ! empty( $_GET['wc-install-plugin-redirect'] ) ) {
			$plugin_slug = wc_clean( $_GET['wc-install-plugin-redirect'] );

			if ( current_user_can( 'install_plugins' ) && in_array( $plugin_slug, array( 'woocommerce-gateway-stripe' ) ) ) {
				$nonce = wp_create_nonce( 'install-plugin_' . $plugin_slug );
				$url   = self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin_slug . '&_wpnonce=' . $nonce );
			} else {
				$url = admin_url( 'plugin-install.php?tab=search&type=term&s=' . $plugin_slug );
			}

			wp_safe_redirect( $url );
			exit;
		}

		// Setup wizard redirect
		if ( get_transient( '_wc_activation_redirect' ) ) {
			delete_transient( '_wc_activation_redirect' );

			if ( ( ! empty( $_GET['page'] ) && in_array( $_GET['page'], array( 'wc-setup' ) ) ) || is_network_admin() || isset( $_GET['activate-multi'] ) || ! current_user_can( 'manage_woocommerce' ) || apply_filters( 'woocommerce_prevent_automatic_wizard_redirect', false ) ) {
				return;
			}

			// If the user needs to install, send them to the setup wizard
			if ( WC_Admin_Notices::has_notice( 'install' ) ) {
				wp_safe_redirect( admin_url( 'index.php?page=wc-setup' ) );
				exit;
			}
		}
	}

	/**
	 * Prevent any user who cannot 'edit_posts' (subscribers, customers etc) from accessing admin.
	 */
	public function prevent_admin_access() {
		$prevent_access = false;

		if ( apply_filters( 'woocommerce_disable_admin_bar', true ) && ! is_ajax() && basename( $_SERVER["SCRIPT_FILENAME"] ) !== 'admin-post.php' ) {
			$has_cap     = false;
			$access_caps = array( 'edit_posts', 'manage_woocommerce', 'view_admin_dashboard' );

			foreach ( $access_caps as $access_cap ) {
				if ( current_user_can( $access_cap ) ) {
					$has_cap = true;
					break;
				}
			}

			if ( ! $has_cap ) {
				$prevent_access = true;
			}
		}

		if ( apply_filters( 'woocommerce_prevent_admin_access', $prevent_access ) ) {
			wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
			exit;
		}
	}

	/**
	 * Preview email template.
	 */
	public function preview_emails() {

		if ( isset( $_GET['preview_woocommerce_mail'] ) ) {
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'preview-mail' ) ) {
				die( 'Security check' );
			}

			// load the mailer class
			$mailer = WC()->mailer();

			// get the preview email subject
			$email_heading = __( 'HTML email template', 'woocommerce' );

			// get the preview email content
			ob_start();
			include 'views/html-email-template-preview.php';
			$message = ob_get_clean();

			// create a new email
			$email = new WC_Email();

			// wrap the content with the email template and then add styles
			$message = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $message ) ) );

			// print the preview email
			echo $message;
			exit;
		}
	}

	/**
	 * Change the admin footer text on WooCommerce admin pages.
	 *
	 * @since  2.3
	 * @param  string $footer_text
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! function_exists( 'wc_get_screen_ids' ) ) {
			return $footer_text;
		}
		$current_screen = get_current_screen();
		$wc_pages       = wc_get_screen_ids();

		// Set only WC pages.
		$wc_pages = array_diff( $wc_pages, array( 'profile', 'user-edit' ) );

		// Check to make sure we're on a WooCommerce admin page.
		if ( isset( $current_screen->id ) && apply_filters( 'woocommerce_display_admin_footer_text', in_array( $current_screen->id, $wc_pages ) ) ) {
			// Change the footer text
			if ( ! get_option( 'woocommerce_admin_footer_text_rated' ) ) {
				$footer_text = sprintf(
					/* translators: 1: WooCommerce 2:: five stars */
					__( 'If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'woocommerce' ),
					sprintf( '<strong>%s</strong>', esc_html__( 'WooCommerce', 'woocommerce' ) ),
					'<a href="https://wordpress.org/support/plugin/woocommerce/reviews?rate=5#new-post" target="_blank" class="wc-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'woocommerce' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
				);
				wc_enqueue_js(
					"jQuery( 'a.wc-rating-link' ).click( function() {
						jQuery.post( '" . WC()->ajax_url() . "', { action: 'woocommerce_rated' } );
						jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
					});"
				);
			} else {
				$footer_text = __( 'Thank you for selling with WooCommerce.', 'woocommerce' );
			}
		}

		return $footer_text;
	}

	/**
	 * Check on a Jetpack install queued by the Setup Wizard.
	 *
	 * See: WC_Admin_Setup_Wizard::install_jetpack()
	 */
	public function setup_wizard_check_jetpack() {
		$jetpack_active = class_exists( 'Jetpack' );

		wp_send_json_success(
			array(
				'is_active' => $jetpack_active ? 'yes' : 'no',
			)
		);
	}

	/**
	 * Include any classes we need within admin.
	 *
	 * @deprecated in favour of autoloader.
	 */
	public function includes() {}

	/**
	 * Preloads some functionality of the Helper to be loaded on the `plugins_loaded` hook.
	 *
	 * @deprecated in favour of autoloader.
	 */
	public function preload_helper() {}

	/**
	 * Include admin files conditionally.
	 *
	 * @deprecated in favour of autoloader.
	 */
	public function conditional_includes() {}
}
