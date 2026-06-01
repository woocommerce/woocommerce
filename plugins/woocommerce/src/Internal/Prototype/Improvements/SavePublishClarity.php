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
	top: <?php echo esc_attr( (string) $top ); ?>px;
	right: 0;
	left: 160px; /* WP sidebar normal */
	z-index: 9998;
	background: #1d2327;
	padding: 0 16px;
	height: <?php echo esc_attr( (string) $bar_h ); ?>px;
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
body.product-php #wpbody-content > .wrap { padding-top: <?php echo esc_attr( (string) $bar_h ); ?>px; }
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
		global $post;
		if ( ! $post ) {
			return;
		}

		$products_url  = admin_url( 'edit.php?post_type=product' );
		$preview_url   = get_preview_post_link( $post );
		$is_published  = in_array( $post->post_status, array( 'publish', 'future' ), true );
		$primary_label = $is_published ? __( 'Update', 'woocommerce' ) : __( 'Publish', 'woocommerce' );

		// Trash URL — only for existing (saved) posts.
		$trash_url = ( $post->ID && 'auto-draft' !== $post->post_status )
			? get_delete_post_link( $post->ID )
			: '';
		?>
<div id="wc-proto-save-header" data-visibility-label="<?php echo esc_attr__( 'Visibility', 'woocommerce' ); ?>">
	<a href="<?php echo esc_url( $products_url ); ?>" class="wc-proto-back">
		&larr; <?php esc_html_e( 'Back to products', 'woocommerce' ); ?>
	</a>
	<div class="wc-proto-actions">
		<?php if ( $preview_url ) : ?>
		<a href="<?php echo esc_url( $preview_url ); ?>" class="wc-proto-btn" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Preview', 'woocommerce' ); ?>
		</a>
		<?php endif; ?>
		<button type="button" id="wc-proto-btn-save-draft" class="wc-proto-btn">
			<?php esc_html_e( 'Save draft', 'woocommerce' ); ?>
		</button>
		<button type="button" id="wc-proto-btn-publish" class="wc-proto-btn wc-proto-btn-primary">
			<?php echo esc_html( $primary_label ); ?>
		</button>
		<div style="position:relative">
			<button type="button" id="wc-proto-kebab-toggle" class="wc-proto-btn wc-proto-btn-kebab" aria-label="<?php esc_attr_e( 'More actions', 'woocommerce' ); ?>" aria-haspopup="true" aria-expanded="false">
				&bull;&bull;&bull;
			</button>
			<div id="wc-proto-kebab-menu" role="menu">
				<a id="wc-proto-copy-draft" href="#" class="wc-proto-kebab-item" role="menuitem" style="display:none">
					<?php esc_html_e( 'Copy to a new draft', 'woocommerce' ); ?>
				</a>
				<?php if ( $trash_url ) : ?>
				<a href="<?php echo esc_url( $trash_url ); ?>" class="wc-proto-kebab-item is-destructive" role="menuitem"
				   onclick="return confirm('<?php echo esc_js( __( 'Move this product to the trash?', 'woocommerce' ) ); ?>')">
					<?php esc_html_e( 'Move to Trash', 'woocommerce' ); ?>
				</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<script>
( function () {
	/* ── Button delegation ─────────────────────────────────── */
	// Delegate to hidden WP form buttons so their nonce/submit logic is unchanged.
	var btnSaveDraft = document.getElementById( 'wc-proto-btn-save-draft' );
	var btnPublish   = document.getElementById( 'wc-proto-btn-publish' );
	var wpSave       = document.getElementById( 'save-post' );    // "Save Draft" in WP form.
	var wpPublish    = document.getElementById( 'publish' );      // "Update" / "Publish" in WP form.

	if ( btnSaveDraft && wpSave ) {
		btnSaveDraft.addEventListener( 'click', function () { wpSave.click(); } );
	}
	if ( btnPublish && wpPublish ) {
		btnPublish.addEventListener( 'click', function () { wpPublish.click(); } );
	}

	/* ── Kebab menu ────────────────────────────────────────── */
	var kebabToggle = document.getElementById( 'wc-proto-kebab-toggle' );
	var kebabMenu   = document.getElementById( 'wc-proto-kebab-menu' );

	if ( kebabToggle && kebabMenu ) {
		var closeKebab = function () {
			kebabMenu.classList.remove( 'is-open' );
			kebabToggle.setAttribute( 'aria-expanded', 'false' );
		};

		kebabToggle.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			var isOpen = kebabMenu.classList.toggle( 'is-open' );
			kebabToggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		} );

		document.addEventListener( 'click', closeKebab );
	}

	/* ── Wire "Copy to a new draft" URL from hidden metabox link ── */
	// WooCommerce renders this link inside #submitdiv; we pull its href at runtime.
	var origCopyLink  = document.querySelector( '#submitdiv .misc-pub-copy-draft a, #submitdiv a[href*="wc-product-duplicate"], #submitdiv a[href*="duplicate"]' );
	var protoCopyLink = document.getElementById( 'wc-proto-copy-draft' );
	if ( origCopyLink && protoCopyLink ) {
		protoCopyLink.href = origCopyLink.href;
		protoCopyLink.style.display = '';
	}

	/* ── Rename "Publish" metabox title to "Visibility" ────── */
	var headerEl     = document.getElementById( 'wc-proto-save-header' );
	var metaboxTitle = document.querySelector( '#submitdiv .hndle span' );
	if ( headerEl && metaboxTitle ) {
		metaboxTitle.textContent = headerEl.dataset.visibilityLabel || 'Visibility';
	}
}() );
</script>
		<?php
	}
}
