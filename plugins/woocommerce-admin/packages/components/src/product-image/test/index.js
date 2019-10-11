/** @format */
/**
 * External dependencies
 */
import { shallow } from 'enzyme';
import { setSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import ProductImage from '../';

describe( 'ProductImage', () => {
	test( 'should render the passed alt prop', () => {
		const image = shallow( <ProductImage alt="testing" /> );
		expect( image ).toMatchSnapshot();
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
		const image = shallow( <ProductImage product={ product } /> );
		expect( image ).toMatchSnapshot();
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
		const image = shallow( <ProductImage product={ product } /> );
		expect( image ).toMatchSnapshot();
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
		const image = shallow( <ProductImage product={ product } /> );
		expect( image ).toMatchSnapshot();
	} );

	test( 'should render a variations image', () => {
		const variation = {
			name: 'Test Variation',
			image: {
				src: 'https://i.cloudup.com/pt4DjwRB84-3000x3000.png',
			},
		};
		const image = shallow( <ProductImage product={ variation } /> );
		expect( image ).toMatchSnapshot();
	} );

	test( 'should render a placeholder image if no product images are found', () => {
		setSetting( 'wcAssetUrl', 'https://woocommerce.com/wp-content/plugins/woocommerce/assets/' );
		const product = {
			name: 'Test Product',
		};
		const image = shallow( <ProductImage product={ product } /> );
		expect( image ).toMatchSnapshot();
		setSetting( 'wcAssetUrl', '' );
	} );
} );
