/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useProductMetadata from '../use-product-metadata';

const mockFnMetadataProp = jest.fn();
jest.mock( '@wordpress/core-data', () => ( {
	useEntityProp: jest.fn().mockImplementation( () => {
		return [
			[
				{
					key: 'field1',
					value: 'value1',
				},
				{
					key: 'field2',
					value: 'value1',
				},
				{
					key: 'existing_field',
					value: 'value1',
				},
			],
			mockFnMetadataProp,
		];
	} ),
} ) );

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn().mockImplementation( ( callback ) => {
		return callback(
			jest.fn().mockReturnValue( {
				getEditedEntityRecord: () => ( {
					meta_data: [
						{
							key: 'field1',
							value: 'value1',
						},
						{
							key: 'field2',
							value: 'value2',
						},
					],
				} ),
			} )
		);
	} ),
} ) );

describe( 'useProductMetadata', () => {
	it( 'should update all the metadata with new values and not replace existing fields', async () => {
		const { updateMetadata } = renderHook( () => useProductMetadata() )
			.result.current;
		updateMetadata( [
			{
				key: 'field1',
				value: 'value2',
			},
			{
				key: 'field2',
				value: 'value2',
			},
		] );
		expect( mockFnMetadataProp ).toHaveBeenCalledWith( [
			{
				key: 'existing_field',
				value: 'value1',
			},
			{
				key: 'field1',
				value: 'value2',
			},
			{
				key: 'field2',
				value: 'value2',
			},
		] );
	} );
	it( 'should return the metadata as an object for easy readings', async () => {
		const { metadata } = renderHook( () =>
			useProductMetadata( 'product-variation', 123, 'product' )
		).result.current;
		expect( metadata ).toEqual( {
			field1: 'value1',
			field2: 'value2',
		} );
	} );
} );
