/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage, { SELECTORS } from './product-collection.page';

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

const getProductCollectionQuery = async ( page: Page ) =>
	page.evaluate( () => {
		const block = window.wp.data
			.select( 'core/block-editor' )
			.getBlocks()
			.find(
				( candidate: { name: string } ) =>
					candidate.name === 'woocommerce/product-collection'
			);

		return block?.attributes.query ?? {};
	} );

test.describe( 'Product Collection: Collection Pickers', () => {
	test.describe( 'Hand-Picked Products', () => {
		test( 'Products are displayed on frontend', async ( {
			pageObject,
			admin,
			editor,
			page,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'handPicked' );

			const productPicker = editor.canvas.locator(
				SELECTORS.productPicker
			);
			const doneButton = productPicker.locator(
				SELECTORS.pickerDoneButton
			);
			await expect( doneButton ).toBeDisabled();

			await productPicker
				.getByRole( 'checkbox', { name: 'Album (woo-album)' } )
				.click();
			await expect( doneButton ).toBeEnabled();
			await productPicker
				.getByRole( 'checkbox', { name: 'Beanie (woo-beanie)' } )
				.click();

			const selectedQuery = await getProductCollectionQuery( page );
			const selectedIds = selectedQuery.woocommerceHandPickedProducts;
			expect( selectedIds ).toHaveLength( 2 );
			expect(
				selectedIds.every( ( id: string | number ) =>
					/^[1-9]\d*$/.test( String( id ) )
				)
			).toBe( true );

			await doneButton.click();
			await expect( productPicker ).toBeHidden();
			await pageObject.refreshLocators( 'editor' );
			await expect( pageObject.productTitles ).toHaveText( [
				'Album',
				'Beanie',
			] );

			await editor.saveDraft();
			await page.reload();
			await editor.canvas.locator( 'body' ).waitFor();
			await editor.canvas
				.locator( '[data-type="woocommerce/product-collection"]' )
				.first()
				.click();
			await expect(
				editor.canvas.locator( SELECTORS.productPicker )
			).toBeHidden();
			expect(
				( await getProductCollectionQuery( page ) )
					.woocommerceHandPickedProducts
			).toEqual( selectedIds );

			await pageObject.refreshLocators( 'editor' );
			await expect( pageObject.productTitles ).toHaveText( [
				'Album',
				'Beanie',
			] );
			await pageObject.publishAndGoToFrontend();
			await expect( pageObject.productTitles ).toHaveText( [
				'Album',
				'Beanie',
			] );
		} );
	} );

	test.describe( 'Products by Category', () => {
		test( 'Products from selected categories are displayed on frontend', async ( {
			pageObject,
			admin,
			editor,
			page,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'productsByCategory' );

			const taxonomyPicker = editor.canvas.locator(
				SELECTORS.taxonomyPicker
			);
			const doneButton = taxonomyPicker.locator(
				SELECTORS.pickerDoneButton
			);
			await expect( doneButton ).toBeDisabled();
			await taxonomyPicker
				.getByRole( 'checkbox', { name: 'Accessories' } )
				.click();
			await expect( doneButton ).toBeEnabled();

			const taxQuery = ( await getProductCollectionQuery( page ) )
				.taxQuery;
			expect( Object.keys( taxQuery ) ).toEqual( [ 'product_cat' ] );
			expect( taxQuery.product_cat ).toHaveLength( 1 );
			expect( String( taxQuery.product_cat[ 0 ] ) ).toMatch(
				/^[1-9]\d*$/
			);

			await doneButton.click();
			await expect( taxonomyPicker ).toBeHidden();
			await pageObject.refreshLocators( 'editor' );
			await expect( pageObject.products ).toHaveCount( 5 );
			await pageObject.publishAndGoToFrontend();
			await expect( pageObject.products ).toHaveCount( 5 );
			await expect
				.poll( async () =>
					(
						await pageObject.productTitles.allTextContents()
					).toSorted()
				)
				.toEqual(
					[
						'Beanie',
						'Beanie with Logo',
						'Belt',
						'Cap',
						'Protected: Sunglasses',
					].toSorted()
				);
		} );
	} );

	test.describe( 'Collection switching', () => {
		test( 'Switching to a non-picker collection displays products immediately', async ( {
			pageObject,
			admin,
			editor,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'handPicked' );

			const productPicker = editor.canvas.locator(
				SELECTORS.productPicker
			);
			await productPicker
				.getByRole( 'checkbox', { name: 'Album (woo-album)' } )
				.click();
			await productPicker.locator( SELECTORS.pickerDoneButton ).click();
			await expect( productPicker ).toBeHidden();

			await pageObject.changeCollectionUsingToolbar(
				'productsByCategory'
			);
			const taxonomyPicker = editor.canvas.locator(
				SELECTORS.taxonomyPicker
			);
			await expect( productPicker ).toBeHidden();
			await expect( taxonomyPicker ).toBeVisible();
			await taxonomyPicker
				.getByRole( 'checkbox', { name: 'Accessories' } )
				.click();
			await taxonomyPicker.locator( SELECTORS.pickerDoneButton ).click();
			await expect( taxonomyPicker ).toBeHidden();

			await pageObject.changeCollectionUsingToolbar( 'featured' );
			await expect( productPicker ).toBeHidden();
			await expect( taxonomyPicker ).toBeHidden();
			await pageObject.refreshLocators( 'editor' );
			await expect( pageObject.productTitles ).toHaveText( [
				'Cap',
				'Hoodie with Zipper',
				'Sunglasses',
				'V-Neck T-Shirt',
			] );
		} );
	} );
} );
