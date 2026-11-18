/**
 * Internal dependencies
 */
import checkoutActions from '../../checkout/inner-blocks/checkout-actions-block/block.json';
import checkoutCoupon from '../../checkout/inner-blocks/checkout-order-summary-coupon-form/block.json';
import cartCoupon from '../../cart/inner-blocks/cart-order-summary-coupon-form/block.json';
import proceedToCheckout from '../../cart/inner-blocks/proceed-to-checkout-block/block.json';

describe.each( [
	checkoutActions,
	checkoutCoupon,
	cartCoupon,
	proceedToCheckout,
] )( '$name button style controls', ( { supports } ) => {
	it( 'shows the primary style controls by default', () => {
		expect( supports.color.__experimentalDefaultControls ).toEqual( {
			background: true,
			text: true,
		} );
		expect(
			supports.typography.__experimentalDefaultControls.fontSize
		).toBe( true );
		expect( supports.typography.textAlign ).toBe( true );
		expect(
			supports.typography.__experimentalDefaultControls.textAlign
		).toBe( true );
		expect(
			supports.__experimentalBorder.__experimentalDefaultControls
		).toEqual( { color: true, radius: true, style: true, width: true } );
		expect( supports.spacing.__experimentalDefaultControls.padding ).toBe(
			true
		);
	} );

	it( 'does not serialize button styles onto the block wrapper', () => {
		for ( const feature of [
			supports.color,
			supports.typography,
			supports.__experimentalBorder,
			supports.spacing,
		] ) {
			expect( feature.__experimentalSkipSerialization ).toBe( true );
		}
	} );
} );
