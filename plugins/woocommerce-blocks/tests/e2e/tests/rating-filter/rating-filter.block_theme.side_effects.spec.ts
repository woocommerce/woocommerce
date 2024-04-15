/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import { cli } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage from '../product-collection/product-collection.page';

const blockData = {
	name: 'Filter by Rating',
	slug: 'woocommerce/rating-filter',
	urlSearchParamWhenFilterIsApplied: '?rating_filter=1',
};

const test = base.extend< {
	productCollectionPageObject: ProductCollectionPage;
} >( {
	productCollectionPageObject: async (
		{ page, admin, editor, templateApiUtils, editorUtils },
		use
	) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
			templateApiUtils,
			editorUtils,
		} );
		await use( pageObject );
	},
} );

test.describe( `${ blockData.name } Block - with PHP classic template`, () => {
	test.beforeEach( async ( { admin, page, editor, editorUtils } ) => {
		await cli(
			'npm run wp-env run tests-cli -- wp option update wc_blocks_use_blockified_product_grid_block_as_template false'
		);

		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
		} );

		await editor.canvas.locator( 'body' ).click();

		await editorUtils.insertBlockUsingGlobalInserter( 'Filter by Rating' );
		await editor.saveSiteEditorEntities();
		await page.goto( `/shop` );
	} );

	test( 'should show all products', async ( { frontendUtils, page } ) => {
		const legacyTemplate = await frontendUtils.getBlockByName(
			'woocommerce/legacy-template'
		);

		const products = legacyTemplate
			.getByRole( 'list' )
			.locator( '.product' );

		await expect( products ).toHaveCount( 16 );

		await expect(
			page.getByRole( 'checkbox', { name: 'Rated 1 out of 5' } )
		).toBeVisible();
	} );

	test( 'should show only products that match the filter', async ( {
		frontendUtils,
		page,
	} ) => {
		await page
			.getByRole( 'checkbox', { name: 'Rated 1 out of 5' } )
			.click();

		const legacyTemplate = await frontendUtils.getBlockByName(
			'woocommerce/legacy-template'
		);

		const products = legacyTemplate
			.getByRole( 'list' )
			.locator( '.product' );

		await expect( page.url ).toContain(
			blockData.urlSearchParamWhenFilterIsApplied
		);

		await expect( products ).toHaveCount( 1 );
	} );
} );

test.describe( `${ blockData.name } Block - with Product Collection`, () => {
	test.beforeEach(
		async ( { admin, editorUtils, productCollectionPageObject } ) => {
			await admin.createNewPost();
			await productCollectionPageObject.insertProductCollection();
			await productCollectionPageObject.chooseCollectionInPost(
				'productCatalog'
			);
			await editorUtils.insertBlockUsingGlobalInserter(
				'Filter by Rating'
			);
			await editorUtils.publishAndVisitPost();
		}
	);

	test( 'should show all products', async ( { page } ) => {
		const products = page
			.locator( '.wp-block-woocommerce-product-template' )
			.getByRole( 'listitem' );

		await expect( products ).toHaveCount( 9 );
	} );

	test( 'should show only products that match the filter', async ( {
		page,
	} ) => {
		await page
			.getByRole( 'checkbox', { name: 'Rated 1 out of 5' } )
			.click();

		await page.waitForURL( ( url ) =>
			url
				.toString()
				.includes( blockData.urlSearchParamWhenFilterIsApplied )
		);

		const products = page
			.locator( '.wp-block-woocommerce-product-template' )
			.getByRole( 'listitem' );

		await expect( products ).toHaveCount( 1 );
	} );

	test( 'should refresh the page only if the user click on button', async ( {
		page,
		admin,
		editor,
		editorUtils,
		productCollectionPageObject,
	} ) => {
		await admin.createNewPost();
		await productCollectionPageObject.insertProductCollection();
		await productCollectionPageObject.chooseCollectionInPost(
			'productCatalog'
		);
		await editorUtils.insertBlockUsingGlobalInserter( 'Filter by Rating' );

		const ratingFilterControls = await editorUtils.getBlockByName(
			'woocommerce/rating-filter'
		);
		await editor.selectBlocks( ratingFilterControls );
		await editor.openDocumentSettingsSidebar();
		await page.getByText( "Show 'Apply filters' button" ).click();
		await editorUtils.publishAndVisitPost();

		await page.addInitScript( () => {
			document.addEventListener( 'DOMContentLoaded', () => {
				// eslint-disable-next-line dot-notation
				window[ '__DOMContentLoaded__' ] = true;
			} );
		} );

		await page
			.getByRole( 'checkbox', { name: 'Rated 1 out of 5' } )
			.click();
		await page.getByRole( 'button', { name: 'Apply' } ).click();

		await page.waitForEvent( 'domcontentloaded' );

		const domContentLoaded = await page.evaluate(
			// eslint-disable-next-line dot-notation
			() => window[ '__DOMContentLoaded__' ] === true
		);

		await expect( page.url() ).toContain(
			blockData.urlSearchParamWhenFilterIsApplied
		);

		expect( domContentLoaded ).toBe( true );
	} );
} );
