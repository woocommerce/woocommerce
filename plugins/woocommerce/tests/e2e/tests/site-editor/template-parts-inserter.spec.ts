/**
 * External dependencies
 */
import { Admin, Editor, PageUtils } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { test, expect } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.use( { storageState: ADMIN_STATE_PATH } );

test.describe( 'Template parts in the Site Editor inserter', () => {
	test( 'shows only compatible template parts', async ( { page } ) => {
		const editor = new Editor( { page } );
		const admin = new Admin( {
			page,
			editor,
			pageUtils: new PageUtils( { page } ),
		} );
		await admin.visitSiteEditor( { canvas: 'edit' } );
		await page
			.getByRole( 'button', { name: 'Block Inserter', exact: true } )
			.click();

		await test.step( 'can insert a template part not related to Mini-Cart and Add to Cart + Options', async () => {
			await page
				.getByRole( 'searchbox', { name: 'Search' } )
				.fill( 'Footer' );

			await expect(
				page.locator( '.editor-block-list-item-template-part' )
			).toBeVisible();
		} );

		await test.step( "can't insert the Mini Cart template part", async () => {
			await page
				.getByRole( 'searchbox', { name: 'Search' } )
				.fill( 'Mini-Cart' );
			const miniCart = page
				.getByRole( 'listbox', { name: 'Blocks', exact: true } )
				.getByRole( 'option', { name: 'Mini-Cart', exact: true } );
			await expect( miniCart ).toHaveCount( 1 );
			await expect( miniCart ).toBeVisible();
		} );

		await test.step( 'hides the Add to Cart + Options template parts', async () => {
			for ( const productType of [
				'Simple',
				'Variable',
				'Grouped',
				'External',
			] ) {
				const title = `${ productType } Product Add to Cart + Options`;
				await page
					.getByRole( 'searchbox', { name: 'Search' } )
					.fill( title );
				await expect(
					page.getByRole( 'option', { name: title, exact: true } )
				).toHaveCount( 0 );
			}
		} );
	} );
} );
