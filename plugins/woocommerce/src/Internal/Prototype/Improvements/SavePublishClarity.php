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

	const FLAG_KEY = 'save_publish_clarity';
	const HEADER_H = 46;
	// px — height of our action bar.
	const ADMINBAR_H = 32;
	// px — standard WP admin bar.
	const WC_HDR_H = 33;
	// px — .woocommerce-layout__header.

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
	left: 160px;
	z-index: 9998;
	background: #f0f0f1;
	border-bottom: 1px solid #c3c4c7;
	height: <?php echo esc_attr( (string) $bar_h ); ?>px;
	display: flex;
	align-items: center;
	box-sizing: border-box;
}
body.folded #wc-proto-save-header {
	left: 36px;
}

/* Inner container aligns to the same max-width as page content */
.wc-proto-inner {
	max-width: 1200px;
	margin: 0 auto;
	width: 100%;
	display: flex;
	justify-content: space-between;
	align-items: center;
	box-sizing: border-box;
}

/* ── Back link ──────────────────────────────────────────── */
.wc-proto-back {
	color: #2c3338;
	text-decoration: none;
	font-size: 13px;
	display: flex;
	align-items: center;
	gap: 2px;
}
.wc-proto-back:hover { color: #2271b1; text-decoration: none; }
.wc-proto-back svg {
	fill: currentColor;
	flex: 0 0 24px;
}

/* ── Button group ───────────────────────────────────────── */
.wc-proto-actions {
	display: flex;
	gap: 4px;
	align-items: center;
	position: relative;
}

/* Reset WP button margins inside the header */
#wc-proto-save-header .button,
#wc-proto-save-header .button-link {
	margin: 0;
	vertical-align: middle;
}

/* ── Kebab toggle (details/summary) ────────────────────── */
.wc-proto-kebab {
	position: relative;
}
.wc-proto-kebab summary {
	list-style: none;
	cursor: pointer;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 30px;
	height: 30px;
	border-radius: 4px;
	background: transparent;
	border: 1px solid transparent;
}
.wc-proto-kebab summary::-webkit-details-marker { display: none; }
.wc-proto-kebab summary svg {
	display: block;
	width: 24px;
	height: 24px;
	fill: #1e1e1e;
}
.wc-proto-kebab summary:hover,
.wc-proto-kebab summary:focus {
	background: #dcdcde;
	border-color: #c3c4c7;
	outline: none;
}
.wc-proto-kebab[open] summary {
	background: #dcdcde;
	border-color: #c3c4c7;
}

/* ── Kebab dropdown ─────────────────────────────────────── */
.wc-proto-kebab-list {
	position: absolute;
	top: calc(100% + 4px);
	right: 0;
	margin: 0;
	padding: 4px 0;
	list-style: none;
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 4px;
	box-shadow: 0 2px 6px rgba(0,0,0,0.18);
	min-width: 190px;
	z-index: 9999;
}
.wc-proto-kebab-item {
	display: block;
	padding: 9px 14px;
	font-size: 13px;
	color: #1d2327;
	text-decoration: none;
	cursor: pointer;
	width: 100%;
	text-align: left;
	background: none;
	border: none;
	box-sizing: border-box;
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

/* Push all wrap content (including page heading) below the fixed bar, with 8px breathing room */
body.product-php #wpbody-content > .wrap { padding-top: <?php echo esc_attr( (string) ( $bar_h + 8 ) ); ?>px; }
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
	<div class="wc-proto-inner">
		<a href="<?php echo esc_url( $products_url ); ?>" class="wc-proto-back">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m14.6 7-1.2-1L8 12l5.4 6 1.2-1-4.6-5z"></path></svg>
			<?php esc_html_e( 'Back to products', 'woocommerce' ); ?>
		</a>
		<div class="wc-proto-actions">
			<?php if ( $preview_url ) : ?>
			<button type="button" id="wc-proto-btn-preview" class="button-link" onclick="window.open( '<?php echo esc_js( $preview_url ); ?>', '_blank' )">
				<?php esc_html_e( 'Preview', 'woocommerce' ); ?>
			</button>
			<?php endif; ?>
			<button type="button" id="wc-proto-btn-save-draft" class="button button-small">
				<?php esc_html_e( 'Save draft', 'woocommerce' ); ?>
			</button>
			<button type="button" id="wc-proto-btn-publish" class="button button-primary button-small">
				<?php echo esc_html( $primary_label ); ?>
			</button>
			<details class="wc-proto-kebab">
				<summary aria-label="<?php esc_attr_e( 'More actions', 'woocommerce' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M13 19h-2v-2h2v2zm0-6h-2v-2h2v2zm0-6h-2V5h2v2z"></path></svg>
				</summary>
				<ul class="wc-proto-kebab-list">
					<li><a id="wc-proto-copy-draft" href="#" class="wc-proto-kebab-item" style="display:none">
						<?php esc_html_e( 'Copy to a new draft', 'woocommerce' ); ?>
					</a></li>
					<?php if ( $trash_url ) : ?>
					<li><a href="<?php echo esc_url( $trash_url ); ?>" class="wc-proto-kebab-item is-destructive"
						onclick="return confirm('<?php echo esc_js( __( 'Move this product to the trash?', 'woocommerce' ) ); ?>')">
						<?php esc_html_e( 'Move to Trash', 'woocommerce' ); ?>
					</a></li>
					<?php endif; ?>
				</ul>
			</details>
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

	/* ── Kebab menu (details/summary — close on outside click or Escape) ── */
	document.addEventListener( 'click', function ( e ) {
		var openMenus = document.querySelectorAll( '.wc-proto-kebab[open]' );
		if ( ! openMenus.length ) { return; }
		openMenus.forEach( function ( menu ) {
			if ( ! ( e.target instanceof Node ) || ! menu.contains( e.target ) ) {
				menu.removeAttribute( 'open' );
			}
		} );
	}, true );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' || e.key === 'Esc' ) {
			document.querySelectorAll( '.wc-proto-kebab[open]' ).forEach( function ( menu ) {
				menu.removeAttribute( 'open' );
			} );
		}
	} );

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

	/* ── Dirty-state: disable primary button until changes are made ── */
	var isNewPost = '<?php echo esc_js( $post->post_status ); ?>' === 'auto-draft';
	var form      = document.getElementById( 'post' );

	if ( btnPublish && ! isNewPost ) {
		btnPublish.disabled = true;

		var markDirty = function () {
			btnPublish.disabled = false;
		};

		if ( form ) {
			form.addEventListener( 'input', markDirty );
			form.addEventListener( 'change', markDirty );
		}

		// TinyMCE doesn't bubble events to the form — hook in via WP's init event.
		if ( typeof jQuery !== 'undefined' ) {
			jQuery( document ).on( 'tinymce-editor-init', function ( event, editor ) {
				editor.on( 'input keydown', markDirty );
			} );
		}
	}
}() );
</script>
		<?php
	}
}
