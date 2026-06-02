<?php
/**
 * EditorControls prototype improvement class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\DevPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Replaces the legacy "Add Media" button with a compact @wordpress/components-style button.
 * The insert-media class is preserved so WordPress's media upload event delegation still works.
 */
class EditorControls {

	/**
	 * Register hooks. No-ops if the dev panel flag is off.
	 *
	 * @internal
	 */
	final public static function init(): void {
		if ( ! DevPanel::is_flag_enabled( 'compact_editor_ui' ) ) {
			return;
		}
		add_action( 'admin_footer', array( self::class, 'replace_media_buttons' ) );
	}

	/**
	 * Replace .add_media buttons with compact component-style buttons.
	 */
	public static function replace_media_buttons(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		?>
		<script>
		( function () {
			var ICON = '<span class="dashicons dashicons-admin-media" aria-hidden="true" style="font-size:16px;width:16px;height:16px;line-height:1;flex-shrink:0;margin-top:1px"></span>';

			document.querySelectorAll( '.add_media' ).forEach( function ( btn ) {
				var editorId = btn.getAttribute( 'data-editor' ) || 'content';
				btn.className = 'components-button is-secondary is-compact insert-media add_media';
				btn.setAttribute( 'data-editor', editorId );
				btn.style.gap = '4px';
				btn.innerHTML = ICON + 'Add Media';
			} );
		}() );
		</script>
		<?php
	}
}
