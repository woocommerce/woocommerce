/**
 * External dependencies
 */
import { BlockData } from '@woocommerce/e2e-types';
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { cli } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */

const blockData: Partial< BlockData > = {
	name: 'woocommerce/legacy-template',
};

const templates = [
	{
		title: 'Single Product',
		slug: 'single-product',
		path: '/product/single/',
	},
	// This test is disabled because archives are disabled for attributes by
	// default. This can be uncommented when this is toggled on.
	//{
	//	title: 'Product Attribute',
	//	slug: 'taxonomy-product_attribute',
	//	path: '/product-attribute/color/',
	//},
	{
		title: 'Product Category',
		slug: 'taxonomy-product_cat',
		path: '/product-category/music/',
	},
	{
		title: 'Product Tag',
		slug: 'taxonomy-product_tag',
		path: '/product-tag/recommended/',
	},
	{
		title: 'Product Catalog',
		slug: 'archive-product',
		path: '/shop/',
	},
	{
		title: 'Product Search Results',
		slug: 'product-search-results',
		path: '/?s=s&post_type=product',
	},
];

test.describe( `${ blockData.name } Block `, () => {
	test.beforeEach( async () => {
		await cli(
			'npm run wp-env run tests-cli -- wp option update wc_blocks_use_blockified_product_grid_block_as_template false'
		);
	} );

	for ( const template of templates ) {
		test( `is rendered on ${ template.title } template`, async ( {
			admin,
			editor,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//${ template.slug }`,
				postType: 'wp_template',
				canvas: 'edit',
			} );

			const block = editor.canvas.locator(
				`[data-type="${ blockData.name }"]`
			);

			await expect( block ).toBeVisible();
		} );

		// These tests consistently fail due to the default content of the
		// page--potentially the classic block is not being used after
		// another test runs. Reenable this when we have a solution for
		// this.
		// eslint-disable-next-line playwright/no-skipped-test
		test.skip( `is rendered on ${ template.title } template - frontend side`, async ( {
			admin,
			editor,
			page,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//${ template.slug }`,
				postType: 'wp_template',
				canvas: 'edit',
			} );

			await editor.insertBlock( {
				name: 'core/paragraph',
				attributes: { content: 'Hello World' },
			} );

			await editor.saveSiteEditorEntities();

			await page.goto( template.path );

			await expect(
				page.getByText( 'Hello World' ).first()
			).toBeVisible();
		} );
	}
} );
