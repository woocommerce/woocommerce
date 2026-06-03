<?php
/**
 * EditorControls prototype improvement class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\DevPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Compact CTA treatment for the classic product editor:
 * normalises all .button / .button-primary / .button-secondary inside metabox `.inside`
 * containers to the 32px WPDS compact button size, and replaces the legacy
 * "Add Media" button with a compact @wordpress/components-style button.
 */
class EditorControls {

	/**
	 * Register hooks. No-ops if the dev panel flag is off.
	 *
	 * @internal
	 */
	final public static function init(): void {
		if ( ! DevPanel::is_flag_enabled( 'compact_editor_ui' ) ) {
			return;
		}
		add_action( 'admin_head', array( self::class, 'output_styles' ) );
		add_action( 'admin_footer', array( self::class, 'replace_media_buttons' ) );
	}

	/**
	 * Output styles for the compact editor controls.
	 */
	public static function output_styles(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		?>
<style id="wc-proto-editor-controls">
.wp-media-buttons { padding-top: 2px; }

/* ── Breathing room between metabox header and its first input.
   #postdivrich (main editor) doesn't use the .postbox > .inside pattern, so
   match it with an equivalent rule on its content wrapper. ── */
body.post-type-product .postbox > .inside,
body.post-type-product #postdivrich > #wp-content-wrap {
	padding-top: 8px;
}

/* ── Screen Options panel: white background to match the metabox cards below ── */
body.post-type-product #screen-options-wrap {
	background: #fff;
}

/* ── Product Data header: align the product-type help-tip with the 32px select ── */
body.post-type-product .product-data-wrapper label[for="product-type"] {
	display: inline-flex;
	align-items: center;
	gap: 6px;
}
body.post-type-product .product-data-wrapper .woocommerce-product-type-tip {
	margin: 0 !important;
}

/* ── Compact CTAs (32px) inside product editor metaboxes — leave .button-small alone ── */
body.post-type-product .inside .button:not(.button-small),
body.post-type-product .inside .button-primary:not(.button-small),
body.post-type-product .inside .button-secondary:not(.button-small),
body.post-type-product .inside .button.button-large,
body.post-type-product .inside input[type="submit"].button:not(.button-small),
body.post-type-product .inside input[type="button"].button:not(.button-small) {
	height: 32px;
	min-height: 32px;
	line-height: 30px;
	padding: 0 12px;
	font-size: var(--wpds-typography-font-size-md, 13px);
	box-sizing: border-box;
}

/* Keep small utility buttons (e.g. "Change Permalink Structure") at their native compact size. */
body.post-type-product .button.button-small {
	height: auto;
	min-height: 26px;
	line-height: 2.18181818;
	padding: 0 8px;
	font-size: 11px;
}

/* Keep media library / modal buttons untouched */
body.post-type-product .media-modal .button,
body.post-type-product .media-modal .button-primary,
body.post-type-product .media-modal .button-secondary {
	height: auto;
	line-height: inherit;
	padding: revert;
	font-size: revert;
}

/* ── Compact inputs (32px) inside product editor metaboxes ── */
body.post-type-product .inside input[type="text"],
body.post-type-product .inside input[type="number"],
body.post-type-product .inside input[type="email"],
body.post-type-product .inside input[type="url"],
body.post-type-product .inside input[type="search"],
body.post-type-product .inside input[type="password"],
body.post-type-product .inside input[type="tel"],
body.post-type-product .inside input[type="date"],
body.post-type-product .inside input[type="datetime-local"],
body.post-type-product .inside input[type="time"],
body.post-type-product .inside select,
body.post-type-product #woocommerce-product-data select#product-type {
	height: 32px;
	min-height: 32px;
	line-height: 30px;
	box-sizing: border-box;
}

/* Keep modal inputs (media library, etc.) untouched */
body.post-type-product .media-modal input,
body.post-type-product .media-modal select {
	height: auto;
	min-height: 0;
	line-height: inherit;
}

/* ── Row alignment for compact 32px rows.
	Setting line-height: 32px on the form-field itself makes every inline element's
	line box 32px tall, so the checkbox/radio (via vertical-align: middle), the floated
	label, and the description span all center on the same y axis. ── */
body.post-type-product .woocommerce_options_panel .form-field {
	line-height: 32px !important;
}
body.post-type-product .woocommerce_options_panel .form-field > label {
	line-height: 32px !important;
	min-height: 32px !important;
	margin-top: 0 !important;
	padding-top: 0 !important;
}
body.post-type-product .woocommerce_options_panel .form-field > .woocommerce-help-tip {
	margin-top: 8px !important;
}
/* Center 16px checkboxes/radios in the 32px row. With line-height: 32px on the form-field,
   vertical-align: middle naturally centers them on the same y axis as the label text. */
