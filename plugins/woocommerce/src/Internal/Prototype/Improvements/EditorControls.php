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
		add_action( 'admin_head', array( self::class, 'output_styles' ) );
		add_action( 'admin_footer', array( self::class, 'replace_media_buttons' ) );
	}

	/**
	 * Output styles for the compact editor controls.
	 */
	public static function output_styles(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		echo '<style id="wc-proto-editor-controls">
			.wp-media-buttons { padding-top: 2px; }
		</style>';
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
			var ICON = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false" style="flex-shrink:0"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM5 4.5h14c.3 0 .5.2.5.5v8.4l-3-2.9c-.3-.3-.8-.3-1 0L11 14l-2.5-2.5c-.3-.3-.8-.3-1.1 0L5 14V5c0-.3.2-.5.5-.5zm14 15H5c-.3 0-.5-.2-.5-.5v-2.4l4-4 2.5 2.5c.3.3.8.3 1.1 0L16 11l3.5 3.4V19c0 .3-.2.5-.5.5z"/></svg>';

			document.querySelectorAll( '.add_media' ).forEach( function ( btn ) {
				var editorId = btn.getAttribute( 'data-editor' ) || 'content';
				btn.className = 'components-button is-secondary is-compact insert-media add_media';
				btn.setAttribute( 'data-editor', editorId );
				btn.style.gap = '4px';
				btn.style.paddingLeft = '8px';
				btn.style.paddingRight = '8px';
				btn.innerHTML = ICON + 'Add Media';
			} );
		}() );
		</script>
		<?php
	}
}
