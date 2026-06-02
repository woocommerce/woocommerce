<?php
/**
 * DownloadableFilesPolish prototype improvement class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\DevPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Strips the table chrome from the Downloadable files row,
 * swaps the legacy drag and delete glyphs for WPDS-style SVGs,
 * and aligns the column tooltips with their labels.
 */
class DownloadableFilesPolish {

	const FLAG_KEY = 'downloadable_files_polish';

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
	 * Build a mask-image data URL from an inline SVG path.
	 *
	 * @param string $path SVG path d="" value.
	 * @return string CSS url(...) value.
	 */
	private static function mask_url( string $path ): string {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="' . $path . '"/></svg>';
		return 'url("data:image/svg+xml;utf8,' . rawurlencode( $svg ) . '")';
	}

	/**
	 * Output inline CSS for the downloadable files polish.
	 */
	public static function output_styles(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}

		$drag_url  = self::mask_url( 'M8 7h2V5H8v2zm0 6h2v-2H8v2zm0 6h2v-2H8v2zm6-14v2h2V5h-2zm0 8h2v-2h-2v2zm0 6h2v-2h-2v2z' );
		$close_url = self::mask_url( 'M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z' );
		?>
<style id="wc-proto-downloadable-files">
/* ── Strip stripe/background chrome, keep outer border ──── */
body.post-type-product .downloadable_files table.widefat tr,
body.post-type-product .downloadable_files table.widefat th,
body.post-type-product .downloadable_files table.widefat td {
	background: transparent !important;
	box-shadow: none !important;
}

body.post-type-product .downloadable_files table.widefat {
	border: 1px solid var(--wpds-color-border-neutral-subtle, #dcdcde) !important;
	border-radius: 4px !important;
	border-collapse: separate !important;
	border-spacing: 0;
}

body.post-type-product .downloadable_files table.widefat th,
body.post-type-product .downloadable_files table.widefat td {
	border: none !important;
	padding: 8px 10px;
	vertical-align: middle;
}

/* Single thin divider below the header — only when rows exist */
body.post-type-product .downloadable_files table.widefat thead th {
	border-bottom: 1px solid var(--wpds-color-border-neutral-subtle, #dcdcde) !important;
}

/* Hide the column headers entirely when there are no files yet */
body.post-type-product .downloadable_files table.widefat:not(:has(tbody tr)) thead {
	display: none;
}

/* ── Column headers: vertically aligned label + tooltip ─── */
body.post-type-product .downloadable_files table.widefat thead th {
	font-size: var(--wpds-typography-font-size-sm, 12px);
	font-weight: var(--wpds-typography-font-weight-regular, 400);
	color: var(--wpds-color-fg-content-neutral-weak, #707070);
	text-align: left;
	line-height: 16px;
	vertical-align: middle;
}

body.post-type-product .downloadable_files table.widefat thead th .woocommerce-help-tip,
body.post-type-product .downloadable_files table.widefat thead th .help_tip {
	display: inline-block !important;
	vertical-align: middle !important;
	width: 14px !important;
	height: 14px !important;
	line-height: 14px !important;
	font-size: 14px !important;
	margin: 0 0 1px 4px !important;
}

/* ── Drag handle: absolutely centered SVG icon ──────────── */
body.post-type-product .downloadable_files td.sort {
	width: 24px !important;
	background: transparent !important;
	background-image: none !important;
	cursor: move;
	position: relative;
	text-indent: -9999px;
	overflow: hidden;
}
body.post-type-product .downloadable_files td.sort::before {
	content: '' !important;
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 20px;
	height: 20px;
	text-indent: 0;
	background-color: var(--wpds-color-fg-content-neutral-weak, #757575);
	-webkit-mask: <?php echo $drag_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> no-repeat center / contain;
	mask: <?php echo $drag_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> no-repeat center / contain;
}

/* ── Delete (X): absolutely centered SVG icon ───────────── */
body.post-type-product .downloadable_files td:has(> a.delete) {
	position: relative;
	width: 24px;
}
body.post-type-product .downloadable_files a.delete {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 16px;
	height: 16px;
	font-size: 0;
	color: transparent;
	text-decoration: none;
	background-color: var(--wpds-color-fg-content-neutral-weak, #757575);
	-webkit-mask: <?php echo $close_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> no-repeat center / contain;
	mask: <?php echo $close_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> no-repeat center / contain;
}
body.post-type-product .downloadable_files a.delete:hover {
	background-color: var(--wpds-color-fg-content-error, #cc1818);
}
</style>
		<?php
	}
}
