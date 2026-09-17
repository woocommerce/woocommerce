/**
 * Internal dependencies
 */
import { migrateAttributesToColorPanel } from '../data';

const mockAttributes = {
	miniCartIcon: 'cart',
	addToCartBehaviour: 'inline',
	hasHiddenPrice: false,
	cartAndCheckoutRenderStyle: true,
	priceColorValue: '#000000',
	iconColorValue: '#ffffff',
	productCountColorValue: '#ff0000',
};
describe( 'migrateAttributesToColorPanel tests', () => {
	test( 'it correctly migrates attributes to color panel', () => {
		const migratedAttributes =
			migrateAttributesToColorPanel( mockAttributes );
		expect( migratedAttributes ).toEqual( {
			miniCartIcon: 'cart',
			addToCartBehaviour: 'inline',
			hasHiddenPrice: false,
			cartAndCheckoutRenderStyle: true,
			priceColor: {
				color: '#000000',
			},
			iconColor: {
				color: '#ffffff',
			},
			productCountColor: {
				color: '#ff0000',
			},
		} );
	} );
} );
