/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import { test, expect, wpCLI } from '@woocommerce/e2e-utils';

const getShopPageId = async () => {
	const cliOutput = await wpCLI( 'option get woocommerce_shop_page_id' );
	const shopPageId = cliOutput.stdout.match( /\d+/ )?.pop();

	if ( ! shopPageId ) {
		throw new Error( 'Shop page ID not found' );
	}

	return shopPageId;
};

const expectShopTemplateToBeLoaded = async ( page: Page ) => {
	await expect(
		page.getByRole( 'heading', { name: 'Shop', level: 1 } )
	).toBeVisible();
	await expect(
		page.getByRole( 'heading', { name: 'Album' } ).first()
	).toBeVisible();
};

test.describe( 'Shop page', () => {
	test( 'template selector is not visible in the Page editor', async ( {
		admin,
		page,
	} ) => {
		const shopPageId = await getShopPageId();

		await admin.editPost( shopPageId );

		await expect( page.getByText( 'Template' ) ).toBeHidden();
	} );

	test( 'loads the product catalog template after the shop page permalink is updated via REST API', async ( {
		page,
		requestUtils,
	} ) => {
		const shopPageId = await getShopPageId();

		await page.goto( 'shop/' );
		await expectShopTemplateToBeLoaded( page );

		const updatedShopPage = await requestUtils.rest( {
			method: 'POST',
			path: `/wp/v2/pages/${ shopPageId }`,
			data: {
				slug: 'market',
			},
		} );

		expect( updatedShopPage.slug ).toBe( 'market' );

		await page.goto( 'market/' );
		await expectShopTemplateToBeLoaded( page );
	} );
} );
