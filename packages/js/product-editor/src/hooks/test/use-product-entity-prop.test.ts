/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useProductEntityProp from '../use-product-entity-prop';

const mockFnMetadataProp = jest.fn();
const mockFnRegularProp = jest.fn();
jest.mock( '@wordpress/core-data', () => ( {
	useEntityProp: jest
		.fn()
		.mockImplementation( ( _postType, _product, property ) => {
			if ( property === 'meta_data' ) {
				return [ [], mockFnMetadataProp ];
			}
			return [ '', mockFnRegularProp ];
		} ),
} ) );

describe( 'useProductEntityProp', () => {
	beforeEach( () => {
		mockFnMetadataProp.mockClear();
		mockFnRegularProp.mockClear();
	} );
	it( 'should correctly set meta_data property with key and value properties', async () => {
		const [ email, setEmail ] = renderHook( () =>
			useProductEntityProp( 'meta_data.email', { fallbackValue: '' } )
		).result.current;
		expect( email ).toBe( '' );
		setEmail( 'someone@wordpress.com' );
		expect( mockFnMetadataProp ).toHaveBeenCalledWith( [
			{
				key: 'email',
				value: 'someone@wordpress.com',
			},
		] );
	} );
	it( 'should call useEntityProp function passing the new value', async () => {
		const [ value, setValue ] = renderHook( () =>
			useProductEntityProp( 'regular_prop', { fallbackValue: '' } )
		).result.current;
		expect( value ).toBe( '' );
		setValue( 'someone@wordpress.com' );
		expect( mockFnRegularProp ).toHaveBeenCalledWith(
			'someone@wordpress.com'
		);
	} );
} );
