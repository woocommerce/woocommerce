/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import { test, expect, RequestUtils } from '@woocommerce/e2e-utils';

const getShopPageId = async ( requestUtils: RequestUtils ) => {
	const pages = await requestUtils.rest( {
		path: '/wp/v2/pages?slug=shop',
	} );
	const shopPageId = pages[ 0 ]?.id;

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
		requestUtils,
	} ) => {
		const shopPageId = await getShopPageId( requestUtils );

		await admin.editPost( String( shopPageId ) );

		await expect( page.getByText( 'Template' ) ).toBeHidden();
	} );

	test( 'loads the product catalog template after the shop page permalink is updated via REST API', async ( {
		page,
		requestUtils,
	} ) => {
		const shopPageId = await getShopPageId( requestUtils );

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
