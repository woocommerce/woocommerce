/**
 * External dependencies
 */
import renderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import ProductPreview from '../';

describe( 'ProductPreview', () => {
	test( 'should render a single product preview with an image', () => {
		const product = {
			id: 1,
			name: 'Winter Jacket',
			price_html:
				'<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&#36;</span>65.00</span>',
			images: [
				{
					src: 'https://example.local/product.jpg',
				},
			],
		};
		const component = renderer.create( <ProductPreview product={ product } /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'should render a single product preview without an image', () => {
		const product = {
			id: 1,
			name: 'Winter Jacket',
			price_html:
				'<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&#36;</span>65.00</span>',
			images: [],
		};
		const component = renderer.create( <ProductPreview product={ product } /> );
		expect( component.toJSON() ).toMatchSnapshot();
	} );
} );
