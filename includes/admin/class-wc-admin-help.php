<?php
/**
 * Add some content to the help tab
 *
 * @package WooCommerce/Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Help Class.
 */
class WC_Admin_Help {

	/**
	 * Singleton instance.
	 *
	 * @var WC_Admin_Help|null
	 */
	protected static $instance = null;

	/**
	 * Return singleston instance.
	 *
	 * @static
	 * @return WC_Admin_Help
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
	 * Add help tabs.
	 */
	public function init() {
		if ( apply_filters( 'woocommerce_enable_admin_help_tab', true ) ) {
			$this->add_tabs();
		}
	}

	/**
	 * Add help tabs.
	 */
	public function add_tabs() {
		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->id, wc_get_screen_ids(), true ) ) {
			return;
		}

		$screen->add_help_tab(
			array(
				'id'      => 'woocommerce_support_tab',
				'title'   => __( 'Help &amp; Support', 'woocommerce' ),
				'content' =>
					'<h2>' . __( 'Help &amp; Support', 'woocommerce' ) . '</h2>' .
					'<p>' . sprintf(
						/* translators: %s: Documentation URL */
						__( 'Should you need help understanding, using, or extending WooCommerce, <a href="%s">please read our documentation</a>. You will find all kinds of resources including snippets, tutorials and much more.', 'woocommerce' ),
						'https://docs.woocommerce.com/documentation/plugins/woocommerce/?utm_source=helptab&utm_medium=product&utm_content=docs&utm_campaign=woocommerceplugin'
					) . '</p>' .
					'<p>' . sprintf(
						/* translators: %s: Forum URL */
						__( 'For further assistance with WooCommerce core you can use the <a href="%1$s">community forum</a>. If you need help with premium extensions sold by WooCommerce, please <a href="%2$s">use our helpdesk</a>.', 'woocommerce' ),
						'https://wordpress.org/support/plugin/woocommerce',
						'https://woocommerce.com/my-account/tickets/?utm_source=helptab&utm_medium=product&utm_content=tickets&utm_campaign=woocommerceplugin'
					) . '</p>' .
					'<p>' . __( 'Before asking for help we recommend checking the system status page to identify any problems with your configuration.', 'woocommerce' ) . '</p>' .
					'<p><a href="' . admin_url( 'admin.php?page=wc-status' ) . '" class="button button-primary">' . __( 'System status', 'woocommerce' ) . '</a> <a href="https://wordpress.org/support/plugin/woocommerce" class="button">' . __( 'Community forum', 'woocommerce' ) . '</a> <a href="https://woocommerce.com/my-account/tickets/?utm_source=helptab&utm_medium=product&utm_content=tickets&utm_campaign=woocommerceplugin" class="button">' . __( 'WooCommerce helpdesk', 'woocommerce' ) . '</a></p>',
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'woocommerce_bugs_tab',
				'title'   => __( 'Found a bug?', 'woocommerce' ),
				'content' =>
					'<h2>' . __( 'Found a bug?', 'woocommerce' ) . '</h2>' .
					/* translators: 1: GitHub issues URL 2: GitHub contribution guide URL 3: System status report URL */
					'<p>' . sprintf( __( 'If you find a bug within WooCommerce core you can create a ticket via <a href="%1$s">Github issues</a>. Ensure you read the <a href="%2$s">contribution guide</a> prior to submitting your report. To help us solve your issue, please be as descriptive as possible and include your <a href="%3$s">system status report</a>.', 'woocommerce' ), 'https://github.com/woocommerce/woocommerce/issues?state=open', 'https://github.com/woocommerce/woocommerce/blob/master/.github/CONTRIBUTING.md', admin_url( 'admin.php?page=wc-status' ) ) . '</p>' .
					'<p><a href="https://github.com/woocommerce/woocommerce/issues?state=open" class="button button-primary">' . __( 'Report a bug', 'woocommerce' ) . '</a> <a href="' . admin_url( 'admin.php?page=wc-status' ) . '" class="button">' . __( 'System status', 'woocommerce' ) . '</a></p>',

			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'woocommerce_education_tab',
				'title'   => __( 'Education', 'woocommerce' ),
				'content' =>
					'<h2>' . __( 'Education', 'woocommerce' ) . '</h2>' .
					'<p>' . __( 'If you would like to learn about using WooCommerce from an expert, consider a WooCommerce course to further your education.', 'woocommerce' ) . '</p>' .
					'<p><a href="https://docs.woocommerce.com/document/further-education/?utm_source=helptab&utm_medium=product&utm_content=edupartners&utm_campaign=woocommerceplugin" class="button button-primary">' . __( 'Further education', 'woocommerce' ) . '</a></p>',
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'woocommerce_onboard_tab',
				'title'   => __( 'Setup wizard', 'woocommerce' ),
				'content' =>
					'<h2>' . __( 'Setup wizard', 'woocommerce' ) . '</h2>' .
					'<p>' . __( 'If you need to access the setup wizard again, please click on the button below.', 'woocommerce' ) . '</p>' .
					'<p><a href="' . admin_url( 'index.php?page=wc-setup' ) . '" class="button button-primary">' . __( 'Setup wizard', 'woocommerce' ) . '</a></p>',

			)
		);

		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'woocommerce' ) . '</strong></p>' .
			'<p><a href="https://woocommerce.com/?utm_source=helptab&utm_medium=product&utm_content=about&utm_campaign=woocommerceplugin" target="_blank">' . __( 'About WooCommerce', 'woocommerce' ) . '</a></p>' .
			'<p><a href="https://wordpress.org/plugins/woocommerce/" target="_blank">' . __( 'WordPress.org project', 'woocommerce' ) . '</a></p>' .
			'<p><a href="https://github.com/woocommerce/woocommerce/" target="_blank">' . __( 'Github project', 'woocommerce' ) . '</a></p>' .
			'<p><a href="https://woocommerce.com/storefront/?utm_source=helptab&utm_medium=product&utm_content=wcthemes&utm_campaign=woocommerceplugin" target="_blank">' . __( 'Official theme', 'woocommerce' ) . '</a></p>' .
			'<p><a href="https://woocommerce.com/product-category/woocommerce-extensions/?utm_source=helptab&utm_medium=product&utm_content=wcextensions&utm_campaign=woocommerceplugin" target="_blank">' . __( 'Official extensions', 'woocommerce' ) . '</a></p>'
		);
	}
}
