/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, expect, test } from '../../fixtures/fixtures';
import { getFakeProduct } from '../../utils/data';
import { setGatewayEnabled } from '../../utils/payment-gateways';
import { ADMIN_STATE_PATH } from '../../playwright.config';

let productId: number, orderId: number;
// Unique per run so concurrent workers don't create products with the same name.
const product = getFakeProduct( { regular_price: '15.99' } );
const productName = product.name;
const productPrice = product.regular_price;

test.describe(
	'WooCommerce Merchant Flow: Orders > Customer Payment Page',
	{ tag: [ tags.PAYMENTS, tags.SERVICES, tags.HPOS ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		let bacsWasEnabled: boolean;

		test.beforeAll( async ( { restApi } ) => {
			// create a simple product
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					name: productName,
					type: 'simple',
					regular_price: productPrice,
				} )
				.then( ( response: { data: { id: number } } ) => {
					productId = response.data.id;
				} );
			// create an order
			await restApi
				.post( `${ WC_API_PATH }/orders`, {
					line_items: [
						{
							product_id: productId,
							quantity: 1,
						},
					],
				} )
				.then( ( response: { data: { id: number } } ) => {
					orderId = response.data.id;
				} );
			// BACS is enabled globally in site setup; guard defensively in case it
			// is somehow off, and restore its prior state in afterAll.
			bacsWasEnabled = await setGatewayEnabled( restApi, 'bacs', true );
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.delete( `${ WC_API_PATH }/products/${ productId }`, {
				force: true,
			} );
			await restApi.delete( `${ WC_API_PATH }/orders/${ orderId }`, {
				force: true,
			} );
			await setGatewayEnabled( restApi, 'bacs', bacsWasEnabled );
		} );

		test(
			'should show the customer payment page link on a pending order',
			{ tag: [ tags.NOT_E2E ] },
			async ( { page } ) => {
				await page.goto(
					`wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
				);

				// verify that the order is pending payment
				await expect(
					page.locator( '#select2-order_status-container' )
				).toContainText( 'Pending payment' );

				//verify that the customer payment page link is displayed
				await expect(
					page.locator( 'label[for=order_status] > a' )
				).toContainText( 'Customer payment page →' );
			}
		);

		test(
			'should load the customer payment page',
			{ tag: [ tags.NOT_E2E ] },
			async ( { page } ) => {
				await page.goto(
					`wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
				);

				// visit the page
				await page.locator( 'label[for=order_status] > a' ).click();

				// verify we landed on the customer payment page
				await expect(
					page.getByRole( 'button', { name: 'Pay for order' } )
				).toBeVisible();
				await expect( page.locator( 'td.product-name' ) ).toContainText(
					productName
				);
				await expect(
					page.locator(
						'span.woocommerce-Price-amount.amount >> nth=0'
					)
				).toContainText( productPrice );
			}
		);

		//todo audit follow-up: this test is using the payment links as a merchant - not sure about its relevance.
		// and checking that the customer can pay for their oder is covered in the shopper tests
		test( 'can pay for the order through the customer payment page', async ( {
			page,
		} ) => {
			await test.step( 'Load the customer payment page', async () => {
				// key required, so can't go directly to the customer payment page
				await page.goto(
					`wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
				);
				await page.locator( 'label[for=order_status] > a' ).click();
			} );
			await test.step( 'Select payment method and pay for the order', async () => {
				// explicitly select the payment method
				await page.getByText( 'Direct bank transfer' ).click();

				// Handle notice if present
				await page.addLocatorHandler(
					page.getByRole( 'link', { name: 'Dismiss' } ),
					async () => {
						await page
							.getByRole( 'link', { name: 'Dismiss' } )
							.click();
					}
				);

				// pay for the order
				await page
					.getByRole( 'button', { name: 'Pay for order' } )
					.click();
			} );
			await test.step( 'Verify the order received page', async () => {
				// Verify we landed on the order received page
				await expect(
					page.getByText( 'Your order has been received' )
				).toBeVisible();
				await expect(
					page.getByText( `Order #: ${ orderId }` )
				).toBeVisible();
				await expect(
					await page.getByText( `Total: $${ productPrice }` ).count()
				).toBeGreaterThan( 0 );
			} );
		} );
	}
);
