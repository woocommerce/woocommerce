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
 * - Strips table chrome and replaces it with a single outer border.
 * - Per-row Update button is hidden; edits to existing fields are tracked by the main post
 *   form, so the main Update button reflects them and saves on submit (matches WP's
 *   $_POST['meta'] handling). No more silent inline AJAX saves.
 * - Delete button becomes a compact X icon.
 * - The separate "Add New Custom Field" form is hidden; its real <select>, <input>, and the
 *   Enter new / Cancel link wrappers are relocated into an inline-add area in a <tfoot> of
 *   #list-table. A "+ Add custom field" trigger reveals them, a Confirm CTA submits via WP's
 *   existing #newmeta-submit endpoint (the add-new path still uses AJAX so the row appears
 *   immediately without a page reload).
 * - Empty state: with no meta rows, the column headers hide so the metabox starts with the
 *   trigger button only (matches DownloadableFilesPolish pattern).
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
/* ── Hide WP's "Add New Custom Field" section — we relocate its controls into <tfoot> ── */
body.post-type-product #postcustomstuff #newmeta,
body.post-type-product #postcustomstuff > p.label-required,
body.post-type-product #postcustomstuff > p:first-of-type,
body.post-type-product #postcustomstuff .submit.add-custom-field,
body.post-type-product #postcustomstuff div.submit.add-custom-field {
	display: none !important;
}

/* ── #list-table outer chrome ─────────────────────────── */
body.post-type-product #postcustomstuff #list-table,
body.post-type-product #postcustomstuff #list-table tr,
body.post-type-product #postcustomstuff #list-table th,
body.post-type-product #postcustomstuff #list-table td {
	background: transparent !important;
	box-shadow: none !important;
}
body.post-type-product #postcustomstuff #list-table {
	display: table !important; /* WP sets inline display:none when no meta; we still want the table for the inline-add UI. */
	border: 1px solid var(--wpds-color-border-neutral-subtle, #dcdcde) !important;
	border-radius: 4px !important;
	border-collapse: separate !important;
	border-spacing: 0;
	margin: 0 0 16px;
	width: 100% !important;
}
/* Hide WP's placeholder empty <tr><td></td></tr> that ships in the no-meta state. */
body.post-type-product #postcustomstuff #the-list > tr:not([id^="meta-"]) {
	display: none;
}
body.post-type-product #postcustomstuff #list-table th,
body.post-type-product #postcustomstuff #list-table td {
	border: none !important;
	padding: 10px;
	vertical-align: top;
}
/* Column widths */
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
/* Empty state: no meta rows → hide column headers. CSS :has() + JS class fallback. */
body.post-type-product #postcustomstuff #list-table:not(:has(#the-list tr)) thead,
body.post-type-product #postcustomstuff #list-table.wc-proto-empty thead {
	display: none;
}

/* Full-width for existing-row inputs only — EXCLUDE #metakeyinput, whose visibility
	is toggled by WP's $.toggle() on Enter new / Cancel. Forcing display:block on it
	would override the inline display:none we set in JS for the initial hidden state. */
body.post-type-product #postcustomstuff #list-table input[type="text"]:not(#metakeyinput) {
	width: 100% !important;
	max-width: 100% !important;
	box-sizing: border-box !important;
	margin: 0 !important;
	display: block !important;
}
/* #metakeyinput: full-width + spacing only when WP shows it; never force display. */
body.post-type-product #postcustomstuff #metakeyinput {
	width: 100% !important;
	max-width: 100% !important;
	box-sizing: border-box !important;
	margin: 0 0 6px !important;
}
body.post-type-product #postcustomstuff textarea {
	width: 100%;
	box-sizing: border-box;
	min-height: 60px;
	font-size: 13px;
	margin: 0 !important;
	display: block;
}

/* Hide the per-row Update button (auto-save on blur in JS) */
body.post-type-product #postcustomstuff .updatemeta {
	display: none !important;
}

/* ── Delete button → centered X icon (mask on <input>) ── */
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
body.post-type-product #postcustomstuff #the-list td:last-child {
	padding-right: 56px !important;
}

