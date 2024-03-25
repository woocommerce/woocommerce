const { test, expect } = require( '@playwright/test' );
const { disableWelcomeModal } = require( '../../utils/editor' );

const transformedCartBlockTitle = `Transformed Cart ${ Date.now() }`;
const transformedCartBlockSlug = transformedCartBlockTitle
	.replace( / /gi, '-' )
	.toLowerCase();

test.describe( 'Transform Classic Cart To Cart Block', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test( 'can transform classic cart to cart block', async ( { page } ) => {
		// go to create a new page
		await page.goto( 'wp-admin/post-new.php?post_type=page' );

		await disableWelcomeModal( { page } );

		// fill page title
		await page
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( transformedCartBlockTitle );

		// add classic cart block
		await page.getByRole( 'textbox', { name: 'Add title' } ).click();
		await page.getByLabel( 'Add block' ).click();
		await page
			.getByPlaceholder( 'Search', { exact: true } )
			.fill( 'classic cart' );
		await page
			.getByRole( 'option' )
			.filter( { hasText: 'Classic Cart' } )
			.click();

		// transform into blocks
		await expect(
			page.locator(
				'.wp-block-woocommerce-classic-shortcode__placeholder-copy'
			)
		).toBeVisible();
		await page
			.getByRole( 'button' )
			.filter( { hasText: 'Transform into blocks' } )
			.click();
		await expect( page.getByLabel( 'Dismiss this notice' ) ).toContainText(
			'Classic shortcode transformed to blocks.'
		);

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
