<?php
/**
 * WooCommerce Beta Tester CLI controls
 *
 * @package WC_Beta_Tester
 */

 use WP_CLI;
 use WP_CLI_Command;

if ( ! class_exists( 'WP_CLI_Command' ) ) {
	return;
}

/**
 * Control your local WooCommerce Beta Tester plugin.
 */
class WC_Beta_Tester_CLI extends WP_CLI_Command {

	/**
	 * Activate a live branch of the WooCommerce plugin
	 *
	 * ## Options
	 * <branch>
	 * : The branch to activate.
	 *
	 * ## Examples
	 *
	 *     wp wc-beta-tester activate update/some-branch    *
	 *
	 * @param array $args Arguments passed to CLI.
	 */
	public function activate( $args ) {
		$installer = new WC_Beta_Tester_Live_Branches_Installer();
		// $installer->deactivate_woocommerce();

		$branch = $args[0];

		$info = $installer->get_branch_from_manifest( $branch );

		if ( ! $download_url ) {
			WP_CLI::error( "Could not find branch $branch in manifest" );
		} else {
			$installer->install( $info->download_url, $info->pr_name, $info->version );
			$installer->activate( $info->version );
		}

		// $this->validation_checks();

		// $plugin = Plugin::get_plugin( $args[0], true );
		// if ( ! $plugin ) {
		// translators: %s: Plugin slug that was not found.
		// WP_CLI::error( sprintf( __( 'Plugin \'%s\' is not known. Use `wp jetpack-beta list` to list known plugins', 'jetpack-beta' ), $args[0] ) );
		// }

		// if ( 'trunk' === $args[1] || 'master' === $args[1] ) {
		// $source = 'trunk';
		// $id     = '';
		// translators: %1$s: Plugin name.
		// $premsg = __( 'Activating %1$s trunk branch', 'jetpack-beta' );
		// translators: %1$s: Plugin name.
		// $postmsg = __( '%1$s is now on the trunk branch', 'jetpack-beta' );
		// } elseif ( 'stable' === $args[1] ) {
		// $source = 'stable';
		// $id     = '';
		// translators: %1$s: Plugin name.
		// $premsg = __( 'Activating %1$s latest release', 'jetpack-beta' );
		// translators: %1$s: Plugin name.
		// $postmsg = __( '%1$s is now on the latest release', 'jetpack-beta' );
		// } elseif ( 'rc' === $args[1] ) {
		// $source = 'rc';
		// $id     = '';
		// translators: %1$s: Plugin name.
		// $premsg = __( 'Activating %1$s release candidate', 'jetpack-beta' );
		// translators: %1$s: Plugin name.
		// $postmsg = __( '%1$s is now on the latest release candidate', 'jetpack-beta' );
		// } elseif ( preg_match( '/^\d+(?:\.\d+)(?:-beta\d*)?$/', $args[1] ) ) {
		// $source = 'release';
		// $id     = $args[1];
		// translators: %1$s: Plugin name. %2$s: Version number.
		// $premsg = __( 'Activating %1$s release version %2$s', 'jetpack-beta' );
		// translators: %1$s: Plugin name. %2$s: Version number.
		// $postmsg = __( '%1$s is now on release version %2$s', 'jetpack-beta' );
		// } else {
		// $source = 'pr';
		// $id     = $args[1];
		// translators: %1$s: Plugin name. %2$s: Branch name.
		// $premsg = __( 'Activating %1$s branch %2$s', 'jetpack-beta' );
		// translators: %1$s: Plugin name. %2$s: Branch name.
		// $postmsg = __( '%1$s is now on branch %2$s', 'jetpack-beta' );
		// }

		// WP_CLI::line( sprintf( $premsg, $plugin->get_name(), $id ) );

		// $ret = $plugin->install_and_activate( $source, $id );
		// if ( is_wp_error( $ret ) ) {
		// WP_CLI::error( $ret->get_error_message() );
		// } else {
		// WP_CLI::line( sprintf( $postmsg, $plugin->get_name(), $id ) );
		// }
	}
}
