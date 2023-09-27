/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useWooEntityProp from '../use-woo-entity-prop';

describe( 'useWooEntityProp', () => {
	it( '', async () => {
		const mockedGuy = jest.fn();
		jest.mock( '@wordpress/core-data', () => ( {
			useEntityProp: jest.fn().mockReturnValue( [ '', mockedGuy ] ),
		} ) );
		const [ value, setValue ] = renderHook( () =>
			useWooEntityProp( 'meta_data.email' )
		).result.current;
		expect( setValue ).to.be( mockedGuy );
	} );
} );
