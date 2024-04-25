const { test, expect } = require( '@playwright/test' );
const {
	goToPageEditor,
	fillPageTitle,
	insertBlock,
	transformIntoBlocks,
} = require( '../../utils/editor' );
const uuid = require( 'uuid' );

const transformedCartBlockTitle = `Transformed Cart ${ uuid.v1() }`;
const transformedCartBlockSlug = transformedCartBlockTitle
	.replace( / /gi, '-' )
	.toLowerCase();

test.describe( 'Transform Classic Cart To Cart Block', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'can transform classic cart to cart block', async ( { page } ) => {
		await goToPageEditor( { page } );

		await fillPageTitle( page, transformedCartBlockTitle );
		await insertBlock( page, 'Classic Cart' );
		await transformIntoBlocks( page );

		// save and publish the page
		await page
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await page
			.getByRole( 'region', { name: 'Editor publish' } )
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await expect(
			page.getByText( `${ transformedCartBlockTitle } is now live.` )
		).toBeVisible();

		// go to frontend to verify transformed cart block
		await page.goto( transformedCartBlockSlug );
		await expect(
			page.getByRole( 'heading', { name: transformedCartBlockTitle } )
		).toBeVisible();
		await expect(
			page.getByRole( 'heading', {
				name: 'Your cart is currently empty!',
			} )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: 'Browse store' } )
		).toBeVisible();
	} );
} );
