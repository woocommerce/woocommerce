/**
 * Internal dependencies
 */
import { getMatchingTemplateData } from '../utils';
import { TEMPLATES } from '../constants';

describe( 'getMatchingTemplateData', () => {
	it( 'should return template data if a correct match has been found', () => {
		expect(
			getMatchingTemplateData(
				TEMPLATES,
				'taxonomy-product_cat-winter-collection'
			)
		).toBe( TEMPLATES[ 'taxonomy-product_cat' ] );

		expect( getMatchingTemplateData( TEMPLATES, 'single-product' ) ).toBe(
			TEMPLATES[ 'single-product' ]
		);

		expect(
			getMatchingTemplateData( TEMPLATES, 'taxonomy-product_tag' )
		).toBe( TEMPLATES[ 'taxonomy-product_tag' ] );
	} );

	it( 'should return null if given template slug does not match any of the expected options', () => {
		expect(
			getMatchingTemplateData( TEMPLATES, 'slug-does-not-match' )
		).toBe( null );
	} );
} );
