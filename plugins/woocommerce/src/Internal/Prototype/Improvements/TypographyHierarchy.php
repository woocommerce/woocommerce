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
/* ── Tier 1: Metabox titles ─────────────────────────────── */
body.post-type-product #poststuff h2.hndle {
    font-size: var(--wpds-typography-font-size-md, 13px);
    font-weight: var(--wpds-typography-font-weight-medium, 499);
    color: var(--wpds-color-fg-content-neutral, #1e1e1e);
}

/* ── Tier 2: Field labels ───────────────────────────────── */
body.post-type-product .woocommerce_options_panel .form-field label,
body.post-type-product .woocommerce_options_panel fieldset.form-field legend {
    font-size: var(--wpds-typography-font-size-sm, 12px);
    font-weight: var(--wpds-typography-font-weight-regular, 400);
    color: var(--wpds-color-fg-content-neutral-weak, #707070);
}

/* ── Tier 3: Helper/description text ────────────────────── */
body.post-type-product .woocommerce_options_panel .form-field .description {
    font-size: var(--wpds-typography-font-size-xs, 11px);
    font-weight: var(--wpds-typography-font-weight-regular, 400);
    color: var(--wpds-color-fg-content-neutral-weak, #707070);
}

/* ── Product data tab labels ────────────────────────────── */
body.post-type-product ul.product_data_tabs li a {
    font-size: var(--wpds-typography-font-size-sm, 12px);
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
    font-size: var(--wpds-typography-font-size-sm, 12px);
    font-weight: var(--wpds-typography-font-weight-regular, 400);
    color: var(--wpds-color-fg-content-neutral-weak, #707070);
}

/* ── Taxonomy checkbox labels ───────────────────────────── */
body.post-type-product .categorychecklist li label {
    font-size: var(--wpds-typography-font-size-sm, 12px);
    font-weight: var(--wpds-typography-font-weight-regular, 400);
    color: var(--wpds-color-fg-content-neutral-weak, #707070);
}
</style>
		<?php
	}
}
