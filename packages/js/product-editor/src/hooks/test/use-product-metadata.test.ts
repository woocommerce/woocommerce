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
} );
