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
/* ── Category tabs: WPDS minimal style ──────────────────── */
body.post-type-product ul.category-tabs {
	display: flex;
	flex-direction: row;
	margin: 0 0 8px;
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
	padding: 6px 12px;
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

</style>
		<?php
	}
}