/* ── Inline-add UI in <tfoot> ─────────────────────────── */
/* Entry + Confirm + Trigger rows */
body.post-type-product #postcustomstuff #list-table tfoot.wc-proto-add tr td {
	border: none !important;
	padding: 12px 10px !important;
	vertical-align: top;
}
/* Top divider for the first visible row in tfoot (whichever it is) */
body.post-type-product #postcustomstuff #list-table tfoot.wc-proto-add tr.wc-proto-add-entry-row > td,
body.post-type-product #postcustomstuff #list-table tfoot.wc-proto-add tr.wc-proto-add-trigger-row > td {
	border-top: 1px solid var(--wpds-color-border-neutral-subtle, #dcdcde) !important;
}
/* In the empty state there's no content above the trigger or entry row, so no divider needed. */
body.post-type-product #postcustomstuff #list-table.wc-proto-empty tfoot.wc-proto-add tr.wc-proto-add-trigger-row > td,
body.post-type-product #postcustomstuff #list-table.wc-proto-empty tfoot.wc-proto-add tr.wc-proto-add-entry-row > td {
	border-top: none !important;
}
/* Match the existing-row padding-right on the value column so textareas align. */
body.post-type-product #postcustomstuff tfoot.wc-proto-add tr.wc-proto-add-entry-row > td.wc-proto-add-value {
	padding-right: 56px !important;
}

/* Name controls: full-width select / input (when visible) */
body.post-type-product #postcustomstuff .wc-proto-add-name #metakeyselect,
body.post-type-product #postcustomstuff .wc-proto-add-name #metakeyinput {
	width: 100% !important;
	max-width: 100% !important;
	box-sizing: border-box !important;
	margin: 0 0 6px !important;
}
/* Only apply block display when the element isn't hidden via WP class/inline style.
	WP toggles via $.show()/$.hide() which sets inline style — those win regardless. */
body.post-type-product #postcustomstuff .wc-proto-add-name #metakeyselect:not([style*="display: none"]):not(.hide-if-js),
body.post-type-product #postcustomstuff .wc-proto-add-name #metakeyinput:not([style*="display: none"]):not(.hide-if-js) {
	display: block;
}

/* Our injected Enter new / Cancel text links (replace WP's button-styled anchors).
   NOTE: display is intentionally NOT !important — JS toggles `style.display` to hide
   one link while the other shows. */
body.post-type-product #postcustomstuff .wc-proto-textlink,
body.post-type-product #postcustomstuff a.wc-proto-textlink {
	display: inline-block;
	margin: 6px 12px 0 0 !important;
	padding: 0 !important;
	background: transparent !important;
	border: 0 !important;
	box-shadow: none !important;
	color: var(--wpds-color-fg-interactive-brand, #3858e9) !important;
	text-decoration: underline !important;
	font-size: var(--wpds-typography-font-size-sm, 12px) !important;
	line-height: 1.4 !important;
	cursor: pointer !important;
	height: auto !important;
	min-height: 0 !important;
	border-radius: 0 !important;
}
body.post-type-product #postcustomstuff .wc-proto-textlink:hover {
	text-decoration: none !important;
}
body.post-type-product #postcustomstuff .wc-proto-textlink:focus,
body.post-type-product #postcustomstuff .wc-proto-textlink:focus-visible {
	outline: none !important;
	box-shadow: none !important;
}

/* Legacy WP "Enter new" / "Cancel" anchors — we drop them in JS, but if anything ever
	reintroduces them, hide them so they never re-appear as button-styled blobs. */
body.post-type-product #postcustomstuff .wc-proto-add-name a.postcustomstuff,
body.post-type-product #postcustomstuff .wc-proto-add-name a.hide-if-no-js {
	display: inline !important;
	margin: 4px 0 0 !important;
	padding: 0 !important;
	background: transparent !important;
	background-color: transparent !important;
	border: 0 !important;
	box-shadow: none !important;
	text-shadow: none !important;
	color: var(--wpds-color-fg-interactive-brand, #3858e9) !important;
	text-decoration: underline !important;
	font-size: var(--wpds-typography-font-size-sm, 12px) !important;
	line-height: 1.4 !important;
	cursor: pointer !important;
	height: auto !important;
	min-height: 0 !important;
	border-radius: 0 !important;
}
body.post-type-product #postcustomstuff .wc-proto-add-name a:hover {
	text-decoration: none !important;
	background: transparent !important;
}
/* Kill the browser default focus outline that looks like a button border. */
body.post-type-product #postcustomstuff .wc-proto-add-name a:focus,
body.post-type-product #postcustomstuff .wc-proto-add-name a:focus-visible {
	outline: none !important;
	box-shadow: none !important;
	border: 0 !important;
	background: transparent !important;
}
/* The inner #enternew/#cancelnew spans should inherit, no extra styling */
body.post-type-product #postcustomstuff #enternew,
body.post-type-product #postcustomstuff #cancelnew {
	background: transparent !important;
	border: 0 !important;
	padding: 0 !important;
	font-weight: inherit !important;
}

