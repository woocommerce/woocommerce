<?php
/**
 * PublishMetabox prototype improvement class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\DevPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Redesigns the publish metabox: visibility dropdown, scheduling colon, OK/Cancel layout.
 * Activated via the 'publish_metabox' dev panel flag.
 */
class PublishMetabox {

	const FLAG_KEY = 'publish_metabox';

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
	 * Output CSS for the publish metabox redesign.
	 */
	public static function output_styles(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		?>
<style id="wc-proto-publish-metabox">
/* Hide radio inputs, their labels, sticky checkbox, and <br> spacers. */
#post-visibility-select input[type="radio"],
#post-visibility-select label.selectit,
#post-visibility-select br,
#catalog-visibility-select input[type="radio"],
#catalog-visibility-select label.selectit,
#catalog-visibility-select br,
#sticky-span {
	display: none !important;
}
/* Password span hidden by default; JS shows it when password option is chosen. */
#post-visibility-select #password-span {
	display: none;
	margin-top: 4px;
}
/* Dropdowns: full width with breathing room above. */
select#wc-proto-vis-select,
select#wc-proto-cat-vis-select,
#post-status-select select {
	display: block;
	width: 100%;
	margin-top: 8px;
	margin-bottom: 4px;
}
/* Catalog visibility helper text — small muted paragraph under the select */
#catalog-visibility-select > p:first-of-type {
	font-size: var(--wpds-typography-font-size-xs, 11px);
	color: var(--wpds-color-fg-content-neutral-weak, #707070);
	margin: 0 0 8px 0;
}
/* Featured checkbox row — sits between helper text and OK/Cancel */
#catalog-visibility-select label[for="_featured"] {
	display: inline-block;
	margin-left: 4px;
	font-size: var(--wpds-typography-font-size-md, 13px);
}

