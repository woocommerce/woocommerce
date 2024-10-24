<?php
/**
 * WooCommerce Admin
 *
 * @class    WC_Admin
 * @package  WooCommerce\Admin
 * @version  2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Admin class.
 */
class WC_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'current_screen', array( $this, 'conditional_includes' ) );
		add_action( 'admin_init', array( $this, 'buffer' ), 1 );
		add_action( 'admin_init', array( $this, 'preview_emails' ) );
		add_action( 'admin_init', array( $this, 'prevent_admin_access' ) );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_action( 'admin_footer', 'wc_print_js', 25 );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );

		// Disable WXR export of schedule action posts.
		add_filter( 'action_scheduler_post_type_args', array( $this, 'disable_webhook_post_export' ) );

		// Add body class for WP 5.3+ compatibility.
		add_filter( 'admin_body_class', array( $this, 'include_admin_body_class' ), 9999 );

		// Add body class for Marketplace and My Subscriptions pages.
		if ( isset( $_GET['page'] ) && 'wc-addons' === $_GET['page'] ) {
			add_filter( 'admin_body_class', array( 'WC_Admin_Addons', 'filter_admin_body_classes' ) );
		}

		add_action('admin_init', function() {
			if ( isset( $_GET['email_preview'] ) && isset( $_GET['order_id'] ) && isset( $_GET['email_type'] ) ) {
				$order = wc_get_order( $_GET[ 'order_id' ] );
				add_filter( 'woocommerce_order_needs_shipping_address', '__return_true' );
				$orderOrSubscription = $order;
				if ( ! $order ) {
					if ( (
						strpos( $_GET[ 'email_type' ], 'WCS_' ) !== false )
						|| ( strpos( $_GET[ 'email_type' ], 'ASP_' ) !== false )
					) {
						$subscriptionProduct = new WC_Product_Subscription();
						$subscriptionProduct->set_name('Dummy Subscription Product');
						$subscriptionProduct->set_regular_price(29.99);
						$subscriptionProduct->set_description('This is a dummy subscription product for testing.');
						$subscriptionProduct->set_sku('dummy-subscription-001');
						$subscriptionProduct->set_virtual(true);
						$subscriptionProduct->set_stock_status('instock');

						$subscription = new WC_Subscription();
						$subscription->set_customer_id(1);
						$subscription->set_parent_id(0);
						$subscription->set_billing_first_name('John');
						$subscription->set_billing_last_name('Doe');
						$subscription->set_billing_email('john.doe@example.com');
						$subscription->add_product($subscriptionProduct, 1);
						$subscription->set_status('active');
						$subscription->set_billing_period('month');
						$subscription->set_billing_interval(1);
						$subscription->set_total(29.99);
						$subscription->update_dates([
							'trial_end' => strtotime('Y-m-d H:i:s', time()),
							'next_payment' => strtotime('Y-m-d H:i:s', time()),
							'cancelled' => strtotime('Y-m-d H:i:s', time()),
							'payment_retry' => strtotime('Y-m-d H:i:s', time()),
							'end' => strtotime('Y-m-d H:i:s', time()),
						]);
						$orderOrSubscription = $subscription;
					} else {
						// Create a dummy product object
						$product = new WC_Product();
						$product->set_name( 'Dummy Product' );
						$product->set_price( 25.99 );
						$product->set_regular_price( 30.00 );
						$product->set_sale_price( 25.99 );
						$product->set_sku( 'DUMMY_SKU_2' );
						$product->set_description( 'This is a dummy product description.' );
						$product->set_short_description( 'Short description for the dummy product.' );
						$product->set_image_id( 30 );

						$order = new WC_Order();
						$order->add_product( wc_get_product(43), 2 );
						$order->set_id( rand(1000000, 9000000) );
						$order->set_date_created( time() );

						$address = array(
							'first_name' => 'John',
							'last_name'  => 'Doe',
							'company'    => 'Dummy Company',
							'email'      => 'john@example.com',
							'phone'      => '555-555-5555',
							'address_1'  => '123 Fake Street',
							'address_2'  => '',
							'city'       => 'Faketown',
							'postcode'   => '12345',
							'country'    => 'US',
							'state'      => 'CA',
						);
						$order->set_address( $address, 'billing' );
						$order->set_address( $address, 'shipping' );
						$order->set_payment_method( 'bacs' );  // Bank Transfer for example
						$order->set_currency( 'USD' );
						$order->set_total( 104.00 );
						$orderOrSubscription = $order;
					}
				}

				$emails = WC()->mailer()->get_emails();
				if ( isset( $emails[ $_GET[ 'email_type' ] ] ) ) {
					$email = $emails[ $_GET[ 'email_type' ] ];
					$email->set_order( $orderOrSubscription );
					$content = $email->get_content_html();
					$styles = $_GET[ 'styles' ] ?? '{}';
					$styles = json_decode( urldecode( stripslashes( $styles ) ), true );
					echo apply_filters( 'woocommerce_mail_content', $email->style_inline( $content, $styles ) );
				}
				exit;
			}
		});
	}

	/**
	 * Output buffering allows admin screens to make redirects later on.
	 */
	public function buffer() {
		ob_start();
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		include_once __DIR__ . '/wc-admin-functions.php';
		include_once __DIR__ . '/wc-meta-box-functions.php';
		include_once __DIR__ . '/class-wc-admin-post-types.php';
		include_once __DIR__ . '/class-wc-admin-taxonomies.php';
		include_once __DIR__ . '/class-wc-admin-menus.php';
		include_once __DIR__ . '/class-wc-admin-customize.php';
		include_once __DIR__ . '/class-wc-admin-notices.php';
		include_once __DIR__ . '/class-wc-admin-assets.php';
		include_once __DIR__ . '/class-wc-admin-api-keys.php';
		include_once __DIR__ . '/class-wc-admin-webhooks.php';
		include_once __DIR__ . '/class-wc-admin-pointers.php';
		include_once __DIR__ . '/class-wc-admin-importers.php';
		include_once __DIR__ . '/class-wc-admin-exporters.php';

		// Help Tabs.
		if ( apply_filters( 'woocommerce_enable_admin_help_tab', true ) ) {
			include_once __DIR__ . '/class-wc-admin-help.php';
		}

		// Helper.
		include_once __DIR__ . '/helper/class-wc-helper.php';

		// Marketplace suggestions & related REST API.
		include_once __DIR__ . '/marketplace-suggestions/class-wc-marketplace-suggestions.php';
		include_once __DIR__ . '/marketplace-suggestions/class-wc-marketplace-updater.php';
	}

	/**
	 * Include admin files conditionally.
	 */
	public function conditional_includes() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		switch ( $screen->id ) {
			case 'dashboard':
			case 'dashboard-network':
				include __DIR__ . '/class-wc-admin-dashboard-setup.php';
				include __DIR__ . '/class-wc-admin-dashboard.php';
				break;
			case 'options-permalink':
				include __DIR__ . '/class-wc-admin-permalink-settings.php';
				break;
			case 'plugins':
				include __DIR__ . '/plugin-updates/class-wc-plugins-screen-updates.php';
				break;
			case 'update-core':
				include __DIR__ . '/plugin-updates/class-wc-updates-screen-updates.php';
				break;
			case 'users':
			case 'user':
			case 'profile':
			case 'user-edit':
				include __DIR__ . '/class-wc-admin-profile.php';
				break;
		}
	}

	/**
	 * Handle redirects to setup/welcome page after install and updates.
	 *
	 * The user must have access rights, and we must ignore the network/bulk plugin updaters.
	 */
	public function admin_redirects() {
		// Don't run this fn from Action Scheduler requests, as it would clear _wc_activation_redirect transient.
		// That means OBW would never be shown.
		if ( wc_is_running_from_async_action_scheduler() ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// Nonced plugin install redirects.
		if ( ! empty( $_GET['wc-install-plugin-redirect'] ) ) {
			$plugin_slug = wc_clean( wp_unslash( $_GET['wc-install-plugin-redirect'] ) );

			if ( current_user_can( 'install_plugins' ) && in_array( $plugin_slug, array( 'woocommerce-gateway-stripe' ), true ) ) {
				$nonce = wp_create_nonce( 'install-plugin_' . $plugin_slug );
				$url   = self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin_slug . '&_wpnonce=' . $nonce );
			} else {
				$url = admin_url( 'plugin-install.php?tab=search&type=term&s=' . $plugin_slug );
			}

			wp_safe_redirect( $url );
			exit;
		}

		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Prevent any user who cannot 'edit_posts' (subscribers, customers etc) from accessing admin.
	 */
	public function prevent_admin_access() {
		$prevent_access = false;

		// Do not interfere with admin-post or admin-ajax requests.
		$exempted_paths = array( 'admin-post.php', 'admin-ajax.php' );

		if (
			/**
			 * This filter is documented in ../wc-user-functions.php
			 *
			 * @since 3.6.0
			 */
			apply_filters( 'woocommerce_disable_admin_bar', true )
			&& isset( $_SERVER['SCRIPT_FILENAME'] )
			&& ! in_array( basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_FILENAME'] ) ) ), $exempted_paths, true )
		) {
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
			if ( ! ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'preview-mail' ) ) ) {
				die( 'Security check' );
			}

			// load the mailer class.
			$mailer = WC()->mailer();

			// get the preview email subject.
			$email_heading = __( 'HTML email template', 'woocommerce' );

			// get the preview email content.
			ob_start();
			include __DIR__ . '/views/html-email-template-preview.php';
			$message = ob_get_clean();

			// create a new email.
			$email = new WC_Email();

			// wrap the content with the email template and then add styles.
			$message = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $message ) ) );

			// print the preview email.
			// phpcs:ignore WordPress.Security.EscapeOutput
			echo $message;
			// phpcs:enable
			exit;
		}
	}

	/**
	 * Change the admin footer text on WooCommerce admin pages.
	 *
	 * @since  2.3
	 * @param  string $footer_text text to be rendered in the footer.
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
		if ( isset( $current_screen->id ) && apply_filters( 'woocommerce_display_admin_footer_text', in_array( $current_screen->id, $wc_pages, true ) ) ) {
			// Change the footer text.
			if ( ! get_option( 'woocommerce_admin_footer_text_rated' ) ) {
				$footer_text = sprintf(
					/* translators: 1: WooCommerce 2:: five stars */
					__( 'If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'woocommerce' ),
					sprintf( '<strong>%s</strong>', esc_html__( 'WooCommerce', 'woocommerce' ) ),
					'<a href="https://wordpress.org/support/plugin/woocommerce/reviews?rate=5#new-post" target="_blank" class="wc-rating-link" aria-label="' . esc_attr__( 'five star', 'woocommerce' ) . '" data-rated="' . esc_attr__( 'Thanks :)', 'woocommerce' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
				);
				wc_enqueue_js(
					"jQuery( 'a.wc-rating-link' ).on( 'click', function() {
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
	 * Disable WXR export of scheduled action posts.
	 *
	 * @since 3.6.2
	 *
	 * @param array $args Scheduled action post type registration args.
	 *
	 * @return array
	 */
	public function disable_webhook_post_export( $args ) {
		$args['can_export'] = false;
		return $args;
	}

	/**
	 * Include admin classes.
	 *
	 * @since 4.2.0
	 * @param string $classes Body classes string.
	 * @return string
	 */
	public function include_admin_body_class( $classes ) {
		if ( in_array( array( 'wc-wp-version-gte-53', 'wc-wp-version-gte-55' ), explode( ' ', $classes ), true ) ) {
			return $classes;
		}

		$raw_version   = get_bloginfo( 'version' );
		$version_parts = explode( '-', $raw_version );
		$version       = count( $version_parts ) > 1 ? $version_parts[0] : $raw_version;

		// Add WP 5.3+ compatibility class.
		if ( $raw_version && version_compare( $version, '5.3', '>=' ) ) {
			$classes .= ' wc-wp-version-gte-53';
		}

		// Add WP 5.5+ compatibility class.
		if ( $raw_version && version_compare( $version, '5.5', '>=' ) ) {
			$classes .= ' wc-wp-version-gte-55';
		}

		return $classes;
	}
}

return new WC_Admin();
