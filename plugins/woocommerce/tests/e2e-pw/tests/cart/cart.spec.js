/**
 * External dependencies
 */
import { addAProductToCart } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { getFakeProduct } from '../../utils/data';
import { createBlocksCartPage, BLOCKS_CART_PAGE } from '../../utils/pages';
import { updateIfNeeded, resetValue } from '../../utils/settings';
import { WC_API_PATH } from '../../utils/api-client';

const cartPages = [ { name: 'classic cart', slug: 'cart' }, BLOCKS_CART_PAGE ];

/* region helpers */
function isBlocksCart( page ) {
	return page.url().includes( BLOCKS_CART_PAGE.slug );
}

function formatAmount( amount ) {
	return parseFloat( amount ).toLocaleString( 'en-US', {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2,
	} );
}

async function checkCartContentInClassicCart(
	page,
	products,
	tax,
	expectedTotal
) {
	for ( const product of products ) {
		const row = await page
			.locator( 'tr.cart_item' )
			.filter( { hasText: product.data.name } );

		await expect( row ).toHaveCount( 1 );
		await expect( row.getByLabel( 'Product quantity' ) ).toHaveValue(
			product.qty.toString()
		);

		await expect( row.locator( 'td.product-price' ) ).toHaveText(
			`$${ formatAmount( product.data.price ) }`
		);
	}

	const totalRow = page.locator( '.cart_totals tr.order-total' );

	await expect( totalRow.locator( 'td' ) ).toHaveText(
		`$${ formatAmount( expectedTotal ) }`
	);
}

async function checkCartContentInBlocksCart(
	page,
	products,
	tax,
	expectedTotal
) {
	for ( const product of products ) {
		const row = await page
			.locator( 'tr.wc-block-cart-items__row' )
			.filter( { hasText: product.data.name } );

		await expect( row ).toHaveCount( 1 );
		await expect(
			row.getByRole( 'spinbutton', { name: 'Quantity' } )
		).toHaveValue( product.qty.toString() );

		const expectedItemTotal = formatAmount(
			parseFloat( product.data.price ) * product.qty
		);
		await expect(
			row.locator( 'td.wc-block-cart-item__total' )
		).toHaveText( `$${ expectedItemTotal }` );
	}

	await expect(
		page.locator( '.wc-block-components-totals-item__value' ).last()
	).toHaveText( `$${ formatAmount( expectedTotal ) }` );
}

async function checkCartContent( slug, page, products, tax ) {
	if ( products.length === 0 ) {
		await expect(
			page.locator( 'main' ).getByText( 'Your cart is currently empty' )
		).toBeVisible();
		return;
	}

	const expectedTotal = products.reduce( ( total, product ) => {
		return (
			total +
			parseFloat( product.data.price ) *
				product.qty *
				( 1 + parseFloat( tax.rate ) / 100 )
		);
	}, 0 );

	if ( isBlocksCart( page ) ) {
		await checkCartContentInBlocksCart(
			page,
			products,
			tax,
			expectedTotal
		);
	} else {
		await checkCartContentInClassicCart(
			page,
			products,
			tax,
			expectedTotal
		);
	}
}

/* endregion */

