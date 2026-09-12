/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { random } from '../../utils/helpers';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { assertTaxCalculationEnabled } from '../../utils/taxes';

const taxClasses = [
	{
		name: 'Tax Class Simple',
		slug: 'tax-class-simple',
	},
];
const taxRates = [
	{
		name: 'Tax Rate Simple',
		rate: '10.0000',
		class: 'tax-class-simple',
	},
];
async function getOrderIdFromPage( page: Page ) {
	// get order ID from the page
	const orderText = await page
		.locator( 'h2.woocommerce-order-data__heading' )
		.textContent();
	const parts = orderText.match( /([0-9])\w+/ );
	return parts[ 0 ];
}

async function addProductToOrder( page: Page, product, quantity: number ) {
	await page.getByRole( 'button', { name: 'Add item(s)' } ).click();
	await page.getByRole( 'button', { name: 'Add product(s)' } ).click();
	const productSearch = page.locator(
		'.select2-container--open input.select2-search__field'
	);
	await expect( productSearch ).toBeVisible();
	await productSearch.fill( product.name );
	await page.getByRole( 'option', { name: product.name } ).first().click();

	const quantityField = page
		.locator( 'tr' )
		.filter( { hasText: product.name } )
		.getByPlaceholder( '1' );
	await quantityField.fill( quantity.toString() );
	// Confirm the quantity stuck before committing, so the line item is never
	// added with a stale/empty value.
	await expect( quantityField ).toHaveValue( quantity.toString() );

	await page.locator( '#btn-ok' ).click();

	// Adding the product fires an AJAX request that inserts the line item and
	// recalculates the order totals. Wait for the item row to render before
	// returning; otherwise a subsequent "Create"/save can persist the order
	// before the line item is fully applied, saving a 0-quantity / $0 line
	// (observed intermittently under parallel load).
	await expect(
		page.locator( 'td.name > a' ).filter( { hasText: product.name } )
	).toBeVisible();
}

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	order: async ( { restApi }, use ) => {
		const order = {};

		await use( order );

		if ( order.id ) {
			await restApi.delete( `${ WC_API_PATH }/orders/${ order.id }`, {
				force: true,
			} );
		}
	},

	customer: async ( { restApi }, use ) => {
		let customer = {};
		const username = `sideshowbob_${ random() }`;

		await restApi
			.post( `${ WC_API_PATH }/customers`, {
				email: `${ username }@example.com`,
				first_name: 'Sideshow',
				last_name: 'Bob',
				username,
				billing: {
					first_name: 'Sideshow',
					last_name: 'Bob',
					company: 'Die Bart Die',
					address_1: '123 Fake St',
					address_2: '',
					city: 'Springfield',
					state: 'FL',
					postcode: '12345',
					country: 'US',
					email: `${ username }@example.com`,
					phone: '555-555-5556',
				},
				shipping: {
					first_name: 'Sideshow',
					last_name: 'Bob',
					company: 'Die Bart Die',
					address_1: '321 Fake St',
					address_2: '',
					city: 'Springfield',
					state: 'FL',
					postcode: '12345',
					country: 'US',
				},
			} )
			.then( ( response ) => {
				customer = response.data;
			} );

		await use( customer );

		// Cleanup
		await restApi.delete( `${ WC_API_PATH }/customers/${ customer.id }`, {
			force: true,
		} );
	},

	simpleProduct: async ( { restApi }, use ) => {
		let product = {};

		await restApi
			.post( `${ WC_API_PATH }/products`, {
				name: `Product simple ${ random() }`,
				type: 'simple',
				regular_price: '100',
				tax_class: 'Tax Class Simple',
			} )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		// Cleanup
		await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
			force: true,
		} );
	},
} );

