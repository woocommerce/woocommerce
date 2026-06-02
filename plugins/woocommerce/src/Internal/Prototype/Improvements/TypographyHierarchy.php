<?php
/**
 * TypographyHierarchy prototype improvement class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\DevPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Applies a three-tier typography hierarchy to the classic product edit screen
 * using WPDS tokens with hardcoded fallbacks.
 */
class TypographyHierarchy {

	const FLAG_KEY = 'typography_hierarchy';

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
	 * Output inline CSS for the typography hierarchy.
	 */
	public static function output_styles(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		?>
<style id="wc-proto-typo-styles">
/* ── Normalize inputs to 13px (WP admin sets 14px in forms.css) ── */
body.post-type-product input,
body.post-type-product select,
body.post-type-product textarea {
	font-size: var(--wpds-typography-font-size-md, 13px);
}

/* ── Tier 1: Metabox titles ─────────────────────────────── */
body.post-type-product #poststuff h2.hndle,
body.post-type-product #postdivrich h2.postbox-header label {
    font-size: var(--wpds-typography-font-size-md, 13px);
    font-weight: var(--wpds-typography-font-weight-medium, 499);
    color: var(--wpds-color-fg-content-neutral, #1e1e1e);
}

/* ── Tier 2: Field labels ───────────────────────────────── */
body.post-type-product .woocommerce_options_panel .form-field label:not(:has(input[type="checkbox"])):not(:has(input[type="radio"])),
body.post-type-product .woocommerce_options_panel fieldset.form-field legend {
    font-size: var(--wpds-typography-font-size-sm, 12px);
    font-weight: var(--wpds-typography-font-weight-regular, 400);
    color: var(--wpds-color-fg-content-neutral-weak, #707070);
}

/* ── Radio/checkbox option labels: restore WP default ───── */
body.post-type-product .woocommerce_options_panel .wc-radios label {
	font-size: inherit;
	font-weight: inherit;
	color: inherit;
}

/* ── Tier 3: Helper/description text ────────────────────── */
body.post-type-product .woocommerce_options_panel .form-field .description,
body.post-type-product p.howto {
    font-size: var(--wpds-typography-font-size-xs, 11px);
    font-weight: var(--wpds-typography-font-weight-regular, 400);
    color: var(--wpds-color-fg-content-neutral-weak, #707070);
}

/* ── Product data tab labels ────────────────────────────── */
body.post-type-product ul.product_data_tabs li a {
    font-size: var(--wpds-typography-font-size-md, 13px);
    font-weight: var(--wpds-typography-font-weight-regular, 400);
    color: var(--wpds-color-fg-content-neutral, #1e1e1e);
}

/* ── Attribute / variation sub-headers ──────────────────── */
body.post-type-product .woocommerce_attribute h3,
body.post-type-product .woocommerce_variation h3 {
    font-size: var(--wpds-typography-font-size-md, 13px);
    font-weight: var(--wpds-typography-font-weight-medium, 499);
    color: var(--wpds-color-fg-content-neutral, #1e1e1e);
}

/* ── Sidebar publish / status text ──────────────────────── */
body.post-type-product .misc-pub-section {
    font-size: var(--wpds-typography-font-size-md, 13px);
    font-weight: var(--wpds-typography-font-weight-regular, 400);
    color: var(--wpds-color-fg-content-neutral, #1e1e1e);
}

/* ── Tags howto — moved inside .jaxtag, sits tight below input ── */
body.post-type-product #tagsdiv-product_tag .jaxtag p.howto {
	margin-top: 2px;
	margin-bottom: 0;
}

/* ── Taxonomy tab links (All categories / Most Used) ────── */
body.post-type-product ul.category-tabs li a {
    font-size: var(--wpds-typography-font-size-sm, 12px);
    font-weight: var(--wpds-typography-font-weight-regular, 400);
    color: var(--wpds-color-fg-content-neutral, #1e1e1e);
}

/* ── Taxonomy checkbox labels ───────────────────────────── */
body.post-type-product .categorychecklist li label {
    font-size: var(--wpds-typography-font-size-sm, 12px);
    font-weight: var(--wpds-typography-font-weight-regular, 400);
    color: var(--wpds-color-fg-content-neutral-weak, #707070);
}

/* ── "Add new" taxonomy links ───────────────────────────── */
body.post-type-product a.taxonomy-add-new {
	font-weight: var(--wpds-typography-font-weight-regular, 400);
}

</style>
		<?php
	}

	/**
	 * Move the tags howto hint inside the input container so it reads as helper text.
	 */
	public static function output_scripts(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		?>
<script>
( function () {
	var howto  = document.querySelector( '#tagsdiv-product_tag p.howto' );
	var jaxtag = document.querySelector( '#tagsdiv-product_tag .jaxtag' );
	if ( howto && jaxtag ) {
		jaxtag.appendChild( howto );
	}
}() );
</script>
		<?php
	}
}