/* region fixtures */
const test = baseTest.extend( {
	page: async ( { page, restApi }, use ) => {
		await createBlocksCartPage( page.context().browser() );

		const calcTaxesState = await updateIfNeeded(
			`general/woocommerce_calc_taxes`,
			'yes'
		);

		// Check id COD payment is enabled and enable it if it is not
		const codResponse = await restApi.get(
			`${ WC_API_PATH }/payment_gateways/cod`
		);
		const codEnabled = codResponse.enabled;

		if ( ! codEnabled ) {
			await restApi.put( `${ WC_API_PATH }/payment_gateways/cod`, {
				enabled: true,
			} );
		}

		// Check id BACS payment is enabled and enable it if it is not
		const bacsResponse = await restApi.get(
			`${ WC_API_PATH }/payment_gateways/bacs`
		);
		const bacsEnabled = bacsResponse.enabled;

		if ( ! bacsEnabled ) {
			await restApi.put( `${ WC_API_PATH }/payment_gateways/bacs`, {
				enabled: true,
			} );
		}

		await page.context().clearCookies();
		await use( page );

		// revert the settings to initial state

		await resetValue( `general/woocommerce_calc_taxes`, calcTaxesState );

		if ( ! codEnabled ) {
			await restApi.put( `${ WC_API_PATH }/payment_gateways/cod`, {
				enabled: codEnabled,
			} );
		}

		if ( ! bacsEnabled ) {
			await restApi.put( `${ WC_API_PATH }/payment_gateways/bacs`, {
				enabled: bacsEnabled,
			} );
		}
	},
	products: async ( { restApi }, use ) => {
		const products = [];

		// Using dec: 0 to avoid small rounding issues
		for ( let i = 0; i < 2; i++ ) {
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					...getFakeProduct( { dec: 0 } ),
					manage_stock: true,
					stock_quantity: 3,
				} )
				.then( ( response ) => {
					products.push( response.data );
				} );
		}

		await use( products );

		for ( const product of products ) {
			await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
				force: true,
			} );
		}
	},
	tax: async ( { restApi }, use ) => {
		let tax;
		await restApi
			.post( `${ WC_API_PATH }/taxes`, {
				country: 'US',
				state: '*',
				cities: '*',
				postcodes: '*',
				rate: '25',
				name: 'US Tax',
				shipping: false,
			} )
			.then( ( r ) => {
				tax = r.data;
			} );

		await use( tax );

		await restApi.delete( `${ WC_API_PATH }/taxes/${ tax.id }`, {
			force: true,
		} );
	},
} );
/* endregion */

/* region tests */
cartPages.forEach( ( { name, slug } ) => {
	test(
		`check ${ name }`,
		{ tag: [ tags.PAYMENTS, tags.SERVICES, tags.HPOS ] },
		async ( { page, products, tax } ) => {
			await test.step( 'empty cart is displayed', async () => {
				page.goto( slug );
				await checkCartContent( slug, page, [], tax );
			} );

			await test.step( 'one product in cart is displayed', async () => {
				await addAProductToCart( page, products[ 0 ].id, 2 );
				await page.goto( slug );
				await checkCartContent(
					slug,
					page,
					[ { data: products[ 0 ], qty: 2 } ],
					tax
				);
			} );

			await test.step( 'can increase quantity', async () => {
				// eslint-disable-next-line playwright/no-conditional-in-test
				if ( isBlocksCart( page ) ) {
					await page
						.getByRole( 'spinbutton', { name: 'Quantity of ' } )
						.fill( '3' );
				} else {
					await page.locator( 'input.qty' ).fill( '3' );
					await page.locator( 'button[name="update_cart"]' ).click();
				}
				await checkCartContent(
					slug,
					page,
					[ { data: products[ 0 ], qty: 3 } ],
					tax
				);
			} );

			await test.step( 'can add another product to cart', async () => {
				await addAProductToCart( page, products[ 1 ].id, 1 );
				await page.goto( slug );
				await checkCartContent(
					slug,
					page,
					[
						{ data: products[ 0 ], qty: 3 },
						{ data: products[ 1 ], qty: 1 },
					],
					tax
				);
			} );

			await test.step( 'can proceed to checkout and return', async () => {
				await page
					.getByRole( 'link', { name: 'Proceed to Checkout' } )
					.click();
				await expect(
					page.getByRole( 'button', { name: 'Place order' } )
				).toBeVisible();
				await page.goBack();
				await checkCartContent(
					slug,
					page,
					[
						{ data: products[ 0 ], qty: 3 },
						{ data: products[ 1 ], qty: 1 },
					],
					tax
				);
			} );

			await test.step( 'can remove the first product', async () => {
				await page
					.getByRole( 'button', {
						name: 'Remove',
					} )
					.first()
					.or(
						page
							.getByRole( 'link', {
								name: 'Remove',
							} )
							.first()
					)
					.click();

				await checkCartContent(
					slug,
					page,
					[ { data: products[ 1 ], qty: 1 } ],
					tax
				);
			} );
			await test.step( 'can remove the last product', async () => {
				await page
					.getByRole( 'button', {
						name: 'Remove',
					} )
					.first()
					.or(
						page
							.getByRole( 'link', {
								name: 'Remove',
							} )
							.first()
					)
					.click();

				await checkCartContent( slug, page, [], tax );
			} );
		}
	);
} );

/* endregion */
