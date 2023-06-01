/**
 * External dependencies
 */
import { BlockData } from '@woocommerce/e2e-types';
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { cli } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */

const blockData: BlockData = {
	name: 'woocommerce/legacy-template',
	mainClass: '.wc-block-price-filter',
	selectors: {
		frontend: {},
		editor: {},
	},
};

const templates = {
	'single-product': {
		templateTitle: 'Single Product',
		slug: 'single-product',
		frontendPage: '/product/single/',
	},
	'taxonomy-product_attribute': {
		templateTitle: 'Product Attribute',
		slug: 'taxonomy-product_attribute',
		frontendPage: '/product-attribute/color/',
	},

	'taxonomy-product_cat': {
		templateTitle: 'Product Category',
		slug: 'taxonomy-product_cat',
		frontendPage: '/product-category/music/',
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
	},
	'product-search-results': {
		templateTitle: 'Product Search Results',
		slug: 'product-search-results',
		frontendPage: '/?s=single&post_type=product',
	},
};

for ( const { templateTitle, slug, frontendPage } of Object.values(
	templates
) ) {
	test.describe( `${ blockData.name } Block `, () => {
		test.beforeAll( async () => {
			await cli(
				'npm run wp-env run tests-cli "wp option update wc_blocks_use_blockified_product_grid_block_as_template false"'
			);
		} );

		test( `is rendered on ${ templateTitle } template`, async ( {
			admin,
			editorUtils,
			editor,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//${ slug }`,
				postType: 'wp_template',
			} );

			await editor.canvas.click( 'body' );

			const block = await editorUtils.getBlockByName( blockData.name );
			expect( block ).not.toBeNull();
		} );

		test( `is rendered on ${ templateTitle } template - frontend side`, async ( {
			admin,
			editor,
			page,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//${ slug }`,
				postType: 'wp_template',
			} );

			await editor.canvas.click( 'body' );

			await editor.insertBlock( {
				name: 'core/paragraph',
				attributes: { content: 'Hello World' },
			} );

			await editor.saveSiteEditorEntities();

			await page.goto( frontendPage );

			const helloWorldText = await page.getByText( 'Hello World' );

			expect( helloWorldText ).not.toBeNull();
		} );

		test.afterAll( async ( { requestUtils } ) => {
			await cli(
				'npm run wp-env run tests-cli "wp option delete wc_blocks_use_blockified_product_grid_block_as_template"'
			);

			await requestUtils.deleteAllTemplates( 'wp_template' );
			await requestUtils.deleteAllTemplates( 'wp_template_part' );
		} );
	} );
}
