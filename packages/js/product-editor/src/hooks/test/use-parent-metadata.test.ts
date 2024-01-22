/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import { useParentMetadata } from '../use-parent-metadata';

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

describe( 'useParentMetadata', () => {
	it( 'should return parent metadata as an object', async () => {
		const test = renderHook( () => useParentMetadata( 123 ) ).result
			.current;
		expect( test ).toEqual( {
			field1: 'value1',
			field2: 'value2',
		} );
	} );
} );
