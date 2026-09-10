/**
 * External dependencies
 */
import { getBlockBindingsSource } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { registerTermImageBinding } from '../register-term-image-binding';

describe( 'registerTermImageBinding', () => {
	it( 'registers a client-side resolver for the product category image', () => {
		registerTermImageBinding();

		const source = getBlockBindingsSource( 'woocommerce/term-image' );
		expect( source ).toEqual(
			expect.objectContaining( {
				usesContext: expect.arrayContaining( [
					'termId',
					'termTaxonomy',
					'woocommerce/termImageId',
					'woocommerce/termImageUrl',
				] ),
				getValues: expect.any( Function ),
			} )
		);

		expect(
			source?.getValues?.( {
				context: {
					'woocommerce/termImageId': 42,
					'woocommerce/termImageUrl':
						'https://example.com/category.jpg',
				},
			} )
		).toEqual( {
			id: 42,
			url: 'https://example.com/category.jpg',
		} );
		expect(
			source?.getValues?.( {
				context: {},
				bindings: { url: { args: { noPlaceholder: true } } },
			} )
		).toEqual( { id: undefined, url: '' } );
		const getMedia = jest.fn().mockReturnValue( {
			media_details: {
				sizes: {
					large: { source_url: 'https://example.com/large.jpg' },
				},
			},
		} );
		expect(
			source?.getValues?.( {
				context: { 'woocommerce/termImageId': 42 },
				bindings: {
					url: { args: { attachmentId: 99, size: 'large' } },
				},
				select: () => ( { getMedia } ),
			} )
		).toEqual( { id: 99, url: 'https://example.com/large.jpg' } );
		expect( getMedia ).toHaveBeenCalledWith( 99 );
	} );
} );
