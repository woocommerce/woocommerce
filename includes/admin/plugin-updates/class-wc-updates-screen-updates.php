<?php
/**
 * Manages WooCommerce plugin updating on the Updates screen.
 *
 * @package WooCommerce/Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Updates_Screen_Updates
 */
class WC_Updates_Screen_Updates extends WC_Plugin_Updates {

	/**
	 * Singleton instance.
	 *
	 * @var WC_Plugins_Screen_Updates|null
	 */
	protected static $instance = null;

	/**
	 * Return singleston instance.
	 *
	 * @static
	 * @return WC_Plugins_Screen_Updates
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
	 * Hook into WP actions/filters.
	 */
	public function init() {
		add_action( 'admin_print_footer_scripts', array( $this, 'update_screen_modal' ) );
	}

	/**
	 * Show a warning message on the upgrades screen if the user tries to upgrade and has untested plugins.
	 */
	public function update_screen_modal() {
		$updateable_plugins = get_plugin_updates();
		if ( empty( $updateable_plugins['woocommerce/woocommerce.php'] )
			|| empty( $updateable_plugins['woocommerce/woocommerce.php']->update )
			|| empty( $updateable_plugins['woocommerce/woocommerce.php']->update->new_version ) ) {
			return;
		}

		$this->new_version            = wc_clean( $updateable_plugins['woocommerce/woocommerce.php']->update->new_version );
		$this->major_untested_plugins = $this->get_untested_plugins( $this->new_version, 'major' );

		if ( ! empty( $this->major_untested_plugins ) ) {
			echo $this->get_extensions_modal_warning(); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
			$this->update_screen_modal_js();
		}
	}

	/**
	 * JS for the modal window on the updates screen.
	 */
	protected function update_screen_modal_js() {
		?>
		<script>
			( function( $ ) {
				var modal_dismissed = false;

				// Show the modal if the WC upgrade checkbox is checked.
				var show_modal_if_checked = function() {
					if ( modal_dismissed ) {
						return;
					}
					var $checkbox = $( 'input[value="woocommerce/woocommerce.php"]' );
					if ( $checkbox.prop( 'checked' ) ) {
						$( '#wc-upgrade-warning' ).click();
					}
				}

				$( '#plugins-select-all, input[value="woocommerce/woocommerce.php"]' ).on( 'change', function() {
					show_modal_if_checked();
				} );

				// Add a hidden thickbox link to use for bringing up the modal.
				$('body').append( '<a href="#TB_inline?height=600&width=550&inlineId=wc_untested_extensions_modal" class="wc-thickbox" id="wc-upgrade-warning" style="display:none"></a>' );

				// Don't show the modal again once it's been accepted.
				$( '#wc_untested_extensions_modal .accept' ).on( 'click', function( evt ) {
					evt.preventDefault();
					modal_dismissed = true;
					tb_remove();
				});

				// Uncheck the WC update checkbox if the modal is canceled.
				$( '#wc_untested_extensions_modal .cancel' ).on( 'click', function( evt ) {
					evt.preventDefault();
					$( 'input[value="woocommerce/woocommerce.php"]' ).prop( 'checked', false );
					tb_remove();
				});
			})( jQuery );
		</script>
		<?php
		$this->generic_modal_js();
	}
}
