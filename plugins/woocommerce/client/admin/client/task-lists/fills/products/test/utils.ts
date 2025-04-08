/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { getProductTypes, getSurfacedProductTypeKeys } from '../utils';
import {
	productTypes,
	onboardingProductTypesToSurfaced,
	SETUP_TASKLIST_PRODUCT_TYPES_FILTER,
} from '../constants';

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

	it( 'should return the product types with extras from filter and excluded items', () => {
		const customProduct = {
			key: 'custom-product',
			title: 'Custom product',
			content: 'A custom product',
			before: '',
			after: '',
		};

		addFilter(
			SETUP_TASKLIST_PRODUCT_TYPES_FILTER,
			'wc/admin/tests',
			( filteredProductTypes ) => {
				return [ ...filteredProductTypes, customProduct ];
			}
		);

		expect(
			getProductTypes( { exclude: [ 'external', 'digital' ] } ).map(
				( p ) => p.key
			)
		).toEqual( [ 'physical', 'variable', 'grouped', 'custom-product' ] );
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
