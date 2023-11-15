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
		if ( class_exists( 'WC_Admin_Status' ) ) {
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_menus' ), 50 );
		}
		add_action( 'admin_footer', array( $this, 'version_information_template' ) );
		add_action( 'admin_head', array( $this, 'hide_from_menus' ) );
	}

	/**
	 * Get issue template from GitHub.
	 *
	 * @return string
	 */
	protected function get_ticket_template() {
		ob_start();
		?>
**Describe the bug**
A clear and concise description of what the bug is. Please be as descriptive as possible; issues lacking detail, or for any other reason than to report a bug, may be closed without action.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Screenshots**
If applicable, add screenshots to help explain your problem.

**Expected behavior**
A clear and concise description of what you expected to happen.

**Isolating the problem (mark completed items with an [x]):**
- [ ] I have deactivated other plugins and confirmed this bug occurs when only WooCommerce plugin is active.
- [ ] This bug happens with a default WordPress theme active, or [Storefront](https://woo.com/storefront/).
- [ ] I can reproduce this bug consistently using the steps above.

**WordPress Environment**
<details>
```
Copy and paste the system status report from **WooCommerce > System Status** in WordPress admin.
```
</details>
		<?php

		$bug_tpl = ob_get_clean();
		return $bug_tpl;
	}

	/**
	 * Get info about the current theme for SSR.
	 *
	 * @param string $theme Current theme.
	 * @return string
	 */
	private function get_theme_ssr_info( $theme ) {
		$theme_info = __( "\n\n### Theme ###\n\n", 'woocommerce-beta-tester' );

		$version = $theme['version'];
		if ( version_compare( $theme['version'], $theme['version_latest'], '<' ) ) {
			/* translators: 1: latest version */
			$version .= sprintf( __( ' - %s is available', 'woocommerce-beta-tester' ), esc_html( $theme['version_latest'] ) );
		}

		$child_theme = $theme['is_child_theme'] ? __( 'Yes', 'woocommerce-beta-tester' ) : __( 'No - If you are modifying WooCommerce on a parent theme that you did not build personally we recommend using a child theme.', 'woocommerce-beta-tester' );

		if ( $theme['is_child_theme'] ) {
			$parent_version = $theme['parent_version'];
			if ( version_compare( $theme['parent_version'], $theme['parent_version_latest'], '<' ) ) {
				/* translators: 1: latest version */
				$parent_version .= sprintf( __( ' - %s is available', 'woocommerce-beta-tester' ), esc_html( $theme['parent_version_latest'] ) );
			}

			$child_theme .= sprintf(
				__( "Parent theme name: %1\$s\nParent theme version: %2\$s\nParent theme author URL: %3\$s\n", 'woocommerce-beta-tester' ),
				$theme['parent_name'],
				$parent_version,
				$theme['parent_author_url']
			);
		}

		$theme_info .= sprintf(
			__( "Name: %1\$s\nVersion: %2\$s\nAuthor URL: %3\$s\nChild Theme: %4\$s\nWooCommerce Support: %5\$s", 'woocommerce-beta-tester' ),
			$theme['name'],
			$version,
			esc_html( $theme['author_url'] ),
			$child_theme,
			! $theme['has_woocommerce_support'] ? __( 'Not declared', 'woocommerce-beta-tester' ) : __( 'Yes', 'woocommerce-beta-tester' )
		);

		return $theme_info;
	}

	/**
	 * Get info about the current plugins for SSR.
	 *
	 * @param array $active_plugins List of active plugins.
	 * @param array $untested_plugins List of untested plugins.
	 * @return string
	 */
	private function get_plugins_ssr_info( $active_plugins, $untested_plugins ) {
		$plugins_info = __( "\n\n### Plugins ###\n\n", 'woocommerce-beta-tester' );

		foreach ( $active_plugins as $plugin ) {
			if ( ! empty( $plugin['name'] ) ) {
				// Link the plugin name to the plugin url if available.
				$plugin_name = esc_html( $plugin['name'] );

				$version_string = '';
				$network_string = '';
				if ( strstr( $plugin['url'], 'woothemes.com' ) || strstr( $plugin['url'], 'woo.com' ) ) {
					if ( ! empty( $plugin['version_latest'] ) && version_compare( $plugin['version_latest'], $plugin['version'], '>' ) ) {
						/* translators: %s: plugin latest version */
						$version_string = sprintf( esc_html__( '%s is available', 'woocommerce-beta-tester' ), $plugin['version_latest'] );
					}

					if ( false !== (bool) $plugin['network_activated'] ) {
						$network_string = __( 'Network enabled', 'woocommerce-beta-tester' );
					}
				}
				$untested_string = '';
				if ( array_key_exists( $plugin['plugin'], $untested_plugins ) ) {
					$untested_string = __( 'Not tested with the active version of WooCommerce', 'woocommerce-beta-tester' );
				}

				$plugins_info .= sprintf( __( "%1\$s: by %2\$s - %3\$s - %4\$s\n", 'woocommerce-beta-tester' ), $plugin_name, $plugin['author_name'], $plugin['version'], $version_string . $untested_string . $network_string );
			}
		}

		return $plugins_info;
	}

	/**
	 * Constructs a WC System Status Report.
	 *
	 * @return string
	 */
	protected function construct_ssr() {
		// This function depends on the WC core global being available. Sometimes, such as when we deactivate
		// WC to install a live branches version, WC will not be available and cause a crash if we don't exit early
		// here.
		if ( ! class_exists( 'WC' ) ) {
			return '';
		}

		if ( version_compare( WC()->version, '3.6', '<' ) ) {
			return '';
		}

		$transient_name = 'wc-beta-tester-ssr';
		$ssr            = get_transient( $transient_name );

		if ( false === $ssr ) {
			// When running WC 3.6 or greater it is necessary to manually load the REST API classes.
			if ( ! did_action( 'rest_api_init' ) ) {
				WC()->api->rest_api_includes();
			}

			if ( ! class_exists( 'WC_REST_System_Status_Controller' ) ) {
				return '';
			}

			// Get SSR.
			$api      = new WC_REST_System_Status_Controller();
			$schema   = $api->get_item_schema();
			$mappings = $api->get_item_mappings();
			$response = array();

			foreach ( $mappings as $section => $values ) {
				foreach ( $values as $key => $value ) {
					if ( isset( $schema['properties'][ $section ]['properties'][ $key ]['type'] ) ) {
						settype( $values[ $key ], $schema['properties'][ $section ]['properties'][ $key ]['type'] );
					}
				}
				settype( $values, $schema['properties'][ $section ]['type'] );
				$response[ $section ] = $values;
			}

			$ssr = '';
			foreach ( $response['environment'] as $key => $value ) {
				$index = $key;
				if ( 'external_object_cache' === $index ) {
					$ssr .= sprintf( "%s: %s\n", 'External object cache', wc_bool_to_string( $value ) );
					continue;
				}

				if ( is_bool( $value ) ) {
					$value = wc_bool_to_string( $value );
				}

				$ssr .= sprintf( "%s: %s\n", $schema['properties']['environment']['properties'][ $index ]['description'], $value );
			}

			$active_plugins   = $api->get_active_plugins();
			$plugin_updates   = new WC_Plugin_Updates();
			$untested_plugins = $plugin_updates->get_untested_plugins( WC()->version, 'minor' );
			$ssr             .= $this->get_plugins_ssr_info( $active_plugins, $untested_plugins );
			$theme            = $api->get_theme_info();
			$ssr             .= $this->get_theme_ssr_info( $theme );

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
		$body    = str_replace( '```', '', $body ); // Remove since this break how is displayed.

		$wc_plugin_data = get_plugin_data( WC_PLUGIN_FILE );
		if ( isset( $wc_plugin_data['Version'] ) ) {
			$version = $wc_plugin_data['Version'];
		} else {
			$version = '-';
		}

		return add_query_arg(
			array(
				'body'  => rawurlencode( $body ),
				/* translators: %s: woocommerce version */
				'title' => rawurlencode( sprintf( __( '[WC Beta Tester] Bug report for version "%s"', 'woocommerce-beta-tester' ), $version ) ),
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
		$wp_admin_bar->add_node(
			array(
				'parent' => 0,
				'id'     => 'wc-beta-tester',
				'title'  => __( 'WC Beta Tester', 'woocommerce-beta-tester' ),
			)
		);

		$settings = WC_Beta_Tester::get_settings();
		switch ( $settings->channel ) {
			case 'beta':
				$current_channel = __( 'Beta', 'woocommerce-beta-tester' );
				break;
			case 'rc':
				$current_channel = __( 'Release Candidate', 'woocommerce-beta-tester' );
				break;
			default:
				$current_channel = __( 'Stable', 'woocommerce-beta-tester' );
				break;
		}

		$nodes = array(
			array(
				'parent' => 'wc-beta-tester',
				'id'     => 'update-channel',
				/* translators: %s: current channel */
				'title'  => sprintf( __( 'Channel: %s', 'woocommerce-beta-tester' ), $current_channel ),
				'href'   => admin_url( 'plugins.php?page=wc-beta-tester' ),
			),
			array(
				'parent' => 'wc-beta-tester',
				'id'     => 'import-export-settings',
				'title'  => __( 'Import/Export Settings', 'woocommerce-beta-tester' ),
				'href'   => admin_url( 'admin.php?page=wc-beta-tester-settings' ),
			),
			array(
				'parent' => 'wc-beta-tester',
				'id'     => 'show-version-info',
				/* translators: %s: current version */
				'title'  => sprintf( __( 'Release %s information', 'woocommerce-beta-tester' ), WC_VERSION ),
				'href'   => '#',
			),
			array(
				'parent' => 'wc-beta-tester',
				'id'     => 'switch-version',
				'title'  => __( 'Switch versions', 'woocommerce-beta-tester' ),
				'href'   => admin_url( 'plugins.php?page=wc-beta-tester-version-picker' ),
			),
			array(
				'parent' => 'wc-beta-tester',
				'id'     => 'submit-gh-ticket',
				'title'  => __( 'Submit bug report', 'woocommerce-beta-tester' ),
				'href'   => '#',
				'meta'   => array(
					// We can't simply use the href here since WP core calls esc_url on it which strips some parts.
					'onclick' => 'javascript:window.open( "' . esc_js( $this->get_github_ticket_url() ) . '" );',
				),
			),
		);

		foreach ( $nodes as $node ) {
			$wp_admin_bar->add_node( $node );
		}
	}

	/**
	 * Hide menu items from view so the pages exist, but the menu items do not.
	 */
	public function hide_from_menus() {
		global $submenu;

		$items_to_remove = array( 'wc-beta-tester-settings', 'wc-beta-tester-version-picker', 'wc-beta-tester' );
		if ( isset( $submenu['plugins.php'] ) ) {
			foreach ( $submenu['plugins.php'] as $key => $menu ) {
				if ( in_array( $menu[2], $items_to_remove, true ) ) {
					unset( $submenu['plugins.php'][ $key ] );
				}
			}
		}
	}

	/**
	 * Template for version information.
	 */
	public function version_information_template() {
		?>
		<script type="text/template" id="tmpl-wc-beta-tester-version-info">
			<div class="wc-backbone-modal wc-backbone-modal-beta-tester-version-info">
				<div class="wc-backbone-modal-content">
					<section class="wc-backbone-modal-main" role="main">
						<header class="wc-backbone-modal-header">
							<h1>
							<?php
								/* translators: %s: version number */
								echo esc_html( sprintf( __( 'Release %s information', 'woocommerce-beta-tester' ), '{{ data.version }}' ) );
							?>
							</h1>
							<button class="modal-close modal-close-link dashicons dashicons-no-alt">
								<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce-beta-tester' ); ?></span>
							</button>
						</header>
						<article>
							<?php do_action( 'woocommerce_admin_version_information_start' ); ?>
							{{ data.description }}
							<?php do_action( 'woocommerce_admin_version_information_end' ); ?>
						</article>
						<footer>
							<a target="_blank" href="https://github.com/woocommerce/woocommerce/releases/tag/{{ data.version }}"><?php esc_html_e( 'Read more on GitHub', 'woocommerce-beta-tester' ); ?></a>
						</footer>
					</section>
				</div>
			</div>
			<div class="wc-backbone-modal-backdrop modal-close"></div>
		</script>
		<?php
	}
}

return new WC_Beta_Tester_Admin_Menus();
