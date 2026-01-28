/**
 * External dependencies
 */
import type { Page, APIRequestContext } from '@playwright/test';

/**
 * Internal dependencies
 */
import { admin } from '../test-data/data';

/**
 * Declare window.wp type for WordPress data API.
 */
declare global {
	interface Window {
		wp: {
			data: {
				select: ( store: string ) => {
					getCurrentUser: () => {
						id: number;
						woocommerce_meta: Record< string, string >;
					};
				};
				dispatch: ( store: string ) => {
					saveUser: ( data: {
						id: number;
						woocommerce_meta: Record< string, string >;
					} ) => Promise< void >;
				};
			};
		};
	}
}

const base64String = Buffer.from(
	`${ admin.username }:${ admin.password }`
).toString( 'base64' );

const headers = {
	Authorization: `Basic ${ base64String }`,
};

/**
 * Enables or disables the product editor tour.
 *
 * @param request - Request context from calling function.
 * @param enable  - Set to `true` if you want to enable the block product tour. `false` if otherwise.
 */
export const toggleBlockProductTour = async (
	request: APIRequestContext,
	enable: boolean
): Promise< void > => {
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

/**
 * Enables or disables the variable product tour.
 *
 * @param page   - The Playwright page.
 * @param enable - Set to `true` if you want to enable the tour. `false` if otherwise.
 */
export const toggleVariableProductTour = async (
	page: Page,
	enable: boolean
): Promise< void > => {
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
