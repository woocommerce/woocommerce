/**
 * External dependencies
 */
import { Page } from '@playwright/test';

// Navigate to the shop page.
export const goToShop = ( page: Page ) => {
	page.goto( '/shop', {
		waitUntil: 'networkidle',
	} );
};
