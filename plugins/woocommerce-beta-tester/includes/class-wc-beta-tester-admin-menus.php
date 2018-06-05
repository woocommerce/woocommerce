<?php
/**
 * Beta Tester Admin Menus class
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Beta_Tester_Admin_Menus Main Class.
 */
class WC_Beta_Tester_Admin_Menus {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menus' ), 31 );
	}

	/**
	 * Get issue template from GitHub.
	 *
	 * @return string
	 */
	protected function get_ticket_template() {
		$bug_tpl = wp_remote_retrieve_body( wp_remote_get( 'https://raw.githubusercontent.com/woocommerce/woocommerce/master/.github/ISSUE_TEMPLATE/Bug_report.md' ) );

		$begin = strpos( $bug_tpl, '**Describe the bug**' );
		if ( $begin ) {
			$bug_tpl = substr( $bug_tpl, $begin );
		}

		return $bug_tpl;
	}

	/**
	 * Constructs a WC System Status Report.
	 *
	 * @return string
	 */
	protected function construct_ssr() {
		$transient_name = 'wc-beta-tester-ssr';
		$ssr = get_transient( $transient_name );

		if ( false === $ssr ) {
			ob_start();

			// Get SSR
			// TODO: See what we can re-use from core `includes/admin/views/html-admin-page-status-report.php`
			WC_Admin_Status::status_report();

			$ssr = ob_get_clean();

			$ssr = substr( $ssr, 0, 2048 );

			set_transient( $transient_name, $ssr, DAY_IN_SECONDS );
		}

		return $ssr;
	}

	/**
	 * Get URL for creating a GitHub ticket.
	 *
	 * @return string
	 */
	protected function get_github_ticket_url() {
		$bug_tpl = $this->get_ticket_template();
		$ssr     = $this->construct_ssr();
		$body    = str_replace( 'Copy and paste the system status report from **WooCommerce > System Status** in WordPress admin.', $ssr, $bug_tpl );

		// TODO: Retrieve this programmatically based on current channel.
		$version = '1.2.3-rc.4';

		return add_query_arg(
			array(
				'body'  => urlencode( $body ),
				'title' => urlencode( sprintf( __( '[WC Beta Tester] Bug report for version "%s"', 'woocommerce-beta-tester' ), $version ) ),
			),
			'https://github.com/woocommerce/woocommerce/issues/new'
		);
	}

	/**
	 * Add the "Visit Store" link in admin bar main menu.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public function admin_bar_menus( $wp_admin_bar ) {
		if ( ! is_admin() || ! is_user_logged_in() ) {
			return;
		}

		// Show only when the user is a member of this site, or they're a super admin.
		if ( ! is_user_member_of_blog() && ! is_super_admin() ) {
			return;
		}

		// Add the beta tester root node.
		$wp_admin_bar->add_node( array(
			'parent' => 0,
			'id'     => 'wc-beta-tester',
			'title'  => __( 'WC Beta Tester', 'woocommerce-beta-tester' ),
		) );

		// TODO: Retrieve this programmatically.
		$current_channel = 'test';

		// TODO: Implementation of each node.
		$nodes = array(
			array(
				'parent' => 'wc-beta-tester',
				'id'     => 'current-channel',
				'title'  => sprintf( __( '<center><i>Current channel: %s</i></center>', 'woocommerce-beta-tester' ), $current_channel ),
			),
			array(
				'parent' => 'wc-beta-tester',
				'id'     => 'submit-gh-ticket',
				'title'  => __( 'Submit a bug ticket to GitHub', 'woocommerce-beta-tester' ),
				'href'   => '#',
				'meta'   => array(
					// We can't simply use the href here since WP core calls esc_url on it which strips some parts.
					'onclick' => 'javascript:window.open( "' . $this->get_github_ticket_url() . '" );',
				),
			),
			array(
				'parent' => 'wc-beta-tester',
				'id'     => 'show-version-info',
				'title'  => __( 'Show version information', 'woocommerce-beta-tester' ),
				'href'   => admin_url( 'plugins.php' ),
			),
			array(
				'parent' => 'wc-beta-tester',
				'id'     => 'switch-version',
				'title'  => __( 'Switch Version', 'woocommerce-beta-tester' ),
				'href'   => admin_url( 'plugins.php' ),
			),
			array(
				'parent' => 'wc-beta-tester',
				'id'     => 'update-channel',
				'title'  => __( 'Update channel settings', 'woocommerce-beta-tester' ),
				'href'   => admin_url( 'plugins.php' ),
			),
		);

		foreach ( $nodes as $node ) {
			$wp_admin_bar->add_node( $node );
		}
	}
}

return new WC_Beta_Tester_Admin_Menus();
