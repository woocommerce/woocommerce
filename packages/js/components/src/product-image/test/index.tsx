/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ProductImage from '..';

describe( 'ProductImage', () => {
	test( 'should have the correct width and height', () => {
		const image = <ProductImage width={ 30 } height={ 30 } />;
		expect( image.props.width ).toBe( 30 );
		expect( image.props.height ).toBe( 30 );
	} );
} );
