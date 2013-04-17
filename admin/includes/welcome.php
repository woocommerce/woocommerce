<?php
/**
 * Welcome Page Class
 *
 * Shows a feature overview for the new version (major) and credits.
 *
 * Adapted from code in EDD (Copyright (c) 2012, Pippin Williamson) and WP.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.0.0
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WC_Welcome_Page class.
 *
 * @since 2.0
 */
class WC_Welcome_Page {

	private $plugin;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->plugin             = 'woocommerce/woocommerce.php';

		add_action( 'admin_menu', array( $this, 'admin_menus') );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'welcome'    ) );
	}

	/**
	 * Add admin menus/screens
	 *
	 * @access public
	 * @return void
	 */
	public function admin_menus() {

		$welcome_page_title = __( 'Welcome to WooCommerce', 'woocommerce' );

		// About
		$about = add_dashboard_page( $welcome_page_title, $welcome_page_title, 'manage_options', 'wc-about', array( $this, 'about_screen' ) );

		// Credits
		$credits = add_dashboard_page( $welcome_page_title, $welcome_page_title, 'manage_options', 'wc-credits', array( $this, 'credits_screen' ) );

		add_action( 'admin_print_styles-'. $about, array( $this, 'admin_css' ) );
		add_action( 'admin_print_styles-'. $credits, array( $this, 'admin_css' ) );
	}

	/**
	 * admin_css function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_css() {
		wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', dirname( dirname( __FILE__ ) ) ) );
	}

	/**
	 * Add styles just for this page, and remove dashboard page links.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_head() {
		global $woocommerce;

		remove_submenu_page( 'index.php', 'wc-about' );
		remove_submenu_page( 'index.php', 'wc-credits' );

		// Badge for welcome page
		$badge_url = $woocommerce->plugin_url() . '/assets/images/welcome/wc-badge.png';
		?>
		<style type="text/css">
			/*<![CDATA[*/
			.wc-badge {
				padding-top: 150px;
				height: 52px;
				width: 185px;
				color: #9c5d90;
				font-weight: bold;
				font-size: 14px;
				text-align: center;
				text-shadow: 0 1px 0 rgba(255, 255, 255, 0.6);
				margin: 0 -5px;
				background: url('<?php echo $woocommerce->plugin_url() . '/assets/images/welcome/wc-welcome.png'; ?>') no-repeat center center;
			}

			@media
			(-webkit-min-device-pixel-ratio: 2),
			(min-resolution: 192dpi) {
				.wc-badge {
					background-image:url('<?php echo $woocommerce->plugin_url() . '/assets/images/welcome/wc-welcome@2x.png'; ?>');
					background-size: 173px 194px;
				}
			}

			.about-wrap .wc-badge {
				position: absolute;
				top: 0;
				right: 0;
			}
			/*]]>*/
		</style>
		<?php
	}

	/**
	 * Into text/links shown on all about pages.
	 *
	 * @access private
	 * @return void
	 */
	private function intro() {
		global $woocommerce;

		// Flush after upgrades
		if ( ! empty( $_GET['wc-updated'] ) || ! empty( $_GET['wc-installed'] ) )
			flush_rewrite_rules();

		// Drop minor version if 0
		$major_version = substr( $woocommerce->version, 0, 3 );
		?>
		<h1><?php printf( __( 'Welcome to WooCommerce %s', 'woocommerce' ), $major_version ); ?></h1>

		<div class="about-text woocommerce-about-text">
			<?php
				if ( ! empty( $_GET['wc-installed'] ) )
					$message = __( 'Thanks, all done!', 'woocommerce' );
				elseif ( ! empty( $_GET['wc-updated'] ) )
					$message = __( 'Thank you for updating to the latest version!', 'woocommerce' );
				else
					$message = __( 'Thanks for installing!', 'woocommerce' );

				printf( __( '%s WooCommerce %s is more powerful, stable, and secure than ever before. We hope you enjoy it.', 'woocommerce' ), $message, $major_version );
			?>
		</div>

		<div class="wc-badge"><?php printf( __( 'Version %s', 'woocommerce' ), $woocommerce->version ); ?></div>

		<p class="woocommerce-actions">
			<a href="<?php echo admin_url('admin.php?page=woocommerce_settings'); ?>" class="button button-primary"><?php _e( 'Settings', 'woocommerce' ); ?></a>
			<a class="docs button button-primary" href="http://docs.woothemes.com/documentation/plugins/woocommerce/"><?php _e( 'Docs', 'woocommerce' ); ?></a>
			<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/woocommerce/" data-text="A open-source (free) #ecommerce plugin for #WordPress that helps you sell anything. Beautifully." data-via="WooThemes" data-size="large" data-hashtags="WooCommerce">Tweet</a>
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
	 *
	 * @access public
	 * @return void
	 */
	public function about_screen() {
		global $woocommerce;
		?>
		<div class="wrap about-wrap">

			<?php $this->intro(); ?>

			<!--<div class="changelog point-releases"></div>-->

			<div class="changelog">

				<h3><?php _e( 'Security in mind', 'woocommerce' ); ?></h3>

				<div class="feature-section images-stagger-right">
					<img src="<?php echo $woocommerce->plugin_url() . '/assets/images/welcome/badge-sucuri.png'; ?>" alt="Sucuri Safe Plugin" style="padding: 1em" />
					<h4><?php _e( 'Sucuri Safe Plugin', 'woocommerce' ); ?></h4>
					<p><?php _e( 'You will be happy to learn that WooCommerce has been audited and certified by the Sucuri Security team. Whilst there is not much to be seen visually to understand the amount of work that went into this audit, rest assured that your website is powered by one of the most powerful and stable eCommerce plugins available.', 'woocommerce' ); ?></p>
				</div>

				<h3><?php _e( 'A Smoother Admin Experience', 'woocommerce' ); ?></h3>

				<div class="feature-section col three-col">

					<div>
						<img src="<?php echo $woocommerce->plugin_url() . '/assets/images/welcome/product.png'; ?>" alt="Product panel screenshot" style="width: 99%; margin: 0 0 1em;" />
						<h4><?php _e( 'New Product Panel', 'woocommerce' ); ?></h4>
						<p><?php _e( 'We have revised the product data panel making it cleaner, more streamlined, and more logical. Adding products is a breeze!', 'woocommerce' ); ?></p>
					</div>

					<div>
						<img src="<?php echo $woocommerce->plugin_url() . '/assets/images/welcome/orders.png'; ?>" alt="Order panel screenshot" style="width: 99%; margin: 0 0 1em;" />
						<h4><?php _e( 'Nicer Order Screens', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Order pages have had a cleanup, with a more easily scannable interface. We particularly like the new status icons!', 'woocommerce' ); ?></p>
					</div>

					<div class="last-feature">
						<img src="<?php echo $woocommerce->plugin_url() . '/assets/images/welcome/downloads.png'; ?>" alt="Download panel screenshot" style="width: 99%; margin: 0 0 1em;" />
						<h4><?php _e( 'Multi-Download Support', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Products can have multiple downloadable files - purchasers will get access to all the files added.', 'woocommerce' ); ?></p>
					</div>

				</div>

				<h3><?php _e( 'Less Taxing Taxes', 'woocommerce' ); ?></h3>

				<div class="feature-section col two-col">

					<img src="<?php echo $woocommerce->plugin_url() . '/assets/images/welcome/taxes.png'; ?>" alt="Tax Options" style="width:99%; margin: 0 0 1em 0;" />
					<div>
						<h4><?php _e( 'New Tax Input Panel', 'woocommerce' ); ?></h4>
						<p><?php _e( 'The tax input pages have been streamlined to make inputting taxes simpler - adding multiple taxes for a single jurisdiction is now much easier using the priority system. There is also CSV import/export support.', 'woocommerce' ); ?></p>
					</div>

					<div class="last-feature">
						<h4><?php _e( 'Improved Tax Options', 'woocommerce' ); ?></h4>
						<p><?php _e( 'As requested by some users, we now support taxing the billing address instead of shipping (optional), and we allow you to choose which tax class applies to shipping.', 'woocommerce' ); ?></p>
					</div>

				</div>

				<h3><?php _e( 'Product Listing Improvements Customers Will Love', 'woocommerce' ); ?></h3>

				<div class="feature-section col three-col">

					<div>
						<img src="<?php echo $woocommerce->plugin_url() . '/assets/images/welcome/sorting.png'; ?>" alt="Sorting" style="width: 99%; margin: 0 0 1em;" />
						<h4><?php _e( 'New Sorting Options', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Customers can now sort products by popularity and ratings.', 'woocommerce' ); ?></p>
					</div>

					<div>
						<img src="<?php echo $woocommerce->plugin_url() . '/assets/images/welcome/pagination.png'; ?>" alt="Pagination" style="width: 99%; margin: 0 0 1em;" />
						<h4><?php _e( 'Better Pagination and Result Counts', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Numbered pagination has been added to core, and we show the number of results found above the listings.', 'woocommerce' ); ?></p>
					</div>

					<div class="last-feature">
						<img src="<?php echo $woocommerce->plugin_url() . '/assets/images/welcome/rating.png'; ?>" alt="Ratings" style="width: 99%; margin: 0 0 1em;" />
						<h4><?php _e( 'Inline Star Rating Display', 'woocommerce' ); ?></h4>
						<p><?php _e( 'We have added star ratings to the catalog which are pulled from reviews.', 'woocommerce' ); ?></p>
					</div>

				</div>

			</div>

			<div class="changelog">
				<h3><?php _e( 'Under the Hood', 'woocommerce' ); ?></h3>

				<div class="feature-section col three-col">
					<div>
						<h4><?php _e( 'New product classes', 'woocommerce' ); ?></h4>
						<p><?php _e( 'The product classes have been rewritten and are now factory based. Much more extendable, and easier to query products using the new <code>get_product()</code> function.', 'woocommerce' ); ?></p>
					</div>

					<div>
						<h4><?php _e( 'Capability overhaul', 'woocommerce' ); ?></h4>
						<p><?php _e( 'More granular capabilities for admin/shop manager roles covering products, orders and coupons.', 'woocommerce' ); ?></p>
					</div>

					<div class="last-feature">
						<h4><?php _e( 'API Improvements', 'woocommerce' ); ?></h4>
						<p><?php _e( '<code>WC-API</code> now has real endpoints, and we\'ve optimised the gateways API significantly by only loading gateways when needed.', 'woocommerce' ); ?></p>
					</div>
				</div>
				<div class="feature-section col three-col">

					<div>
						<h4><?php _e( 'Cache-friendly cart widgets', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Cart widgets and other "fragments" are now pulled in via AJAX - this works wonders with static page caching.', 'woocommerce' ); ?></p>
					</div>

					<div>
						<h4><?php _e( 'Session handling', 'woocommerce' ); ?></h4>
						<p><?php _e( 'PHP SESSIONS have been a problem for many users in the past, so we\'ve developed our own handler using cookies and options to make these more reliable.', 'woocommerce' ); ?></p>
					</div>

					<div class="last-feature">
						<h4><?php _e( 'Retina Ready', 'woocommerce' ); ?></h4>
						<p><?php _e( 'All graphics within WC have been optimised for HiDPI displays.', 'woocommerce' ); ?></p>
					</div>

				</div>
				<div class="feature-section col three-col">

					<div>
						<h4><?php _e( 'Better stock handling', 'woocommerce' ); ?></h4>
						<p><?php _e( 'We have added an option to hold stock for unpaid orders (defaults to 60mins). When this time limit is reached, and the order is not paid for, stock is released and the order is cancelled.', 'woocommerce' ); ?></p>
					</div>

					<div>
						<h4><?php _e( 'Improved Line-item storage', 'woocommerce' ); ?></h4>
						<p><?php _e( 'We have changed how order items get stored making them easier (and faster) to access for reporting. Order items are no longer serialised within an order - they are stored within their own table.', 'woocommerce' ); ?></p>
					</div>

					<div class="last-feature">
						<h4><?php _e( 'Autoload', 'woocommerce' ); ?></h4>
						<p><?php _e( 'We have setup autoloading for classes - this has dramatically reduced memory usage in 2.0.', 'woocommerce' ); ?></p>
					</div>

				</div>
			</div>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'woocommerce_settings' ), 'admin.php' ) ) ); ?>"><?php _e( 'Go to WooCommerce Settings', 'woocommerce' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the credits.
	 *
	 * @access public
	 * @return void
	 */
	public function credits_screen() {
		?>
		<div class="wrap about-wrap">

			<?php $this->intro(); ?>

			<p class="about-description"><?php _e( 'WooCommerce is developed and maintained by a worldwide team of passionate individuals and backed by an awesome developer community. Want to see your name? <a href="https://github.com/woothemes/woocommerce/blob/master/CONTRIBUTING.md">Contribute to WooCommerce</a>.', 'woocommerce' ); ?></p>

			<?php echo $this->contributors(); ?>

		</div>
		<?php
	}

	/**
	 * Render Contributors List
	 *
	 * @access public
	 * @return string $contributor_list HTML formatted list of contributors.
	 */
	public function contributors() {
		$contributors = $this->get_contributors();

		if ( empty( $contributors ) )
			return '';

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
	 * Retreive list of contributors from GitHub.
	 *
	 * @access public
	 * @return void
	 */
	public function get_contributors() {
		$contributors = get_transient( 'woocommerce_contributors' );

		if ( false !== $contributors )
			return $contributors;

		$response = wp_remote_get( 'https://api.github.com/repos/woothemes/woocommerce/contributors', array( 'sslverify' => false ) );

		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) )
			return array();

		$contributors = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! is_array( $contributors ) )
			return array();

		set_transient( 'woocommerce_contributors', $contributors, 3600 );

		return $contributors;
	}

	/**
	 * Sends user to the welcome page on first activation
	 */
	public function welcome() {

		// Bail if no activation redirect transient is set
	    if ( ! get_transient( '_wc_activation_redirect' ) )
			return;

		// Delete the redirect transient
		delete_transient( '_wc_activation_redirect' );

		// Bail if we are waiting to install or update via the interface update/install links
		if ( get_option( '_wc_needs_update' ) == 1 || get_option( '_wc_needs_pages' ) == 1 )
			return;

		// Bail if activating from network, or bulk, or within an iFrame
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) )
			return;

		if ( ( isset( $_GET['action'] ) && 'upgrade-plugin' == $_GET['action'] ) && ( isset( $_GET['plugin'] ) && strstr( $_GET['plugin'], 'woocommerce.php' ) ) )
			return;

		wp_safe_redirect( admin_url( 'index.php?page=wc-about' ) );
		exit;
	}
}

new WC_Welcome_Page();