<?php
/**
 * CustomFieldsPolish prototype improvement class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\DevPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Strips the table chrome from the Custom Fields metabox (#postcustom):
 * removes alternating-row backgrounds and inner borders on #list-table and #newmeta,
 * gives both tables a single subtle outer border, modernises the Delete/Update/Enter new
 * controls, and tightens helper-text spacing.
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
	}

	/**
	 * Output inline CSS for the custom fields polish.
	 */
	public static function output_styles(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		?>
<style id="wc-proto-custom-fields">
/* ── Strip stripe/background chrome on both tables, keep outer border ─ */
body.post-type-product #postcustomstuff table,
body.post-type-product #postcustomstuff table tr,
body.post-type-product #postcustomstuff table th,
body.post-type-product #postcustomstuff table td {
	background: transparent !important;
	box-shadow: none !important;
}

body.post-type-product #postcustomstuff #list-table,
body.post-type-product #postcustomstuff #newmeta {
	border: 1px solid var(--wpds-color-border-neutral-subtle, #dcdcde) !important;
	border-radius: 4px !important;
	border-collapse: separate !important;
	border-spacing: 0;
	margin: 0 0 8px;
}

body.post-type-product #postcustomstuff table th,
body.post-type-product #postcustomstuff table td {
	border: none !important;
	padding: 8px 10px;
	vertical-align: top;
}

/* Single thin divider below the header row */
body.post-type-product #postcustomstuff table thead th {
	border-bottom: 1px solid var(--wpds-color-border-neutral-subtle, #dcdcde) !important;
	font-size: var(--wpds-typography-font-size-sm, 12px);
	font-weight: var(--wpds-typography-font-weight-regular, 400);
	color: var(--wpds-color-fg-content-neutral-weak, #707070);
	text-align: left;
	line-height: 16px;
}

/* Hide the empty-state column headers on #list-table when no meta yet */
body.post-type-product #postcustomstuff #list-table:not(:has(#the-list tr)) thead {
	display: none;
}

/* ── Existing meta rows — compact inputs/textarea ─────────── */
body.post-type-product #postcustomstuff #list-table input[type="text"],
body.post-type-product #postcustomstuff #newmeta input[type="text"],
body.post-type-product #postcustomstuff #newmeta select {
	width: 100%;
	box-sizing: border-box;
}

body.post-type-product #postcustomstuff textarea {
	width: 100%;
	box-sizing: border-box;
	min-height: 60px;
	font-size: 13px;
}

/* ── Delete / Update / Enter new actions: text-link buttons ─ */
body.post-type-product #postcustomstuff .submit,
body.post-type-product #postcustomstuff .updatemeta,
body.post-type-product #postcustomstuff .deletemeta,
body.post-type-product #postcustomstuff #enternew,
body.post-type-product #postcustomstuff #cancel {
	display: inline-flex !important;
	align-items: center !important;
	height: 28px !important;
	min-height: 0 !important;
	line-height: 26px !important;
	padding: 0 8px !important;
	margin: 4px 4px 0 0 !important;
	font-size: 12px !important;
	background: none !important;
	border: 1px solid transparent !important;
	border-radius: var(--wpds-border-radius-xs, 2px) !important;
	color: var(--wpds-color-fg-interactive-brand, #3858e9) !important;
	box-shadow: none !important;
	text-shadow: none !important;
	text-decoration: none !important;
	cursor: pointer !important;
}

body.post-type-product #postcustomstuff .deletemeta {
	color: var(--wpds-color-fg-content-error, #b32d2e) !important;
}

body.post-type-product #postcustomstuff .submit:hover,
body.post-type-product #postcustomstuff .updatemeta:hover,
body.post-type-product #postcustomstuff #enternew:hover,
body.post-type-product #postcustomstuff #cancel:hover {
	background: var(--wpds-color-bg-interactive-brand-weak-active, #e8eaff) !important;
}

body.post-type-product #postcustomstuff .deletemeta:hover {
	background: var(--wpds-color-bg-interactive-error-weak-active, #fcf0f1) !important;
}

/* "Add Custom Field" primary submit — keep visually distinct */
body.post-type-product #postcustomstuff #newmeta-submit {
	height: 32px !important;
	line-height: 30px !important;
	padding: 0 12px !important;
	font-size: 13px !important;
	margin: 8px 0 0 !important;
	background: transparent !important;
	color: var(--wpds-color-fg-interactive-brand, #3858e9) !important;
	border: 1px solid var(--wpds-color-fg-interactive-brand, #3858e9) !important;
	border-radius: var(--wpds-border-radius-xs, 2px) !important;
	box-shadow: none !important;
	text-shadow: none !important;
	cursor: pointer !important;
}

body.post-type-product #postcustomstuff #newmeta-submit:hover {
	background: var(--wpds-color-bg-interactive-brand-weak-active, #e8eaff) !important;
}

/* ── "Add Custom Field:" label — tighter spacing ────────── */
body.post-type-product #postcustomstuff p.label-required,
body.post-type-product #postcustomstuff > p:first-of-type {
	margin: 16px 0 8px;
	font-weight: var(--wpds-typography-font-weight-medium, 499);
	font-size: var(--wpds-typography-font-size-md, 13px);
	color: var(--wpds-color-fg-content-neutral, #1e1e1e);
}

/* ── Helper text footer ─────────────────────────────────── */
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
}
