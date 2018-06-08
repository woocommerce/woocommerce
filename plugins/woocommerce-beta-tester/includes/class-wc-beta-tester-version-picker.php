<?php
/**
 * Beta Tester plugin Version Picker class
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings Class.
 */
class WC_Beta_Tester_Version_Picker {

	/**
	 * Currently installed version of WooCommerce plugin.
	 *
	 * @var string
	 */
	protected $current_version = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_to_menus' ) );
		add_action( 'admin_init', array( $this, 'handle_version_switch' ) );
	}

	/**
	 * Handler for the version switch button.
	 */
	public function handle_version_switch() {
		if ( empty( $_GET['wcbt_switch_to_version'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wcbt_switch_version_nonce' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce-beta-tester' ) );
		}

		include dirname( __FILE__ ) . '/class-wc-beta-tester-plugin-upgrader.php';

		$plugin_name = 'woocommerce';
		$plugin      = 'woocommerce/woocommerce.php';
		$skin_args   = array(
			'type'    => 'web',
			'url'     => 'tools.php?page=wc-beta-tester-version-picker',
			'title'   => 'Version switch result',
			'plugin'  => $plugin_name,
			'version' => $_GET['wcbt_switch_to_version'],
			'nonce'   => $_GET['_wpnonce'],
		);

		$skin = new Automatic_Upgrader_Skin( $skin_args );
		$upgrader = new WC_Beta_Tester_Plugin_Upgrader( $skin );

		$result = $upgrader->switch_version( $plugin );

		if ( $result ) {
			// Activate plugin.
			// TODO: this causes some issues...
			//$result = activate_plugin( $plugin, self_admin_url('plugins.php?error=true&plugin=' . urlencode( $plugin ) ), is_network_admin(), true );

			// ... so just do a redirect instead
			$activate_url_encoded = wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php&plugin_status=active' ), 'activate-plugin_woocommerce/woocommerce.php' );
			$activate_url         = str_replace( '&amp;', '&', $activate_url_encoded );
			wp_redirect( $activate_url );
		} else {
			// TODO: fail more gracefully.
			print_r( $skin->get_upgrade_messages() );
		}
	}

	/**
	 * Add options page to menu.
	 *
	 * @return void
	 */
	public function add_to_menus() {
		add_submenu_page( 'tools.php',
			__( 'WooCommerce Beta Tester Version Switch', 'woocommerce-beta-tester' ),
			__( 'WooCommerce Beta Tester Version Switch', 'woocommerce-beta-tester' ),
			'install_plugins',
			'wc-beta-tester-version-picker',
			array( $this, 'select_versions_form_html' ) );
	}

	/**
	 * Return HTML code representation of list of WooCommerce versions for the selected channel.
	 *
	 * @param string $channel Filter versions by channel: all|beta|rc|stable.
	 * @return string
	 */
	public function get_versions_html( $channel ) {
		$tags = WC_Beta_Tester::instance()->get_tags( $channel );

		if ( ! $tags ) {
			return '';
		}

		usort( $tags, 'version_compare' );
		$tags = array_reverse( $tags );

		$versions_html         = '';

		if ( ! empty( $_GET['switched'] ) ) {
			$versions_html    .= __( '<h2>Successfully switched version.</h2>', 'woocommerce-beta-tester' );
		}

		$versions_html        .= '<ul class="wcbt-version-list">';
		$plugin_data           = WC_Beta_Tester::instance()->get_plugin_data();
		$this->current_version = $plugin_data['Version'];

		// Loop through versions and output in a radio list.
		foreach ( $tags as $tag_version ) {

			$versions_html .= '<li class="wcbt-version-li">';
			$versions_html .= '<label><input type="radio" value="' . esc_attr( $tag_version ) . '" name="wcbt_switch_to_version">' . $tag_version;

			// Is this the current version?
			if ( $tag_version === $this->current_version ) {
				$versions_html .= '<span class="wcbt-current-version">' . esc_html__( '&nbsp;Installed Version', 'woocommerce-beta-tester' ) . '</span>';
			}

			$versions_html .= '</label>';
			$versions_html .= '</li>';
		}

		$versions_html .= '</ul>';

		return $versions_html;
	}

	/**
	 * Echo HTML form to switch WooCommerce versions, filtered for the selected channel.
	 */
	public function select_versions_form_html() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		$settings = WC_Beta_Tester::get_settings();
		$channel  = $settings->channel;

		?>
		<div class="wrap">
			<div class="wcbt-content-wrap">
				<h1><?php esc_html_e( 'Available WooCommerce versions', 'woocommerce-beta-tester' ); ?></h1>
				<form name="wcbt-select-version" class="wcbt-select-version-form" action="<?php echo esc_attr( admin_url( '/tools.php' ) ); ?>">
					<div class="wcbt-versions-wrap">
						<?php echo $this->get_versions_html( $channel ); // WPCS: XSS ok. ?>
					</div>
					<div class="wcbt-submit-wrap">
						<a href="#wcbt-modal-version-switch-confirm" class="button-primary" id="wcbt-modal-version-switch-confirm"><?php esc_html_e( 'Switch version', 'woocommerce-beta-tester' ); ?></a>
					</div>
					<?php wp_nonce_field( 'wcbt_switch_version_nonce' ); ?>
					<input type="hidden" name="noheader" value="1">
					<input type="hidden" name="page" value="wc-beta-tester-version-picker">

					<script type="text/template" id="tmpl-wcbt-version-switch-confirm">
						<div class="wc-backbone-modal wc-backbone-modal-beta-tester-version-info">
							<div class="wc-backbone-modal-content">
								<section class="wc-backbone-modal-main" role="main">
									<header class="wc-backbone-modal-header">
										<h1>
											<?php
											esc_html_e( 'Are you sure you want to switch the version of WooCommerce plugin?', 'woocommerce-beta-tester' );
											?>
										</h1>
										<button class="modal-close modal-close-link dashicons dashicons-no-alt">
											<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce-beta-tester' ); ?></span>
										</button>
									</header>
									<article>

										<table class="wcbt-widefat">
											<tbody>
											<tr class="alternate">
												<td class="row-title">
													<label for="tablecell"><?php esc_html_e( 'Installed Version:', 'woocommerce-beta-tester' ); ?></label>
												</td>
												<td><span class="wcbt-installed-version"><?php echo esc_html( $this->current_version ); ?></span></td>
											</tr>
											<tr>
												<td class="row-title">
													<label for="tablecell"><?php esc_html_e( 'New Version:', 'woocommerce-beta-tester' ); ?></label>
												</td>
												<td><span class="wcbt-new-version">{{ data.new_version }}</span></td>
											</tr>
											</tbody>
										</table>

										<div class="wcbt-notice">
											<p><?php esc_html_e( 'Notice: We strongly recommend you perform the test on a staging site and create a complete backup of your WordPress files and database prior to performing a version switch. We are not responsible for any misuse, deletions, white screens, fatal errors, or any other issue arising from using this plugin.', 'woocommerce-beta-tester' ); ?></p>
										</div>
									</article>
									<footer>
										<input type="submit" value="<?php esc_attr_e( 'Switch version', 'woocommerce-beta-tester' ); ?>" class="button-primary wcbt-go" id="wcbt-submit-version-switch"/>
										<a href="#" class="modal-close modal-close-link"><?php esc_attr_e( 'Cancel', 'woocommerce-beta-tester' ); ?></a>
									</footer>
								</section>
							</div>
						</div>
						<div class="wc-backbone-modal-backdrop modal-close"></div>
					</script>

				</form>
			</div>
		</div>
		<?php
	}
}

new WC_Beta_Tester_Version_Picker();
