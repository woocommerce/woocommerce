/**
 * @format
 */

/**
 * Internal dependencies
 */
import { createSimpleProduct } from '../../utils/components';
import { CustomerFlow, StoreOwnerFlow } from '../../utils/flows';
import { uiUnblocked } from '../../utils';

let simplePostIdValue;
const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

describe( 'Single Product Page', () => {
	it( 'should be able to create simple product', async () => {
		await StoreOwnerFlow.login();
		simplePostIdValue = await createSimpleProduct();
		await StoreOwnerFlow.logout();
	} );

	it( 'should be able to add simple product to the cart', async () => {
		// Add 5 simple products to cart
		await CustomerFlow.goToProduct( simplePostIdValue );
		await expect( page ).toFill( 'div.quantity input.qty', '5' );
		await CustomerFlow.addToCart();
		await expect( page ).toMatchElement( '.woocommerce-message', { text: 'have been added to your cart.' } );

		// Verify cart contents
		await CustomerFlow.goToCart();
		await CustomerFlow.productIsInCart( simpleProductName, 5 );

		// Remove items from cart
		await CustomerFlow.removeFromCart( simpleProductName );
		await uiUnblocked();
		await expect( page ).toMatchElement( '.cart-empty', { text: 'Your cart is currently empty.' } );
	} );
} );
