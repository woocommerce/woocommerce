<?php
/**
 * SavePublishClarity prototype improvement class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\DevPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a fixed action bar (Back to products, Preview, Save draft, Update/Publish, kebab menu)
 * below the WordPress and WooCommerce admin headers. Strips the Publish metabox down to
 * status and visibility fields only.
 */
class SavePublishClarity {

	const FLAG_KEY   = 'save_publish_clarity';
	const HEADER_H   = 46; // px — height of our action bar.
	const ADMINBAR_H = 32; // px — standard WP admin bar.
	const WC_HDR_H   = 33; // px — .woocommerce-layout__header.

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
		add_action( 'admin_footer', array( self::class, 'output_header_html' ) );
	}

	/**
	 * Output inline CSS for the action bar and the publish metabox cleanup.
	 */
	public static function output_styles(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		$top   = self::ADMINBAR_H + self::WC_HDR_H;
		$bar_h = self::HEADER_H;
		?>
<style id="wc-proto-spc-styles">
/* ── Action bar ─────────────────────────────────────────── */
#wc-proto-save-header {
	position: fixed;
	top: <?php echo esc_attr( $top ); ?>px;
	right: 0;
	left: 160px; /* WP sidebar normal */
	z-index: 9998;
	background: #1d2327;
	padding: 0 16px;
	height: <?php echo esc_attr( $bar_h ); ?>px;
	display: flex;
	justify-content: space-between;
	align-items: center;
	box-sizing: border-box;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
body.folded #wc-proto-save-header {
	left: 36px; /* WP sidebar collapsed */
}

/* ── Back link ──────────────────────────────────────────── */
.wc-proto-back {
	color: #a7aaad;
	text-decoration: none;
	font-size: 13px;
}
.wc-proto-back:hover { color: #fff; }

/* ── Button group ───────────────────────────────────────── */
.wc-proto-actions {
	display: flex;
	gap: 8px;
	align-items: center;
	position: relative;
}
.wc-proto-btn {
	font-size: 13px;
	line-height: 1;
	border-radius: 3px;
	padding: 6px 12px;
	cursor: pointer;
	border: 1px solid #50575e;
	background: transparent;
	color: #a7aaad;
	text-decoration: none;
	white-space: nowrap;
}
.wc-proto-btn:hover { color: #fff; border-color: #a7aaad; }
.wc-proto-btn-primary {
	background: #2271b1;
	color: #fff;
	border-color: #2271b1;
	font-weight: 600;
}
.wc-proto-btn-primary:hover { background: #135e96; border-color: #135e96; color: #fff; }
.wc-proto-btn-kebab {
	padding: 6px 9px;
	font-size: 16px;
	letter-spacing: 1px;
}

/* ── Kebab dropdown ─────────────────────────────────────── */
#wc-proto-kebab-menu {
	display: none;
	position: absolute;
	top: calc(100% + 4px);
	right: 0;
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
	box-shadow: 0 2px 6px rgba(0,0,0,0.18);
	min-width: 190px;
	z-index: 9999;
}
#wc-proto-kebab-menu.is-open { display: block; }
.wc-proto-kebab-item {
	display: block;
	padding: 9px 14px;
	font-size: 13px;
	color: #1d2327;
	text-decoration: none;
}
.wc-proto-kebab-item:hover { background: #f0f0f1; color: #1d2327; }
.wc-proto-kebab-item.is-destructive { color: #b32d2e; }
.wc-proto-kebab-item.is-destructive:hover { background: #f0f0f1; }

/* ── Hide publish metabox action areas ──────────────────── */
#minor-publishing-actions,
#major-publishing-actions { display: none !important; }

/* Hide "Copy to a new draft" in the misc-pub area */
#submitdiv #misc-publishing-actions .misc-pub-section:last-child,
#submitdiv .misc-pub-copy-draft { display: none !important; }

/* Add space so content is not hidden under the fixed bar */
body.product-php #wpbody-content > .wrap { padding-top: <?php echo esc_attr( $bar_h ); ?>px; }
</style>
		<?php
	}

	/**
	 * Output the action bar HTML and inline JS.
	 */
	public static function output_header_html(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		// HTML/JS implemented in Task 4.
	}
}
