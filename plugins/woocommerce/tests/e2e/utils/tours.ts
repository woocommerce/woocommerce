/**
 * External dependencies
 */
import type { Page, APIRequestContext } from '@playwright/test';

/**
 * Internal dependencies
 */
import { admin } from '../test-data/data';

const base64String = Buffer.from(
	`${ admin.username }:${ admin.password }`
).toString( 'base64' );

const headers = {
	Authorization: `Basic ${ base64String }`,
};

/**
 * Enables or disables the product editor tour.
 *
 * @param {import('@playwright/test').APIRequestContext} request Request context from calling function.
 * @param {boolean}                                      enable  Set to `true` if you want to enable the block product tour. `false` if otherwise.
 */
const toggleBlockProductTour = async (
	request: APIRequestContext,
	enable: boolean
) => {
	const url = './wp-json/wc-admin/options';
	const params = { _locale: 'user' };
	const toggleValue = enable ? 'no' : 'yes';
	const data = { woocommerce_block_product_tour_shown: toggleValue };

	await request.post( url, {
		data,
		params,
		headers,
	} );
};

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

export { toggleBlockProductTour, toggleVariableProductTour };
