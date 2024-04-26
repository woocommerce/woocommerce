/**
 * External dependencies
 */
import path from 'path';

export const BLOCK_THEME_WITH_TEMPLATES_SLUG = 'theme-with-woo-templates';
export const BLOCK_THEME_WITH_TEMPLATES_NAME = 'Theme with Woo Templates';
export const BLOCK_THEME_SLUG = 'twentytwentyfour';
export const BLOCK_THEME_NAME = 'Twenty Twenty-Four';
export const BLOCK_CHILD_THEME_WITH_BLOCK_NOTICES_FILTER_SLUG = `${ BLOCK_THEME_SLUG }-child__block-notices-filter`;
export const BLOCK_CHILD_THEME_WITH_BLOCK_NOTICES_TEMPLATE_SLUG = `${ BLOCK_THEME_SLUG }-child__block-notices-template`;
export const BLOCK_CHILD_THEME_WITH_CLASSIC_NOTICES_TEMPLATE_SLUG = `${ BLOCK_THEME_SLUG }-child__classic-notices-template`;
export const CLASSIC_THEME_SLUG = 'storefront';
export const CLASSIC_THEME_NAME = 'Storefront';
export const CLASSIC_CHILD_THEME_WITH_BLOCK_NOTICES_FILTER_SLUG = `${ CLASSIC_THEME_SLUG }-child__block-notices-filter`;
export const CLASSIC_CHILD_THEME_WITH_BLOCK_NOTICES_TEMPLATE_SLUG = `${ CLASSIC_THEME_SLUG }-child__block-notices-template`;
export const CLASSIC_CHILD_THEME_WITH_CLASSIC_NOTICES_TEMPLATE_SLUG = `${ CLASSIC_THEME_SLUG }-child__classic-notices-template`;
export const BASE_URL = 'http://localhost:8889';

export const WC_TEMPLATES_SLUG = 'woocommerce/woocommerce';

export const WP_ARTIFACTS_PATH =
	process.env.WP_ARTIFACTS_PATH ||
	path.join( process.cwd(), 'tests/e2e/artifacts' );

export const STORAGE_STATE_PATH =
	process.env.STORAGE_STATE_PATH ||
	path.join( WP_ARTIFACTS_PATH, 'storage-states/admin.json' );

// User roles storage states
export const adminFile = STORAGE_STATE_PATH;
export const customerFile = path.join(
	path.dirname( STORAGE_STATE_PATH ),
	'customer.json'
);
export const guestFile = { cookies: [], origins: [] };

export const DB_EXPORT_FILE = 'blocks_e2e.sql';
