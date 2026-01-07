/**
 * External dependencies
 */
import TestRenderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import ProductPrice from '../index';

describe( 'ProductPrice', () => {
	const currency = {
		code: 'GBP',
		currency_code: 'GBP',
		currency_decimal_separator: '.',
		currency_minor_unit: 2,
		currency_prefix: '£',
		currency_suffix: '',
		currency_symbol: '£',
		currency_thousand_separator: ',',
		decimalSeparator: '.',
		minorUnit: 2,
		prefix: '£',
		price: '61400',
		price_range: null,
		raw_prices: {
			precision: 6,
			price: '614000000',
			regular_price: '614000000',
			sale_price: '614000000',
		},
		regular_price: '61400',
		sale_price: '61400',
		suffix: '',
		symbol: '£',
		thousandSeparator: ',',
	};

	test( 'should use default price if no format is provided', () => {
		const component = TestRenderer.create(
			<ProductPrice
				price={ 50 }
				regularPrice={ 100 }
				currency={ currency }
			/>
		);

		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'should apply the format if one is provided', () => {
		const component = TestRenderer.create(
			<ProductPrice
				price={ 50 }
				regularPrice={ 100 }
				currency={ currency }
				format="pre price <price/> Test format"
			/>
		);

		expect( component.toJSON() ).toMatchSnapshot();
	} );

	test( 'should hide price when both price and regularPrice are 0', () => {
		const component = TestRenderer.create(
			<ProductPrice
				price={ 0 }
				regularPrice={ 0 }
				currency={ currency }
			/>
		);

		// Should render wrapper with empty price span (no FormattedMonetaryAmount)
		const json = component.toJSON();
		expect( json.type ).toBe( 'span' );
		expect( json.children ).toHaveLength( 1 );
		expect( json.children[ 0 ].type ).toBe( 'span' );
		expect( json.children[ 0 ].children ).toBeNull();
	} );

	test( 'should show $0.00 when price is 0 and regularPrice is undefined', () => {
		const component = TestRenderer.create(
			<ProductPrice price={ 0 } currency={ currency } />
		);

		// Should render $0.00 - regularPrice must be explicitly 0 to hide
		const json = component.toJSON();
		expect( json.type ).toBe( 'span' );
		// Should contain FormattedMonetaryAmount with £0.00
		const priceSpan = json.children[ 0 ];
		expect( priceSpan.props.className ).toContain(
			'wc-block-components-product-price__value'
		);
		expect( priceSpan.children[ 0 ] ).toBe( '£0.00' );
	} );

	test( 'should show strikethrough price when price is 0 but regularPrice is greater', () => {
		const component = TestRenderer.create(
			<ProductPrice
				price={ 0 }
				regularPrice={ 100 }
				currency={ currency }
			/>
		);

		// Should render as SalePrice (strikethrough)
		const json = component.toJSON();
		expect( json.type ).toBe( 'span' );
		// Should contain screen reader text and price elements
		expect( json.children.length ).toBeGreaterThan( 1 );
	} );
} );
