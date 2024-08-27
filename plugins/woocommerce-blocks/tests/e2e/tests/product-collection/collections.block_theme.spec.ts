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

test.describe( 'Product Collection', () => {
	test.describe( 'Collections', () => {
		test( 'New Arrivals Collection can be added and displays proper products', async ( {
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
		test.skip( 'Top Rated Collection can be added and displays proper products', async ( {
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
			await expect( pageObject.productTitles ).toHaveText(
				topRatedProducts
			);

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.products ).toHaveCount( 5 );
		} );

		// There's no orders in test env so the order of Best Sellers
		// is undeterministic in test env. Requires further work.
		// eslint-disable-next-line playwright/no-skipped-test
		test.skip( 'Best Sellers Collection can be added and displays proper products', async ( {
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

		test( 'On Sale Collection can be added and displays proper products', async ( {
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
			await expect( pageObject.productTitles ).toHaveText(
				onSaleProducts
			);

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.products ).toHaveCount( 5 );
		} );

		test( 'Featured Collection can be added and displays proper products', async ( {
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
			await expect( pageObject.productTitles ).toHaveText(
				featuredProducts
			);

			await pageObject.publishAndGoToFrontend();

			await expect( pageObject.products ).toHaveCount( 4 );
		} );

		test( 'Product Catalog Collection can be added in post and syncs query with template', async ( {
			pageObject,
		} ) => {
			await pageObject.createNewPostAndInsertBlock( 'productCatalog' );

			const usePageContextToggle = pageObject
				.locateSidebarSettings()
				.locator( `${ SELECTORS.usePageContextControl } input` );

			await expect( usePageContextToggle ).toBeVisible();
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

			const sidebarSettings = pageObject.locateSidebarSettings();
			const input = sidebarSettings.locator(
				`${ SELECTORS.usePageContextControl } input`
			);

			await expect( input ).toBeChecked();
		} );

		test.describe( 'Have hidden implementation in UI', () => {
			test( 'New Arrivals', async ( { pageObject } ) => {
				await pageObject.createNewPostAndInsertBlock( 'newArrivals' );
				const input = await pageObject.getOrderByElement();

				await expect( input ).toBeHidden();
			} );

			test( 'Top Rated', async ( { pageObject } ) => {
				await pageObject.createNewPostAndInsertBlock( 'topRated' );
				const input = await pageObject.getOrderByElement();

				await expect( input ).toBeHidden();
			} );

			test( 'Best Sellers', async ( { pageObject } ) => {
				await pageObject.createNewPostAndInsertBlock( 'bestSellers' );
				const input = await pageObject.getOrderByElement();

				await expect( input ).toBeHidden();
			} );

			test( 'On Sale', async ( { pageObject } ) => {
				await pageObject.createNewPostAndInsertBlock( 'onSale' );
				const sidebarSettings = pageObject.locateSidebarSettings();
				const input = sidebarSettings.getByLabel(
					SELECTORS.onSaleControlLabel
				);

				await expect( input ).toBeHidden();
			} );

			test( 'Featured', async ( { pageObject } ) => {
				await pageObject.createNewPostAndInsertBlock( 'featured' );
				const sidebarSettings = pageObject.locateSidebarSettings();
				const input = sidebarSettings.getByLabel(
					SELECTORS.featuredControlLabel
				);

				await expect( input ).toBeHidden();
			} );
		} );
	} );
} );