test.describe(
	'WooCommerce Orders > Add new order',
	{ tag: [ tags.SERVICES, tags.HPOS ] },
	() => {
		test.beforeAll( async ( { restApi } ) => {
			await assertTaxCalculationEnabled( restApi );
			// add tax classes
			for ( const taxClass of taxClasses ) {
				await restApi.post(
					`${ WC_API_PATH }/taxes/classes`,
					taxClass
				);
			}
			// attach rates to the classes
			for ( let i = 0; i < taxRates.length; i++ ) {
				await restApi.post( `${ WC_API_PATH }/taxes`, taxRates[ i ] );
			}
		} );

		test.afterAll( async ( { restApi } ) => {
			// clean up tax classes and rates
			for ( const { slug } of taxClasses ) {
				await restApi
					.delete( `${ WC_API_PATH }/taxes/classes/${ slug }`, {
						force: true,
					} )
					.catch(
						( error: { response: { data: { code: string } } } ) => {
							if (
								error.response.data.code ===
								'woocommerce_rest_invalid_tax_class'
							) {
								// do nothing, probably the tax class was not created due to a failing test
							} else {
								// Something else went wrong.
								throw new Error( error.response.data.code );
							}
						}
					);
			}
		} );

		test( 'can add a product using the keyboard without a rogue search box', async ( {
			page,
			simpleProduct,
		} ) => {
			await page.goto( 'wp-admin/admin.php?page=wc-orders&action=new' );

			// Open the Add products modal.
			await page.getByRole( 'button', { name: 'Add item(s)' } ).click();
			await page
				.getByRole( 'button', { name: 'Add product(s)' } )
				.click();

			const modal = page.locator( '.wc-backbone-modal-content' );
			await expect( modal ).toBeVisible();

			// Focus the (closed) product-search control and press Enter.
			// Before the fix this submitted the modal, closing it and
			// stranding a detached selectWoo dropdown at the top-left of the
			// page.
			await modal.locator( '.select2-selection' ).first().focus();
			await page.keyboard.press( 'Enter' );

			// The modal must still be open: Enter on the search control must
			// not submit/close the modal.
			await expect( modal ).toBeVisible();

			// selectWoo attaches its search dropdown to <body> (not inside the
			// modal), so target the search field at the page level — the same
			// locator the mouse-driven helper uses. Enter (above) opened the
			// dropdown; type the query, wait for the result, then press Enter to
			// choose it with the keyboard (results are loaded first, so this is
			// not the premature-Enter path).
			await page
				.locator( 'span > .select2-search__field' )
				.fill( simpleProduct.name );
			await page
				.getByRole( 'option', { name: simpleProduct.name } )
				.first()
				.waitFor();
			await page.keyboard.press( 'Enter' );

			// The product is selected in the modal's search control; commit.
			// Assert on the rendered selection specifically — a bare getByText
			// also matches the hidden <option>, which trips strict mode.
			await expect(
				modal
					.locator( '.select2-selection__rendered' )
					.filter( { hasText: simpleProduct.name } )
			).toBeVisible();
			await page.locator( '#btn-ok' ).click();

			// Line item lands on the order.
			await expect(
				page
					.locator( 'td.name > a' )
					.filter( { hasText: simpleProduct.name } )
			).toBeVisible();
		} );

		test( 'can create an order for an existing customer', async ( {
			page,
			restApi,
			simpleProduct,
			customer,
			order,
		} ) => {
			await page.goto( 'wp-admin/admin.php?page=wc-orders&action=new' );
			order.id = await getOrderIdFromPage( page );

			// Select customer
			await page.locator( '#select2-customer_user-container' ).click();
			await page
				.locator( 'input[aria-owns="select2-customer_user-results"]' )
				.fill( customer.username );
			await page
				.getByRole( 'option', {
					name: `${ customer.first_name } ${ customer.last_name }`,
				} )
				.click();

			await expect( page.locator( '#_billing_first_name' ) ).toHaveValue(
				'Sideshow'
			);
			await expect( page.locator( '#_billing_address_1' ) ).toHaveValue(
				'123 Fake St'
			);
			await expect( page.locator( '#_shipping_first_name' ) ).toHaveValue(
				'Sideshow'
			);
			await expect( page.locator( '#_shipping_address_1' ) ).toHaveValue(
				'321 Fake St'
			);

			await page.locator( '#_billing_address_1' ).fill( '124 Fake St' );
			page.on( 'dialog', ( dialog ) => dialog.accept() );
			await page
				.getByRole( 'link', { name: 'Copy billing address' } )
				.click();
			await expect( page.locator( '#_shipping_address_1' ) ).toHaveValue(
				'124 Fake St'
			);

			await page
				.locator( '#order_status' )
				.selectOption( 'wc-processing' );
			await page
				.getByPlaceholder( 'Customer notes about the order' )
				.fill( 'Leave the order with the prison guard' );

			await addProductToOrder( page, simpleProduct, 2 );
			await page
				.getByRole( 'button', { name: 'Recalculate', exact: true } )
				.click();
			await expect( page.locator( 'th.line_tax' ) ).toHaveText(
				'Tax Rate Simple'
			);

			await page.getByRole( 'button', { name: 'Create' } ).click();
			await expect( page.getByText( 'Order updated' ) ).toBeVisible();

			await page.goto(
				`wp-admin/admin.php?page=wc-orders&action=edit&id=${ order.id }`
			);
			await expect( page.locator( '#_billing_address_1' ) ).toHaveValue(
				'124 Fake St'
			);
			await expect( page.locator( '#_shipping_address_1' ) ).toHaveValue(
				'124 Fake St'
			);
			await expect( page.locator( '#order_status' ) ).toHaveValue(
				'wc-processing'
			);
			await expect(
				page.getByPlaceholder( 'Customer notes about the order' )
			).toHaveValue( 'Leave the order with the prison guard' );
			await expect(
				page.locator( 'td.name > a' ).filter( {
					hasText: simpleProduct.name,
				} )
			).toBeVisible();
			await expect( page.locator( 'th.line_tax' ) ).toHaveText(
				'Tax Rate Simple'
			);

			const response = await restApi.get(
				`${ WC_API_PATH }/orders/${ order.id }`
			);
			const persistedOrder = response.data;
			expect( persistedOrder.customer_id ).toBe( customer.id );
			expect( persistedOrder.status ).toBe( 'processing' );
			expect( persistedOrder.customer_note ).toBe(
				'Leave the order with the prison guard'
			);
			expect( persistedOrder.billing.address_1 ).toBe( '124 Fake St' );
			expect( persistedOrder.shipping.address_1 ).toBe( '124 Fake St' );
			expect( persistedOrder.line_items ).toHaveLength( 1 );
			expect( persistedOrder.line_items[ 0 ].product_id ).toBe(
				simpleProduct.id
			);
			expect( persistedOrder.line_items[ 0 ].quantity ).toBe( 2 );
			expect( Number( persistedOrder.line_items[ 0 ].total ) ).toBe(
				200
			);
			expect( persistedOrder.tax_lines ).toHaveLength( 1 );
			expect( persistedOrder.tax_lines[ 0 ].label ).toBe(
				'Tax Rate Simple'
			);
			expect( Number( persistedOrder.tax_lines[ 0 ].tax_total ) ).toBe(
				20
			);
			expect( Number( persistedOrder.total ) ).toBe( 220 );
		} );
	}
);
