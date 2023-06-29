/**
 * External dependencies
 */
import { Page } from '@playwright/test';

export const addToCart = async ( page: Page ) => {
	await page.click( 'text=Add to cart' );
	await page.waitForLoadState( 'networkidle' );
};
