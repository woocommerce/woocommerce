/**
 * Internal dependencies
 */
import { expect, test } from '../../fixtures/fixtures';
import { WC_API_PATH } from '../../utils/api-client';

const randomNum = new Date().getTime().toString();
const customer = {
	username: `customer${ randomNum }`,
	password: 'password',
	email: `customer${ randomNum }@woocommercecoree2etestsuite.com`,
};
const product = {
	name: 'Downloadable product My Account',
	downloadLimit: '1',
};

test.describe( 'Customer can manage downloadable file in My Account > Downloads page', () => {
	let productId, orderId;

	test.beforeAll( async ( { restApi, baseURL } ) => {
		// add product
		await restApi
			.post( `${ WC_API_PATH }/products`, {
				name: product.name,
				type: 'simple',
				regular_price: '9.99',
				downloadable: true,
				downloads: [
					{
						name: 'Test file',
						file: `${ baseURL }/test-file/`,
					},
				],
				download_limit: 1,
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		// create customer
		await restApi
			.post( `${ WC_API_PATH }/customers`, customer )
			.then( ( response ) => ( customer.id = response.data.id ) );
		// create an order
		await restApi
			.post( `${ WC_API_PATH }/orders`, {
				set_paid: true,
				status: 'completed',
				billing: {
					first_name: 'Jane',
					last_name: 'Smith',
					email: customer.email,
				},
				line_items: [
					{
						product_id: productId,
						quantity: 1,
					},
				],
			} )
			.then( ( response ) => {
				orderId = response.data.id;
			} );
		// once the order is created, assign it to our existing customer user
		await restApi.put( `${ WC_API_PATH }/orders/${ orderId }`, {
			customer_id: customer.id,
		} );
	} );

	test.afterAll( async ( { restApi } ) => {
		await restApi.delete( `${ WC_API_PATH }/products/${ productId }`, {
			force: true,
		} );
		await restApi.delete( `${ WC_API_PATH }/orders/${ orderId }`, {
			force: true,
		} );
		await restApi.delete( `${ WC_API_PATH }/customers/${ customer.id }`, {
			force: true,
		} );
	} );

	test( 'can see downloadable file and click to download it', async ( {
		page,
	} ) => {
		await page.goto( 'my-account/downloads/' );
		// sign in as the "customer" user
		await page.locator( '#username' ).fill( customer.username );
		await page.locator( '#password' ).fill( customer.password );
		await page.locator( 'text=Log in' ).click();

		// verify that the downloadable product exist in downloads
		await expect(
			page.getByRole( 'heading', { name: 'Downloads' } )
		).toBeVisible();
		await expect( page.locator( 'td.download-product' ) ).toContainText(
			product.name
		);
		await expect( page.locator( 'td.download-remaining' ) ).toContainText(
			product.downloadLimit
		);
		await expect(
			page.locator( '.woocommerce-MyAccount-downloads-file' )
		).toContainText( 'Test file' );

		// click to simulate downloading and verify the file doesn't exist anymore in downloads
		await page.locator( '.woocommerce-MyAccount-downloads-file' ).click();
		await page.goto( 'my-account/downloads/' );
		await expect(
			page.getByText( 'No downloads available yet.' )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: 'Browse products' } )
		).toBeVisible();

		await page.getByRole( 'link', { name: 'Browse products' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Shop' } )
		).toBeVisible();
	} );
} );
