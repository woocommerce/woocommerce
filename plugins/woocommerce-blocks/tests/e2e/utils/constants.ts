/**
 * External dependencies
 */
import path from 'path';

export const BLOCK_THEME_WITH_TEMPLATES_SLUG = 'theme-with-woo-templates';
export const BLOCK_THEME_WITH_TEMPLATES_NAME = 'Theme with Woo Templates';
export const BLOCK_THEME_SLUG = 'twentytwentyfour';
export const BLOCK_THEME_NAME = 'Twenty Twenty-Four';
export const CLASSIC_THEME_SLUG = 'storefront';
export const CLASSIC_THEME_NAME = 'Storefront';
export const BASE_URL = 'http://localhost:8889';

export const WP_ARTIFACTS_PATH =
	process.env.WP_ARTIFACTS_PATH || path.join( process.cwd(), 'artifacts' );

export const STORAGE_STATE_PATH =
	process.env.STORAGE_STATE_PATH ||
	path.join( WP_ARTIFACTS_PATH, 'storage-states/admin.json' );

// User roles file paths
export const adminFile = STORAGE_STATE_PATH;
export const customerFile = path.join(
	path.dirname( STORAGE_STATE_PATH ),
	'customer.json'
);
