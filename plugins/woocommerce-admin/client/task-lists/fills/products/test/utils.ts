/**
 * Internal dependencies
 */
import { getProductTypes, getSurfacedProductTypeKeys } from '../utils';
import { productTypes, onboardingProductTypesToSurfaced } from '../constants';

describe( 'getProductTypes', () => {
	it( 'should return the product types', () => {
		expect( getProductTypes() ).toEqual( productTypes );
	} );

	it( 'should return the product types without excluded items', () => {
		expect(
			getProductTypes( { exclude: [ 'external', 'digital' ] } ).map(
				( p ) => p.key
			)
		).toEqual( [ 'physical', 'variable', 'grouped' ] );
	} );
} );

describe( 'getSurfacedProductTypeKeys', () => {
	test.each( [
		{
			selectedTypes: [ 'physical' ],
			expected: onboardingProductTypesToSurfaced.physical,
		},
		{
			selectedTypes: [ 'physical', 'downloads' ],
			expected: onboardingProductTypesToSurfaced[ 'downloads,physical' ],
		},
		{
			selectedTypes: [ 'physical', 'downloads', 'membership', 'booking' ],
			expected: onboardingProductTypesToSurfaced[ 'downloads,physical' ],
		},
		{
			selectedTypes: [],
			expected: onboardingProductTypesToSurfaced.physical,
		},
	] )(
		'should return expected surfaced product keys when onboarding product type contains $selected',
		( { selectedTypes, expected } ) => {
			expect( getSurfacedProductTypeKeys( selectedTypes ) ).toEqual(
				expected
			);
		}
	);
} );
