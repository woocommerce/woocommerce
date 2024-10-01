/**
 * External dependencies
 */
import { test, expect, BlockData } from '@woocommerce/e2e-utils';

const blockData: BlockData = {
	name: 'Product Filters (Experimental)',
	slug: 'woocommerce/product-filters',
	mainClass: '.wp-block-woocommerce-product-filters',
	selectors: {
		editor: {
			block: '.wp-block-woocommerce-product-filters',
		},
		frontend: {},
	},
};

test.describe( 'Product Filters Template Part', () => {
	test.beforeEach( async ( { admin, requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-enable-experimental-features'
		);
		await admin.visitSiteEditor( {
			postType: 'wp_template_part',
			postId: 'woocommerce/woocommerce//product-filters',
			canvas: 'edit',
		} );
	} );

	test( 'should be visible in the templates part list', async ( {
		page,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postType: 'wp_template_part',
		} );
		const templatePart = page
			.getByLabel( 'Patterns content' )
			.getByText( 'Product Filters (Experimental)', { exact: true } )
			.and( page.getByRole( 'button' ) );
		await expect( templatePart ).toBeVisible();
	} );

	test( 'should render the Product Filters block', async ( { editor } ) => {
		const productFiltersBlock = editor.canvas.getByLabel(
			`Block: ${ blockData.name }`
		);
		await expect( productFiltersBlock ).toBeVisible();
	} );

	test( 'Filters > can be added multiple times', async ( { editor } ) => {
		const block = editor.canvas.getByLabel( `Block: ${ blockData.name }` );
		await expect( block ).toBeVisible();

		const searchTerms = [ 'Color (Experimental)', 'Active (Experimental)' ];

		for ( const filter of searchTerms ) {
			await editor.selectBlocks( blockData.selectors.editor.block );

			const addBlock = block.getByRole( 'button', {
				name: 'Add block',
			} );

			await addBlock.click();

			await editor.page.getByPlaceholder( 'Search' ).fill( filter );

			const searchResult = editor.page.getByRole( 'option', {
				name: filter,
			} );
			await expect( searchResult ).toBeVisible();

			await searchResult.click();

			const _locator = `[aria-label="Block: ${ filter }"]`;

			await expect( editor.canvas.locator( _locator ) ).toHaveCount( 2 );
		}
	} );
} );