body.post-type-product .woocommerce_options_panel .form-field > input[type="checkbox"],
body.post-type-product .woocommerce_options_panel .form-field > input[type="radio"] {
	margin: 0 !important;
	vertical-align: middle !important;
}
/* For radio groups (.wc-radios is taller than one row) — align the label to the top
	so it lines up with the first option. */
body.post-type-product .woocommerce_options_panel .form-field .wc-radios {
	margin: 0 !important;
	padding: 0 !important;
	list-style: none;
}
body.post-type-product .woocommerce_options_panel .form-field .wc-radios li {
	display: flex;
	align-items: center;
}
body.post-type-product .woocommerce_options_panel .form-field .wc-radios input[type="radio"] {
	margin: 0 6px 0 0 !important;
}

/* ── Helper text (.description) — minimal style override.
	Excludes inline labels next to checkboxes/radios, which are styled
	by TypographyHierarchy at 13px neutral, not 12px helper-text grey. ── */
body.post-type-product .woocommerce_options_panel .form-field input:not([type="checkbox"]):not([type="radio"]) ~ .description,
body.post-type-product .woocommerce_options_panel .form-field > select ~ .description,
body.post-type-product .woocommerce_options_panel .form-field > textarea ~ .description {
	font-size: var(--wpds-typography-font-size-sm, 12px) !important;
	color: var(--wpds-color-fg-content-neutral-weak, #757575) !important;
	font-style: normal !important;
}

</style>
		<?php
	}

	/**
	 * Replace .add_media buttons with compact component-style buttons.
	 */
	public static function replace_media_buttons(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		?>
		<script>
		( function () {
			var ICON = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false" style="flex-shrink:0"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM5 4.5h14c.3 0 .5.2.5.5v8.4l-3-2.9c-.3-.3-.8-.3-1 0L11 14l-2.5-2.5c-.3-.3-.8-.3-1.1 0L5 14V5c0-.3.2-.5.5-.5zm14 15H5c-.3 0-.5-.2-.5-.5v-2.4l4-4 2.5 2.5c.3.3.8.3 1.1 0L16 11l3.5 3.4V19c0 .3-.2.5-.5.5z"/></svg>';

			document.querySelectorAll( '.add_media' ).forEach( function ( btn ) {
				var editorId = btn.getAttribute( 'data-editor' ) || 'content';
				btn.className = 'components-button is-secondary is-compact insert-media add_media';
				btn.setAttribute( 'data-editor', editorId );
				btn.style.gap = '4px';
				btn.style.paddingLeft = '8px';
				btn.style.paddingRight = '8px';
				btn.innerHTML = ICON + 'Add Media';
			} );

			/* Sale price dates: WC outputs the help-tip after the Cancel link, which floats
			 * to the bottom-left under the date inputs. Move it next to the first date input
			 * so it sits in the conventional right-of-input position. */
			var saleDatesRow = document.querySelector( 'p.form-field.sale_price_dates_fields' );
			if ( saleDatesRow ) {
				var fromInput = saleDatesRow.querySelector( '#_sale_price_dates_from' );
				var tip       = saleDatesRow.querySelector( '.woocommerce-help-tip' );
				if ( fromInput && tip && fromInput.nextSibling !== tip ) {
					saleDatesRow.insertBefore( tip, fromInput.nextSibling );
				}
			}

			/* "Available for POS": replace its tooltip with inline description text. */
			var posRow = document.querySelector( 'p.form-field._visible_in_pos_field' );
			if ( posRow ) {
				var posTip = posRow.querySelector( '.woocommerce-help-tip' );
				if ( posTip ) {
					var text = posTip.getAttribute( 'data-tip' ) || posTip.getAttribute( 'aria-label' ) || '';
					posTip.remove();
					if ( text ) {
						var posDesc = document.createElement( 'span' );
						posDesc.className   = 'description';
						posDesc.textContent = text;
						posRow.appendChild( posDesc );
					}
				}
			}

			/* "Enable reviews": no description by default — add inline helper text. */
			var reviewsRow = document.querySelector( 'p.form-field.comment_status_field' );
			if ( reviewsRow && ! reviewsRow.querySelector( '.description' ) ) {
				var revDesc = document.createElement( 'span' );
				revDesc.className   = 'description';
				revDesc.textContent = '<?php echo esc_js( __( 'Allow customers to leave reviews on this product.', 'woocommerce' ) ); ?>';
				reviewsRow.appendChild( revDesc );
			}
		}() );
		</script>
		<?php
	}
}