/* ── OK / Cancel: own row, left-aligned, compact 32px, 8px gap ── */
#post-visibility-select > p,
#post-status-select > p,
#catalog-visibility-select > p:last-of-type,
#timestampdiv > p {
	display: flex !important;
	width: 100% !important;
	box-sizing: border-box !important;
	align-items: center !important;
	justify-content: flex-start !important;
	gap: 8px !important;
	margin: 8px 0 0 !important;
}
/* Save: secondary outlined — matches @wordpress/components Button "secondary" variant. */
.save-post-visibility.button,
.save-post-status.button,
.save-timestamp.button {
	height: 32px !important;
	min-height: 0 !important;
	line-height: 30px !important;
	padding: 0 12px !important;
	font-size: 13px !important;
	margin: 0 !important;
	vertical-align: middle !important;
	background: transparent !important;
	color: var(--wpds-color-fg-interactive-brand, #3858e9) !important;
	border: 1px solid var(--wpds-color-fg-interactive-brand, #3858e9) !important;
	border-radius: var(--wpds-border-radius-xs, 2px) !important;
	box-shadow: none !important;
	text-shadow: none !important;
}
.save-post-visibility.button:hover,
.save-post-status.button:hover,
.save-timestamp.button:hover {
	background: var(--wpds-color-bg-interactive-brand-weak-active, #e8eaff) !important;
	color: var(--wpds-color-fg-interactive-brand, #3858e9) !important;
}
/* Cancel: tertiary/minimal — 32px, no background. */
.cancel-post-visibility,
.cancel-post-status,
.cancel-timestamp {
	display: inline-flex !important;
	align-items: center !important;
	height: 32px !important;
	padding: 0 12px !important;
	font-size: 13px !important;
	background: none !important;
	border: 1px solid transparent !important;
	border-radius: var(--wpds-border-radius-xs, 2px) !important;
	color: var(--wpds-color-fg-interactive-brand, #3858e9) !important;
	box-shadow: none !important;
	text-shadow: none !important;
	text-decoration: none !important;
	cursor: pointer !important;
	margin: 0 !important;
}
.cancel-post-visibility:hover,
.cancel-post-status:hover,
.cancel-timestamp:hover {
	background: var(--wpds-color-bg-interactive-brand-weak-active, #e8eaff) !important;
	color: var(--wpds-color-fg-interactive-brand, #3858e9) !important;
	text-decoration: none !important;
}

/* ── Native datetime-local input ── */
#wc-proto-datetime {
	display: block;
	width: 100%;
	box-sizing: border-box;
	margin-bottom: 4px;
	height: 32px;
	padding: 0 8px;
	font-size: 13px;
}
/* Left-align the date edit fields (browsers center by default in wide inputs). */
#wc-proto-datetime::-webkit-datetime-edit {
	text-align: left;
}
#wc-proto-datetime::-webkit-datetime-edit-fields-wrapper {
	justify-content: flex-start;
}
#wc-proto-datetime::-webkit-calendar-picker-indicator {
	margin-left: auto;
}

/* Hide label colon while edit panel is open. */
.misc-pub-section.wc-proto-editing .wc-proto-label-colon {
	display: none;
}
/* When timestamp panel is open, hide only the bold date value — keep the label. */
.misc-pub-section.wc-proto-editing #timestamp b {
	display: none !important;
}
</style>
		<?php
	}

	/**
	 * Output JS for the publish metabox redesign.
	 */
	public static function output_scripts(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		?>
<script>
( function () {
	/* ── Rename metabox heading "Publish" → "Visibility" ────── */
	var pubHeading = document.querySelector( '#submitdiv .hndle' );
	if ( pubHeading ) {
		for ( var i = 0; i < pubHeading.childNodes.length; i++ ) {
			var node = pubHeading.childNodes[ i ];
			if ( 3 === node.nodeType && /Publish/.test( node.textContent ) ) {
				node.textContent = node.textContent.replace( /Publish/g, 'Visibility' );
			}
		}
	}

	/* ── Relabel all "OK" buttons to "Confirm" (multiple panels share class names) ── */
	[ '.save-post-visibility', '.save-post-status', '.save-timestamp' ].forEach( function ( sel ) {
		document.querySelectorAll( sel ).forEach( function ( btn ) {
			btn.textContent = 'Confirm';
		} );
	} );

	/* ── Visibility dropdown ────────────────────────────────── */
	var visPanel = document.getElementById( 'post-visibility-select' );
	if ( visPanel ) {
		var checked = visPanel.querySelector( 'input[type="radio"]:checked' );
		var curVal  = checked ? checked.value : 'public';
		var sel     = document.createElement( 'select' );
		sel.id        = 'wc-proto-vis-select';
		[
			{ value: 'public',   label: 'Public' },
			{ value: 'password', label: 'Password protected' },
			{ value: 'private',  label: 'Private' },
		].forEach( function ( opt ) {
			var o         = document.createElement( 'option' );
			o.value       = opt.value;
			o.textContent = opt.label;
			if ( opt.value === curVal ) { o.selected = true; }
			sel.appendChild( o );
		} );
		sel.addEventListener( 'change', function () {
			var radio = document.getElementById( 'visibility-radio-' + sel.value );
			if ( radio ) { radio.checked = true; }
			var pwSpan = document.getElementById( 'password-span' );
			if ( pwSpan ) {
				pwSpan.style.display = ( 'password' === sel.value ) ? 'block' : 'none';
			}
		} );
		visPanel.insertBefore( sel, visPanel.firstChild );
	}

	/* ── "Published on:" / "Publish on:" → "Published:" / "Publish:" ── */
	var tsLabel = document.getElementById( 'timestamp' );
	if ( tsLabel ) {
		var tsNodes = tsLabel.childNodes;
		for ( var ti = 0; ti < tsNodes.length; ti++ ) {
			var tNode = tsNodes[ ti ];
			if ( 3 === tNode.nodeType ) {
				tNode.textContent = tNode.textContent.replace( /\b(Publish(?:ed)?) on\b/, '$1' );
			}
		}
	}

	/* ── Catalog visibility dropdown ─────────────────────────── */
	var catVisPanel = document.getElementById( 'catalog-visibility-select' );
	if ( catVisPanel ) {
		var catRadios = catVisPanel.querySelectorAll( 'input[type="radio"][name="_visibility"]' );
		var catChecked = catVisPanel.querySelector( 'input[type="radio"][name="_visibility"]:checked' );
		var catCurVal  = catChecked ? catChecked.value : 'visible';
		var catSel     = document.createElement( 'select' );
		catSel.id      = 'wc-proto-cat-vis-select';
		catRadios.forEach( function ( radio ) {
			var o         = document.createElement( 'option' );
			o.value       = radio.value;
			o.textContent = radio.dataset.label || radio.value;
			if ( radio.value === catCurVal ) { o.selected = true; }
			catSel.appendChild( o );
		} );
		catSel.addEventListener( 'change', function () {
			var r = document.getElementById( '_visibility_' + catSel.value );
			if ( r ) { r.checked = true; }
		} );
		catVisPanel.insertBefore( catSel, catVisPanel.firstChild );

		// Reorder: helper paragraph moves below the select (it's the first <p>).
		var helperP = catVisPanel.querySelector( 'p' );
		if ( helperP ) {
			catVisPanel.insertBefore( helperP, catSel.nextSibling );
		}
	}

	/* ── Wrap status OK/Cancel in <p> so flex row CSS applies ── */
	var statusDiv = document.getElementById( 'post-status-select' );
	if ( statusDiv ) {
		var saveBtn   = statusDiv.querySelector( '.save-post-status' );
		var cancelBtn = statusDiv.querySelector( '.cancel-post-status' );
		if ( saveBtn && cancelBtn && saveBtn.parentElement === statusDiv ) {
			var btnRow = document.createElement( 'p' );
			statusDiv.insertBefore( btnRow, saveBtn );
			btnRow.appendChild( saveBtn );
			btnRow.appendChild( cancelBtn );
		}
	}

	/* ── Native datetime-local input replaces WP's month/day/year/hour/minute fields ── */
	var tsDiv = document.getElementById( 'timestampdiv' );
	if ( tsDiv ) {
		var aa  = document.getElementById( 'aa' );
		var mm  = document.getElementById( 'mm' );
		var jj  = document.getElementById( 'jj' );
		var hh  = document.getElementById( 'hh' );
		var mn  = document.getElementById( 'mn' );
		var pad = function ( n ) { return String( n ).padStart( 2, '0' ); };
		var now = new Date();

		var dtInput = document.createElement( 'input' );
		dtInput.type = 'datetime-local';
		dtInput.id   = 'wc-proto-datetime';
		dtInput.value =
			( aa ? aa.value : now.getFullYear() ) + '-' +
			pad( mm ? mm.value : now.getMonth() + 1 ) + '-' +
			pad( jj ? jj.value : now.getDate() ) + 'T' +
			pad( hh ? hh.value : 0 ) + ':' +
			pad( mn ? mn.value : 0 );

		var tsWrap = tsDiv.querySelector( '.timestamp-wrap' );
		if ( tsWrap ) {
			tsDiv.insertBefore( dtInput, tsWrap );
			tsWrap.style.display = 'none';
		}

		dtInput.addEventListener( 'change', function () {
			var parts = dtInput.value.split( /[-T:]/ );
			if ( parts.length < 5 ) { return; }
			if ( aa ) { aa.value = parts[ 0 ]; }
			if ( mm ) { mm.value = parts[ 1 ]; }
			if ( jj ) { jj.value = parts[ 2 ]; }
			if ( hh ) { hh.value = parts[ 3 ]; }
			if ( mn ) { mn.value = parts[ 4 ]; }
		} );
	}

	/* ── Hide display value + colon while panel is open ────── */
	function wrapLabelColon( section ) {
		var nodes = section.childNodes;
		for ( var i = 0; i < nodes.length; i++ ) {
			var node = nodes[ i ];
			if ( 3 !== node.nodeType ) { continue; }
			var idx = node.textContent.indexOf( ':' );
			if ( -1 === idx ) { continue; }
			var before = document.createTextNode( node.textContent.slice( 0, idx ) );
			var colon  = document.createElement( 'span' );
			colon.className   = 'wc-proto-label-colon';
			colon.textContent = ':';
			var after = document.createTextNode( node.textContent.slice( idx + 1 ) );
			section.insertBefore( before, node );
			section.insertBefore( colon,  node );
			section.insertBefore( after,  node );
			section.removeChild( node );
			return;
		}
	}

	/* Status and visibility: MutationObserver on <div> works reliably. */
	function watchPanel( panelId, displayId ) {
		var panel   = document.getElementById( panelId );
		var display = document.getElementById( displayId );
		if ( ! panel ) { return; }
		var section = panel.closest ? panel.closest( '.misc-pub-section' ) : null;
		if ( section ) { wrapLabelColon( section ); }
		new MutationObserver( function () {
			var open = 'none' !== window.getComputedStyle( panel ).display;
			if ( display ) { display.style.display = open ? 'none' : ''; }
			if ( section ) { section.classList.toggle( 'wc-proto-editing', open ); }
		} ).observe( panel, { attributes: true, attributeFilter: [ 'style' ] } );
	}
	watchPanel( 'post-visibility-select',    'post-visibility-display' );
	watchPanel( 'post-status-select',        'post-status-display' );
	watchPanel( 'catalog-visibility-select', 'catalog-visibility-display' );

	/* Timestamp: use click handlers — <fieldset> style mutations are unreliable. */
	var tsDisplay   = document.getElementById( 'timestamp' );
	var tsSection   = tsDisplay ? ( tsDisplay.closest ? tsDisplay.closest( '.misc-pub-section' ) : null ) : null;
	var tsEditBtn   = document.querySelector( '.edit-timestamp' );
	var tsCancelBtn = document.querySelector( '.cancel-timestamp' );
	var tsOkBtn     = document.querySelector( '.save-timestamp' );
	/* Wrap the colon inside #timestamp so CSS can hide it when editing — label stays visible. */
	if ( tsDisplay ) { wrapLabelColon( tsDisplay ); }
	function tsSetEditing( open ) {
		if ( tsSection ) { tsSection.classList.toggle( 'wc-proto-editing', open ); }
	}
	if ( tsEditBtn )   { tsEditBtn.addEventListener( 'click',   function () { tsSetEditing( true ); } ); }
	if ( tsCancelBtn ) { tsCancelBtn.addEventListener( 'click', function () { tsSetEditing( false ); } ); }
	if ( tsOkBtn )     { tsOkBtn.addEventListener( 'click',     function () { tsSetEditing( false ); } ); }

	/* WP may auto-open the timestamp panel after our script runs — sync state on load + observe changes. */
	var tsDivEl = document.getElementById( 'timestampdiv' );
	if ( tsDivEl ) {
		function syncTsState() {
			tsSetEditing( 'none' !== window.getComputedStyle( tsDivEl ).display );
		}
		new MutationObserver( syncTsState ).observe( tsDivEl, {
			attributes: true,
			attributeFilter: [ 'style', 'class' ],
		} );
		window.addEventListener( 'load', syncTsState );
		syncTsState();
	}
}() );
</script>
		<?php
	}
}
