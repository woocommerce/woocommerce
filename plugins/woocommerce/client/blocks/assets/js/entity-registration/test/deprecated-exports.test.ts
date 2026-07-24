/**
 * External dependencies
 */
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import {
	isExternalProduct,
	isProductResponseItem,
	useProduct,
} from '../deprecated-exports';
import {
	isExternalProduct as isExternalProductInternal,
	isProductResponseItem as isProductResponseItemInternal,
	useProduct as useProductInternal,
} from '../../entities';

jest.mock( '@wordpress/deprecated', () => jest.fn() );
jest.mock( '../../entities', () => ( {
	isExternalProduct: jest.fn(),
	isProductResponseItem: jest.fn(),
	useProduct: jest.fn(),
} ) );

describe( 'deprecated wc.wcEntities exports', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it.each( [
		[ 'useProduct', useProduct, useProductInternal, 123 ],
		[
			'isExternalProduct',
			isExternalProduct,
			isExternalProductInternal,
			{ id: 123 },
		],
		[
			'isProductResponseItem',
			isProductResponseItem,
			isProductResponseItemInternal,
			{ id: 123 },
		],
	] )(
		'warns once and delegates %s',
		( functionName, compatibilityFunction, internalFunction, argument ) => {
			compatibilityFunction( argument as never );
			compatibilityFunction( argument as never );

			expect( deprecated ).toHaveBeenCalledTimes( 1 );
			expect( deprecated ).toHaveBeenCalledWith(
				`wc.wcEntities.${ functionName }()`,
				{
					since: '11.1.0',
					alternative: `${ functionName } from @woocommerce/entities`,
					plugin: 'WooCommerce',
					hint: 'The wc.wcEntities global is deprecated and will be removed in a future release.',
				}
			);
			expect( internalFunction ).toHaveBeenCalledTimes( 2 );
			expect( internalFunction ).toHaveBeenCalledWith( argument );
		}
	);
} );
