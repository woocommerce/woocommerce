/**
 * Internal dependencies
 */
import { TEMPLATES } from '../constants';
import { getTemplateDetailsBySlug } from '../utils';

describe( 'getTemplateDetailsBySlug', function () {
	it( 'should return single-product object when given an exact match', () => {
		expect(
			getTemplateDetailsBySlug( 'single-product', TEMPLATES )
		).toStrictEqual( TEMPLATES[ 'single-product' ] );
	} );

	it( 'should return single-product object when given a partial match', () => {
		expect(
			getTemplateDetailsBySlug( 'single-product-hoodie', TEMPLATES )
		).toStrictEqual( TEMPLATES[ 'single-product' ] );
	} );

	it( 'should return taxonomy-product object when given a partial match', () => {
		expect(
			getTemplateDetailsBySlug( 'taxonomy-product_tag', TEMPLATES )
		).toStrictEqual( TEMPLATES[ 'taxonomy-product_tag' ] );
	} );

	it( 'should return taxonomy-product object when given an exact match', () => {
		expect(
			getTemplateDetailsBySlug( 'taxonomy-product_brands', TEMPLATES )
		).toStrictEqual( TEMPLATES[ 'taxonomy-product' ] );
	} );

	it( 'should return null object when given an incorrect match', () => {
		expect( getTemplateDetailsBySlug( 'void', TEMPLATES ) ).toBeNull();
	} );
} );
