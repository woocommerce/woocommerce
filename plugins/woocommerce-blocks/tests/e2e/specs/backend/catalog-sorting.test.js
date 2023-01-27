/**
 * External dependencies
 */
import {
	canvas,
	createNewPost,
	switchUserToAdmin,
	searchForBlock,
} from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import {
	filterCurrentBlocks,
	goToSiteEditor,
	useTheme,
	waitForCanvas,
} from '../../utils.js';

const block = {
	name: 'Catalog Sorting',
	slug: 'woocommerce/catalog-sorting',
	class: '.wc-block-catalog-sorting',
};

describe( `${ block.name } Block`, () => {
	it( 'can not be inserted in a post', async () => {
		await switchUserToAdmin();
		await createNewPost( {
			postType: 'post',
			title: block.name,
		} );
		await searchForBlock( block.name );
		expect( page ).toMatch( 'No results found.' );
	} );

	describe( 'in FSE editor', () => {
		useTheme( 'emptytheme' );

		beforeEach( async () => {
			await goToSiteEditor();
			await waitForCanvas();
		} );

		it( 'can be inserted in FSE area', async () => {
			// We are using here the "insertCatalogSorting" function because the
			// tests are flickering when we use the "insertBlock" function.
			await insertCatalogSorting();

			await expect( canvas() ).toMatchElement( block.class );
		} );

		it( 'can be inserted more than once', async () => {
			await insertCatalogSorting();
			await insertCatalogSorting();
			const catalogStoringBlock = await filterCurrentBlocks(
				( b ) => b.name === block.slug
			);
			expect( catalogStoringBlock ).toHaveLength( 2 );
		} );
	} );
} );

const insertCatalogSorting = async () => {
	await searchForBlock( block.name );
	await page.waitForXPath( `//button//span[text()='${ block.name }']` );
	const insertButton = (
		await page.$x( `//button//span[text()='${ block.name }']` )
	 )[ 0 ];
	await insertButton.click();
};
