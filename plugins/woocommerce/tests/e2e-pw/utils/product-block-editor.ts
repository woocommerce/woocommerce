/**
 * External dependencies
 */
import { expect } from '@playwright/test';
import type { Page, Expect } from '@playwright/test';

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

/**
 * Parameters for updateProduct function.
 */
interface UpdateProductParams {
	page: Page;
	expect: Expect;
}

/**
 * Parameters for disableVariableProductBlockTour function.
 */
interface DisableVariableProductBlockTourParams {
	page: Page;
}

/**
 * Update a product in the block editor and verify the update.
 *
 * @param params - Object containing page and expect.
 */
export const updateProduct = async ( {
	page,
	expect: expectFn,
}: UpdateProductParams ): Promise< void > => {
	await page.getByRole( 'button', { name: 'Update' } ).click();
	// Verify product was updated
	await expectFn( page.getByLabel( 'Dismiss this notice' ) ).toContainText(
		'Product updated'
	);
};

/**
 * Disable the variable product block tour.
 *
 * @param params - Object containing page.
 */
export const disableVariableProductBlockTour = async ( {
	page,
}: DisableVariableProductBlockTourParams ): Promise< void > => {
	// Further info: https://github.com/woocommerce/woocommerce/pull/45856/
	await page.waitForLoadState( 'domcontentloaded' );

	// Get the current user data
	const { id: userId, woocommerce_meta } = await page.evaluate( () => {
		return window.wp.data.select( 'core' ).getCurrentUser();
	} );

	// Disable the variable product block tour
	const updatedWooCommerceMeta = {
		...woocommerce_meta,
		variable_product_block_tour_shown: '"yes"',
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
