/**
 * External dependencies
 */
import { Page } from '@playwright/test';

export const getProductsNameFromClassicTemplate = async ( page: Page ) => {
	const products = page.locator( '.woocommerce-loop-product__title' );
	return await products.allTextContents();
};

export const getProductsNameFromProductQuery = async ( page: Page ) => {
	const products = page.locator( '.wp-block-query .wp-block-post-title' );
	return await products.allTextContents();
};
