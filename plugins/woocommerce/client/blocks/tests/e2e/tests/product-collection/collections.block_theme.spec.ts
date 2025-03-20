/**
 * External dependencies
 */
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

test.describe( 'Product Collection: Collections', () => {
	test( 'New Arrivals collection can be added and displays proper products', async ( {
		pageObject,
	} ) => {
		await pageObject.createNewPostAndInsertBlock( 'newArrivals' );

		// New Arrivals are by default filtered to display products from last 7 days.
		// Products in our test env have creation date set to much older, hence
		// no products are expected to be displayed by default.
		await expect( pageObject.products ).toHaveCount( 0 );

		await pageObject.publishAndGoToFrontend();

		await expect( pageObject.products ).toHaveCount( 0 );
	} );

	// When creating reviews programmatically the ratings are not propagated
	// properly so products order by rating is undeterministic in test env.
	// eslint-disable-next-line playwright/no-skipped-test
	test.skip( 'Top Rated Products collection can be added and displays proper products', async ( {
		pageObject,
	} ) => {
		await pageObject.createNewPostAndInsertBlock( 'topRated' );

		const topRatedProducts = [
			'V Neck T Shirt',
			'Hoodie',
			'Hoodie with Logo',
			'T-Shirt',
			'Beanie',
		];

		await expect( pageObject.products ).toHaveCount( 5 );
		await expect( pageObject.productTitles ).toHaveText( topRatedProducts );

		await pageObject.publishAndGoToFrontend();

		await expect( pageObject.products ).toHaveCount( 5 );
	} );

	// There's no orders in test env so the order of Best Sellers
	// is undeterministic in test env. Requires further work.
	// eslint-disable-next-line playwright/no-skipped-test
	test.skip( 'Best Sellers collection can be added and displays proper products', async ( {
		pageObject,
	} ) => {
		await pageObject.createNewPostAndInsertBlock( 'bestSellers' );

		const bestSellersProducts = [
			'Album',
			'Hoodie',
			'Single',
			'Hoodie with Logo',
			'T-Shirt with Logo',
		];

		await expect( pageObject.products ).toHaveCount( 5 );
		await expect( pageObject.productTitles ).toHaveText(
			bestSellersProducts
		);

		await pageObject.publishAndGoToFrontend();

		await expect( pageObject.products ).toHaveCount( 5 );
	} );

	test( 'On Sale Products collection can be added and displays proper products', async ( {
		pageObject,
	} ) => {
		await pageObject.createNewPostAndInsertBlock( 'onSale' );

		const onSaleProducts = [
			'Beanie',
			'Beanie with Logo',
			'Belt',
			'Cap',
			'Hoodie',
		];

		await expect( pageObject.products ).toHaveCount( 5 );
		await expect( pageObject.productTitles ).toHaveText( onSaleProducts );

		await pageObject.publishAndGoToFrontend();

		await expect( pageObject.products ).toHaveCount( 5 );
	} );

	test( 'Featured Products collection can be added and displays proper products', async ( {
		pageObject,
	} ) => {
		await pageObject.createNewPostAndInsertBlock( 'featured' );

		const featuredProducts = [
			'Cap',
			'Hoodie with Zipper',
			'Sunglasses',
			'V-Neck T-Shirt',
		];

		await expect( pageObject.products ).toHaveCount( 4 );
		await expect( pageObject.productTitles ).toHaveText( featuredProducts );

		await pageObject.publishAndGoToFrontend();

		await expect( pageObject.products ).toHaveCount( 4 );
	} );

	test( 'Product Catalog collection can be added in post and syncs query with template', async ( {
		pageObject,
	} ) => {
		await pageObject.createNewPostAndInsertBlock( 'productCatalog' );

		const queryTypeLocator = pageObject
			.locateSidebarSettings()
			.getByLabel( SELECTORS.usePageContextControl );

		await expect( queryTypeLocator.getByLabel( 'Default' ) ).toBeChecked();
		await expect(
			queryTypeLocator.getByLabel( 'Custom' )
		).not.toBeChecked();
		await expect( pageObject.products ).toHaveCount( 9 );

		await pageObject.publishAndGoToFrontend();

		await expect( pageObject.products ).toHaveCount( 9 );
	} );

	test( 'Product Catalog Collection can be added in product archive and syncs query with template', async ( {
		pageObject,
		editor,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await editor.setContent( '' );

		await pageObject.insertProductCollection();
		await pageObject.chooseCollectionInTemplate();
		await editor.openDocumentSettingsSidebar();

		const queryTypeLocator = pageObject
			.locateSidebarSettings()
			.getByLabel( SELECTORS.usePageContextControl );

		await expect( queryTypeLocator.getByLabel( 'Default' ) ).toBeChecked();
		await expect(
			queryTypeLocator.getByLabel( 'Custom' )
		).not.toBeChecked();
	} );

	test.describe( 'Have hidden implementation in UI', () => {
		test( 'New Arrivals', async ( { pageObject } ) => {
			await pageObject.createNewPostAndInsertBlock( 'newArrivals' );
			const input = await pageObject.getOrderByElement();

			await expect( input ).toBeHidden();
		} );

		test( 'Top Rated Products', async ( { pageObject } ) => {
			await pageObject.createNewPostAndInsertBlock( 'topRated' );
			const input = await pageObject.getOrderByElement();

			await expect( input ).toBeHidden();
		} );

		test( 'Best Sellers', async ( { pageObject } ) => {
			await pageObject.createNewPostAndInsertBlock( 'bestSellers' );
			const input = await pageObject.getOrderByElement();

			await expect( input ).toBeHidden();
		} );

		test( 'On Sale Products', async ( { pageObject } ) => {
			await pageObject.createNewPostAndInsertBlock( 'onSale' );
			const sidebarSettings = pageObject.locateSidebarSettings();
			const input = sidebarSettings.getByLabel(
				SELECTORS.onSaleControlLabel
			);

			await expect( input ).toBeHidden();
		} );

		test( 'Featured Products', async ( { pageObject } ) => {
			await pageObject.createNewPostAndInsertBlock( 'featured' );
			const sidebarSettings = pageObject.locateSidebarSettings();
			const input = sidebarSettings.getByLabel(
				SELECTORS.featuredControlLabel
			);

			await expect( input ).toBeHidden();
		} );
	} );

	test.describe( 'Related Products collection', () => {
		test( 'Can configure related products criteria using "Related by" settings', async ( {
			pageObject,
			editor,
		} ) => {
			// "Related by" control shouldn't be visible for other collections
			await pageObject.createNewPostAndInsertBlock( 'bestSellers' );
			const sidebarSettings = pageObject.locateSidebarSettings();

			const relatedByControl = sidebarSettings.locator(
				'.wc-block-editor-product-collection-inspector-controls__relate-by'
			);
			await expect( relatedByControl ).toBeHidden();

			// Change collection type to "Related Products"
			// And verify that "Related by" control is visible
			await pageObject.changeCollectionUsingToolbar( 'relatedProducts' );
			await pageObject.chooseProductInEditorProductPickerIfAvailable(
				editor.canvas
			);
			await expect( relatedByControl ).toBeVisible();

			// Verify that both checkboxes (Categories and Tags) are checked by default
			const categoriesCheckbox =
				sidebarSettings.getByLabel( 'Categories' );
			const tagsCheckbox = sidebarSettings.getByLabel( 'Tags' );
			await expect( categoriesCheckbox ).toBeChecked();
			await expect( tagsCheckbox ).toBeChecked();
			await expect( pageObject.productTitles ).toHaveText( [ 'Single' ] );

			// Uncheck "Categories" checkbox
			await categoriesCheckbox.uncheck();
			await expect(
				editor.canvas.getByText(
					'No products to display. Try adjusting the filters in the block settings panel.'
				)
			).toBeVisible();

			// Verify on frontend
			await categoriesCheckbox.check();
			await pageObject.publishAndGoToFrontend();
			await expect( pageObject.productTitles ).toHaveText( [ 'Single' ] );
		} );
	} );
} );
