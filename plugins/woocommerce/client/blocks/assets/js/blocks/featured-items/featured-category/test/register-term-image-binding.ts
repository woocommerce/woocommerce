/**
 * Internal dependencies
 */
import { registerTermImageBinding } from '../register-term-image-binding';

describe( 'registerTermImageBinding', () => {
	it( 'registers a client-side resolver for the product category image', () => {
		const registerBlockBindingsSource = jest.fn();
		Object.defineProperty( window, 'wp', {
			configurable: true,
			value: {
				blocks: { registerBlockBindingsSource },
			},
		} );

		registerTermImageBinding();

		expect( registerBlockBindingsSource ).toHaveBeenCalledWith(
			expect.objectContaining( {
				name: 'woocommerce/term-image',
				usesContext: expect.arrayContaining( [
					'termId',
					'termTaxonomy',
					'woocommerce/termImageId',
					'woocommerce/termImageUrl',
				] ),
				getValues: expect.any( Function ),
			} )
		);

		const { getValues } = registerBlockBindingsSource.mock.calls[ 0 ][ 0 ];
		expect(
			getValues( {
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
	} );
} );
