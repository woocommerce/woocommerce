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
	test.describe.configure( { mode: 'parallel' } );

	test.beforeEach( async ( { page } ) => {
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

		await page
			.getByRole( 'searchbox', { name: 'Search' } )
			.fill( 'Header' );
		await expect(
			page
				.getByRole( 'listbox', { name: 'Blocks', exact: true } )
				.getByRole( 'option', { name: 'Header', exact: true } )
		).toBeVisible();
	} );

	test( 'can insert a template part not related to Mini-Cart and Add to Cart with Options', async ( {
		page,
	} ) => {
		const editor = new Editor( { page } );
		await editor.setContent( '' );
		await page
			.getByRole( 'searchbox', { name: 'Search' } )
			.fill( 'Footer' );

		await expect(
			page.locator(
				'.editor-block-list-item-template-part/instance_footer'
			)
		).toBeVisible();
	} );

	test( "can't insert the Mini Cart template part", async ( { page } ) => {
		await page
			.getByRole( 'searchbox', { name: 'Search' } )
			.fill( 'Mini-Cart' );
		const miniCart = page
			.getByRole( 'listbox', { name: 'Blocks', exact: true } )
			.getByRole( 'option', { name: 'Mini-Cart', exact: true } );
		await expect( miniCart ).toHaveCount( 1 );
		await expect( miniCart ).toBeVisible();
	} );

	test( 'hides the Add to Cart with Options template parts', async ( {
		page,
	} ) => {
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
				page
					.getByRole( 'listbox', { name: 'Blocks', exact: true } )
					.getByRole( 'option', { name: title, exact: true } )
			).toHaveCount( 0 );
		}
	} );
} );
