/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

const toggleVariableProductTour = async ( page: Page, enable: boolean ) => {
	await page.waitForLoadState( 'domcontentloaded' );

	// Get the current user data
	const { id: userId, woocommerce_meta } = await page.evaluate( () => {
		return window.wp.data.select( 'core' ).getCurrentUser();
	} );

	const toggleValue = enable ? 'no' : 'yes';
	const updatedWooCommerceMeta = {
		...woocommerce_meta,
		variable_product_tour_shown: toggleValue,
	};

	// Push the updated user data
	await page.evaluate(
		// eslint-disable-next-line @typescript-eslint/no-shadow
		async ( { userId, updatedWooCommerceMeta } ) => {
			await window.wp.data.dispatch( 'core' ).saveUser( {
				id: userId,
				woocommerce_meta: updatedWooCommerceMeta,
			} );
		},
		{ userId, updatedWooCommerceMeta }
	);

	await page.reload();
};

export { toggleVariableProductTour };
