/**
 * External dependencies
 */
import { test, expect, wpCLI, BlockData, Editor } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */

const blockData: Partial< BlockData > = {
	name: 'woocommerce/legacy-template',
};

const classicTemplateBlockNames = [
	'WooCommerce Classic Template',
	'Product (Classic)',
	'Product Attribute (Classic)',
	'Product Category (Classic)',
	'Product Tag (Classic)',
	"Product's Custom Taxonomy (Classic)",
	'Product Search Results (Classic)',
	'Product Grid (Classic)',
];

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

const getClassicTemplateBlocksInInserter = async ( {
	editor,
}: {
	editor: Editor;
} ) => {
	await editor.openGlobalBlockInserter();

	await editor.page
		.getByLabel( 'Search for blocks and patterns' )
		.fill( 'classic' );

	// Wait for blocks search to have finished.
	await expect(
		editor.page.getByRole( 'heading', {
			name: 'Available to install',
			exact: true,
		} )
	).toBeVisible();

	const inserterBlocks = editor.page.getByRole( 'listbox', {
		name: 'Blocks',
		exact: true,
	} );
	const options = inserterBlocks.locator( 'role=option' );

	// Filter out blocks that don't match one of the possible Classic Template block names (case-insensitive).
	const classicTemplateBlocks = await options.evaluateAll(
		( elements, blockNames ) => {
			const blockOptions = elements.filter( ( element ) => {
				return blockNames.some(
					( name ) => element.textContent === name
				);
			} );
			return blockOptions.map( ( element ) => element.textContent );
		},
		classicTemplateBlockNames
	);

	return classicTemplateBlocks;
};

test.describe( `${ blockData.name } Block `, () => {
	test.beforeEach( async () => {
		await wpCLI(
			'option update wc_blocks_use_blockified_product_grid_block_as_template false'
		);
	} );

	test( `is registered/unregistered when navigating from a non-WC template to a WC template and back`, async ( {
		admin,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `twentytwentyfour//home`,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		let classicTemplateBlocks = await getClassicTemplateBlocksInInserter( {
			editor,
		} );

		expect( classicTemplateBlocks ).toHaveLength( 0 );

		await editor.page.getByLabel( 'Open Navigation' ).click();
		await editor.page
			.getByLabel( 'Product Catalog', { exact: true } )
			.click();

		classicTemplateBlocks = await getClassicTemplateBlocksInInserter( {
			editor,
		} );

		expect( classicTemplateBlocks ).toHaveLength( 1 );

		await editor.page.getByLabel( 'Open Navigation' ).click();
		await editor.page.getByLabel( 'Blog Home', { exact: true } ).click();

		classicTemplateBlocks = await getClassicTemplateBlocksInInserter( {
			editor,
		} );

		expect( classicTemplateBlocks ).toHaveLength( 0 );
	} );

	test( `is registered/unregistered when navigating from a WC template to a non-WC template and back`, async ( {
		admin,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `woocommerce/woocommerce//archive-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		let classicTemplateBlocks = await getClassicTemplateBlocksInInserter( {
			editor,
		} );

		expect( classicTemplateBlocks ).toHaveLength( 1 );

		await editor.page.getByLabel( 'Open Navigation' ).click();
		await editor.page.getByLabel( 'Blog Home', { exact: true } ).click();

		classicTemplateBlocks = await getClassicTemplateBlocksInInserter( {
			editor,
		} );

		expect( classicTemplateBlocks ).toHaveLength( 0 );

		await editor.page.getByLabel( 'Open Navigation' ).click();
		await editor.page
			.getByLabel( 'Product Catalog', { exact: true } )
			.click();

		classicTemplateBlocks = await getClassicTemplateBlocksInInserter( {
			editor,
		} );

		expect( classicTemplateBlocks ).toHaveLength( 1 );
	} );

	test( `updates block title when navigating between WC templates`, async ( {
		admin,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `woocommerce/woocommerce//archive-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		let classicTemplateBlocks = await getClassicTemplateBlocksInInserter( {
			editor,
		} );

		expect( classicTemplateBlocks[ 0 ] ).toBe( 'Product Grid (Classic)' );

		await editor.page.getByLabel( 'Open Navigation' ).click();
		await editor.page
			.getByLabel( 'Product Search Results', { exact: true } )
			.click();

		classicTemplateBlocks = await getClassicTemplateBlocksInInserter( {
			editor,
		} );

		expect( classicTemplateBlocks[ 0 ] ).toBe(
			'Product Search Results (Classic)'
		);
	} );

	test( `is not available when editing template parts`, async ( {
		admin,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `twentytwentyfour//header`,
			postType: 'wp_template_part',
			canvas: 'edit',
		} );

		const classicTemplateBlocks = await getClassicTemplateBlocksInInserter(
			{
				editor,
			}
		);

		expect( classicTemplateBlocks ).toHaveLength( 0 );
	} );

	// @see https://github.com/woocommerce/woocommerce-blocks/issues/9637
	test( `is still available after resetting a modified WC template`, async ( {
		admin,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `woocommerce/woocommerce//single-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: 'Hello World' },
		} );

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await editor.page.getByLabel( 'Open Navigation' ).click();

		// Reset the template.
		await editor.page.getByPlaceholder( 'Search' ).fill( 'Single Product' );
		const resetNotice = editor.page
			.getByLabel( 'Dismiss this notice' )
			.getByText( `"Single Product" reset.` );
		const searchResults = editor.page.getByLabel( 'Actions', {
			exact: true,
		} );
		await expect.poll( async () => await searchResults.count() ).toBe( 1 );
		await searchResults.first().click();
		await editor.page.getByRole( 'menuitem', { name: 'Reset' } ).click();
		await editor.page.getByRole( 'button', { name: 'Reset' } ).click();
		await expect( resetNotice ).toBeVisible();

		// Open the template again.
		await editor.page.getByRole( 'menuitem', { name: 'Edit' } ).click();

		// Verify the Classic Template block is still registered.
		const classicTemplateBlocks = await getClassicTemplateBlocksInInserter(
			{
				editor,
			}
		);

		expect( classicTemplateBlocks ).toHaveLength( 1 );
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

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( template.path );

			await expect(
				page.getByText( 'Hello World' ).first()
			).toBeVisible();
		} );
	}
} );
