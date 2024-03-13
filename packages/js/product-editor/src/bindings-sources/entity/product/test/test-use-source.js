/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import productEntitySourceHandler from '..';

jest.mock( '@wordpress/core-data', () => ( {
	useEntityProp: jest.fn(),
} ) );

describe( 'useSource', () => {
	let blockInstance;

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
} );
