/** @format */
/**
 * External dependencies
 */
import { shallow } from 'enzyme';

/**
 * Internal dependencies
 */
import ImageUpload from '../index';

describe( 'ImageUpload', () => {
	describe( 'basic rendering', () => {
		it( 'should render an image uploader ready for upload', () => {
			const uploader = shallow( <ImageUpload /> );
			expect( uploader.children().hasClass( 'woocommerce-image-upload' ) ).toBe( true );
			expect( uploader.children().hasClass( 'no-image' ) ).toBe( true );
		} );

		it( 'should render an image uploader prepopulated with an upload', () => {
			const image = {
				id: 1234,
				url: 'https://upload.wikimedia.org/wikipedia/en/a/a9/Example.jpg',
			};
			const uploader = shallow( <ImageUpload image={ image } /> );
			expect( uploader.children().hasClass( 'woocommerce-image-upload' ) ).toBe( true );
			expect( uploader.children().hasClass( 'has-image' ) ).toBe( true );
		} );
	} );
} );
