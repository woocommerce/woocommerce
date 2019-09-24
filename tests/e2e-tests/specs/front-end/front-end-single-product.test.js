/**
 * @format
 */

/**
 * External dependencies
 */
import { activatePlugin } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { createSimpleProduct, createVariableProduct } from "../../utils/components";
import { CustomerFlow, StoreOwnerFlow } from "../../utils/flows";
import { uiUnblocked } from '../../utils';

describe( 'Single Product Page', () => {
	beforeAll( async () => {
		await activatePlugin( 'woocommerce' );
		await createSimpleProduct();
		await createVariableProduct();
		await StoreOwnerFlow.logout();
	} );

	it( 'should be able to add simple products to the cart', async () => {
		// Add 5 simple products to cart
		await CustomerFlow.goToProduct( 'simple-product' );
		await expect( page ).toFill( 'div.quantity input.qty', '5' );
		await CustomerFlow.addToCart();
		await expect( page ).toMatchElement( '.woocommerce-message', { text: 'have been added to your cart.' } );

		// Verify cart contents
		await CustomerFlow.goToCart();
		await CustomerFlow.productIsInCart( 'Simple product', 5 );

		// Remove items from cart
		await CustomerFlow.removeFromCart( 'Simple product' );
		await uiUnblocked();
		await expect( page ).toMatchElement( '.cart-empty', { text: 'Your cart is currently empty.' } );
	} );

	it( 'should be able to add variation products to the cart', async () => {
		// Add a product with one set of variations to cart
		await CustomerFlow.goToProduct( 'variable-product-with-two-variations' );
		await expect( page ).toSelect( '#attr-1', 'val1' );
		await expect( page ).toSelect( '#attr-2', 'val1' );
		await expect( page ).toSelect( '#attr-3', 'val1' );
		await CustomerFlow.addToCart();
		await expect( page ).toMatchElement( '.woocommerce-message', { text: 'has been added to your cart.' } );

		// Verify cart contents
		await CustomerFlow.goToCart();
		await CustomerFlow.productIsInCart( 'Variable Product with Two Variations' );

		// Remove items from cart
		await CustomerFlow.removeFromCart( 'Variable Product with Two Variations' );
		await uiUnblocked();
		await expect( page ).toMatchElement( '.cart-empty', { text: 'Your cart is currently empty.' } );
	} );
} );
