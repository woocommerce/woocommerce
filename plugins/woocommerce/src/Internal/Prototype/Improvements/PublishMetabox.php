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
#sticky-span {
	display: none !important;
}
/* Password span hidden by default; JS shows it when password option is chosen. */
#post-visibility-select #password-span {
	display: none;
	margin-top: 4px;
}
/* Injected visibility dropdown. */
select#wc-proto-vis-select {
	display: block;
	width: 100%;
	margin-bottom: 4px;
}

/* Ensure panel containers are block so the select and OK/Cancel row stack vertically. */
#post-visibility-select,
#post-status-select {
	display: block !important;
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
/* Cancel: tertiary/minimal — 32px, no background. Matches Preview changes style. */
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

/* Hide the label colon while the edit panel is open. */
.misc-pub-section.wc-proto-editing .wc-proto-label-colon {
	display: none;
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

	/* ── "Publish:" colon for draft/immediate state ─────────── */
	var tsDisplay = document.getElementById( 'timestamp' );
	if ( tsDisplay ) {
		tsDisplay.innerHTML = tsDisplay.innerHTML.replace( /^Publish </, 'Publish: <' );
	}

	/* ── Watch edit panels: hide repeated label text and colon ── */
	function wrapLabelColon( section ) {
		var nodes = section.childNodes;
		for ( var i = 0; i < nodes.length; i++ ) {
			var node = nodes[ i ];
			if ( 3 !== node.nodeType ) { continue; }
			var colonIdx = node.textContent.indexOf( ':' );
			if ( -1 === colonIdx ) { continue; }
			var before = document.createTextNode( node.textContent.slice( 0, colonIdx ) );
			var colon  = document.createElement( 'span' );
			colon.className = 'wc-proto-label-colon';
			colon.textContent = ':';
			var after  = document.createTextNode( node.textContent.slice( colonIdx + 1 ) );
			section.insertBefore( before, node );
			section.insertBefore( colon,  node );
			section.insertBefore( after,  node );
			section.removeChild( node );
			return;
		}
	}

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
	watchPanel( 'post-visibility-select', 'post-visibility-display' );
	watchPanel( 'post-status-select',     'post-status-display' );
	watchPanel( 'timestampdiv',           'timestamp' );
}() );
</script>
		<?php
	}
}
