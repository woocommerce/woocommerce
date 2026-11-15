/**
 * Bundles the WordPress Design System tokens as a standalone stylesheet.
 *
 * It is only enqueued when the `wp-theme` style (WordPress 7.1+ or Gutenberg) is not available.
 * See `WCAdminAssets::enqueue_design_tokens()`.
 */
import '@wordpress/theme/design-tokens.css';
