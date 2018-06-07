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

		$versions_html         = '<ul class="wcbt-version-list">';
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
		$channel = $settings->channel;

		?>
		<div class="wrap">
			<div class="wcbt-content-wrap">
				<h1><?php esc_html_e( 'Available WooCommerce versions', 'woocommerce-beta-tester' ); ?></h1>
				<form name="wcbt-select-version" class="wcbt-select-version-form" action="<?php echo esc_attr( admin_url( '/index.php' ) ); ?>">
					<div class="wcbt-versions-wrap">
						<?php echo $this->get_versions_html( $channel ); // WPCS: XSS ok. ?>
					</div>
					<div class="wcbt-submit-wrap">
						<a href="#wcbt-modal-version-switch-confirm" class="button-primary" id="wcbt-modal-version-switch-confirm"><?php esc_html_e( 'Switch version', 'woocommerce-beta-tester' ); ?></a>
					</div>
					<?php wp_nonce_field( 'wcbt_switch_version_nonce' ); ?>

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
										<input type="submit" value="<?php esc_attr_e( 'Switch version', 'woocommerce-beta-tester' ); ?>" class="button-primary wcbt-go" id="wcbt-submit-version-switch" />
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
