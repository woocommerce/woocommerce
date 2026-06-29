/**
 * External dependencies
 */
import { getBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import '../';

jest.mock( '@woocommerce/settings', () => {
	const originalModule = jest.requireActual( '@woocommerce/settings' );
	return {
		...originalModule,
		getSetting: jest.fn( ( key, defaultValue ) => {
			if ( key === 'attributes' ) {
				return [
					{
						attribute_id: '1',
						attribute_name: 'color',
						attribute_label: 'Color',
					},
				];
			}
			if ( key === 'defaultProductFilterAttribute' ) {
				return {
					attribute_id: '1',
					attribute_name: 'color',
					attribute_label: 'Color',
				};
			}
			return originalModule.getSetting( key, defaultValue );
		} ),
	};
} );

describe( 'Attribute Filter block registration', () => {
	test( 'describes attribute variations without implying sidebar attribute selection', () => {
		const blockType = getBlockType(
			'woocommerce/product-filter-attribute'
		);

		expect( blockType?.variations?.[ 0 ].description ).toBe(
			'Let shoppers filter products by color.'
		);
	} );
} );
