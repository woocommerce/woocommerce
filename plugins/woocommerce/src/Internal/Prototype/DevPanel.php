<?php
/**
 * Prototype dev panel class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype;

defined( 'ABSPATH' ) || exit;

/**
 * Floating dev panel for the classic product editor.
 * Toggles prototype feature flags via cookies.
 */
class DevPanel {

	const COOKIE_KEY = 'wc_prototype_flags';

	const FLAGS = array(
		'reorder_controls' => 'Reorder controls via Screen Options',
	);

	/**
	 * Register hooks.
	 *
	 * @internal
	 */
	final public static function init(): void {
		add_action( 'admin_footer', array( self::class, 'render' ) );
	}

	/**
	 * Check whether a prototype flag is enabled via cookie.
	 *
	 * @param string $key Flag key.
	 * @return bool
	 */
	public static function is_flag_enabled( string $key ): bool {
		if ( ! isset( $_COOKIE[ self::COOKIE_KEY ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return false;
		}
		$flags = json_decode( stripslashes( $_COOKIE[ self::COOKIE_KEY ] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! is_array( $flags ) ) {
			return false;
		}
		return ! empty( $flags[ $key ] );
	}

	/**
	 * Render the dev panel on the product edit screen.
	 */
	public static function render(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || 'product' !== $screen->post_type ) {
			return;
		}
		self::output_panel_html();
	}

	/**
	 * Output the floating panel HTML and inline JS.
	 */
	private static function output_panel_html(): void {
		$cookie_key = self::COOKIE_KEY;
		?>
		<div id="wc-proto-panel" style="position:fixed;bottom:16px;right:16px;z-index:99999;font-family:monospace;font-size:12px;">
			<div id="wc-proto-card" style="display:none;background:rgba(0,0,0,0.85);color:#fff;border-radius:8px;padding:12px;margin-bottom:8px;min-width:220px;">
				<?php foreach ( self::FLAGS as $key => $label ) : ?>
				<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
					<label for="proto-flag-<?php echo esc_attr( $key ); ?>" style="margin-right:12px;cursor:pointer;">
						<?php echo esc_html( $label ); ?>
					</label>
					<input
						type="checkbox"
						id="proto-flag-<?php echo esc_attr( $key ); ?>"
						data-flag="<?php echo esc_attr( $key ); ?>"
						<?php checked( self::is_flag_enabled( $key ) ); ?>
					/>
				</div>
				<?php endforeach; ?>
			</div>
			<button id="wc-proto-toggle" style="background:rgba(0,0,0,0.75);color:#fff;border:none;border-radius:12px;padding:4px 12px;cursor:pointer;display:block;margin-left:auto;">Dev</button>
		</div>
		<script>
		( function () {
			var COOKIE_KEY = '<?php echo esc_js( $cookie_key ); ?>';

			function getFlags() {
				var match = document.cookie.match( new RegExp( '(?:^|; )' + COOKIE_KEY.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) + '=([^;]*)' ) );
				if ( ! match ) {
					return {};
				}
				try {
					return JSON.parse( decodeURIComponent( match[1] ) );
				} catch ( e ) {
					return {};
				}
			}

			function setFlags( flags ) {
				document.cookie = COOKIE_KEY + '=' + encodeURIComponent( JSON.stringify( flags ) ) + ';path=/;max-age=86400';
			}

			document.getElementById( 'wc-proto-toggle' ).addEventListener( 'click', function () {
				var card = document.getElementById( 'wc-proto-card' );
				card.style.display = card.style.display === 'none' ? 'block' : 'none';
			} );

			document.querySelectorAll( '[data-flag]' ).forEach( function ( checkbox ) {
				checkbox.addEventListener( 'change', function () {
					var flags = getFlags();
					flags[ this.dataset.flag ] = this.checked;
					setFlags( flags );
					location.reload();
				} );
			} );
		}() );
		</script>
		<?php
	}
}
