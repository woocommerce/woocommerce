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
/* Hide native radio buttons and labels; show only the injected select. */
#post-visibility-select .post-visibility-choice {
	display: none !important;
}
#post-visibility-select #password-span {
	display: none;
	margin-top: 4px;
}
select#wc-proto-vis-select {
	display: block;
	width: 100%;
	margin-bottom: 4px;
}

/* OK / Cancel: right-aligned flex row, Cancel left of OK. */
.misc-pub-section .save-post-visibility,
.misc-pub-section .save-timestamp {
	display: flex !important;
	justify-content: flex-end !important;
	gap: 4px !important;
	margin-top: 6px !important;
}
.misc-pub-section .save-post-visibility a.cancel-post-visibility,
.misc-pub-section .save-timestamp a.cancel-timestamp {
	order: 1 !important;
	margin-right: auto !important;
}
.misc-pub-section .save-post-visibility a.save-post-visibility,
.misc-pub-section .save-timestamp a.save-timestamp {
	order: 2 !important;
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
