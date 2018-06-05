<?php
/**
 * Beta Tester plugin settings class
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings Class.
 */
class WC_Beta_Tester_Settings {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'settings_init' ) );
		add_action( 'admin_menu', array( $this, 'add_to_menus' ) );
	}

	/**
	 * Initialise settings
	 *
	 * @return void
	 */
	public function settings_init() {
		register_setting( 'wc-beta-tester', 'wc_beta_tester_options' );

		add_settings_section(
			'wc-beta-tester-update',
			__( 'Plugin Update Settings', 'woocommerce-beta-tester' ),
			array( $this, 'update_section_html' ),
			'wc-beta-tester'
		);

		add_settings_field(
			'wc-beta-tester-version',
			__( 'WooCommerce Version', 'woocommerce-beta-tester' ),
			array( $this, 'version_select_html' ),
			'wc-beta-tester',
			'wc-beta-tester-update',
			array(
				'label_for' => 'wc-beta-tester-version',
			)
		);
	}

	/**
	 * Update section HTML output.
	 *
	 * @param array $args Arguments.
	 * @return void
	 */
	public function update_section_html( $args ) {
	?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Which version of WooCommerce to provide updates for.', 'woocommerce-beta-tester' ); ?></p>
	<?php
	}

	/**
	 * Version select markup output
	 *
	 * @param array $args Arguments.
	 * @return void
	 */
	public function version_select_html( $args ) {
		$options = get_option( 'wc_beta_tester_options' );
		?>
		<select
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			name="wc_beta_tester_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
		>
			<option value="beta" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], 'beta', false ) ) : ( '' ); ?>>
				<?php esc_html_e( 'Beta', 'woocommerce-beta-tester' ); ?>
			</option>
			<option value="rc" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], 'rc', false ) ) : ( '' ); ?>>
				<?php esc_html_e( 'Release Candidate', 'woocommerce-beta-tester' ); ?>
			</option>
			<option value="stable" <?php echo isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], 'stable', false ) ) : ( '' ); ?>>
				<?php esc_html_e( 'Stable', 'woocommerce-beta-tester' ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Which version of WooCommerce do you want to keep up to date with?.', 'woocommerce-beta-tester' ); ?>
		</p>
		<?php
	}

	/**
	 * Add options page to menu
	 *
	 * @return void
	 */
	public function add_to_menus() {
		add_submenu_page( 'plugins.php', __( 'WooCommerce Beta Tester', 'woocommerce-beta-tester' ), __( 'WooCommerce Beta Tester', 'woocommerce-beta-tester' ), 'install_plugins', 'wc-beta-tester', array( $this, 'settings_page_html' ) );
	}

	/**
	 * Output settings HTML
	 *
	 * @return void
	 */
	public function settings_page_html() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error( 'wc-beta-tester-messages', 'wc-beta-tester-message', __( 'Settings Saved', 'woocommerce-beta-tester' ), 'updated' );
		}

		// show error/update messages.
		settings_errors( 'wc-beta-tester-messages' );

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
		<?php

		settings_fields( 'wc-beta-tester' );
		do_settings_sections( 'wc-beta-tester' );
		submit_button( 'Save Settings' );

		?>
			</form>
		</div>
		<?php
	}
}

new WC_Beta_Tester_Settings();
