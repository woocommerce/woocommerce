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
 * - Replaces the textual "Delete" button with a compact centered X icon.
 * - Merges the "Add Custom Field" workflow into the main table as an inline-add row:
 *   one trigger button reveals Name/Value inputs and a Confirm CTA below them.
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
/* ── Hide WP's separate "Add Custom Field" UI — we replace it with inline rows in #list-table ── */
body.post-type-product #postcustomstuff #newmeta,
body.post-type-product #postcustomstuff p.label-required,
body.post-type-product #postcustomstuff > p.label-required,
body.post-type-product #postcustomstuff > p:first-of-type {
	display: none !important;
}

/* ── Strip stripe/background chrome on #list-table, keep outer border ─ */
body.post-type-product #postcustomstuff #list-table,
body.post-type-product #postcustomstuff #list-table tr,
body.post-type-product #postcustomstuff #list-table th,
body.post-type-product #postcustomstuff #list-table td {
	background: transparent !important;
	box-shadow: none !important;
}

body.post-type-product #postcustomstuff #list-table {
	border: 1px solid var(--wpds-color-border-neutral-subtle, #dcdcde) !important;
	border-radius: 4px !important;
	border-collapse: separate !important;
	border-spacing: 0;
	margin: 0 0 16px;
	width: 100% !important;
}
body.post-type-product #postcustomstuff #list-table th,
body.post-type-product #postcustomstuff #list-table td {
	border: none !important;
	padding: 10px;
	vertical-align: top;
}
/* Column widths — Name 35%, Value 65% (X icon sits in the value column's right padding) */
body.post-type-product #postcustomstuff #list-table th:first-child,
body.post-type-product #postcustomstuff #list-table td:first-child {
	width: 35%;
}
body.post-type-product #postcustomstuff #list-table thead th {
	border-bottom: 1px solid var(--wpds-color-border-neutral-subtle, #dcdcde) !important;
	font-size: var(--wpds-typography-font-size-sm, 12px);
	font-weight: var(--wpds-typography-font-weight-regular, 400);
	color: var(--wpds-color-fg-content-neutral-weak, #707070);
	text-align: left;
	line-height: 16px;
}

body.post-type-product #postcustomstuff #list-table input[type="text"] {
	width: 100% !important;
	max-width: 100% !important;
	box-sizing: border-box !important;
	margin: 0 !important;
	display: block !important;
}

body.post-type-product #postcustomstuff textarea {
	width: 100%;
	box-sizing: border-box;
	min-height: 60px;
	font-size: 13px;
}

/* Hide the per-row Update button (auto-save on blur in JS) */
body.post-type-product #postcustomstuff .updatemeta {
	display: none !important;
}

/* ── Delete button → centered X icon (mask applied directly to <input>) ───── */
body.post-type-product #postcustomstuff #the-list tr {
	position: relative;
}
body.post-type-product #postcustomstuff .deletemeta {
	position: absolute !important;
	top: 16px !important;
	right: 12px !important;
	transform: none !important;
	width: 20px !important;
	height: 20px !important;
	padding: 0 !important;
	margin: 0 !important;
	font-size: 0 !important;
	color: transparent !important;
	border: none !important;
	box-shadow: none !important;
	text-shadow: none !important;
	cursor: pointer !important;
	background-color: var(--wpds-color-fg-content-neutral-weak, #757575) !important;
	-webkit-mask: <?php echo $close_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> no-repeat center / 16px 16px !important;
	mask: <?php echo $close_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> no-repeat center / 16px 16px !important;
}
body.post-type-product #postcustomstuff .deletemeta:hover {
	background-color: var(--wpds-color-fg-content-error, #cc1818) !important;
}

/* Pad the value column so there's clear breathing room between textarea and X */
body.post-type-product #postcustomstuff #the-list td:last-child {
	padding-right: 56px !important;
}

/* ── Inline-add UI inside #list-table ────────────────────── */
/* The new-entry row (hidden until the trigger is clicked) — symmetric 10px padding both sides */
body.post-type-product #postcustomstuff #list-table tr.wc-proto-add-entry-row > td,
body.post-type-product #postcustomstuff #list-table tr.wc-proto-add-entry-row > td:last-child {
	border-top: 1px solid var(--wpds-color-border-neutral-subtle, #dcdcde) !important;
	padding: 12px 10px !important;
}

