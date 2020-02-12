/**
 * @format
 */

/**
 * Internal dependencies
 */
import { createVariableProduct } from '../../utils/components';
import { CustomerFlow, StoreOwnerFlow } from '../../utils/flows';
import { uiUnblocked } from '../../utils';

let variablePostIdValue;
const config = require( 'config' );
const variableProductName = config.get( 'products.variable.name' );

describe( 'Variable Product Page', () => {
	it( 'should be able to create variable product', async () => {
		await StoreOwnerFlow.login();
		variablePostIdValue = await createVariableProduct();
		await StoreOwnerFlow.logout();
	} );

	it( 'should be able to add variation product to the cart', async () => {
		// Add a product with one set of variations to cart
		await CustomerFlow.goToProduct( variablePostIdValue );
		await expect( page ).toSelect( '#attr-1', 'val1' );
		await expect( page ).toSelect( '#attr-2', 'val1' );
		await expect( page ).toSelect( '#attr-3', 'val1' );
		await CustomerFlow.addToCart();
		await expect( page ).toMatchElement( '.woocommerce-message', { text: 'has been added to your cart.' } );

		// Verify cart contents
		await CustomerFlow.goToCart();
		await CustomerFlow.productIsInCart( variableProductName );

		// Remove items from cart
		await CustomerFlow.removeFromCart( variableProductName );
		await uiUnblocked();
		await expect( page ).toMatchElement( '.cart-empty', { text: 'Your cart is currently empty.' } );
	} );
} );
