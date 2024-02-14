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
	useEntityId: jest.fn().mockReturnValue( 123 ),
} ) );

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn().mockImplementation( ( callback ) => {
		return callback(
			jest.fn().mockReturnValue( {
				hasFinishedResolution: jest.fn().mockReturnValue( true ),
				getEditedEntityRecord: () => ( {
					meta_data: [
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
				} ),
			} )
		);
	} ),
	useDispatch: jest.fn().mockImplementation( () => {
		return {
			editEntityRecord: mockFnMetadataProp,
		};
	} ),
} ) );

describe( 'useProductMetadata', () => {
	it( 'should update all the metadata with new values and not replace existing fields', async () => {
		const { update } = renderHook( () => useProductMetadata() ).result
			.current;
		update( [
			{
				key: 'field1',
				value: 'value2',
			},
			{
				key: 'field2',
				value: 'value2',
			},
		] );
		expect( mockFnMetadataProp ).toHaveBeenCalledWith(
			'postType',
			'product',
			123,
			{
				meta_data: [
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
				],
			}
		);
	} );
	it( 'should return the metadata as an object for easy readings', async () => {
		const { metadata } = renderHook( () =>
			useProductMetadata( { postType: 'product', id: 123 } )
		).result.current;
		expect( metadata ).toEqual( {
			field1: 'value1',
			field2: 'value1',
			existing_field: 'value1',
		} );
	} );
	it( 'should return isLoading as false when the resolution is finished', async () => {
		const { isLoading } = renderHook( () =>
			useProductMetadata( { postType: 'product', id: 123 } )
		).result.current;
		expect( isLoading ).toEqual( false );
	} );
} );
