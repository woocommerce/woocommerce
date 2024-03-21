/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import productEntitySourceHandler from '..';

let mockState;

jest.mock( '@wordpress/core-data', () => ( {
	useEntityProp: jest.fn().mockImplementation( ( kind, name, key ) => {
		return mockState?.[ key ] ? mockState[ key ] : [];
	} ),
} ) );

describe( 'useSource', () => {
	let blockInstance;

	mockState = {
		external_property_name: [ 'External source property Value' ],
	};

	beforeEach( () => {
		blockInstance = {
			name: 'woocommerce/block-with-entity',
			attributes: {
				prop: 'value',
			},
			className: 'wp-block-woocommerce-block-with-entity',
			context: {},
			clientId: '<client-id-instance>',
			isSelected: false,
			setAttributes: jest.fn(),
		};
	} );

	it( 'throws an error if sourceArgs is undefined', () => {
		const { useSource } = productEntitySourceHandler;
		const { result } = renderHook( () =>
			useSource( blockInstance, undefined )
		);

		expect( result.error ).toEqual(
			new Error( 'The "args" argument is required.' )
		);
	} );

	it( 'throws an error if prop in sourceArgs is undefined', () => {
		const { useSource } = productEntitySourceHandler;
		const { result } = renderHook( () =>
			useSource( blockInstance, { prop: undefined } )
		);

		expect( result.error ).toEqual(
			new Error( 'The "prop" argument is required.' )
		);
	} );

	it( 'return the value of the product entity property', () => {
		const { useSource } = productEntitySourceHandler;
		const { result } = renderHook( () =>
			useSource( blockInstance, {
				prop: 'external_property_name',
			} )
		);

		expect( result.current.value ).toEqual(
			'External source property Value'
		);
	} );
} );
