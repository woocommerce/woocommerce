/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ImageUpload from '../index';

describe( 'ImageUpload', () => {
	describe( 'basic rendering', () => {
		it( 'should render an image uploader ready for upload', () => {
			const { getByRole, queryByRole } = render( <ImageUpload /> );

			expect( queryByRole( 'img' ) ).toBeNull();
			expect(
				getByRole( 'button', { name: 'Add an image' } )
			).toBeInTheDocument();
		} );

		it( 'should render an image uploader prepopulated with an upload', () => {
			const image = {
				id: 1234,
				url: 'https://upload.wikimedia.org/wikipedia/en/a/a9/Example.jpg',
			};
			const { getByRole } = render( <ImageUpload image={ image } /> );

			expect( getByRole( 'img' ) ).toHaveAttribute( 'src', image.url );
			expect(
				getByRole( 'button', { name: 'Remove image' } )
			).toBeInTheDocument();
		} );
	} );
} );
