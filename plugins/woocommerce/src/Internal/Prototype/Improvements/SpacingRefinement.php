<?php
/**
 * SpacingRefinement prototype improvement class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\DevPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Cleans up layout spacing on the classic product editor.
 * - Hides the empty permalink row on new (unsaved) products and reveals it
 *   via MutationObserver once WordPress populates it after the first save.
 * - Removes the excess top margin on the product description metabox.
 */
class SpacingRefinement {

	/**
	 * Register hooks. No-ops if the dev panel flag is off.
	 *
	 * @internal
	 */
	final public static function init(): void {
		if ( ! DevPanel::is_flag_enabled( 'spacing_refinement' ) ) {
			return;
		}
		add_action( 'admin_head', array( self::class, 'output_styles' ) );
		add_action( 'admin_footer', array( self::class, 'output_scripts' ) );
	}

	private static function is_product_screen(): bool {
		$screen = get_current_screen();
		return $screen && 'post' === $screen->base && 'product' === $screen->post_type;
	}

	/**
	 * Inject CSS improvements.
	 */
	public static function output_styles(): void {
		if ( ! self::is_product_screen() ) {
			return;
		}

		echo '<style id="wc-proto-spacing-refinement">
			#titlediv { margin-bottom: 0 !important; }
			#postdivrich {
				margin-top: var(--wpds-dimension-padding-xl, 20px) !important;
				margin-left: 0 !important;
				margin-right: 0 !important;
			}
		</style>' . "\n";
	}

	/**
	 * Inject JS to hide the permalink row when empty and reveal it on first populate.
	 */
	public static function output_scripts(): void {
		if ( ! self::is_product_screen() ) {
			return;
		}
		?>
		<script>
		( function () {
			var box = document.getElementById( 'edit-slug-box' );
			if ( ! box || box.innerHTML.trim() ) {
				return;
			}

			box.style.display = 'none';

			new MutationObserver( function () {
				if ( box.innerHTML.trim() ) {
					box.style.removeProperty( 'display' );
				}
			} ).observe( box, { childList: true, subtree: true } );
		}() );
		</script>
		<?php
	}
}
