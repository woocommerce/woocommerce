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
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'admin_head', array( self::class, 'output_styles' ) );
		add_action( 'admin_footer', array( self::class, 'output_scripts' ) );
	}

	/**
	 * Enqueue flatpickr (bundled with WordPress 5.5+).
	 */
	public static function enqueue_assets(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		wp_enqueue_script( 'flatpickr' );
		wp_enqueue_style( 'flatpickr' );
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
#sticky-span {
	display: none !important;
}
/* Password span hidden by default; JS shows it when password option is chosen. */
#post-visibility-select #password-span {
	display: none;
	margin-top: 4px;
}
/* Dropdowns: full width. */
select#wc-proto-vis-select,
#post-status-select select {
	display: block;
	width: 100%;
	margin-bottom: 4px;
}

/* ── OK / Cancel: own row, right-aligned, compact 32px, 8px gap ── */
#post-visibility-select > p,
#post-status-select > p,
#timestampdiv > p {
	display: flex !important;
	width: 100% !important;
	box-sizing: border-box !important;
	align-items: center !important;
	justify-content: flex-end !important;
	gap: 8px !important;
	margin: 8px 0 0 !important;
}
/* OK: compact 32px — matches @wordpress/components Button compact height. */
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
}
/* Cancel: tertiary/minimal — 32px, no background. */
.cancel-post-visibility,
.cancel-post-status,
.cancel-timestamp {
	order: -1 !important;
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

/* ── Flatpickr date/time input (only when JS confirms flatpickr loaded) ── */
#wc-proto-datetime {
	display: block;
	width: 100%;
	box-sizing: border-box;
	margin-bottom: 4px;
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

	/* ── Flatpickr date/time picker ─────────────────────────── */
	function initFlatpickr() {
		var tsDiv = document.getElementById( 'timestampdiv' );
		if ( ! tsDiv || ! window.flatpickr ) { return; }

		var aa    = document.getElementById( 'aa' );
		var mm    = document.getElementById( 'mm' );
		var jj    = document.getElementById( 'jj' );
		var hh    = document.getElementById( 'hh' );
		var mn    = document.getElementById( 'mn' );
		var year  = aa ? parseInt( aa.value, 10 ) : new Date().getFullYear();
		var month = mm ? parseInt( mm.value, 10 ) - 1 : new Date().getMonth();
		var day   = jj ? parseInt( jj.value, 10 ) : new Date().getDate();
		var hour  = hh ? parseInt( hh.value, 10 ) : 0;
		var min   = mn ? parseInt( mn.value, 10 ) : 0;

		var fpInput = document.createElement( 'input' );
		fpInput.type = 'text';
		fpInput.id   = 'wc-proto-datetime';

		var tsWrap = tsDiv.querySelector( '.timestamp-wrap' );
		if ( tsWrap ) {
			tsDiv.insertBefore( fpInput, tsWrap );
			tsWrap.style.display = 'none';
		}

		flatpickr( fpInput, {
			enableTime:  true,
			dateFormat:  'F j, Y H:i',
			defaultDate: new Date( year, month, day, hour, min ),
			time_24hr:   true,
			onChange: function ( dates ) {
				if ( ! dates.length ) { return; }
				var d   = dates[ 0 ];
				var pad = function ( n ) { return String( n ).padStart( 2, '0' ); };
				if ( aa ) { aa.value = d.getFullYear(); }
				if ( mm ) { mm.value = pad( d.getMonth() + 1 ); }
				if ( jj ) { jj.value = pad( d.getDate() ); }
				if ( hh ) { hh.value = pad( d.getHours() ); }
				if ( mn ) { mn.value = pad( d.getMinutes() ); }
			},
		} );
	}

	if ( window.flatpickr ) {
		initFlatpickr();
	} else {
		/* Fallback: load flatpickr from CDN if WordPress didn't bundle it. */
		var fpCss = document.createElement( 'link' );
		fpCss.rel  = 'stylesheet';
		fpCss.href = 'https://cdn.jsdelivr.net/npm/flatpickr@4/dist/flatpickr.min.css';
		document.head.appendChild( fpCss );

		var fpJs = document.createElement( 'script' );
		fpJs.src = 'https://cdn.jsdelivr.net/npm/flatpickr@4/dist/flatpickr.min.js';
		fpJs.onload = initFlatpickr;
		document.head.appendChild( fpJs );
	}

	/* ── Hide display value while its panel is open ─────────── */
	function watchPanel( panelId, displayId ) {
		var panel   = document.getElementById( panelId );
		var display = document.getElementById( displayId );
		if ( ! panel || ! display ) { return; }
		new MutationObserver( function () {
			var open = 'none' !== window.getComputedStyle( panel ).display;
			display.style.display = open ? 'none' : '';
		} ).observe( panel, { attributes: true, attributeFilter: [ 'style' ] } );
	}
	watchPanel( 'post-visibility-select', 'post-visibility-display' );
	watchPanel( 'post-status-select',     'post-status-display' );
	watchPanel( 'timestampdiv',           'timestamp' );
}() );
</script>
		<?php
	}
}
