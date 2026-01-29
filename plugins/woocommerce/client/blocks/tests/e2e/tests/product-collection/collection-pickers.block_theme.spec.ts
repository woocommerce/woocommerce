/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage, {
	Collections,
	SELECTORS,
} from './product-collection.page';

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

/**
 * Taxonomy-based collections configuration for parameterized tests.
 */
const taxonomyCollections: {
	slug: Collections;
	name: string;
	termName: string;
	termLabel: string;
	expectedProductCount: number;
}[] = [
	{
		slug: 'productsByCategory',
		name: 'Products by Category',
		termName: 'categories',
		termLabel: 'Music',
		expectedProductCount: 2, // Album and Single are in Music category
	},
	{
		slug: 'productsByTag',
		name: 'Products by Tag',
		termName: 'tags',
		termLabel: 'Recommended',
		expectedProductCount: 1,
	},
];

test.describe( 'Product Collection: Collection Pickers', () => {
	test.describe( 'Hand-Picked Products', () => {
		test( 'Picker is shown immediately when collection is selected', async ( {
			pageObject,
			admin,
			editor,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'handPicked' );

			// Verify the product picker is shown
			const productPicker = editor.canvas.locator(
				SELECTORS.productPicker
			);
			await expect( productPicker ).toBeVisible();

			// Verify the Done button is visible but disabled (no products selected)
			const doneButton = productPicker.locator(
				SELECTORS.pickerDoneButton
			);
			await expect( doneButton ).toBeVisible();
			await expect( doneButton ).toBeDisabled();
		} );

		test( 'Can select multiple products and click Done', async ( {
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
			await expect( productPicker ).toBeVisible();

			// Select first product (Album)
			await productPicker
				.getByRole( 'option', { name: 'Album' } )
				.click();

			// Done button should now be enabled
			const doneButton = productPicker.locator(
				SELECTORS.pickerDoneButton
			);
			await expect( doneButton ).toBeEnabled();

			// Select second product (Beanie)
			await productPicker
				.getByRole( 'option', { name: 'Beanie' } )
				.click();

			// Click Done
			await doneButton.click();

			// Picker should be hidden and products should be displayed
			await expect( productPicker ).toBeHidden();
			await expect( pageObject.products ).toHaveCount( 2 );
		} );

		test( 'Picker is not shown after save and refresh when products are selected', async ( {
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

			// Select a product and click Done
			await productPicker
				.getByRole( 'option', { name: 'Album' } )
				.click();
			await productPicker.locator( SELECTORS.pickerDoneButton ).click();

			// Save and refresh
			await editor.saveDraft();
			await page.reload();
			await editor.canvas.locator( 'body' ).waitFor();

			// Click on the block to select it
			await editor.canvas
				.locator( '[data-type="woocommerce/product-collection"]' )
				.first()
				.click();

			// Picker should not be shown
			const pickerAfterRefresh = editor.canvas.locator(
				SELECTORS.productPicker
			);
			await expect( pickerAfterRefresh ).toBeHidden();

			// Products should be visible
			await expect( pageObject.products ).toHaveCount( 1 );
		} );

		test( 'Products are displayed on frontend', async ( {
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

			// Select products and click Done
			await productPicker
				.getByRole( 'option', { name: 'Album' } )
				.click();
			await productPicker
				.getByRole( 'option', { name: 'Beanie' } )
				.click();
			await productPicker.locator( SELECTORS.pickerDoneButton ).click();

			// Publish and verify on frontend
			await pageObject.publishAndGoToFrontend();
			await expect( pageObject.products ).toHaveCount( 2 );
			await expect( pageObject.productTitles ).toContainText( [
				'Album',
				'Beanie',
			] );
		} );
	} );

	for ( const collection of taxonomyCollections ) {
		test.describe( `${ collection.name }`, () => {
			test( 'Picker is shown immediately when collection is selected', async ( {
				pageObject,
				admin,
				editor,
			} ) => {
				await admin.createNewPost();
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInPost( collection.slug );

				// Verify the taxonomy picker is shown
				const taxonomyPicker = editor.canvas.locator(
					SELECTORS.taxonomyPicker
				);
				await expect( taxonomyPicker ).toBeVisible();

				// Verify the Done button is visible but disabled (no terms selected)
				const doneButton = editor.canvas.locator(
					`${ SELECTORS.taxonomyPicker } ${ SELECTORS.pickerDoneButton }`
				);
				await expect( doneButton ).toBeVisible();
				await expect( doneButton ).toBeDisabled();
			} );

			test( `Can select ${ collection.termName } and click Done`, async ( {
				pageObject,
				admin,
				editor,
			} ) => {
				await admin.createNewPost();
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInPost( collection.slug );

				const taxonomyPicker = editor.canvas.locator(
					SELECTORS.taxonomyPicker
				);
				await expect( taxonomyPicker ).toBeVisible();

				// Select a term
				await taxonomyPicker
					.getByRole( 'option', { name: collection.termLabel } )
					.click();

				// Done button should now be enabled
				const doneButton = editor.canvas.locator(
					`${ SELECTORS.taxonomyPicker } ${ SELECTORS.pickerDoneButton }`
				);
				await expect( doneButton ).toBeEnabled();

				// Click Done
				await doneButton.click();

				// Picker should be hidden and products should be displayed
				await expect( taxonomyPicker ).toBeHidden();
				await expect( pageObject.products ).toHaveCount(
					collection.expectedProductCount
				);
			} );

			test( `Products from selected ${ collection.termName } are displayed on frontend`, async ( {
				pageObject,
				admin,
				editor,
			} ) => {
				await admin.createNewPost();
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInPost( collection.slug );

				const taxonomyPicker = editor.canvas.locator(
					SELECTORS.taxonomyPicker
				);

				// Select term and click Done
				await taxonomyPicker
					.getByRole( 'option', { name: collection.termLabel } )
					.click();
				await editor.canvas
					.locator(
						`${ SELECTORS.taxonomyPicker } ${ SELECTORS.pickerDoneButton }`
					)
					.click();

				// Publish and verify on frontend
				await pageObject.publishAndGoToFrontend();
				await expect( pageObject.products ).toHaveCount(
					collection.expectedProductCount
				);
			} );
		} );
	}

	test.describe( 'Collection switching', () => {
		test( 'Switching from Hand-Picked to Products by Category shows appropriate picker', async ( {
			pageObject,
			admin,
			editor,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'handPicked' );

			// Verify product picker is shown
			const productPicker = editor.canvas.locator(
				SELECTORS.productPicker
			);
			await expect( productPicker ).toBeVisible();

			// Select a product and click Done
			await productPicker
				.getByRole( 'option', { name: 'Album' } )
				.click();
			await productPicker.locator( SELECTORS.pickerDoneButton ).click();
			await expect( productPicker ).toBeHidden();

			// Switch to Products by Category using toolbar
			await pageObject.changeCollectionUsingToolbar(
				'productsByCategory'
			);

			// Taxonomy picker should now be shown
			const taxonomyPicker = editor.canvas.locator(
				SELECTORS.taxonomyPicker
			);
			await expect( taxonomyPicker ).toBeVisible();
		} );

		test( 'Switching to a non-picker collection hides any picker', async ( {
			pageObject,
			admin,
			editor,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'handPicked' );

			// Verify product picker is shown
			const productPicker = editor.canvas.locator(
				SELECTORS.productPicker
			);
			await expect( productPicker ).toBeVisible();

			// Switch to Featured Products (no picker needed)
			await pageObject.changeCollectionUsingToolbar( 'featured' );

			// No picker should be shown
			await expect( productPicker ).toBeHidden();
			const taxonomyPicker = editor.canvas.locator(
				SELECTORS.taxonomyPicker
			);
			await expect( taxonomyPicker ).toBeHidden();

			// Products should be displayed
			await expect( pageObject.products ).toHaveCount( 4 );
		} );
	} );
} );
