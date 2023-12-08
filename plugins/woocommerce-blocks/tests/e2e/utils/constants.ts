/**
 * External dependencies
 */
import path from 'path';

export const BLOCK_THEME_SLUG = 'twentytwentyfour';
export const BLOCK_THEME_NAME = 'Twenty Twenty-Four';
export const CLASSIC_THEME_SLUG = 'storefront';
export const CLASSIC_THEME_NAME = 'Storefront';
export const BASE_URL = 'http://localhost:8889';
export const STORAGE_STATE_PATH = path.join(
	process.cwd(),
	'artifacts/storage-states/admin.json'
);

// User roles file paths
export const adminFile = '.auth/admin.json';
export const customerFile = '.auth/customer.json';
export const guestFile = '.auth/guest.json';
