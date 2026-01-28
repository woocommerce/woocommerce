/**
 * External dependencies
 */
import { expect } from '@playwright/test';
import type { Page, Locator } from '@playwright/test';

/**
 * Product interface for cart verification.
 */
interface CartProduct {
	data: {
		name: string;
		price: string;
	};
	qty: number;
}

/**
 * Tax interface for cart calculations.
 */
interface Tax {
	rate: string;
}

/**
 * Format an amount to US locale with 2 decimal places.
 *
 * @param amount - The amount to format.
 * @return Formatted amount string.
 */
function formatAmount( amount: number | string ): string {
	return parseFloat( String( amount ) ).toLocaleString( 'en-US', {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2,
	} );
}

/**
 * Verifies the contents of a classic cart.
 *
 * @param page          - The Playwright page object.
 * @param products      - An array of objects in the format { data: { name: '', price: '', }, qty: quantity } expected to be in the cart.
 * @param expectedTotal - The expected total amount in the cart.
 */
export async function checkCartContentInClassicCart(
	page: Page,
	products: CartProduct[],
	expectedTotal: number
): Promise< void > {
	for ( const product of products ) {
		const row: Locator = page
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
 * @param page          - The Playwright page object.
 * @param products      - An array of objects in the format { data: { name: '', price: '', }, qty: quantity } expected to be in the cart.
 * @param expectedTotal - The expected total amount in the cart.
 */
export async function checkCartContentInBlocksCart(
	page: Page,
	products: CartProduct[],
	expectedTotal: number
): Promise< void > {
	for ( const product of products ) {
		const row: Locator = page
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
 * @param isClassicCart - Indicates whether the cart is a classic cart.
 * @param page          - The Playwright page object.
 * @param products      - An array of objects in the format { data: { name: 'Product name', price: '12', }, qty: quantity } expected to be in the cart.
 * @param tax           - The tax object containing the tax rate. Expected format: { rate: '0.00' }
 */
export async function checkCartContent(
	isClassicCart: boolean,
	page: Page,
	products: CartProduct[],
	tax: Tax
): Promise< void > {
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
