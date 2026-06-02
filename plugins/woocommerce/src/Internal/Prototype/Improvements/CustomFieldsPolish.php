<?php
/**
 * CustomFieldsPolish prototype improvement class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\DevPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Polishes the Custom Fields metabox (#postcustom):
 * - Strips table chrome (alt-row backgrounds, inner borders) and replaces with one outer border.
 * - Hides the per-row "Update" button; key/value edits auto-save on blur via the existing WP AJAX endpoint.
 * - Replaces the textual "Delete" button with a compact centered X icon — same pattern as DownloadableFilesPolish.
 * - Modernises "Enter new" / "Add Custom Field" controls to brand-blue text-buttons.
 */
class CustomFieldsPolish {

	const FLAG_KEY = 'custom_fields_polish';

	/**
	 * Register hooks. No-ops if the dev panel flag is off.
	 *
	 * @internal
	 */
	final public static function init(): void {
		if ( ! DevPanel::is_flag_enabled( self::FLAG_KEY ) ) {
			return;
		}
		add_action( 'admin_head', array( self::class, 'output_styles' ) );
		add_action( 'admin_footer', array( self::class, 'output_scripts' ) );
	}

	/**
	 * Build a CSS mask-image value from an inline SVG path.
	 *
	 * @param string $path SVG path d="" attribute.
	 * @return string CSS url(...) value.
	 */
	private static function mask_url( string $path ): string {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="' . $path . '"/></svg>';
		return 'url("data:image/svg+xml;utf8,' . rawurlencode( $svg ) . '")';
	}

	/**
	 * Output inline CSS for the custom fields polish.
	 */
	public static function output_styles(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}

		$close_url = self::mask_url( 'M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z' );
		?>
<style id="wc-proto-custom-fields">
/* ── Strip stripe/background chrome on both tables, keep outer border ─ */
body.post-type-product #postcustomstuff table,
body.post-type-product #postcustomstuff table tr,
body.post-type-product #postcustomstuff table th,
body.post-type-product #postcustomstuff table td {
	background: transparent !important;
	box-shadow: none !important;
}

body.post-type-product #postcustomstuff #list-table,
body.post-type-product #postcustomstuff #newmeta {
	border: 1px solid var(--wpds-color-border-neutral-subtle, #dcdcde) !important;
	border-radius: 4px !important;
	border-collapse: separate !important;
	border-spacing: 0;
	margin: 0 0 8px;
}

body.post-type-product #postcustomstuff table th,
body.post-type-product #postcustomstuff table td {
	border: none !important;
	padding: 8px 10px;
	vertical-align: middle;
}

/* Single thin divider below the header row */
body.post-type-product #postcustomstuff table thead th {
	border-bottom: 1px solid var(--wpds-color-border-neutral-subtle, #dcdcde) !important;
	font-size: var(--wpds-typography-font-size-sm, 12px);
	font-weight: var(--wpds-typography-font-weight-regular, 400);
	color: var(--wpds-color-fg-content-neutral-weak, #707070);
	text-align: left;
	line-height: 16px;
}

/* Hide the empty-state column headers on #list-table when no meta yet */
body.post-type-product #postcustomstuff #list-table:not(:has(#the-list tr)) thead {
	display: none;
}

/* ── Inputs: full-width, compact ────────────────────────── */
body.post-type-product #postcustomstuff #list-table input[type="text"],
body.post-type-product #postcustomstuff #newmeta input[type="text"],
body.post-type-product #postcustomstuff #newmeta select {
	width: 100%;
	box-sizing: border-box;
}

body.post-type-product #postcustomstuff textarea {
	width: 100%;
	box-sizing: border-box;
	min-height: 60px;
	font-size: 13px;
}

/* ── Hide Update + submit wrapper, repurpose existing row for X delete ── */
body.post-type-product #postcustomstuff .submit {
	display: contents !important;
}
body.post-type-product #postcustomstuff .updatemeta {
	display: none !important;
}

/* ── Delete button → centered X icon, like the downloads table ───── */
body.post-type-product #postcustomstuff #the-list tr {
	position: relative;
}
body.post-type-product #postcustomstuff .deletemeta {
	position: absolute !important;
	top: 12px !important;
	right: 10px !important;
	width: 20px !important;
	height: 20px !important;
	padding: 0 !important;
	margin: 0 !important;
	font-size: 0 !important;
	color: transparent !important;
	background: transparent !important;
	border: none !important;
	box-shadow: none !important;
	text-shadow: none !important;
	cursor: pointer !important;
}
body.post-type-product #postcustomstuff .deletemeta::before {
	content: '';
	position: absolute;
	inset: 0;
	background-color: var(--wpds-color-fg-content-neutral-weak, #757575);
	-webkit-mask: <?php echo $close_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> no-repeat center / 16px 16px;
	mask: <?php echo $close_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> no-repeat center / 16px 16px;
}
body.post-type-product #postcustomstuff .deletemeta:hover::before {
	background-color: var(--wpds-color-fg-content-error, #cc1818);
}

