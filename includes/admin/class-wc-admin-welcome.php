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
 * @version     2.1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Admin_Welcome class.
 */
class WC_Admin_Welcome {

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
		if ( empty( $_GET['page'] ) ) {
			return;
		}

		$welcome_page_name  = __( 'About WooCommerce', 'woocommerce' );
		$welcome_page_title = __( 'Welcome to WooCommerce', 'woocommerce' );

		switch ( $_GET['page'] ) {
			case 'wc-about' :
				$page = add_dashboard_page( $welcome_page_title, $welcome_page_name, 'manage_options', 'wc-about', array( $this, 'about_screen' ) );
				add_action( 'admin_print_styles-'. $page, array( $this, 'admin_css' ) );
			break;
			case 'wc-credits' :
				$page = add_dashboard_page( $welcome_page_title, $welcome_page_name, 'manage_options', 'wc-credits', array( $this, 'credits_screen' ) );
				add_action( 'admin_print_styles-'. $page, array( $this, 'admin_css' ) );
			break;
			case 'wc-translators' :
				$page = add_dashboard_page( $welcome_page_title, $welcome_page_name, 'manage_options', 'wc-translators', array( $this, 'translators_screen' ) );
				add_action( 'admin_print_styles-'. $page, array( $this, 'admin_css' ) );
			break;
		}
	}

	/**
	 * admin_css function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_css() {
		wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', WC_PLUGIN_FILE ), array(), WC_VERSION );
	}

	/**
	 * Add styles just for this page, and remove dashboard page links.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_head() {
		remove_submenu_page( 'index.php', 'wc-about' );
		remove_submenu_page( 'index.php', 'wc-credits' );
		remove_submenu_page( 'index.php', 'wc-translators' );

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
			.about-wrap .feature-rest div {
				width: 50% !important;
				padding-<?php echo is_rtl() ? 'left' : 'right'; ?>: 100px;
				-moz-box-sizing: border-box;
				box-sizing: border-box;
				margin: 0 !important;
			}
			.about-wrap .feature-rest div.last-feature {
				padding-<?php echo is_rtl() ? 'right' : 'left'; ?>: 100px;
				padding-<?php echo is_rtl() ? 'left' : 'right'; ?>: 0;
			}
			.about-wrap div.icon {
				width: 0 !important;
				padding: 0;
				margin: 0;
			}
			.about-wrap .feature-rest div.icon:before {
				font-family: WooCommerce !important;
				font-weight: normal;
				width: 100%;
				font-size: 170px;
				line-height: 125px;
				color: #9c5d90;
				display: inline-block;
				position: relative;
				text-align: center;
				speak: none;
				margin: <?php echo is_rtl() ? '0 -100px 0 0' : '0 0 0 -100px'; ?>;
				content: "\e01d";
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
			}
			.about-integrations {
				background: #fff;
				margin: 20px 0;
				padding: 1px 20px 10px;
			}
			.changelog h4 {
				line-height: 1.4;
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

		// Flush after upgrades
		if ( ! empty( $_GET['wc-updated'] ) || ! empty( $_GET['wc-installed'] ) )
			flush_rewrite_rules();

		// Drop minor version if 0
		$major_version = substr( WC()->version, 0, 3 );
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

				printf( __( '%s WooCommerce %s is more powerful, stable and secure than ever before. We hope you enjoy using it.', 'woocommerce' ), $message, $major_version );
			?>
		</div>

		<div class="wc-badge"><?php printf( __( 'Version %s', 'woocommerce' ), WC()->version ); ?></div>

		<p class="woocommerce-actions">
			<a href="<?php echo admin_url('admin.php?page=wc-settings'); ?>" class="button button-primary"><?php _e( 'Settings', 'woocommerce' ); ?></a>
			<a href="<?php echo esc_url( apply_filters( 'woocommerce_docs_url', 'http://docs.woothemes.com/documentation/plugins/woocommerce/', 'woocommerce' ) ); ?>" class="docs button button-primary"><?php _e( 'Docs', 'woocommerce' ); ?></a>
			<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/woocommerce/" data-text="A open-source (free) #ecommerce plugin for #WordPress that helps you sell anything. Beautifully." data-via="WooThemes" data-size="large" data-hashtags="WooCommerce">Tweet</a>
			<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		</p>

		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php if ( $_GET['page'] == 'wc-about' ) echo 'nav-tab-active'; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wc-about' ), 'index.php' ) ) ); ?>">
				<?php _e( "What's New", 'woocommerce' ); ?>
			</a><a class="nav-tab <?php if ( $_GET['page'] == 'wc-credits' ) echo 'nav-tab-active'; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wc-credits' ), 'index.php' ) ) ); ?>">
				<?php _e( 'Credits', 'woocommerce' ); ?>
			</a><a class="nav-tab <?php if ( $_GET['page'] == 'wc-translators' ) echo 'nav-tab-active'; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wc-translators' ), 'index.php' ) ) ); ?>">
				<?php _e( 'Translators', 'woocommerce' ); ?>
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

			<!--<div class="changelog point-releases"></div>-->

			<div class="changelog">
				<div class="wc-feature feature-rest feature-section col three-col">
					<div>
						<h4><?php _e( 'Perform partial refunds on orders', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Store owners can now do partial refunds on orders; define the refund amount, qty, and optionally restore inventory. If the gateway supports it, the payment can be automatically refunded too!', 'woocommerce' ); ?></p>
					</div>
					<div class="icon"></div>
					<div class="last-feature">
						<h4><?php _e( 'Updated order totals UI', 'woocommerce' ); ?></h4>
						<p><?php _e( 'To support the partial refund functionality, the order items and totals panels have been combined. As a result we have a simpler, more user friendly order page.', 'woocommerce' ); ?></p>
					</div>
				</div>
			</div>
			<div class="changelog about-integrations">
				<h3><?php _e( 'WooCommerce REST API version 2', 'woocommerce' ); ?></h3>
				<div class="wc-feature feature-section col three-col">
					<div>
						<h4><?php _e( 'Introducing PUT/POST/DELETE methods', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Update, delete and create orders, customers, products and coupons via the API.', 'woocommerce' ); ?></p>
					</div>
					<div>
						<h4><?php _e( 'Other enhancements', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Resources can now be ordered by any field you define for greater control over returned results. v2 also introduces an endpoint for getting product categories from your store.', 'woocommerce' ); ?></p>
					</div>
					<div class="last-feature">
						<h4><?php _e( 'Webhooks', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Trigger webhooks during events such as when an order is created. Opens up all kinds of external integration opportunities.', 'woocommerce' ); ?></p>
					</div>
				</div>
			</div>
			<div class="changelog">
				<div class="feature-section col three-col">
					<div>
						<h4><?php _e( 'Language pack downloader', 'woocommerce' ); ?></h4>
						<p><?php _e( 'Due to the size of PO and MO files, we have removed them from core and included our "Language Pack Downloader". International users can download and update their translation files easily from the dashboard.', 'woocommerce' ); ?></p>
					</div>
					<div>
						<h4><?php _e( 'Variation stock management', 'woocommerce' ); ?></h4>
						<p><?php _e( 'You can now set stock management options (such as backorder support) at variation level giving much greater control over stock.', 'woocommerce' ); ?></p>
					</div>
					<div class="last-feature">
						<h4><?php _e( 'Improved Payment Gateways', 'woocommerce' ); ?></h4>
						<p><?php _e( 'The Payment Gateway API has been enhanced to support refunds and storing transaction IDs.', 'woocommerce' ); ?></p>
					</div>
				</div>
			</div>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wc-settings' ), 'admin.php' ) ) ); ?>"><?php _e( 'Go to WooCommerce Settings', 'woocommerce' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the credits.
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
	 * Output the translators screen
	 */
	public function translators_screen() {
		?>
		<div class="wrap about-wrap">

			<?php $this->intro(); ?>

			<p class="about-description"><?php printf( __( 'WooCommerce has been kindly translated into several other languages thanks to our translation team. Want to see your name? <a href="%s">Translate WooCommerce</a>.', 'woocommerce' ), 'https://www.transifex.com/projects/p/woocommerce/' ); ?></p>

			<?php
				// Have to use this to get the list until the API is open...
				/*
				$contributor_json = json_decode( 'string from https://www.transifex.com/api/2/project/woocommerce/languages/', true );

				$contributors = array();

				foreach ( $contributor_json as $group ) {
					$contributors = array_merge( $contributors, $group['coordinators'], $group['reviewers'], $group['translators'] );
				}

				$contributors = array_filter( array_unique( $contributors ) );

				natsort( $contributors );

				foreach ( $contributors as $contributor ) {
					echo htmlspecialchars( '<a href="https://www.transifex.com/accounts/profile/' . $contributor . '">' . $contributor . '</a>, ' );
				}
				*/
			?>

			<p class="wp-credits-list">
				<a href="https://www.transifex.com/accounts/profile/ABSOLUTE_Web">ABSOLUTE_Web</a>, <a href="https://www.transifex.com/accounts/profile/AIRoman">AIRoman</a>, <a href="https://www.transifex.com/accounts/profile/Adam_Bajer">Adam_Bajer</a>, <a href="https://www.transifex.com/accounts/profile/Aerendir">Aerendir</a>, <a href="https://www.transifex.com/accounts/profile/Aliom">Aliom</a>, <a href="https://www.transifex.com/accounts/profile/Almaz">Almaz</a>, <a href="https://www.transifex.com/accounts/profile/Andriy.Gusak">Andriy.Gusak</a>, <a href="https://www.transifex.com/accounts/profile/AngeloLazzari">AngeloLazzari</a>, <a href="https://www.transifex.com/accounts/profile/Apelsinova">Apelsinova</a>, <a href="https://www.transifex.com/accounts/profile/ArtGoddess">ArtGoddess</a>, <a href="https://www.transifex.com/accounts/profile/Ashleyking">Ashleyking</a>, <a href="https://www.transifex.com/accounts/profile/AslanDoma">AslanDoma</a>, <a href="https://www.transifex.com/accounts/profile/Axium">Axium</a>, <a href="https://www.transifex.com/accounts/profile/Bhuvanendran">Bhuvanendran</a>, <a href="https://www.transifex.com/accounts/profile/Bitly">Bitly</a>, <a href="https://www.transifex.com/accounts/profile/Bogusław">Bogusław</a>, <a href="https://www.transifex.com/accounts/profile/Chaos">Chaos</a>, <a href="https://www.transifex.com/accounts/profile/Chea">Chea</a>, <a href="https://www.transifex.com/accounts/profile/Clausen">Clausen</a>, <a href="https://www.transifex.com/accounts/profile/Closemarketing">Closemarketing</a>, <a href="https://www.transifex.com/accounts/profile/CoachBirgit">CoachBirgit</a>, <a href="https://www.transifex.com/accounts/profile/Compute">Compute</a>, <a href="https://www.transifex.com/accounts/profile/DAJOHH">DAJOHH</a>, <a href="https://www.transifex.com/accounts/profile/DJIO">DJIO</a>, <a href="https://www.transifex.com/accounts/profile/Didierjr">Didierjr</a>, <a href="https://www.transifex.com/accounts/profile/Dimis13">Dimis13</a>, <a href="https://www.transifex.com/accounts/profile/Dmitrijb3">Dmitrijb3</a>, <a href="https://www.transifex.com/accounts/profile/EmilEriksen">EmilEriksen</a>, <a href="https://www.transifex.com/accounts/profile/Fdu4">Fdu4</a>, <a href="https://www.transifex.com/accounts/profile/Flobin">Flobin</a>, <a href="https://www.transifex.com/accounts/profile/FrancoBaccarini">FrancoBaccarini</a>, <a href="https://www.transifex.com/accounts/profile/Fredev">Fredev</a>, <a href="https://www.transifex.com/accounts/profile/GabrielGil">GabrielGil</a>, <a href="https://www.transifex.com/accounts/profile/GeertDD">GeertDD</a>, <a href="https://www.transifex.com/accounts/profile/Gonzalez74">Gonzalez74</a>, <a href="https://www.transifex.com/accounts/profile/Graya">Graya</a>, <a href="https://www.transifex.com/accounts/profile/Griga_M">Griga_M</a>, <a href="https://www.transifex.com/accounts/profile/Grześ">Grześ</a>, <a href="https://www.transifex.com/accounts/profile/Gustavogcps">Gustavogcps</a>, <a href="https://www.transifex.com/accounts/profile/HelgaRakel">HelgaRakel</a>, <a href="https://www.transifex.com/accounts/profile/Ian_Razwadowski">Ian_Razwadowski</a>, <a href="https://www.transifex.com/accounts/profile/JKKim">JKKim</a>, <a href="https://www.transifex.com/accounts/profile/JamesIng">JamesIng</a>, <a href="https://www.transifex.com/accounts/profile/Janjaapvandijk">Janjaapvandijk</a>, <a href="https://www.transifex.com/accounts/profile/JoakimAndersen">JoakimAndersen</a>, <a href="https://www.transifex.com/accounts/profile/Joeri">Joeri</a>, <a href="https://www.transifex.com/accounts/profile/JohnRevel">JohnRevel</a>, <a href="https://www.transifex.com/accounts/profile/KennethJ">KennethJ</a>, <a href="https://www.transifex.com/accounts/profile/Kiba_No_Ou">Kiba_No_Ou</a>, <a href="https://www.transifex.com/accounts/profile/Kind">Kind</a>, <a href="https://www.transifex.com/accounts/profile/Komarovski">Komarovski</a>, <a href="https://www.transifex.com/accounts/profile/Lazybadger">Lazybadger</a>, <a href="https://www.transifex.com/accounts/profile/Leones">Leones</a>, <a href="https://www.transifex.com/accounts/profile/M.Mellet">M.Mellet</a>, <a href="https://www.transifex.com/accounts/profile/Mastersky">Mastersky</a>, <a href="https://www.transifex.com/accounts/profile/Miefos">Miefos</a>, <a href="https://www.transifex.com/accounts/profile/Miodrag018">Miodrag018</a>, <a href="https://www.transifex.com/accounts/profile/MondayStar">MondayStar</a>, <a href="https://www.transifex.com/accounts/profile/Morten">Morten</a>, <a href="https://www.transifex.com/accounts/profile/NANARUIZS1989">NANARUIZS1989</a>, <a href="https://www.transifex.com/accounts/profile/NeoTrafy">NeoTrafy</a>, <a href="https://www.transifex.com/accounts/profile/Nettpilot">Nettpilot</a>, <a href="https://www.transifex.com/accounts/profile/Pal74">Pal74</a>, <a href="https://www.transifex.com/accounts/profile/Promosnet">Promosnet</a>, <a href="https://www.transifex.com/accounts/profile/Pytlas">Pytlas</a>, <a href="https://www.transifex.com/accounts/profile/RealFugu">RealFugu</a>, <a href="https://www.transifex.com/accounts/profile/Rhys">Rhys</a>, <a href="https://www.transifex.com/accounts/profile/Ricky1990">Ricky1990</a>, <a href="https://www.transifex.com/accounts/profile/RistoNiinemets">RistoNiinemets</a>, <a href="https://www.transifex.com/accounts/profile/Rudimidtgaard">Rudimidtgaard</a>, <a href="https://www.transifex.com/accounts/profile/Samf">Samf</a>, <a href="https://www.transifex.com/accounts/profile/Sasni">Sasni</a>, <a href="https://www.transifex.com/accounts/profile/SeaBiz">SeaBiz</a>, <a href="https://www.transifex.com/accounts/profile/SergeyBiryukov">SergeyBiryukov</a>, <a href="https://www.transifex.com/accounts/profile/Shimlesha">Shimlesha</a>, <a href="https://www.transifex.com/accounts/profile/SilverXp">SilverXp</a>, <a href="https://www.transifex.com/accounts/profile/SkyHiRider">SkyHiRider</a>, <a href="https://www.transifex.com/accounts/profile/SzLegradi">SzLegradi</a>, <a href="https://www.transifex.com/accounts/profile/TRFlavourart">TRFlavourart</a>, <a href="https://www.transifex.com/accounts/profile/Tarantulo">Tarantulo</a>, <a href="https://www.transifex.com/accounts/profile/Thalitapinheiro">Thalitapinheiro</a>, <a href="https://www.transifex.com/accounts/profile/TheJoe">TheJoe</a>, <a href="https://www.transifex.com/accounts/profile/ThemeBoy">ThemeBoy</a>, <a href="https://www.transifex.com/accounts/profile/TomiToivio">TomiToivio</a>, <a href="https://www.transifex.com/accounts/profile/TopOSScz">TopOSScz</a>, <a href="https://www.transifex.com/accounts/profile/Triheads">Triheads</a>, <a href="https://www.transifex.com/accounts/profile/Updulah">Updulah</a>, <a href="https://www.transifex.com/accounts/profile/UrgentTranslation">UrgentTranslation</a>, <a href="https://www.transifex.com/accounts/profile/Vaclad">Vaclad</a>, <a href="https://www.transifex.com/accounts/profile/Vinci">Vinci</a>, <a href="https://www.transifex.com/accounts/profile/Violyne">Violyne</a>, <a href="https://www.transifex.com/accounts/profile/WebArt.es">WebArt.es</a>, <a href="https://www.transifex.com/accounts/profile/Wen89">Wen89</a>, <a href="https://www.transifex.com/accounts/profile/Zouza">Zouza</a>, <a href="https://www.transifex.com/accounts/profile/Zuige">Zuige</a>, <a href="https://www.transifex.com/accounts/profile/aOOn">aOOn</a>, <a href="https://www.transifex.com/accounts/profile/abdmc">abdmc</a>, <a href="https://www.transifex.com/accounts/profile/abouolia">abouolia</a>, <a href="https://www.transifex.com/accounts/profile/adamedotco">adamedotco</a>, <a href="https://www.transifex.com/accounts/profile/adiuvo">adiuvo</a>, <a href="https://www.transifex.com/accounts/profile/ahmedbadawy">ahmedbadawy</a>, <a href="https://www.transifex.com/accounts/profile/akmalff">akmalff</a>, <a href="https://www.transifex.com/accounts/profile/akorsar">akorsar</a>, <a href="https://www.transifex.com/accounts/profile/alaa13212">alaa13212</a>, <a href="https://www.transifex.com/accounts/profile/alaershov">alaershov</a>, <a href="https://www.transifex.com/accounts/profile/alichani">alichani</a>, <a href="https://www.transifex.com/accounts/profile/alvarogois">alvarogois</a>, <a href="https://www.transifex.com/accounts/profile/amisfranky">amisfranky</a>, <a href="https://www.transifex.com/accounts/profile/amitgilad">amitgilad</a>, <a href="https://www.transifex.com/accounts/profile/andercola">andercola</a>, <a href="https://www.transifex.com/accounts/profile/andrey.lima.ramos">andrey.lima.ramos</a>, <a href="https://www.transifex.com/accounts/profile/anope">anope</a>, <a href="https://www.transifex.com/accounts/profile/arhipaiva">arhipaiva</a>, <a href="https://www.transifex.com/accounts/profile/arielk">arielk</a>, <a href="https://www.transifex.com/accounts/profile/aroland.hu">aroland.hu</a>, <a href="https://www.transifex.com/accounts/profile/artprojectgroup">artprojectgroup</a>, <a href="https://www.transifex.com/accounts/profile/aruffini">aruffini</a>, <a href="https://www.transifex.com/accounts/profile/asapvaleriy">asapvaleriy</a>, <a href="https://www.transifex.com/accounts/profile/audilu">audilu</a>, <a href="https://www.transifex.com/accounts/profile/aureliash">aureliash</a>, <a href="https://www.transifex.com/accounts/profile/avarx">avarx</a>, <a href="https://www.transifex.com/accounts/profile/axdil">axdil</a>, <a href="https://www.transifex.com/accounts/profile/badsha_eee">badsha_eee</a>, <a href="https://www.transifex.com/accounts/profile/badushich">badushich</a>, <a href="https://www.transifex.com/accounts/profile/banned">banned</a>, <a href="https://www.transifex.com/accounts/profile/baobinh152">baobinh152</a>, <a href="https://www.transifex.com/accounts/profile/bergslay">bergslay</a>, <a href="https://www.transifex.com/accounts/profile/blaagnu">blaagnu</a>, <a href="https://www.transifex.com/accounts/profile/blackieA">blackieA</a>, <a href="https://www.transifex.com/accounts/profile/bluecafe">bluecafe</a>, <a href="https://www.transifex.com/accounts/profile/bohoejgaard">bohoejgaard</a>, <a href="https://www.transifex.com/accounts/profile/bornforlogic">bornforlogic</a>, <a href="https://www.transifex.com/accounts/profile/busic">busic</a>, <a href="https://www.transifex.com/accounts/profile/cadoo">cadoo</a>, <a href="https://www.transifex.com/accounts/profile/calkut">calkut</a>, <a href="https://www.transifex.com/accounts/profile/carletto0282">carletto0282</a>, <a href="https://www.transifex.com/accounts/profile/cdevreugd">cdevreugd</a>, <a href="https://www.transifex.com/accounts/profile/cegomez">cegomez</a>, <a href="https://www.transifex.com/accounts/profile/cglaudel">cglaudel</a>, <a href="https://www.transifex.com/accounts/profile/claudiosmweb">claudiosmweb</a>, <a href="https://www.transifex.com/accounts/profile/clausRO">clausRO</a>, <a href="https://www.transifex.com/accounts/profile/clausewitz45">clausewitz45</a>, <a href="https://www.transifex.com/accounts/profile/coenjacobs">coenjacobs</a>, <a href="https://www.transifex.com/accounts/profile/cool2014">cool2014</a>, <a href="https://www.transifex.com/accounts/profile/corsonr">corsonr</a>, <a href="https://www.transifex.com/accounts/profile/cpelham">cpelham</a>, <a href="https://www.transifex.com/accounts/profile/cris701">cris701</a>, <a href="https://www.transifex.com/accounts/profile/cristi.dbr">cristi.dbr</a>, <a href="https://www.transifex.com/accounts/profile/culkman">culkman</a>, <a href="https://www.transifex.com/accounts/profile/dacthang1991">dacthang1991</a>, <a href="https://www.transifex.com/accounts/profile/danielp">danielp</a>, <a href="https://www.transifex.com/accounts/profile/danitag78">danitag78</a>, <a href="https://www.transifex.com/accounts/profile/darudar">darudar</a>, <a href="https://www.transifex.com/accounts/profile/deckerweb">deckerweb</a>, <a href="https://www.transifex.com/accounts/profile/deepinsource">deepinsource</a>, <a href="https://www.transifex.com/accounts/profile/dekaru">dekaru</a>, <a href="https://www.transifex.com/accounts/profile/delitestudio">delitestudio</a>, <a href="https://www.transifex.com/accounts/profile/denarefyev">denarefyev</a>, <a href="https://www.transifex.com/accounts/profile/dhikkay14">dhikkay14</a>, <a href="https://www.transifex.com/accounts/profile/dickysun">dickysun</a>, <a href="https://www.transifex.com/accounts/profile/didikpri">didikpri</a>, <a href="https://www.transifex.com/accounts/profile/difreo">difreo</a>, <a href="https://www.transifex.com/accounts/profile/disaada">disaada</a>, <a href="https://www.transifex.com/accounts/profile/dix.alex">dix.alex</a>, <a href="https://www.transifex.com/accounts/profile/doorbook">doorbook</a>, <a href="https://www.transifex.com/accounts/profile/dualcore">dualcore</a>, <a href="https://www.transifex.com/accounts/profile/dudlaj">dudlaj</a>, <a href="https://www.transifex.com/accounts/profile/e01">e01</a>, <a href="https://www.transifex.com/accounts/profile/edea">edea</a>, <a href="https://www.transifex.com/accounts/profile/eduardoarandah">eduardoarandah</a>, <a href="https://www.transifex.com/accounts/profile/egill">egill</a>, <a href="https://www.transifex.com/accounts/profile/elct9620">elct9620</a>, <a href="https://www.transifex.com/accounts/profile/ellena">ellena</a>, <a href="https://www.transifex.com/accounts/profile/elwins">elwins</a>, <a href="https://www.transifex.com/accounts/profile/embuck">embuck</a>, <a href="https://www.transifex.com/accounts/profile/emidiobattipaglia">emidiobattipaglia</a>, <a href="https://www.transifex.com/accounts/profile/endestaque">endestaque</a>, <a href="https://www.transifex.com/accounts/profile/endomenec">endomenec</a>, <a href="https://www.transifex.com/accounts/profile/ernexto">ernexto</a>, <a href="https://www.transifex.com/accounts/profile/espellcaste">espellcaste</a>, <a href="https://www.transifex.com/accounts/profile/esspressions">esspressions</a>, <a href="https://www.transifex.com/accounts/profile/estebanburgos">estebanburgos</a>, <a href="https://www.transifex.com/accounts/profile/eugenpaun_ro">eugenpaun_ro</a>, <a href="https://www.transifex.com/accounts/profile/fantasy1612">fantasy1612</a>, <a href="https://www.transifex.com/accounts/profile/fdaciuk">fdaciuk</a>, <a href="https://www.transifex.com/accounts/profile/finnes">finnes</a>, <a href="https://www.transifex.com/accounts/profile/flyingoff">flyingoff</a>, <a href="https://www.transifex.com/accounts/profile/fnalescio">fnalescio</a>, <a href="https://www.transifex.com/accounts/profile/fquantium">fquantium</a>, <a href="https://www.transifex.com/accounts/profile/funmist">funmist</a>, <a href="https://www.transifex.com/accounts/profile/fxbenard">fxbenard</a>, <a href="https://www.transifex.com/accounts/profile/gabejshn">gabejshn</a>, <a href="https://www.transifex.com/accounts/profile/gaspas">gaspas</a>, <a href="https://www.transifex.com/accounts/profile/geerthoekzema">geerthoekzema</a>, <a href="https://www.transifex.com/accounts/profile/george_pt">george_pt</a>, <a href="https://www.transifex.com/accounts/profile/gingermig">gingermig</a>, <a href="https://www.transifex.com/accounts/profile/givitis">givitis</a>, <a href="https://www.transifex.com/accounts/profile/globalaperta">globalaperta</a>, <a href="https://www.transifex.com/accounts/profile/goksy973">goksy973</a>, <a href="https://www.transifex.com/accounts/profile/gonzunigad">gonzunigad</a>, <a href="https://www.transifex.com/accounts/profile/gopress.co.il">gopress.co.il</a>, <a href="https://www.transifex.com/accounts/profile/gordon168">gordon168</a>, <a href="https://www.transifex.com/accounts/profile/greenbee">greenbee</a>, <a href="https://www.transifex.com/accounts/profile/greencore">greencore</a>, <a href="https://www.transifex.com/accounts/profile/greguly">greguly</a>, <a href="https://www.transifex.com/accounts/profile/guxin">guxin</a>, <a href="https://www.transifex.com/accounts/profile/hafizero">hafizero</a>, <a href="https://www.transifex.com/accounts/profile/hamalah">hamalah</a>, <a href="https://www.transifex.com/accounts/profile/hangga">hangga</a>, <a href="https://www.transifex.com/accounts/profile/hannit">hannit</a>, <a href="https://www.transifex.com/accounts/profile/haruman">haruman</a>, <a href="https://www.transifex.com/accounts/profile/henryk.ibemeinhardt">henryk.ibemeinhardt</a>, <a href="https://www.transifex.com/accounts/profile/hfelipe">hfelipe</a>, <a href="https://www.transifex.com/accounts/profile/hhaawwaa">hhaawwaa</a>, <a href="https://www.transifex.com/accounts/profile/hildago">hildago</a>, <a href="https://www.transifex.com/accounts/profile/hisoka512">hisoka512</a>, <a href="https://www.transifex.com/accounts/profile/huy.ng">huy.ng</a>, <a href="https://www.transifex.com/accounts/profile/huytuduy">huytuduy</a>, <a href="https://www.transifex.com/accounts/profile/iagomelanias">iagomelanias</a>, <a href="https://www.transifex.com/accounts/profile/ideodora">ideodora</a>, <a href="https://www.transifex.com/accounts/profile/idofri">idofri</a>, <a href="https://www.transifex.com/accounts/profile/ikadar">ikadar</a>, <a href="https://www.transifex.com/accounts/profile/ilan256">ilan256</a>, <a href="https://www.transifex.com/accounts/profile/imSuhaib">imSuhaib</a>, <a href="https://www.transifex.com/accounts/profile/inceptive">inceptive</a>, <a href="https://www.transifex.com/accounts/profile/inlaand">inlaand</a>, <a href="https://www.transifex.com/accounts/profile/inpsyde">inpsyde</a>, <a href="https://www.transifex.com/accounts/profile/ironist">ironist</a>, <a href="https://www.transifex.com/accounts/profile/irsyadzaki">irsyadzaki</a>, <a href="https://www.transifex.com/accounts/profile/ishay1999">ishay1999</a>, <a href="https://www.transifex.com/accounts/profile/israel.cefrin">israel.cefrin</a>, <a href="https://www.transifex.com/accounts/profile/iwocs">iwocs</a>, <a href="https://www.transifex.com/accounts/profile/jameskoster">jameskoster</a>, <a href="https://www.transifex.com/accounts/profile/jamesrod29">jamesrod29</a>, <a href="https://www.transifex.com/accounts/profile/jeanmichell">jeanmichell</a>, <a href="https://www.transifex.com/accounts/profile/jhn_rustan">jhn_rustan</a>, <a href="https://www.transifex.com/accounts/profile/jhovel">jhovel</a>, <a href="https://www.transifex.com/accounts/profile/jlgd">jlgd</a>, <a href="https://www.transifex.com/accounts/profile/jluisfreitas">jluisfreitas</a>, <a href="https://www.transifex.com/accounts/profile/joelbal">joelbal</a>, <a href="https://www.transifex.com/accounts/profile/joesadaeng">joesadaeng</a>, <a href="https://www.transifex.com/accounts/profile/joesalty">joesalty</a>, <a href="https://www.transifex.com/accounts/profile/jolish">jolish</a>, <a href="https://www.transifex.com/accounts/profile/joseluis">joseluis</a>, <a href="https://www.transifex.com/accounts/profile/josh_marom">josh_marom</a>, <a href="https://www.transifex.com/accounts/profile/joy.doctor">joy.doctor</a>, <a href="https://www.transifex.com/accounts/profile/jpBenfica">jpBenfica</a>, <a href="https://www.transifex.com/accounts/profile/jsparic">jsparic</a>, <a href="https://www.transifex.com/accounts/profile/jugmar">jugmar</a>, <a href="https://www.transifex.com/accounts/profile/jujjer">jujjer</a>, <a href="https://www.transifex.com/accounts/profile/junedzhan">junedzhan</a>, <a href="https://www.transifex.com/accounts/profile/justina_ba">justina_ba</a>, <a href="https://www.transifex.com/accounts/profile/kampit">kampit</a>, <a href="https://www.transifex.com/accounts/profile/karama89">karama89</a>, <a href="https://www.transifex.com/accounts/profile/karistuck">karistuck</a>, <a href="https://www.transifex.com/accounts/profile/keller2.m">keller2.m</a>, <a href="https://www.transifex.com/accounts/profile/khalil.delavaran">khalil.delavaran</a>, <a href="https://www.transifex.com/accounts/profile/kikarina">kikarina</a>, <a href="https://www.transifex.com/accounts/profile/kikehz">kikehz</a>, <a href="https://www.transifex.com/accounts/profile/kjosenet">kjosenet</a>, <a href="https://www.transifex.com/accounts/profile/konglehong">konglehong</a>, <a href="https://www.transifex.com/accounts/profile/kornienko">kornienko</a>, <a href="https://www.transifex.com/accounts/profile/kraudio">kraudio</a>, <a href="https://www.transifex.com/accounts/profile/kreatik">kreatik</a>, <a href="https://www.transifex.com/accounts/profile/krzysko">krzysko</a>, <a href="https://www.transifex.com/accounts/profile/kubik999">kubik999</a>, <a href="https://www.transifex.com/accounts/profile/kweekarius">kweekarius</a>, <a href="https://www.transifex.com/accounts/profile/lahiponeja">lahiponeja</a>, <a href="https://www.transifex.com/accounts/profile/lamibo">lamibo</a>, <a href="https://www.transifex.com/accounts/profile/laszlo.espadas">laszlo.espadas</a>, <a href="https://www.transifex.com/accounts/profile/laurbb">laurbb</a>, <a href="https://www.transifex.com/accounts/profile/lincw">lincw</a>, <a href="https://www.transifex.com/accounts/profile/lingfeng">lingfeng</a>, <a href="https://www.transifex.com/accounts/profile/long.run.international">long.run.international</a>, <a href="https://www.transifex.com/accounts/profile/lopescmauro">lopescmauro</a>, <a href="https://www.transifex.com/accounts/profile/louiseana">louiseana</a>, <a href="https://www.transifex.com/accounts/profile/lubalee">lubalee</a>, <a href="https://www.transifex.com/accounts/profile/lucasfreitas">lucasfreitas</a>, <a href="https://www.transifex.com/accounts/profile/lucaso">lucaso</a>, <a href="https://www.transifex.com/accounts/profile/luciferbui">luciferbui</a>, <a href="https://www.transifex.com/accounts/profile/luisrull">luisrull</a>, <a href="https://www.transifex.com/accounts/profile/m1k3lm">m1k3lm</a>, <a href="https://www.transifex.com/accounts/profile/maayehkhaled">maayehkhaled</a>, <a href="https://www.transifex.com/accounts/profile/macbluy">macbluy</a>, <a href="https://www.transifex.com/accounts/profile/madebyh">madebyh</a>, <a href="https://www.transifex.com/accounts/profile/manuelvillagrdo">manuelvillagrdo</a>, <a href="https://www.transifex.com/accounts/profile/marciotoledo">marciotoledo</a>, <a href="https://www.transifex.com/accounts/profile/marcosof">marcosof</a>, <a href="https://www.transifex.com/accounts/profile/marioscrafts">marioscrafts</a>, <a href="https://www.transifex.com/accounts/profile/maros336">maros336</a>, <a href="https://www.transifex.com/accounts/profile/martian36">martian36</a>, <a href="https://www.transifex.com/accounts/profile/martinezmr">martinezmr</a>, <a href="https://www.transifex.com/accounts/profile/martinproject">martinproject</a>, <a href="https://www.transifex.com/accounts/profile/math_beck">math_beck</a>, <a href="https://www.transifex.com/accounts/profile/mattyza">mattyza</a>, <a href="https://www.transifex.com/accounts/profile/maxlam">maxlam</a>, <a href="https://www.transifex.com/accounts/profile/me2you">me2you</a>, <a href="https://www.transifex.com/accounts/profile/meryjoearmstrong">meryjoearmstrong</a>, <a href="https://www.transifex.com/accounts/profile/metallicamu">metallicamu</a>, <a href="https://www.transifex.com/accounts/profile/michalvittek">michalvittek</a>, <a href="https://www.transifex.com/accounts/profile/michelle_zhang">michelle_zhang</a>, <a href="https://www.transifex.com/accounts/profile/mikaeldui">mikaeldui</a>, <a href="https://www.transifex.com/accounts/profile/mikejolley">mikejolley</a>, <a href="https://www.transifex.com/accounts/profile/mikseris1001">mikseris1001</a>, <a href="https://www.transifex.com/accounts/profile/milord">milord</a>, <a href="https://www.transifex.com/accounts/profile/minimalstudio">minimalstudio</a>, <a href="https://www.transifex.com/accounts/profile/mirkowhat">mirkowhat</a>, <a href="https://www.transifex.com/accounts/profile/mjepson">mjepson</a>, <a href="https://www.transifex.com/accounts/profile/mktunited">mktunited</a>, <a href="https://www.transifex.com/accounts/profile/mobarak">mobarak</a>, <a href="https://www.transifex.com/accounts/profile/mobiletalk">mobiletalk</a>, <a href="https://www.transifex.com/accounts/profile/mod7">mod7</a>, <a href="https://www.transifex.com/accounts/profile/molfar">molfar</a>, <a href="https://www.transifex.com/accounts/profile/monferro">monferro</a>, <a href="https://www.transifex.com/accounts/profile/monsterporing">monsterporing</a>, <a href="https://www.transifex.com/accounts/profile/moon.modena">moon.modena</a>, <a href="https://www.transifex.com/accounts/profile/mortifactor">mortifactor</a>, <a href="https://www.transifex.com/accounts/profile/mostafizur">mostafizur</a>, <a href="https://www.transifex.com/accounts/profile/mruizoea">mruizoea</a>, <a href="https://www.transifex.com/accounts/profile/mucheroni">mucheroni</a>, <a href="https://www.transifex.com/accounts/profile/muhammetayten">muhammetayten</a>, <a href="https://www.transifex.com/accounts/profile/muratbutun">muratbutun</a>, <a href="https://www.transifex.com/accounts/profile/mustafamsy">mustafamsy</a>, <a href="https://www.transifex.com/accounts/profile/mylene">mylene</a>, <a href="https://www.transifex.com/accounts/profile/nabil_kadimi">nabil_kadimi</a>, <a href="https://www.transifex.com/accounts/profile/nalvesrpd">nalvesrpd</a>, <a href="https://www.transifex.com/accounts/profile/nelblack">nelblack</a>, <a href="https://www.transifex.com/accounts/profile/ng3but">ng3but</a>, <a href="https://www.transifex.com/accounts/profile/nicolasleon">nicolasleon</a>, <a href="https://www.transifex.com/accounts/profile/niels.heijman">niels.heijman</a>, <a href="https://www.transifex.com/accounts/profile/njevdjo">njevdjo</a>, <a href="https://www.transifex.com/accounts/profile/nodarik">nodarik</a>, <a href="https://www.transifex.com/accounts/profile/nsitbon">nsitbon</a>, <a href="https://www.transifex.com/accounts/profile/oisie">oisie</a>, <a href="https://www.transifex.com/accounts/profile/orlandobp31">orlandobp31</a>, <a href="https://www.transifex.com/accounts/profile/pabambino">pabambino</a>, <a href="https://www.transifex.com/accounts/profile/paletta">paletta</a>, <a href="https://www.transifex.com/accounts/profile/paoloalbera">paoloalbera</a>, <a href="https://www.transifex.com/accounts/profile/pastynko">pastynko</a>, <a href="https://www.transifex.com/accounts/profile/patjun">patjun</a>, <a href="https://www.transifex.com/accounts/profile/patrickheiloo">patrickheiloo</a>, <a href="https://www.transifex.com/accounts/profile/paulgor">paulgor</a>, <a href="https://www.transifex.com/accounts/profile/paulofioratti">paulofioratti</a>, <a href="https://www.transifex.com/accounts/profile/pdb">pdb</a>, <a href="https://www.transifex.com/accounts/profile/peboom">peboom</a>, <a href="https://www.transifex.com/accounts/profile/perdersongedal">perdersongedal</a>, <a href="https://www.transifex.com/accounts/profile/pfrankov">pfrankov</a>, <a href="https://www.transifex.com/accounts/profile/pindi">pindi</a>, <a href="https://www.transifex.com/accounts/profile/pksupply">pksupply</a>, <a href="https://www.transifex.com/accounts/profile/plaguna">plaguna</a>, <a href="https://www.transifex.com/accounts/profile/platzh1rsch">platzh1rsch</a>, <a href="https://www.transifex.com/accounts/profile/playseebow">playseebow</a>, <a href="https://www.transifex.com/accounts/profile/porclick">porclick</a>, <a href="https://www.transifex.com/accounts/profile/potgieterg">potgieterg</a>, <a href="https://www.transifex.com/accounts/profile/ppv1979">ppv1979</a>, <a href="https://www.transifex.com/accounts/profile/prepu">prepu</a>, <a href="https://www.transifex.com/accounts/profile/pulanito">pulanito</a>, <a href="https://www.transifex.com/accounts/profile/rabas.marek">rabas.marek</a>, <a href="https://www.transifex.com/accounts/profile/rafaelfunchal">rafaelfunchal</a>, <a href="https://www.transifex.com/accounts/profile/rafalwolak">rafalwolak</a>, <a href="https://www.transifex.com/accounts/profile/ragulka">ragulka</a>, <a href="https://www.transifex.com/accounts/profile/rahmatilham">rahmatilham</a>, <a href="https://www.transifex.com/accounts/profile/ramoonus">ramoonus</a>, <a href="https://www.transifex.com/accounts/profile/razorfish79">razorfish79</a>, <a href="https://www.transifex.com/accounts/profile/rbrock">rbrock</a>, <a href="https://www.transifex.com/accounts/profile/rcovarru">rcovarru</a>, <a href="https://www.transifex.com/accounts/profile/read1">read1</a>, <a href="https://www.transifex.com/accounts/profile/renatofrota">renatofrota</a>, <a href="https://www.transifex.com/accounts/profile/richardshaylor">richardshaylor</a>, <a href="https://www.transifex.com/accounts/profile/rickbauck">rickbauck</a>, <a href="https://www.transifex.com/accounts/profile/rickserrat">rickserrat</a>, <a href="https://www.transifex.com/accounts/profile/ridhoyp">ridhoyp</a>, <a href="https://www.transifex.com/accounts/profile/rkrizanovskis">rkrizanovskis</a>, <a href="https://www.transifex.com/accounts/profile/rodrigoprior">rodrigoprior</a>, <a href="https://www.transifex.com/accounts/profile/roidayan">roidayan</a>, <a href="https://www.transifex.com/accounts/profile/ronshe">ronshe</a>, <a href="https://www.transifex.com/accounts/profile/rot13">rot13</a>, <a href="https://www.transifex.com/accounts/profile/rozumno">rozumno</a>, <a href="https://www.transifex.com/accounts/profile/rpetkov">rpetkov</a>, <a href="https://www.transifex.com/accounts/profile/rvoogdgeert">rvoogdgeert</a>, <a href="https://www.transifex.com/accounts/profile/s0w4">s0w4</a>, <a href="https://www.transifex.com/accounts/profile/scottbasgaard">scottbasgaard</a>, <a href="https://www.transifex.com/accounts/profile/sennbrink">sennbrink</a>, <a href="https://www.transifex.com/accounts/profile/serpav">serpav</a>, <a href="https://www.transifex.com/accounts/profile/shady55">shady55</a>, <a href="https://www.transifex.com/accounts/profile/shoresh319">shoresh319</a>, <a href="https://www.transifex.com/accounts/profile/sindri">sindri</a>, <a href="https://www.transifex.com/accounts/profile/sirdaniel">sirdaniel</a>, <a href="https://www.transifex.com/accounts/profile/slasher.art">slasher.art</a>, <a href="https://www.transifex.com/accounts/profile/smartdatasoft">smartdatasoft</a>, <a href="https://www.transifex.com/accounts/profile/snowre">snowre</a>, <a href="https://www.transifex.com/accounts/profile/soldier99">soldier99</a>, <a href="https://www.transifex.com/accounts/profile/sovichet">sovichet</a>, <a href="https://www.transifex.com/accounts/profile/srpski.dizajn">srpski.dizajn</a>, <a href="https://www.transifex.com/accounts/profile/standoutmedia">standoutmedia</a>, <a href="https://www.transifex.com/accounts/profile/stgoos">stgoos</a>, <a href="https://www.transifex.com/accounts/profile/studionetting">studionetting</a>, <a href="https://www.transifex.com/accounts/profile/stuk88">stuk88</a>, <a href="https://www.transifex.com/accounts/profile/sukruozge">sukruozge</a>, <a href="https://www.transifex.com/accounts/profile/sumodirjo">sumodirjo</a>, <a href="https://www.transifex.com/accounts/profile/supertommi">supertommi</a>, <a href="https://www.transifex.com/accounts/profile/sverrirp">sverrirp</a>, <a href="https://www.transifex.com/accounts/profile/svetrov">svetrov</a>, <a href="https://www.transifex.com/accounts/profile/swissky">swissky</a>, <a href="https://www.transifex.com/accounts/profile/swoboda">swoboda</a>, <a href="https://www.transifex.com/accounts/profile/syao.pin">syao.pin</a>, <a href="https://www.transifex.com/accounts/profile/sylvie_janssens">sylvie_janssens</a>, <a href="https://www.transifex.com/accounts/profile/t4rv1">t4rv1</a>, <a href="https://www.transifex.com/accounts/profile/tamvo">tamvo</a>, <a href="https://www.transifex.com/accounts/profile/tanin">tanin</a>, <a href="https://www.transifex.com/accounts/profile/teddyostergaard">teddyostergaard</a>, <a href="https://www.transifex.com/accounts/profile/teotonioricardo">teotonioricardo</a>, <a href="https://www.transifex.com/accounts/profile/tetsu">tetsu</a>, <a href="https://www.transifex.com/accounts/profile/the_fafa">the_fafa</a>, <a href="https://www.transifex.com/accounts/profile/tinaswelt">tinaswelt</a>, <a href="https://www.transifex.com/accounts/profile/tinygiantstudios">tinygiantstudios</a>, <a href="https://www.transifex.com/accounts/profile/tivnet">tivnet</a>, <a href="https://www.transifex.com/accounts/profile/tntc1978">tntc1978</a>, <a href="https://www.transifex.com/accounts/profile/toblues">toblues</a>, <a href="https://www.transifex.com/accounts/profile/tomasha">tomasha</a>, <a href="https://www.transifex.com/accounts/profile/tomboersma">tomboersma</a>, <a href="https://www.transifex.com/accounts/profile/tshowhey">tshowhey</a>, <a href="https://www.transifex.com/accounts/profile/tszming">tszming</a>, <a href="https://www.transifex.com/accounts/profile/tue.holm">tue.holm</a>, <a href="https://www.transifex.com/accounts/profile/tukangbajaksawah">tukangbajaksawah</a>, <a href="https://www.transifex.com/accounts/profile/tuzka">tuzka</a>, <a href="https://www.transifex.com/accounts/profile/uah">uah</a>, <a href="https://www.transifex.com/accounts/profile/urioste">urioste</a>, <a href="https://www.transifex.com/accounts/profile/uworx">uworx</a>, <a href="https://www.transifex.com/accounts/profile/valurthorgunnarsson">valurthorgunnarsson</a>, <a href="https://www.transifex.com/accounts/profile/vanbo">vanbo</a>, <a href="https://www.transifex.com/accounts/profile/vernandosimbolon">vernandosimbolon</a>, <a href="https://www.transifex.com/accounts/profile/viamarket">viamarket</a>, <a href="https://www.transifex.com/accounts/profile/viancu">viancu</a>, <a href="https://www.transifex.com/accounts/profile/viktorhanacek">viktorhanacek</a>, <a href="https://www.transifex.com/accounts/profile/vlinicx">vlinicx</a>, <a href="https://www.transifex.com/accounts/profile/w4advn">w4advn</a>, <a href="https://www.transifex.com/accounts/profile/wachirakorn">wachirakorn</a>, <a href="https://www.transifex.com/accounts/profile/wasim">wasim</a>, <a href="https://www.transifex.com/accounts/profile/wasley">wasley</a>, <a href="https://www.transifex.com/accounts/profile/webby1973">webby1973</a>, <a href="https://www.transifex.com/accounts/profile/willemsiebe">willemsiebe</a>, <a href="https://www.transifex.com/accounts/profile/woodyln">woodyln</a>, <a href="https://www.transifex.com/accounts/profile/wpsk">wpsk</a>, <a href="https://www.transifex.com/accounts/profile/xdosil">xdosil</a>, <a href="https://www.transifex.com/accounts/profile/xepin">xepin</a>, <a href="https://www.transifex.com/accounts/profile/xevivb">xevivb</a>, <a href="https://www.transifex.com/accounts/profile/y12studio">y12studio</a>, <a href="https://www.transifex.com/accounts/profile/zaantar">zaantar</a>, <a href="https://www.transifex.com/accounts/profile/zanguanga">zanguanga</a>, <a href="https://www.transifex.com/accounts/profile/zodiac1978">zodiac1978</a>, <a href="https://www.transifex.com/accounts/profile/Натали">Натали</a>
			</p>
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
	 * @access public
	 * @return mixed
	 */
	public function get_contributors() {
		$contributors = get_transient( 'woocommerce_contributors' );

		if ( false !== $contributors ) {
			return $contributors;
		}

		$response = wp_remote_get( 'https://api.github.com/repos/woothemes/woocommerce/contributors', array( 'sslverify' => false ) );

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

	/**
	 * Sends user to the welcome page on first activation
	 */
	public function welcome() {
		// Bail if no activation redirect transient is set
	    if ( ! get_transient( '_wc_activation_redirect' ) ) {
			return;
	    }

		// Delete the redirect transient
		delete_transient( '_wc_activation_redirect' );

		// Bail if we are waiting to install or update via the interface update/install links
		if ( get_option( '_wc_needs_update' ) == 1 || get_option( '_wc_needs_pages' ) == 1 ) {
			return;
		}

		// Bail if activating from network, or bulk, or within an iFrame
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) ) {
			return;
		}

		if ( ( isset( $_GET['action'] ) && 'upgrade-plugin' == $_GET['action'] ) && ( isset( $_GET['plugin'] ) && strstr( $_GET['plugin'], 'woocommerce.php' ) ) ) {
			return;
		}

		wp_redirect( admin_url( 'index.php?page=wc-about' ) );
		exit;
	}
}

new WC_Admin_Welcome();
