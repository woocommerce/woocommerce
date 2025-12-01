/**
 * External dependencies
 */
import { expect } from '@playwright/test';

function formatAmount( amount ) {
	return parseFloat( amount ).toLocaleString( 'en-US', {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2,
	} );
}

/**
 * Verifies the contents of a classic cart.
 *
 * @param {Object} page          - The Playwright page object.
 * @param {Array}  products      - An array of objects in the format { data: { name: '', price: '', }, qty: quantity } expected to be in the cart.
 * @param {number} expectedTotal - The expected total amount in the cart.
 */
export async function checkCartContentInClassicCart(
	page,
	products,
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

/**
 * Verifies the contents of a blocks cart.
 *
 * @param {Object} page          - The Playwright page object.
 * @param {Array}  products      - An array of objects in the format { data: { name: '', price: '', }, qty: quantity } expected to be in the cart.
 * @param {number} expectedTotal - The expected total amount in the cart.
 */
export async function checkCartContentInBlocksCart(
	page,
	products,
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

/**
 * Verifies the contents of a cart.
 *
 * @param {boolean} isClassicCart - Indicates whether the cart is a classic cart.
 * @param {Object}  page          - The Playwright page object.
 * @param {Array}   products      - An array of objects in the format { data: { name: 'Product name', price: '12', }, qty: quantity } expected to be in the cart.
 * @param {Object}  tax           - The tax object containing the tax rate. Expected format: { rate: '0.00' }
 */
export async function checkCartContent( isClassicCart, page, products, tax ) {
	if ( products.length === 0 ) {
		await expect(
			page.locator( 'main' ).getByText( 'Your cart is currently empty' )
		).toBeVisible();
		return;
	}

	const expectedTotal = products.reduce( ( total, product ) => {
		const taxRate = parseFloat( tax.rate ) || 0;
		return (
			total +
			parseFloat( product.data.price ) *
				product.qty *
				( 1 + taxRate / 100 )
		);
	}, 0 );

	if ( isClassicCart ) {
		await checkCartContentInClassicCart( page, products, expectedTotal );
	} else {
		await checkCartContentInBlocksCart( page, products, expectedTotal );
	}
}
