<?php
/**
 * Welcome Page Class
 *
 * Shows a feature overview for the new version (major) and credits.
 *
 * Adapted from code in EDD (Copyright (c) 2012, Pippin Williamson) and WP.
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
 * WC_Admin_Welcome class
 */
class WC_Admin_Welcome {

	/** @var array Tweets user can optionally send after install */
	private $tweets = array(
		'WooCommerce kickstarts online stores. It\'s free and has been downloaded over 6 million times.',
		'Building an online store? WooCommerce is the leading #eCommerce plugin for WordPress (and it\'s free).',
		'WooCommerce is a free #eCommerce plugin for #WordPress for selling #allthethings online, beautifully.',
		'Ready to ship your idea? WooCommerce is the fastest growing #eCommerce plugin for WordPress on the web'
	);

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus') );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		shuffle( $this->tweets );
	}

	/**
	 * Add admin menus/screens.
	 */
	public function admin_menus() {
		$welcome_page_name  = __( 'About WooCommerce', 'woocommerce' );
		$welcome_page_title = __( 'Welcome to WooCommerce', 'woocommerce' );

		switch ( $_GET['page'] ) {
			case 'wc-about' :
				$page = add_dashboard_page( $welcome_page_title, $welcome_page_name, 'manage_options', 'wc-about', array( $this, 'about_screen' ) );
				add_action( 'admin_print_styles-' . $page, array( $this, 'admin_css' ) );
			break;
			case 'wc-credits' :
				$page = add_dashboard_page( $welcome_page_title, $welcome_page_name, 'manage_options', 'wc-credits', array( $this, 'credits_screen' ) );
				add_action( 'admin_print_styles-' . $page, array( $this, 'admin_css' ) );
			break;
		}
	}

	/**
	 * admin_css function.
	 */
	public function admin_css() {
		wp_enqueue_style( 'woocommerce-activation', WC()->plugin_url() . '/assets/css/activation.css', array(), WC_VERSION );
	}

	/**
	 * Add styles just for this page, and remove dashboard page links.
	 */
	public function admin_head() {
		remove_submenu_page( 'index.php', 'wc-about' );
		remove_submenu_page( 'index.php', 'wc-credits' );
		?>
		<style type="text/css">
			/*<![CDATA[*/
			.wc-badge:before {
				font-family: WooCommerce !important;
				content: "\e03d";
				color: #fff;
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
				font-size: 80px;
				font-weight: normal;
				width: 165px;
				height: 165px;
				line-height: 165px;
				text-align: center;
				position: absolute;
				top: 0;
				<?php echo is_rtl() ? 'right' : 'left'; ?>: 0;
				margin: 0;
				vertical-align: middle;
			}
			.wc-badge {
				position: relative;
				background: #9c5d90;
				text-rendering: optimizeLegibility;
				padding-top: 150px;
				height: 52px;
				width: 165px;
				font-weight: 600;
				font-size: 14px;
				text-align: center;
				color: #ddc8d9;
				margin: 5px 0 0 0;
				-webkit-box-shadow: 0 1px 3px rgba(0,0,0,.2);
				box-shadow: 0 1px 3px rgba(0,0,0,.2);
			}
			.about-wrap .wc-badge {
				position: absolute;
				top: 0;
				<?php echo is_rtl() ? 'left' : 'right'; ?>: 0;
			}
			.about-wrap .feature-section {
				margin-bottom: 40px;
			}
			.about-wrap .last-feature-section {
				border-bottom: 0;
				padding-bottom: 0;
				margin-bottom: 0;
			}
			.about-wrap .wc-feature {
				overflow: visible !important;
				*zoom:1;
			}
			.about-wrap h3 + .wc-feature {
				margin-top: 0;
			}
			.about-wrap .wc-feature:before,
			.about-wrap .wc-feature:after {
				content: " ";
				display: table;
			}
			.about-wrap .wc-feature:after {
				clear: both;
			}
			.about-wrap div.icon {
				width: 0 !important;
				padding: 0;
				margin: 20px 0 !important;
			}
			.about-integrations {
				background: #fff;
				margin: 20px 0;
				padding: 1px 20px 10px;
			}
			.about-integrations .feature-section {
				padding: 20px 0;
			}
			.changelog h4 {
				line-height: 1.4;
			}
			/*]]>*/
		</style>
		<?php
	}

	/**
	 * Intro text/links shown on all about pages.
	 */
	private function intro() {
		// Drop minor version if 0
		$major_version = substr( WC()->version, 0, 3 );
		?>
		<h1><?php printf( __( 'Welcome to WooCommerce %s', 'woocommerce' ), $major_version ); ?></h1>

		<div class="about-text woocommerce-about-text">
			<?php
				if ( ! empty( $_GET['wc-installed'] ) ) {
					$message = __( 'Thanks, all done!', 'woocommerce' );
				} elseif ( ! empty( $_GET['wc-updated'] ) ) {
					$message = __( 'Thank you for updating to the latest version!', 'woocommerce' );
				} else {
					$message = __( 'Thanks for installing!', 'woocommerce' );
				}

				printf( __( '%s WooCommerce %s is more powerful, stable and secure than ever before. We hope you enjoy using it.', 'woocommerce' ), $message, $major_version );
			?>
		</div>

		<div class="wc-badge"><?php printf( __( 'Version %s', 'woocommerce' ), WC()->version ); ?></div>

		<p class="woocommerce-actions">
			<a href="<?php echo admin_url('admin.php?page=wc-settings'); ?>" class="button button-primary"><?php _e( 'Settings', 'woocommerce' ); ?></a>
			<a href="<?php echo esc_url( apply_filters( 'woocommerce_docs_url', 'http://docs.woothemes.com/documentation/plugins/woocommerce/', 'woocommerce' ) ); ?>" class="docs button button-primary"><?php _e( 'Docs', 'woocommerce' ); ?></a>
			<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/woocommerce/" data-text="<?php echo esc_attr( $this->tweets[0] ); ?>" data-via="WooThemes" data-size="large">Tweet</a>
			<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		</p>

		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php if ( $_GET['page'] == 'wc-about' ) echo 'nav-tab-active'; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wc-about' ), 'index.php' ) ) ); ?>">
				<?php _e( "What's New", 'woocommerce' ); ?>
			</a><a class="nav-tab <?php if ( $_GET['page'] == 'wc-credits' ) echo 'nav-tab-active'; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wc-credits' ), 'index.php' ) ) ); ?>">
				<?php _e( 'Credits', 'woocommerce' ); ?>
			</a>
		</h2>
		<?php
	}

	/**
	 * Output the about screen.
	 */
	public function about_screen() {
		?>
		<div class="wrap about-wrap">

			<?php $this->intro(); ?>

			<div class="changelog">
				<div class="changelog about-integrations">
					<div class="wc-feature feature-section last-feature-section col three-col">
						<div>
							<h4><?php _e( 'Improved Product Variation Editor', 'woocommerce' ); ?></h4>
							<p><?php _e( 'When editing product variations in the backend, we have added a new, paginated interface to make the process of adding complex product variations both quicker and more reliable.', 'woocommerce' ); ?></p>
						</div>
						<div>
							<h4><?php _e( 'Frontend Variation Performance', 'woocommerce' ); ?></h4>
							<p><?php _e( 'If your products have many variations (20+) they will use an ajax powered add-to-cart form. Select all options and the matching variation will be found via AJAX. This improves performance on the product page.', 'woocommerce' ); ?></p>
						</div>
						<div class="last-feature">
							<h4><?php _e( 'Flat Rate Shipping, Simplified', 'woocommerce' ); ?></h4>
							<p><?php _e( 'Flat Rate Shipping was overly complex in previous versions of WooCommerce. We have simplified the interface (without losing the flexibility) making Flat Rate and International Shipping much more intuitive.', 'woocommerce' ); ?></p>
						</div>
					</div>
				</div>
			</div>
			<div class="changelog">
				<div class="feature-section col three-col">
					<div>
						<h4><?php _e( 'Geolocation with Caching', 'woocommerce' ); ?></h4>
						<p><?php printf( __( 'If you use static caching you may have found geolocation did not work for non-logged-in customers. We have now introduced a new javascript based Geocaching solution to help. Enable this in the %ssettings%s.', 'woocommerce' ), '<a href="' . admin_url( 'admin.php?page=wc-settings' ) . '">', '</a>' ); ?></p>
					</div>
					<div>
						<h4><?php _e( 'Onboarding Experience', 'woocommerce' ); ?></h4>
						<p><?php _e( 'We have added our "WooCommerce 101" tutorial videos to the help tabs throughout admin if you need some help understanding how to use WooCommerce. New installs will also see the new setup wizard to help guide through initial setup.', 'woocommerce' ); ?></p>
					</div>
					<div class="last-feature">
						<h4><?php _e( 'Custom AJAX Endpoints', 'woocommerce' ); ?></h4>
						<p><?php printf( __( 'To improve performance on the frontend, we\'ve introduced new AJAX endpoints which avoid the overhead of making calls to admin-ajax.php for events such as adding products to the cart.', 'woocommerce' ), '<a href="https://wordpress.org/plugins/woocommerce-colors/">', '</a>' ); ?></p>
					</div>
				</div>
				<div class="feature-section last-feature-section col three-col">
					<div>
						<h4><?php _e( 'Visual API Authentication', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Services which integrate with the REST API can now use the visual authentication endpoint so a user can log in and grant API permission from a single page before being redirected back.', 'woocommerce' ); ?></p>
					</div>
					<div>
						<h4><?php _e( 'Email Notification Improvements', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Email templates have been improved to support a wider array of email clients, and extra notifications, such as partial refund notifications, have been included.', 'woocommerce' ); ?></p>
					</div>
					<div class="last-feature">
						<h4><?php _e( 'Shipping Method Priorities', 'woocommerce' ); ?></h4>
						<p><?php _e( 'To give more control over which shipping method is selected by default for customers, each method can now be given a numeric priority.', 'woocommerce' ); ?></p>
					</div>
				</div>
			</div>

			<hr />

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wc-settings' ), 'admin.php' ) ) ); ?>"><?php _e( 'Go to WooCommerce Settings', 'woocommerce' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the credits screen.
	 */
	public function credits_screen() {
		?>
		<div class="wrap about-wrap">

			<?php $this->intro(); ?>

			<p class="about-description"><?php printf( __( 'WooCommerce is developed and maintained by a worldwide team of passionate individuals and backed by an awesome developer community. Want to see your name? <a href="%s">Contribute to WooCommerce</a>.', 'woocommerce' ), 'https://github.com/woothemes/woocommerce/blob/master/CONTRIBUTING.md' ); ?></p>

			<?php echo $this->contributors(); ?>
		</div>
		<?php
	}

	/**
	 * Render Contributors List.
	 *
	 * @return string $contributor_list HTML formatted list of contributors.
	 */
	public function contributors() {
		$contributors = $this->get_contributors();

		if ( empty( $contributors ) ) {
			return '';
		}

		$contributor_list = '<ul class="wp-people-group">';

		foreach ( $contributors as $contributor ) {
			$contributor_list .= '<li class="wp-person">';
			$contributor_list .= sprintf( '<a href="%s" title="%s">',
				esc_url( 'https://github.com/' . $contributor->login ),
				esc_html( sprintf( __( 'View %s', 'woocommerce' ), $contributor->login ) )
			);
			$contributor_list .= sprintf( '<img src="%s" width="64" height="64" class="gravatar" alt="%s" />', esc_url( $contributor->avatar_url ), esc_html( $contributor->login ) );
			$contributor_list .= '</a>';
			$contributor_list .= sprintf( '<a class="web" href="%s">%s</a>', esc_url( 'https://github.com/' . $contributor->login ), esc_html( $contributor->login ) );
			$contributor_list .= '</a>';
			$contributor_list .= '</li>';
		}

		$contributor_list .= '</ul>';

		return $contributor_list;
	}

	/**
	 * Retrieve list of contributors from GitHub.
	 *
	 * @return mixed
	 */
	public function get_contributors() {
		$contributors = get_transient( 'woocommerce_contributors' );

		if ( false !== $contributors ) {
			return $contributors;
		}

		$response = wp_safe_remote_get( 'https://api.github.com/repos/woothemes/woocommerce/contributors' );

		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$contributors = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! is_array( $contributors ) ) {
			return array();
		}

		set_transient( 'woocommerce_contributors', $contributors, HOUR_IN_SECONDS );

		return $contributors;
	}
}

new WC_Admin_Welcome();
