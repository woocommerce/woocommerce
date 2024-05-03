/**
 * External dependencies
 */
import { BlockData } from '@woocommerce/e2e-types';
import { test, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import {
	getProductsNameFromClassicTemplate,
	getProductsNameFromProductQuery,
} from './utils';

const blockData: BlockData = {
	name: 'core/query',
	mainClass: '.wc-block-price-filter',
	selectors: {
		frontend: {},
		editor: {},
	},
};

const templates = {
	// This test is disabled because archives are disabled for attributes by default. This can be uncommented when this is toggled on.
	//'taxonomy-product_attribute': {
	//	templateTitle: 'Product Attribute',
	//	slug: 'taxonomy-product_attribute',
	//	frontendPage: '/product-attribute/color/',
	//	legacyBlockName: 'woocommerce/legacy-template',
	//},
	'taxonomy-product_cat': {
		templateTitle: 'Product Category',
		slug: 'taxonomy-product_cat',
		frontendPage: '/product-category/music/',
		legacyBlockName: 'woocommerce/legacy-template',
	},
	// We don't have products with tags in the test site. Uncomment this when we have products with tags.
	// 'taxonomy-product_tag': {
	// 	templateTitle: 'Product Tag',
	// 	slug: 'taxonomy-product_tag',
	// 	frontendPage: '/product-tag/hoodie/',
	// },
	'archive-product': {
		templateTitle: 'Product Catalog',
		slug: 'archive-product',
		frontendPage: '/shop/',
		legacyBlockName: 'woocommerce/legacy-template',
	},
	'product-search-results': {
		templateTitle: 'Product Search Results',
		slug: 'product-search-results',
		frontendPage: '/?s=shirt&post_type=product',
		legacyBlockName: 'woocommerce/legacy-template',
	},
};

test.describe( `${ blockData.name } Block `, () => {
	test( 'when Inherit Query from template is enabled all the settings that customize the query should be hidden', async ( {
		admin,
		editorUtils,
		editor,
		page,
	} ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
		} );

		await editor.canvas.locator( 'body' ).click();

		const block = await editorUtils.getBlockByName( blockData.name );
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
	} );
	test( 'when Inherit Query from template is disabled all the settings that customize the query should be visble', async ( {
		admin,
		editorUtils,
		editor,
		page,
	} ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
		} );

		await editor.canvas.locator( 'body' ).click();

		const block = await editorUtils.getBlockByName( blockData.name );
		await editor.selectBlocks( block );

		await editor.openDocumentSettingsSidebar();

		const advancedFilterOption = page.getByLabel(
			'Advanced Filters options'
		);
		const inheritQueryFromTemplateOption = page.getByLabel(
			'Inherit query from template'
		);

		await inheritQueryFromTemplateOption.click();

		await expect( advancedFilterOption ).toBeVisible();
		await expect( inheritQueryFromTemplateOption ).toBeVisible();
	} );
} );

for ( const {
	templateTitle,
	slug,
	frontendPage,
	legacyBlockName,
} of Object.values( templates ) ) {
	test.describe( `${ templateTitle } template`, () => {
		test( 'Products block matches with classic template block', async ( {
			admin,
			editor,
			page,
			editorUtils,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//${ slug }`,
				postType: 'wp_template',
			} );

			await editor.canvas.locator( 'body' ).click();
			const block = await editorUtils.getBlockByName( blockData.name );
			// eslint-disable-next-line playwright/no-conditional-in-test
			const clientId = ( await block.getAttribute( 'data-block' ) ) ?? '';
			const parentClientId =
				// eslint-disable-next-line playwright/no-conditional-in-test
				( await editorUtils.getBlockRootClientId( clientId ) ) ?? '';
			await editor.selectBlocks( block );
			await editorUtils.insertBlock(
				{ name: legacyBlockName },
				undefined,
				parentClientId
			);

			await editor.saveSiteEditorEntities();

			await page.goto( frontendPage );

			const classicProducts = await getProductsNameFromClassicTemplate(
				page
			);
			const productQueryProducts = await getProductsNameFromProductQuery(
				page
			);

			expect( classicProducts ).toEqual( productQueryProducts );
		} );
	} );
}
