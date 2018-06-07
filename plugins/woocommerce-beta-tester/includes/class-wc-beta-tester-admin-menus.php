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
		$ssr            = get_transient( $transient_name );

		if ( false === $ssr ) {
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
				// @todo remove this hack after fix schema in WooCommerce, and Claudio never let you folks forget that need to write schema first, and review after every change, schema is the most important part of the REST API.
				if ( 'version' === $index ) {
					$index = 'wc_version';
				} elseif ( 'external_object_cache' === $index ) {
					$ssr .= sprintf( "%s: %s\n", 'External object cache', wc_bool_to_string( $value ) );
					continue;
				}

				if ( is_bool( $value ) ) {
					$value = wc_bool_to_string( $value );
				}

				$ssr .= sprintf( "%s: %s\n", $schema['properties']['environment']['properties'][ $index ]['description'], $value );
			}

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
		$wp_admin_bar->add_node( array(
			'parent' => 0,
			'id'     => 'wc-beta-tester',
			'title'  => __( 'WC Beta Tester', 'woocommerce-beta-tester' ),
		) );

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

		// TODO: Implementation of each node.
		$nodes = array(
			array(
				'parent' => 'wc-beta-tester',
				'id'     => 'update-channel',
				/* translators: %s: current channel */
				'title'  => sprintf( __( 'Current channel: %s', 'woocommerce-beta-tester' ), $current_channel ),
				'href'   => admin_url( 'plugins.php?page=wc-beta-tester' ),
			),
			array(
				'parent' => 'wc-beta-tester',
				'id'     => 'show-version-info',
				/* translators: %s: current version */
				'title'  => sprintf( __( 'Version %s information', 'woocommerce-beta-tester' ), WC_VERSION ),
				'href'   => '#',
			),
			array(
				'parent' => 'wc-beta-tester',
				'id'     => 'switch-version',
				'title'  => __( 'Switch versions', 'woocommerce-beta-tester' ),
				'href'   => admin_url( 'tools.php?page=wc-beta-tester-version-picker' ),
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
								echo esc_html( sprintf( __( 'Version information for %s', 'woocommerce-beta-tester' ), '{{ data.version }}' ) );
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
							<a target="_blank" href="https://github.com/woocommerce/woocommerce/releases/tag/{{ data.version }}">Read more</a>
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
