/**
 * Saved Item block frontend entry.
 *
 * Side-effect import: registers the `woocommerce/save-for-later` Interactivity API store
 * so the block's `data-wp-on` bindings have their action handlers attached at runtime.
 * The actual store definition lives alongside the other shared WooCommerce iAPI stores.
 */

/**
 * External dependencies
 */
import '@woocommerce/stores/woocommerce/saved-for-later';
