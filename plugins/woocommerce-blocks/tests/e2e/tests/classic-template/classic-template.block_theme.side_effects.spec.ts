/**
 * External dependencies
 */
import { BlockData } from '@woocommerce/e2e-types';
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { cli } from '@woocommerce/e2e-utils';
import { deleteAllTemplates } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */

const blockData: Partial< BlockData > = {
	name: 'woocommerce/legacy-template',
};

const templates = {
	'single-product': {
		templateTitle: 'Single Product',
		slug: 'single-product',
		frontendPage: '/product/single/',
	},
	// This test is disabled because archives are disabled for attributes by default. This can be uncommented when this is toggled on.
	//'taxonomy-product_attribute': {
	//	templateTitle: 'Product Attribute',
	//	slug: 'taxonomy-product_attribute',
	//	frontendPage: '/product-attribute/color/',
	//},
	'taxonomy-product_cat': {
		templateTitle: 'Product Category',
		slug: 'taxonomy-product_cat',
		frontendPage: '/product-category/music/',
	},
	'taxonomy-product_tag': {
		templateTitle: 'Product Tag',
		slug: 'taxonomy-product_tag',
		frontendPage: '/product-tag/recommended/',
	},
	'archive-product': {
		templateTitle: 'Product Catalog',
		slug: 'archive-product',
		frontendPage: '/shop/',
	},
	'product-search-results': {
		templateTitle: 'Product Search Results',
		slug: 'product-search-results',
		frontendPage: '/?s=s&post_type=product',
	},
};

test.beforeAll( async () => {
	await cli(
		'npm run wp-env run tests-cli -- wp option update wc_blocks_use_blockified_product_grid_block_as_template false'
	);
	await deleteAllTemplates( 'wp_template' );
} );

test.afterAll( async () => {
	await cli(
		'npm run wp-env run tests-cli -- wp option delete wc_blocks_use_blockified_product_grid_block_as_template'
	);
	await deleteAllTemplates( 'wp_template' );
} );

for ( const { templateTitle, slug } of Object.values( templates ) ) {
	test.describe( `${ blockData.name } Block `, () => {
		test( `is rendered on ${ templateTitle } template`, async ( {
			admin,
			editorUtils,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//${ slug }`,
				postType: 'wp_template',
			} );
			await editorUtils.enterEditMode();
			const block = await editorUtils.getBlockByName( blockData.name );

			await expect( block ).toBeVisible();
		} );

		// These tests consistently fail due to the default content of the page--potentially the classic block is not being
		// used after another test runs. Reenable this when we have a solution for this.
		// eslint-disable-next-line playwright/no-skipped-test
		test.skip( `is rendered on ${ templateTitle } template - frontend side`, async ( {
			admin,
			editor,
			editorUtils,
			page,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//${ slug }`,
				postType: 'wp_template',
			} );
			await editorUtils.enterEditMode();
			await editor.insertBlock( {
				name: 'core/paragraph',
				attributes: { content: 'Hello World' },
			} );
			await editor.saveSiteEditorEntities();
			await page.goto( frontendPage );

			await expect(
				page.getByText( 'Hello World' ).first()
			).toBeVisible();
		} );
	} );
}