/* Show the entry/confirm rows only while adding */
body.post-type-product #postcustomstuff tr.wc-proto-add-entry-row,
body.post-type-product #postcustomstuff tr.wc-proto-add-confirm-row {
	display: none;
}
body.post-type-product #postcustomstuff tfoot.wc-proto-add.is-adding tr.wc-proto-add-entry-row,
body.post-type-product #postcustomstuff tfoot.wc-proto-add.is-adding tr.wc-proto-add-confirm-row {
	display: table-row;
}
body.post-type-product #postcustomstuff tfoot.wc-proto-add.is-adding tr.wc-proto-add-trigger-row {
	display: none;
}

/* The "+ Add custom field" trigger button — secondary outlined, regular weight */
body.post-type-product .wc-proto-add-trigger {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	height: 32px;
	padding: 0 12px;
	font-size: 13px;
	font-weight: var(--wpds-typography-font-weight-regular, 400);
	background: transparent;
	color: var(--wpds-color-fg-interactive-brand, #3858e9);
	border: 1px solid var(--wpds-color-fg-interactive-brand, #3858e9);
	border-radius: var(--wpds-border-radius-xs, 2px);
	cursor: pointer;
}
body.post-type-product .wc-proto-add-trigger:hover {
	background: var(--wpds-color-bg-interactive-brand-weak-active, #e8eaff);
}

/* Confirm CTA — secondary outlined */
body.post-type-product .wc-proto-confirm {
	display: inline-flex;
	align-items: center;
	height: 32px;
	padding: 0 12px;
	font-size: 13px;
	font-weight: var(--wpds-typography-font-weight-regular, 400);
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

/* Cancel — minimal/tertiary button, matches visibility metabox Cancel */
body.post-type-product .wc-proto-cancel {
	display: inline-flex;
	align-items: center;
	height: 32px;
	padding: 0 12px;
	font-size: 13px;
	font-weight: var(--wpds-typography-font-weight-regular, 400);
	background: none;
	color: var(--wpds-color-fg-interactive-brand, #3858e9);
	border: 1px solid transparent;
	border-radius: var(--wpds-border-radius-xs, 2px);
	box-shadow: none;
	text-shadow: none;
	text-decoration: none;
	cursor: pointer;
	margin: 0;
}
body.post-type-product .wc-proto-cancel:hover {
	background: var(--wpds-color-bg-interactive-brand-weak-active, #e8eaff);
	color: var(--wpds-color-fg-interactive-brand, #3858e9);
	text-decoration: none;
}

/* ── Helper text footer ───────────────────────────────── */
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

</style>
		<?php
	}

	/**
	 * Output JS: relocate WP's add-form controls into a <tfoot> trigger/entry/confirm UI,
	 * and toggle an empty-state class on #list-table so the column headers can hide.
	 */
	public static function output_scripts(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		?>
<script>
( function () {
	var list      = document.getElementById( 'the-list' );
	var listTable = document.getElementById( 'list-table' );
	if ( ! list || ! listTable ) { return; }

	/* ── Empty-state toggle: only count real meta rows (id="meta-XXX"). ── */
	function syncEmptyState() {
		var hasMeta = !! list.querySelector( 'tr[id^="meta-"]' );
		listTable.classList.toggle( 'wc-proto-empty', ! hasMeta );
	}
	syncEmptyState();
	new MutationObserver( syncEmptyState ).observe( list, { childList: true } );

	/* ── Inline add UI in <tfoot> ────────────────────────── */
	var newmetaSubmit = document.getElementById( 'newmeta-submit' );
	if ( ! newmetaSubmit ) { return; }

	var tfoot = document.createElement( 'tfoot' );
	tfoot.className = 'wc-proto-add';

	function makeRow( cls, inner ) {
		var tr = document.createElement( 'tr' );
		tr.className = cls;
		tr.innerHTML = inner;
		return tr;
	}

	var entryRow = makeRow( 'wc-proto-add-entry-row',
		'<td class="left wc-proto-add-name"></td>' +
		'<td class="wc-proto-add-value"></td>'
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
	tfoot.appendChild( entryRow );
	tfoot.appendChild( confirmRow );
	tfoot.appendChild( triggerRow );
	listTable.appendChild( tfoot );

	/* Move WP's REAL controls (select, text input, textarea) into our cells, but DROP
		WP's <a> "Enter new" / "Cancel" wrappers — we render our own clean links that we
		fully control, instead of fighting unknown classes/inline styles. */
	var nameCell    = entryRow.querySelector( '.wc-proto-add-name' );
	var valueCell   = entryRow.querySelector( '.wc-proto-add-value' );
	var newmetaLeft = document.getElementById( 'newmetaleft' );
	var metaSelect  = document.getElementById( 'metakeyselect' );
	var metaInput   = document.getElementById( 'metakeyinput' );
	var metaValue   = document.getElementById( 'metavalue' );

	if ( metaSelect && nameCell ) { nameCell.appendChild( metaSelect ); }
	if ( metaInput  && nameCell ) { nameCell.appendChild( metaInput  ); }
	if ( metaValue  && valueCell ) { valueCell.appendChild( metaValue ); }

	/* Inject our own toggle links (initial state: "Enter new" visible, "Cancel" hidden). */
	var enterLink  = document.createElement( 'a' );
	enterLink.href      = '#';
	enterLink.className = 'wc-proto-textlink wc-proto-enter-new';
	enterLink.textContent = 'Enter new';

	var cancelLink = document.createElement( 'a' );
	cancelLink.href      = '#';
	cancelLink.className = 'wc-proto-textlink wc-proto-cancel-new';
	cancelLink.textContent = 'Cancel';
	cancelLink.style.display = 'none';

	if ( nameCell ) {
		nameCell.appendChild( enterLink );
		nameCell.appendChild( cancelLink );
	}

	/* Initial state: hide the text-input variant. */
	if ( metaInput ) { metaInput.style.display = 'none'; }

	function showSelectMode() {
		if ( metaSelect ) { metaSelect.style.display = ''; }
		if ( metaInput )  { metaInput.style.display  = 'none'; }
		enterLink.style.display  = '';
		cancelLink.style.display = 'none';
		if ( metaSelect ) { metaSelect.focus(); }
	}
	function showInputMode() {
		if ( metaSelect ) { metaSelect.style.display = 'none'; }
		if ( metaInput )  { metaInput.style.display  = ''; }
		enterLink.style.display  = 'none';
		cancelLink.style.display = '';
		if ( metaInput ) { metaInput.focus(); }
	}
	enterLink.addEventListener(  'click', function ( e ) { e.preventDefault(); showInputMode();  } );
	cancelLink.addEventListener( 'click', function ( e ) { e.preventDefault(); showSelectMode(); } );

	function openAdder() {
		tfoot.classList.add( 'is-adding' );
		var first = nameCell.querySelector( 'select, input[type="text"]:not(.hide-if-js)' );
		if ( first ) { first.focus(); }
	}
	function closeAdder() {
		tfoot.classList.remove( 'is-adding' );
		if ( metaSelect ) { metaSelect.value = '#NONE#'; metaSelect.style.display = ''; }
		if ( metaInput  ) { metaInput.value  = '';        metaInput.style.display  = 'none'; }
		if ( metaValue  ) { metaValue.value  = ''; }
		enterLink.style.display  = '';
		cancelLink.style.display = 'none';
	}

	triggerRow.querySelector( '.wc-proto-add-trigger' ).addEventListener( 'click', openAdder );
	confirmRow.querySelector( '.wc-proto-cancel' ).addEventListener( 'click', closeAdder );

	/* Capture the original #newmeta cells so we can re-home the inputs at submit time.
		WP's wpList serialises inputs INSIDE #newmeta when reading add-meta payload — if we
		leave the inputs in our tfoot, nothing gets submitted. */
	var newmeta              = document.getElementById( 'newmeta' );
	var newmetaValueCell     = newmeta && newmeta.querySelector( 'tbody tr:first-child td:not(.left)' );
	confirmRow.querySelector( '.wc-proto-confirm' ).addEventListener( 'click', function () {
		var moved = [];

		/* Move ONLY the real WP inputs back into #newmeta so wpList serialises them.
			Skip our injected text links — they don't belong in WP's form payload. */
		[ metaSelect, metaInput ].forEach( function ( el ) {
			if ( el && newmetaLeft && el.parentNode !== newmetaLeft ) {
				moved.push( { node: el, prev: el.parentNode } );
				newmetaLeft.appendChild( el );
			}
		} );
		if ( newmetaValueCell && metaValue && metaValue.parentNode !== newmetaValueCell ) {
			moved.push( { node: metaValue, prev: valueCell } );
			newmetaValueCell.appendChild( metaValue );
		}

		newmetaSubmit.click();

		/* Restore the inputs to our entry row so the UI keeps working visually. */
		moved.forEach( function ( o ) {
			o.prev.appendChild( o.node );
		} );

		closeAdder();
	} );
}() );
</script>
		<?php
	}
}
