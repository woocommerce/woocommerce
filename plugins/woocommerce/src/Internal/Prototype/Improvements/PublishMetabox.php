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

/* OK / Cancel: side-by-side row. */
#post-visibility-select p,
#timestampdiv p {
	display: flex !important;
	align-items: center !important;
	gap: 8px !important;
	margin-top: 6px !important;
}
/* Cancel: minimal/tertiary — no background or border, just colored text. */
.cancel-post-visibility,
.cancel-timestamp {
	order: -1 !important;
	background: none !important;
	border: none !important;
	box-shadow: none !important;
	text-shadow: none !important;
	color: var(--wpds-color-fg-interactive-brand, #3858e9) !important;
	padding: 0 !important;
	height: auto !important;
	line-height: inherit !important;
	text-decoration: none !important;
	font-size: 13px !important;
	cursor: pointer !important;
}
.cancel-post-visibility:hover,
.cancel-timestamp:hover {
	background: none !important;
	color: var(--wpds-color-fg-interactive-brand, #3858e9) !important;
	text-decoration: underline !important;
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

	/* ── "Publish:" colon in all timestamp states ───────────── */
	var tsDisplay = document.getElementById( 'timestamp' );
	if ( tsDisplay ) {
		tsDisplay.innerHTML = tsDisplay.innerHTML.replace( /^Publish </, 'Publish: <' );
	}
}() );
</script>
		<?php
	}
}
