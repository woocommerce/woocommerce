const { test, expect } = require( '@playwright/test' );
const { disableWelcomeModal } = require( '../../utils/editor' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const transformedCheckoutBlockTitle = `Transformed Checkout ${ Date.now() }`;
const transformedCheckoutBlockSlug = transformedCheckoutBlockTitle
	.replace( / /gi, '-' )
	.toLowerCase();

const simpleProductName = 'Very Simple Product';
const singleProductPrice = '999.00';

let productId;

test.describe( 'Transform Classic Checkout To Checkout Block', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// add product
		await api
			.post( 'products', {
				name: simpleProductName,
				type: 'simple',
				regular_price: singleProductPrice,
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `products/${ productId }`, {
			force: true,
		} );
	} );

	test( 'can transform classic checkout to checkout block', async ( {
		page,
	} ) => {
		// go to create a new page
		await page.goto( 'wp-admin/post-new.php?post_type=page' );

		await disableWelcomeModal( { page } );

		// fill page title
		await page
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( transformedCheckoutBlockTitle );

		// add classic checkout block
		await page.getByRole( 'textbox', { name: 'Add title' } ).click();
		await page.getByLabel( 'Add block' ).click();
		await page
			.getByPlaceholder( 'Search', { exact: true } )
			.fill( 'classic checkout' );
		await page
			.getByRole( 'option' )
			.filter( { hasText: 'Classic Checkout' } )
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

		// set terms & conditions and privacy policy as mandatory option
		await page.locator( '.wc-block-checkout__terms' ).click();
		await page.getByLabel( 'Require checkbox' ).click();

		// save and publish the page
		await page
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await page
			.getByRole( 'region', { name: 'Editor publish' } )
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await expect(
			page.getByText( `${ transformedCheckoutBlockTitle } is now live.` )
		).toBeVisible();

		// go to frontend to verify transformed checkout block
		// before that add product to cart to be able to visit checkout page
		await page.goto( `/cart/?add-to-cart=${ productId }` );
		await page.goto( transformedCheckoutBlockSlug );
		await expect(
			page.getByRole( 'heading', { name: transformedCheckoutBlockTitle } )
		).toBeVisible();
		await expect(
			page
				.getByRole( 'group', { name: 'Contact information' } )
				.locator( 'legend' )
		).toBeVisible();
		await expect(
			page.locator( '.wp-block-woocommerce-checkout-order-summary-block' )
		).toBeVisible();
		await expect(
			page.locator( '.wc-block-components-address-form' ).first()
		).toBeVisible();

		// verify existence of the terms & conditions and privacy policy checkbox
		await expect(
			page.getByText(
				'You must accept our Terms and Conditions and Privacy Policy to continue with your purchase.'
			)
		).toBeVisible();
		await expect( page.locator( '#terms-and-conditions' ) ).toBeVisible();
		await page.locator( '#terms-and-conditions' ).check();
		await expect( page.locator( '#terms-and-conditions' ) ).toBeChecked();
	} );
} );
