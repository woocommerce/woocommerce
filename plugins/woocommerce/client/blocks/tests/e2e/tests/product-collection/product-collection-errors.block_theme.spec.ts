/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage from './product-collection.page';

const test = base.extend< { pageObject: ProductCollectionPage } >( {
	pageObject: async ( { page, admin, editor }, use ) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Product Collection: Collections', () => {
	test( 'displays error notice when adding out-of-stock product via Product Collection block', async ( {
		page,
		pageObject,
	} ) => {
		await pageObject.createNewPostAndInsertBlock( 'productCatalog' );

		await pageObject.setFilterComboboxValue( 'Stock status', [
			'Out of stock',
		] );

		await expect( pageObject.productTitles ).toHaveText( [
			'T-Shirt with Logo',
		] );

		await pageObject.publishAndGoToFrontend();

		await page.getByRole( 'link', { name: 'Read More' } ).click();

		await page.addToCart( 'T-Shirt with Logo' );
	} );
} );