/* Pad the value column so the X never sits on the textarea */
body.post-type-product #postcustomstuff #the-list td:last-child {
	padding-right: 36px !important;
}

/* ── "Enter new" / "Cancel" inline links ────────────────── */
body.post-type-product #postcustomstuff #enternew,
body.post-type-product #postcustomstuff #cancel {
	display: inline-flex !important;
	align-items: center !important;
	height: 28px !important;
	line-height: 26px !important;
	padding: 0 8px !important;
	margin: 4px 4px 0 0 !important;
	font-size: 12px !important;
	background: none !important;
	border: 1px solid transparent !important;
	border-radius: var(--wpds-border-radius-xs, 2px) !important;
	color: var(--wpds-color-fg-interactive-brand, #3858e9) !important;
	box-shadow: none !important;
	text-shadow: none !important;
	text-decoration: none !important;
	cursor: pointer !important;
}
body.post-type-product #postcustomstuff #enternew:hover,
body.post-type-product #postcustomstuff #cancel:hover {
	background: var(--wpds-color-bg-interactive-brand-weak-active, #e8eaff) !important;
}

/* "Add Custom Field" submit — secondary outlined */
body.post-type-product #postcustomstuff #newmeta-submit {
	height: 32px !important;
	line-height: 30px !important;
	padding: 0 12px !important;
	font-size: 13px !important;
	margin: 8px 0 0 !important;
	background: transparent !important;
	color: var(--wpds-color-fg-interactive-brand, #3858e9) !important;
	border: 1px solid var(--wpds-color-fg-interactive-brand, #3858e9) !important;
	border-radius: var(--wpds-border-radius-xs, 2px) !important;
	box-shadow: none !important;
	text-shadow: none !important;
	cursor: pointer !important;
}
body.post-type-product #postcustomstuff #newmeta-submit:hover {
	background: var(--wpds-color-bg-interactive-brand-weak-active, #e8eaff) !important;
}

/* ── "Add Custom Field:" label — tighter spacing ────────── */
body.post-type-product #postcustomstuff p.label-required,
body.post-type-product #postcustomstuff > p:first-of-type {
	margin: 16px 0 8px;
	font-weight: var(--wpds-typography-font-weight-medium, 499);
	font-size: var(--wpds-typography-font-size-md, 13px);
	color: var(--wpds-color-fg-content-neutral, #1e1e1e);
}

/* ── Helper text footer ─────────────────────────────────── */
body.post-type-product #postcustomstuff .howto,
body.post-type-product #postcustomstuff p.description,
body.post-type-product #postcustom > .inside > p:last-child {
	margin: 12px 0 0 !important;
	padding: 0 !important;
	font-size: var(--wpds-typography-font-size-sm, 12px) !important;
	color: var(--wpds-color-fg-content-neutral-weak, #757575) !important;
	font-style: normal !important;
	line-height: 1.4 !important;
}

/* Subtle saved-flash on the row */
body.post-type-product #postcustomstuff #the-list tr.wc-proto-saved {
	transition: background-color 600ms ease-out;
	background-color: var(--wpds-color-bg-interactive-brand-weak-active, #e8eaff) !important;
}
</style>
		<?php
	}

	/**
	 * Output JS that auto-triggers the per-row Update AJAX when key/value fields lose focus.
	 * This makes the now-hidden Update button unnecessary while keeping WP's AJAX path intact.
	 */
	public static function output_scripts(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		?>
<script>
( function () {
	var list = document.getElementById( 'the-list' );
	if ( ! list ) { return; }

	function autoSaveRow( row ) {
		var updateBtn = row.querySelector( '.updatemeta' );
		if ( ! updateBtn ) { return; }
		updateBtn.click();
		row.classList.add( 'wc-proto-saved' );
		setTimeout( function () { row.classList.remove( 'wc-proto-saved' ); }, 700 );
	}

	function wire( row ) {
		if ( row.dataset.wcProtoWired ) { return; }
		row.dataset.wcProtoWired = '1';
		row.querySelectorAll( 'input[type="text"], textarea' ).forEach( function ( field ) {
			var initial = field.value;
			field.addEventListener( 'blur', function () {
				if ( field.value !== initial ) {
					initial = field.value;
					autoSaveRow( row );
				}
			} );
		} );
	}

	list.querySelectorAll( 'tr' ).forEach( wire );

	/* When WPList AJAX swaps in a new row after add/update, wire it too. */
	new MutationObserver( function ( mutations ) {
		mutations.forEach( function ( m ) {
			m.addedNodes.forEach( function ( node ) {
				if ( node.nodeType === 1 && node.tagName === 'TR' ) { wire( node ); }
			} );
		} );
	} ).observe( list, { childList: true } );
}() );
</script>
		<?php
	}
}
