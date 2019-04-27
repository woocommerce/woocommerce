/**
 * @format
 */

/** 
 * Internal dependencies
 */
const { CustomerFlow, uiUnblocked } = require( '../utils' );

describe( 'Cart page', () => {
    beforeAll( async () => {
		await jestPuppeteer.resetContext();
    } );

    it( 'should displays no item in the cart', async () => {
        await CustomerFlow.goToCart();
        await expect( page ).toMatchElement( '.cart-empty', { text: 'Your cart is currently empty.' } );
    } );
    
    it( 'should adds the product to the cart when "Add to cart" is clicked', async () => {
        await CustomerFlow.goToShop();
        await CustomerFlow.addToCartFromShopPage( 'Album' );
        await CustomerFlow.addToCartFromShopPage( 'Polo' );

        await CustomerFlow.goToCart();
        await CustomerFlow.productIsInCart( 'Album' );
        await CustomerFlow.productIsInCart( 'Polo' );
    } );
    
    it( 'should increases item qty when "Add to cart" of the same product is clicked', async () => {
		await CustomerFlow.goToShop();
        await CustomerFlow.addToCartFromShopPage( 'Album' );

        await CustomerFlow.goToCart();
        await CustomerFlow.productIsInCart( 'Album', 2 );
        await CustomerFlow.productIsInCart( 'Polo', 1 );
    } );
    
    it( 'should updates qty when updated via qty input', async () => {
        await CustomerFlow.goToCart();
        await CustomerFlow.setCartQuantity( 'Album', 4 );
        await CustomerFlow.setCartQuantity( 'Polo', 3 );
        await expect( page ).toClick( 'button', { text: 'Update cart' } );
        await uiUnblocked();

        await CustomerFlow.productIsInCart( 'Album', 4 );
        await CustomerFlow.productIsInCart( 'Polo', 3 );
    } );
    
    it( 'should remove the item from the cart when remove is clicked', async () => {
		await CustomerFlow.goToCart();
        await CustomerFlow.removeCartProduct( 'Album' );
        await uiUnblocked();
        await CustomerFlow.removeCartProduct( 'Polo' );
        await uiUnblocked();

        await expect( page ).toMatchElement( '.cart-empty', { text: 'Your cart is currently empty.' } );
    } );
    
    it( 'should update subtotal in cart totals when adding product to the cart', async () => {
        await CustomerFlow.goToShop();
        await CustomerFlow.addToCartFromShopPage( 'Album' );

        await CustomerFlow.goToCart();
        await CustomerFlow.productIsInCart( 'Album', 1 );
        await expect( page ).toMatchElement( '.cart-subtotal .amount', { text: '$15.00' } );

        await CustomerFlow.setCartQuantity( 'Album', 2 );
        await expect( page ).toClick( 'button', { text: 'Update cart' } );
        await uiUnblocked();

        await expect( page ).toMatchElement( '.cart-subtotal .amount', { text: '$30.00' } );
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
