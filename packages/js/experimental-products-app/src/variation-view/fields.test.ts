/**
 * Internal dependencies
 */
import { variationFields } from './fields';

jest.mock( '@woocommerce/settings', () => ( {
	CURRENCY: {
		code: 'USD',
		symbol: '$',
		symbolPosition: 'left',
		precision: 2,
	},
	getSetting: jest.fn(),
} ) );

describe( 'variationFields', () => {
	it( 'includes the variation options field and reused product fields', () => {
		const fieldIds = variationFields.map( ( field ) => field.id );

		expect( fieldIds ).toEqual(
			expect.arrayContaining( [
				'variation_options',
				'name',
				'sku',
				'price',
				'regular_price',
				'sale_price',
				'stock',
				'stock_quantity',
				'manage_stock',
				'product_status',
				'images',
				'downloadable',
				'weight',
				'length',
				'width',
				'height',
				'shipping_class',
				'tax_status',
			] )
		);
	} );
} );
