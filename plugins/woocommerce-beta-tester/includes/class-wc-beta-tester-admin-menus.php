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
				'href'   => admin_url( 'plugins.php' ),
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
