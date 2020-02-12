/**
 * @format
 */

/**
 * Internal dependencies
 */
import { createSimpleProduct } from '../../utils/components';
import { CustomerFlow, StoreOwnerFlow } from '../../utils/flows';
import { uiUnblocked } from '../../utils';

describe( 'Cart Page', () => {
	beforeAll( async () => {
		await StoreOwnerFlow.login();
		await createSimpleProduct();
		await StoreOwnerFlow.logout();
	} );

	it( 'should display no item in the cart', async () => {
		await CustomerFlow.goToCart();
		await expect( page ).toMatchElement( '.cart-empty', { text: 'Your cart is currently empty.' } );
	} );

	it( 'should add the product to the cart when "Add to cart" is clicked', async () => {
		await CustomerFlow.goToShop();
		await CustomerFlow.addToCartFromShopPage( 'Simple product' );

		await CustomerFlow.goToCart();
		await CustomerFlow.productIsInCart( 'Simple product' );
	} );

	it( 'should increase item qty when "Add to cart" of the same product is clicked', async () => {
		await CustomerFlow.goToShop();
		await CustomerFlow.addToCartFromShopPage( 'Simple product' );

		await CustomerFlow.goToCart();
		await CustomerFlow.productIsInCart( 'Simple product', 2 );
	} );

	it( 'should update qty when updated via qty input', async () => {
		await CustomerFlow.goToCart();
		await CustomerFlow.setCartQuantity( 'Simple product', 4 );
		await expect( page ).toClick( 'button', { text: 'Update cart' } );
		await uiUnblocked();

		await CustomerFlow.productIsInCart( 'Simple product', 4 );
	} );

	it( 'should remove the item from the cart when remove is clicked', async () => {
		await CustomerFlow.goToCart();
		await CustomerFlow.removeFromCart( 'Simple product' );
		await uiUnblocked();

		await expect( page ).toMatchElement( '.cart-empty', { text: 'Your cart is currently empty.' } );
	} );

	it( 'should update subtotal in cart totals when adding product to the cart', async () => {
		await CustomerFlow.goToShop();
		await CustomerFlow.addToCartFromShopPage( 'Simple product' );

		await CustomerFlow.goToCart();
		await CustomerFlow.productIsInCart( 'Simple product', 1 );
		await expect( page ).toMatchElement( '.cart-subtotal .amount', { text: '$9.99' } );

		await CustomerFlow.setCartQuantity( 'Simple product', 2 );
		await expect( page ).toClick( 'button', { text: 'Update cart' } );
		await uiUnblocked();

		await expect( page ).toMatchElement( '.cart-subtotal .amount', { text: '$19.98' } );
	} );

	it( 'should go to the checkout page when "Proceed to Checkout" is clicked', async () => {
		await CustomerFlow.goToCart();
		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			expect( page ).toClick( '.checkout-button', { text: 'Proceed to checkout' } ),
		] );

		await expect( page ).toMatchElement( '#order_review' );
	} );
} );
