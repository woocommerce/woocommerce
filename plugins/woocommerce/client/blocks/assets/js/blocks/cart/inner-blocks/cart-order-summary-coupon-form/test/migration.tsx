/**
 * External dependencies
 */
import { parse, serialize } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import '../index';
import '../../cart-order-summary-coupon-form-input';
import '../../cart-order-summary-coupon-form-button';

describe( 'Coupon form migration', () => {
	it( 'upgrades a saved form and preserves its attributes', () => {
		const [ block ] = parse(
			`<!-- wp:woocommerce/cart-order-summary-coupon-form-block {"className":"custom-form","lock":{"move":true,"remove":false}} -->
<div class="wp-block-woocommerce-cart-order-summary-coupon-form-block custom-form"></div>
<!-- /wp:woocommerce/cart-order-summary-coupon-form-block -->`
		);
		expect( block.isValid ).toBe( true );
		expect( block.attributes.className ).toBe( 'custom-form' );
		expect( block.attributes.lock ).toEqual( {
			move: true,
			remove: false,
		} );
		expect( block.innerBlocks.map( ( child ) => child.name ) ).toEqual( [
			'woocommerce/cart-order-summary-coupon-form-input-block',
			'woocommerce/cart-order-summary-coupon-form-button-block',
		] );
		const [ reparsed ] = parse( serialize( [ block ] ) );
		expect( reparsed.isValid ).toBe( true );
		expect( reparsed.innerBlocks ).toHaveLength( 2 );
		expect( reparsed.innerBlocks.every( ( child ) => child.isValid ) ).toBe(
			true
		);
	} );

	it( 'preserves existing inner blocks and their button styles', () => {
		const [ migrated ] = parse(
			`<!-- wp:woocommerce/cart-order-summary-coupon-form-block -->
<div class="wp-block-woocommerce-cart-order-summary-coupon-form-block"></div>
<!-- /wp:woocommerce/cart-order-summary-coupon-form-block -->`
		);
		migrated.innerBlocks[ 1 ].attributes.style = {
			color: { background: '#123456' },
		};
		const [ block ] = parse( serialize( [ migrated ] ) );
		expect( block.isValid ).toBe( true );
		expect( block.innerBlocks ).toHaveLength( 2 );
		expect( block.innerBlocks[ 1 ].attributes.style ).toEqual( {
			color: { background: '#123456' },
		} );
	} );
} );