/* The Confirm/Cancel row sits below the inputs, no top divider */
body.post-type-product #postcustomstuff #list-table tr.wc-proto-add-confirm-row > td {
	padding: 8px 10px 12px !important;
	text-align: left;
}

/* The trigger row sits at the bottom of the table */
body.post-type-product #postcustomstuff #list-table tr.wc-proto-add-trigger-row > td {
	border-top: 1px solid var(--wpds-color-border-neutral-subtle, #dcdcde) !important;
	padding: 12px 10px !important;
}

/* The "+ Add custom field" trigger — text-link styled for low chrome */
body.post-type-product .wc-proto-add-trigger {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	height: 32px;
	padding: 0 10px;
	font-size: 13px;
	background: transparent;
	color: var(--wpds-color-fg-interactive-brand, #3858e9);
	border: 1px solid var(--wpds-color-fg-interactive-brand, #3858e9);
	border-radius: var(--wpds-border-radius-xs, 2px);
	cursor: pointer;
}
body.post-type-product .wc-proto-add-trigger:hover {
	background: var(--wpds-color-bg-interactive-brand-weak-active, #e8eaff);
}

/* Confirm CTA — secondary outlined (matches the publish metabox Confirm) */
body.post-type-product .wc-proto-confirm {
	display: inline-flex;
	align-items: center;
	height: 32px;
	padding: 0 12px;
	font-size: 13px;
	background: transparent;
	color: var(--wpds-color-fg-interactive-brand, #3858e9);
	border: 1px solid var(--wpds-color-fg-interactive-brand, #3858e9);
	border-radius: var(--wpds-border-radius-xs, 2px);
	cursor: pointer;
	margin-right: 8px;
}
body.post-type-product .wc-proto-confirm:hover {
	background: var(--wpds-color-bg-interactive-brand-weak-active, #e8eaff);
}

/* Cancel — tertiary text link */
body.post-type-product .wc-proto-cancel {
	display: inline-flex;
	align-items: center;
	height: 32px;
	padding: 0 8px;
	font-size: 13px;
	background: none;
	color: var(--wpds-color-fg-interactive-brand, #3858e9);
	border: 1px solid transparent;
	border-radius: var(--wpds-border-radius-xs, 2px);
	cursor: pointer;
	text-decoration: underline;
}
body.post-type-product .wc-proto-cancel:hover {
	background: none;
	text-decoration: none;
}

/* Hide entry/confirm rows by default (revealed via .wc-proto-adding class on tbody) */
body.post-type-product #postcustomstuff tr.wc-proto-add-entry-row,
body.post-type-product #postcustomstuff tr.wc-proto-add-confirm-row {
	display: none;
}
body.post-type-product #postcustomstuff #the-list.wc-proto-adding tr.wc-proto-add-entry-row,
body.post-type-product #postcustomstuff #the-list.wc-proto-adding tr.wc-proto-add-confirm-row {
	display: table-row;
}
body.post-type-product #postcustomstuff #the-list.wc-proto-adding tr.wc-proto-add-trigger-row {
	display: none;
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

/* Subtle saved-flash on existing rows after auto-save */
body.post-type-product #postcustomstuff #the-list tr.wc-proto-saved {
	transition: background-color 600ms ease-out;
	background-color: var(--wpds-color-bg-interactive-brand-weak-active, #e8eaff) !important;
}
</style>
		<?php
	}

	/**
	 * Output JS for inline-add UI plus auto-save on blur for existing rows.
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

	/* ── Auto-save existing meta on blur ─────────────────── */
	function autoSaveRow( row ) {
		var updateBtn = row.querySelector( '.updatemeta' );
		if ( ! updateBtn ) { return; }
		updateBtn.click();
		row.classList.add( 'wc-proto-saved' );
		setTimeout( function () { row.classList.remove( 'wc-proto-saved' ); }, 700 );
	}
	function wire( row ) {
		if ( row.dataset.wcProtoWired ) { return; }
		if ( row.classList && (
			row.classList.contains( 'wc-proto-add-entry-row' ) ||
			row.classList.contains( 'wc-proto-add-confirm-row' ) ||
			row.classList.contains( 'wc-proto-add-trigger-row' )
		) ) { return; }
		row.dataset.wcProtoWired = '1';
		row.querySelectorAll( 'input[type="text"], textarea' ).forEach( function ( field ) {
			if ( field.id === 'wc-proto-new-key' || field.id === 'wc-proto-new-value' ) { return; }
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
	new MutationObserver( function ( mutations ) {
		mutations.forEach( function ( m ) {
			m.addedNodes.forEach( function ( node ) {
				if ( node.nodeType === 1 && node.tagName === 'TR' ) { wire( node ); }
			} );
		} );
	} ).observe( list, { childList: true } );

	/* ── Inline add-row UI ───────────────────────────────── */
	var newmetaSubmit = document.getElementById( 'newmeta-submit' );
	var metaKeyInput  = document.getElementById( 'metakeyinput' );
	var metaKeySelect = document.getElementById( 'metakeyselect' );
	var metaValue     = document.getElementById( 'metavalue' );
	if ( ! newmetaSubmit ) { return; }

	function makeRow( cls, inner ) {
		var tr = document.createElement( 'tr' );
		tr.className = cls;
		tr.innerHTML = inner;
		return tr;
	}

	/* Build a <datalist> from WP's #metakeyselect options so the Name input
	   offers existing keys as autocomplete suggestions while still allowing free text. */
	var datalistHtml = '';
	if ( metaKeySelect ) {
		datalistHtml = '<datalist id="wc-proto-key-suggestions">';
		Array.prototype.forEach.call( metaKeySelect.options, function ( opt ) {
			if ( opt.value && opt.value !== '#NONE#' ) {
				datalistHtml += '<option value="' + opt.value.replace( /"/g, '&quot;' ) + '">';
			}
		} );
		datalistHtml += '</datalist>';
	}

	var entryRow = makeRow( 'wc-proto-add-entry-row',
		'<td class="left">' +
			'<input type="text" id="wc-proto-new-key" placeholder="Name"' +
			( datalistHtml ? ' list="wc-proto-key-suggestions" autocomplete="off"' : '' ) +
			'>' + datalistHtml +
		'</td>' +
		'<td><textarea id="wc-proto-new-value" placeholder="Value" rows="2"></textarea></td>'
	);
	var confirmRow = makeRow( 'wc-proto-add-confirm-row',
		'<td colspan="2">' +
			'<button type="button" class="wc-proto-confirm">Confirm</button>' +
			'<button type="button" class="wc-proto-cancel">Cancel</button>' +
		'</td>'
	);
	var triggerRow = makeRow( 'wc-proto-add-trigger-row',
		'<td colspan="2"><button type="button" class="wc-proto-add-trigger">+ Add custom field</button></td>'
	);
	list.appendChild( entryRow );
	list.appendChild( confirmRow );
	list.appendChild( triggerRow );

	function openAdder() {
		list.classList.add( 'wc-proto-adding' );
		document.getElementById( 'wc-proto-new-key' ).focus();
	}
	function closeAdder() {
		list.classList.remove( 'wc-proto-adding' );
		document.getElementById( 'wc-proto-new-key' ).value = '';
		document.getElementById( 'wc-proto-new-value' ).value = '';
	}

	triggerRow.querySelector( '.wc-proto-add-trigger' ).addEventListener( 'click', openAdder );
	confirmRow.querySelector( '.wc-proto-cancel' ).addEventListener( 'click', closeAdder );
	confirmRow.querySelector( '.wc-proto-confirm' ).addEventListener( 'click', function () {
		var key   = document.getElementById( 'wc-proto-new-key' ).value.trim();
		var value = document.getElementById( 'wc-proto-new-value' ).value;
		if ( ! key ) {
			document.getElementById( 'wc-proto-new-key' ).focus();
			return;
		}
		if ( metaKeySelect ) { metaKeySelect.value = '#NONE#'; }
		if ( metaKeyInput )  { metaKeyInput.value  = key; }
		if ( metaValue )     { metaValue.value     = value; }
		newmetaSubmit.click();
		closeAdder();
	} );
}() );
</script>
		<?php
	}
}
