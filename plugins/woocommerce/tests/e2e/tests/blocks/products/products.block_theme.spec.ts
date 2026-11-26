/**
 * External dependencies
 */
import {
	test as base,
	expect,
	guestFile,
	BlockData,
	BLOCK_THEME_SLUG,
	WPTemplate,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	getProductsNameFromClassicTemplate,
	getProductCollectionQuery,
	getProductsNameFromProductCollection,
	getProductsNameFromProductQuery,
	insertProductsQuery,
} from './utils';
import { locks } from '../../../fixtures/fixtures';

const blockData: BlockData = {
	name: 'core/query',
	slug: '',
	mainClass: '.wc-block-price-filter',
	selectors: {
		frontend: {},
		editor: {},
	},
};

const CATEGORY_PAGE = '/product-category/clothing/tshirts/';
const LEGACY_BLOCK_NAME = 'woocommerce/legacy-template';

const test = base.extend< { categoryTemplate: WPTemplate } >( {
	// A template for the Tshirts category only, so no other page changes.
	categoryTemplate: async ( { requestUtils }, use ) => {
		// A stopped run can leave the template, and a second one gets a
		// different slug, which the Tshirts page does not use.
		await requestUtils
			.rest( {
				method: 'DELETE',
				path: `/wp/v2/templates/${ BLOCK_THEME_SLUG }//taxonomy-product_cat-tshirts`,
				params: { force: true },
			} )
			.catch( ( error ) => {
				if ( error?.code !== 'rest_template_not_found' ) {
					throw error;
				}
			} );
		const template = ( await requestUtils.createTemplate( 'wp_template', {
			slug: 'taxonomy-product_cat-tshirts',
			title: 'Products by Category: Tshirts',
			content: '',
		} ) ) as WPTemplate;
		await use( template );
		await requestUtils.revertTemplate( 'wp_template', template.id );
	},
} );

test.describe( `${ blockData.name } Block `, () => {
	test( 'when Inherits Query From Template other options are hidden, show up otherwise', async ( {
		admin,
		editor,
		page,
	} ) => {
		// This test does not save the template.
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//archive-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
		await editor.setContent( '' );
		await insertProductsQuery( editor );
		const block = await editor.getBlockByName( blockData.name );
		await editor.selectBlocks( block );
		await editor.openDocumentSettingsSidebar();
		const advancedFilterOption = page.getByLabel(
			'Advanced Filters options'
		);
		const inheritQueryFromTemplateOption = page.getByLabel(
			'Inherit query from template'
		);

		await expect( advancedFilterOption ).toBeHidden();
		await expect( inheritQueryFromTemplateOption ).toBeVisible();

		await inheritQueryFromTemplateOption.click();

		await expect( advancedFilterOption ).toBeVisible();
		await expect( inheritQueryFromTemplateOption ).toBeVisible();
	} );
} );

test.describe(
	'Tshirts category template',
	{ lock: locks.TSHIRTS_CATEGORY_TEMPLATE },
	() => {
		test.beforeEach( async ( { admin, editor, categoryTemplate } ) => {
			await admin.visitSiteEditor( {
				postId: categoryTemplate.id,
				postType: 'wp_template',
				canvas: 'edit',
			} );
			await insertProductsQuery( editor );
		} );

		test( 'product button should add product to the cart when inheriting query from template', async ( {
			editor,
			browser,
		} ) => {
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			// A guest cart, because all workers share the admin cart.
			const guestContext = await browser.newContext( {
				storageState: guestFile,
			} );
			const guestPage = await guestContext.newPage();
			await guestPage.goto( CATEGORY_PAGE );

			const addToCartButton = guestPage.getByRole( 'button', {
				name: 'Add to cart: “Polo”',
			} );
			await addToCartButton.click();
			await expect( addToCartButton ).toHaveText( '1 in cart' );
			const cartLink = guestPage.getByRole( 'link', {
				name: 'View cart',
			} );
			await expect( cartLink ).toBeVisible();
			await guestContext.close();
		} );

		test( 'Product Collection matches with classic template block', async ( {
			editor,
			page,
		} ) => {
			await page
				.getByRole( 'button', {
					name: 'Upgrade to Product Collection',
				} )
				.click();
			const expectProductCollectionQuery = async () => {
				// The helper returns `{}` until the upgraded block reaches the
				// editor store, and reading it once would dereference that empty
				// object. Poll for the shape first. This is a condition on the
				// block's own attributes, not a wait for the editor to settle.
				await expect
					.poll( () => getProductCollectionQuery( page ) )
					.toMatchObject( {
						isProductCollectionBlock: true,
						inherit: true,
					} );

				const query = await getProductCollectionQuery( page );
				expect( query.perPage ).toBeGreaterThan( 1 );
			};
			await expectProductCollectionQuery();

			await editor.insertBlock( { name: LEGACY_BLOCK_NAME } );
			await editor.canvas.locator( 'body' ).click();

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );
			await page.reload();
			await editor.canvas.locator( 'body' ).waitFor();
			await expectProductCollectionQuery();

			await page.goto( CATEGORY_PAGE );

			const classicProducts =
				await getProductsNameFromClassicTemplate( page );
			const productCollectionProducts =
				await getProductsNameFromProductCollection( page );

			expect( classicProducts.length ).toBeGreaterThan( 1 );
			expect( productCollectionProducts.length ).toBeGreaterThan( 1 );
			expect( classicProducts ).toEqual( productCollectionProducts );
		} );

		test( 'Products block matches the classic template block on a product category archive', async ( {
			editor,
			page,
		} ) => {
			await editor.insertBlock( { name: LEGACY_BLOCK_NAME } );
			await editor.canvas.locator( 'body' ).click();

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( CATEGORY_PAGE );
			await expect(
				page.locator( '.woocommerce-loop-product__title' ).first()
			).toBeVisible();

			const classicProducts =
				await getProductsNameFromClassicTemplate( page );
			const productQueryProducts =
				await getProductsNameFromProductQuery( page );

			expect( classicProducts.length ).toBeGreaterThan( 1 );
			expect( classicProducts ).toEqual( productQueryProducts );
		} );
	}
);
