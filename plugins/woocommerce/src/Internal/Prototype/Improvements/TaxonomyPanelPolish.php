<?php
/**
 * TaxonomyPanelPolish prototype improvement class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\DevPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Modernises the taxonomy metabox UI on the classic product edit screen:
 * restyles the All/Most-Used tabs to match the WPDS minimal tab style,
 * and removes the bordered box around the checkbox list.
 */
class TaxonomyPanelPolish {

	const FLAG_KEY = 'taxonomy_panel_polish';

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
	 * Output inline CSS for taxonomy panel polish.
	 */
	public static function output_styles(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		?>
<style id="wc-proto-taxonomy-panel">
/* ── Tighten metabox top padding for taxonomy panels ── */
body.post-type-product #categorydiv .inside,
body.post-type-product #tagsdiv-product_tag .inside,
body.post-type-product .categorydiv,
body.post-type-product [id^="taxonomy-"] .inside {
	padding-top: 0 !important;
}

/* ── Category tabs: WPDS minimal style, tight spacing ──────────────────── */
body.post-type-product ul.category-tabs {
	display: flex;
	flex-direction: row;
	margin: 0 0 4px;
	padding: 0;
	border-bottom: 1px solid var(--wpds-color-border-neutral-subtle, #dcdcdc);
	list-style: none;
}
body.post-type-product ul.category-tabs li {
	float: none;
	background: none;
	border: none;
	margin: 0;
	padding: 0;
}
body.post-type-product ul.category-tabs li a {
	display: inline-block;
	padding: 4px 10px;
	font-size: var(--wpds-typography-font-size-sm, 12px);
	font-weight: var(--wpds-typography-font-weight-regular, 400);
	color: var(--wpds-color-fg-content-neutral-weak, #707070);
	text-decoration: none;
	border-bottom: 2px solid transparent;
	margin-bottom: -1px;
}
body.post-type-product ul.category-tabs li a:hover {
	color: var(--wpds-color-fg-content-neutral, #1e1e1e);
}
body.post-type-product ul.category-tabs li.tabs a {
	color: var(--wpds-color-fg-content-neutral, #1e1e1e);
	font-weight: var(--wpds-typography-font-weight-medium, 499);
	border-bottom: 2px solid var(--wpds-color-fg-content-neutral, #1e1e1e);
}

/* ── Reset h4 wrapper around "Add new" taxonomy link ─────── */
body.post-type-product .wp-hidden-children > h4 {
	font-size: var(--wpds-typography-font-size-md, 13px);
	font-weight: var(--wpds-typography-font-weight-regular, 400);
	line-height: 1.4;
	margin: 8px 0 0;
}

/* ── Remove bottom spacing after "Add new" link ─────────── */
body.post-type-product a.taxonomy-add-new {
	margin-bottom: 0;
}

/* ── Remove the bordered box around the checkbox list ────── */
body.post-type-product .tabs-panel {
	border: none;
	background: transparent;
	padding: 0;
	height: auto;
	min-height: 0;
	max-height: 200px;
	overflow-y: auto;
}
/* Replace the default UL top margin with a tighter 6px gap below the tabs */
body.post-type-product .tabs-panel > ul,
body.post-type-product ul.categorychecklist {
	margin: 6px 0 0;
	padding: 0;
}
body.post-type-product ul.categorychecklist > li {
	margin: 0;
}

/* ── Product tag chips: pill style with X on the right ──── */
body.post-type-product #tagsdiv-product_tag .tagchecklist {
	display: flex;
	flex-wrap: wrap;
	gap: 6px;
	margin: 14px 0 0;
	padding: 0;
	list-style: none;
}
body.post-type-product #tagsdiv-product_tag .tagchecklist li {
	display: inline-flex;
	align-items: center;
	flex-direction: row-reverse;
	gap: 4px;
	margin: 0;
	padding: 4px 6px 4px 10px;
	background: var(--wpds-color-bg-content-neutral-subtle, #f0f0f1);
	border-radius: 14px;
	font-size: var(--wpds-typography-font-size-sm, 12px);
	line-height: 1.2;
	color: var(--wpds-color-fg-content-neutral, #1e1e1e);
}
body.post-type-product #tagsdiv-product_tag .tagchecklist li > span:not(.screen-reader-text):not(.remove-tag-icon) {
	color: var(--wpds-color-fg-content-neutral, #1e1e1e);
	margin: 0;
	padding: 0;
}
body.post-type-product #tagsdiv-product_tag .tagchecklist li .ntdelbutton {
	background: transparent;
	border: none;
	padding: 0;
	margin: 0;
	width: 16px;
	height: 16px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	color: var(--wpds-color-fg-content-neutral-weak, #757575);
	position: static;
}
body.post-type-product #tagsdiv-product_tag .tagchecklist li .ntdelbutton:hover {
	color: var(--wpds-color-fg-content-error, #cc1818);
	background: transparent;
}
/* Hide every default X glyph (WP markup varies across versions — the X can be on
	.ntdelbutton::before OR on .remove-tag-icon::before with a negative margin that
	overlaps the tag text). We render a single clean X via .ntdelbutton::before below. */
body.post-type-product #tagsdiv-product_tag .tagchecklist .ntdelbutton .remove-tag-icon::before {
	content: none !important;
	display: none !important;
}
body.post-type-product #tagsdiv-product_tag .tagchecklist .ntdelbutton::before {
	content: "\f335" !important; /* dashicons-no-alt */
	font-family: dashicons !important;
	font-size: 16px !important;
	line-height: 1 !important;
	margin: 0 !important;
	padding: 0 !important;
	color: inherit !important;
	background: transparent !important;
	position: static !important;
	float: none !important;
	display: inline-block !important;
	width: auto !important;
	height: auto !important;
	-webkit-font-smoothing: antialiased;
}

/* ── Most-used tag cloud: pill chips (no border, no X, click-to-add) ── */
body.post-type-product #tagsdiv-product_tag .the-tagcloud {
	border: none !important;
	padding: 0 !important;
	margin: 8px 0 0 !important;
}
body.post-type-product #tagsdiv-product_tag .wp-tag-cloud {
	display: flex;
	flex-wrap: wrap;
	gap: 6px;
	margin: 0;
	padding: 0;
	list-style: none;
}
body.post-type-product #tagsdiv-product_tag .wp-tag-cloud li {
	margin: 0;
	padding: 0;
	display: inline-block;
}
body.post-type-product #tagsdiv-product_tag .wp-tag-cloud li a,
body.post-type-product #tagsdiv-product_tag .wp-tag-cloud li a.tag-cloud-link {
	display: inline-block;
	padding: 4px 10px;
	background: var(--wpds-color-bg-content-neutral-subtle, #f0f0f1);
	border-radius: 14px;
	font-size: var(--wpds-typography-font-size-sm, 12px);
	line-height: 1.2;
	color: var(--wpds-color-fg-content-neutral, #1e1e1e) !important;
	text-decoration: none !important;
	cursor: pointer;
}
body.post-type-product #tagsdiv-product_tag .wp-tag-cloud li a:hover {
	background: var(--wpds-color-bg-content-neutral, #e0e0e0);
	color: var(--wpds-color-fg-content-neutral, #1e1e1e) !important;
}

</style>
		<?php
	}

	/**
	 * Click-to-add shim for the product-tags most-used cloud — WP's native
	 * tagBox handler isn't firing reliably on this metabox, so we route the
	 * click through the existing "Add" button which we know works.
	 */
	public static function output_scripts(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		?>
<script>
( function () {
	document.addEventListener( 'click', function ( e ) {
		var link = e.target.closest( '#tagsdiv-product_tag .the-tagcloud a' );
		if ( ! link ) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();

		var wrapper = link.closest( '#tagsdiv-product_tag' );
		var name    = ( link.textContent || '' ).trim();
		var input   = wrapper && wrapper.querySelector( '.newtag' );
		var add     = wrapper && wrapper.querySelector( '.tagadd' );
		if ( ! name || ! input || ! add ) {
			return;
		}

		input.value = name;
		input.dispatchEvent( new Event( 'input',  { bubbles: true } ) );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		add.click();
		input.value = '';
	}, true );
}() );
</script>
		<?php
	}
}
