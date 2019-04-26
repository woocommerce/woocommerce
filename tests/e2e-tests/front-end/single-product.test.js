/**
 * @format
 */

/** 
 * Internal dependencies
 */
const { CustomerFlow } = require( '../utils' );

describe( 'Single Product Page', () => {
    beforeAll( async () => {
		await jestPuppeteer.resetContext();
    } );

    it( 'should be able to add simple products to the cart', async () => {
        // Add 5 T-shirts to cart
        await CustomerFlow.goToProduct( 't-shirt' );
        await expect( page ).toFill( 'div.quantity input.qty', '5' );
        await CustomerFlow.addToCart();
        await expect( page ).toMatchElement( '.woocommerce-message', { text: 'have been added to your cart.' } );

        // Verify cart contents
        await CustomerFlow.goToCart();
        await expect( page ).toMatchElement( '.cart_item .product-name a', { text: 'T-Shirt' } );
        await expect( page ).toMatchElement( '.cart_item .product-quantity input[value="5"]' );
    } );
    
    it( 'should be able to add variation products to the cart', async () => {
		// Add a blue logo hoodie to cart
        await CustomerFlow.goToProduct( 'hoodie' );
        await expect( page ).toSelect( '#pa_color', 'Blue' );
        await expect( page ).toSelect( '#logo', 'Yes' );
        await CustomerFlow.addToCart();
        await expect( page ).toMatchElement( '.woocommerce-message', { text: 'has been added to your cart.' } );

        // Add a green, no logo hoodie to cart
        await CustomerFlow.goToProduct( 'hoodie' );
        await expect( page ).toSelect( '#pa_color', 'Green' );
        await expect( page ).toSelect( '#logo', 'No' );
        await CustomerFlow.addToCart();
        await expect( page ).toMatchElement( '.woocommerce-message', { text: 'has been added to your cart.' } );

        // Verify cart contents
        await CustomerFlow.goToCart();
        await expect( page ).toMatchElement( '.cart_item .product-name a', { text: 'Hoodie - Blue, Yes' } );
        await expect( page ).toMatchElement( '.cart_item .product-name a', { text: 'Hoodie - Green, No' } );
	} );
} );
