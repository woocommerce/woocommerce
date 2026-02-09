/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ProductImage from '..';

describe( 'ProductImage', () => {
	test( 'should render the passed alt prop', () => {
		const { container } = render( <ProductImage alt="testing" /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'should fallback to product alt text', () => {
		const product = {
			name: 'Test Product',
			images: [
				{
					src: 'https://i.cloudup.com/pt4DjwRB84-3000x3000.png',
					alt: 'hello world',
				},
			],
		};
		const { container } = render( <ProductImage product={ product } /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'should fallback to empty alt attribute if not passed via prop or product object', () => {
		const product = {
			name: 'Test Product',
			images: [
				{
					src: 'https://i.cloudup.com/pt4DjwRB84-3000x3000.png',
				},
			],
		};
		const { container } = render( <ProductImage product={ product } /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'should have the correct width and height', () => {
		const image = <ProductImage width={ 30 } height={ 30 } />;
		expect( image.props.width ).toBe( 30 );
		expect( image.props.height ).toBe( 30 );
	} );

	test( 'should render a product image', () => {
		const product = {
			name: 'Test Product',
			images: [
				{
					src: 'https://i.cloudup.com/pt4DjwRB84-3000x3000.png',
				},
			],
		};
		const { container } = render( <ProductImage product={ product } /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render a variations image', () => {
		const variation = {
			name: 'Test Variation',
			image: {
				src: 'https://i.cloudup.com/pt4DjwRB84-3000x3000.png',
			},
		};
		const { container } = render( <ProductImage product={ variation } /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render a placeholder image if no product images are found', () => {
		const product = {
			name: 'Test Product',
		};
		const { container } = render( <ProductImage product={ product } /> );
		expect( container ).toMatchSnapshot();
	} );
} );
