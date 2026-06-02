<?php
/**
 * EditorControls prototype improvement class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\DevPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Compact CTA treatment for the classic product editor:
 * normalises all .button / .button-primary / .button-secondary inside metabox `.inside`
 * containers to the 32px WPDS compact button size, and replaces the legacy
 * "Add Media" button with a compact @wordpress/components-style button.
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
		?>
<style id="wc-proto-editor-controls">
.wp-media-buttons { padding-top: 2px; }

/* ── Compact CTAs (32px) inside product editor metaboxes ── */
body.post-type-product .inside .button,
body.post-type-product .inside .button-primary,
body.post-type-product .inside .button-secondary,
body.post-type-product .inside .button.button-large,
body.post-type-product .inside input[type="submit"].button,
body.post-type-product .inside input[type="button"].button {
	height: 32px;
	min-height: 32px;
	line-height: 30px;
	padding: 0 12px;
	font-size: var(--wpds-typography-font-size-md, 13px);
	box-sizing: border-box;
}

/* Keep media library / modal buttons untouched */
body.post-type-product .media-modal .button,
body.post-type-product .media-modal .button-primary,
body.post-type-product .media-modal .button-secondary {
	height: auto;
	line-height: inherit;
	padding: revert;
	font-size: revert;
}

/* ── Compact inputs (32px) inside product editor metaboxes ── */
body.post-type-product .inside input[type="text"],
body.post-type-product .inside input[type="number"],
body.post-type-product .inside input[type="email"],
body.post-type-product .inside input[type="url"],
body.post-type-product .inside input[type="search"],
body.post-type-product .inside input[type="password"],
body.post-type-product .inside input[type="tel"],
body.post-type-product .inside input[type="date"],
body.post-type-product .inside input[type="datetime-local"],
body.post-type-product .inside input[type="time"],
body.post-type-product .inside select,
body.post-type-product #woocommerce-product-data select#product-type {
	height: 32px;
	min-height: 32px;
	line-height: 30px;
	box-sizing: border-box;
}

/* Keep modal inputs (media library, etc.) untouched */
body.post-type-product .media-modal input,
body.post-type-product .media-modal select {
	height: auto;
	min-height: 0;
	line-height: inherit;
}
</style>
		<?php
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
