/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

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
		const { container } = render(
			<ProductPrice
				price={ 50 }
				regularPrice={ 100 }
				currency={ currency }
			/>
		);

		expect( container ).toMatchSnapshot();
	} );

	test( 'should apply the format if one is provided', () => {
		const { container } = render(
			<ProductPrice
				price={ 50 }
				regularPrice={ 100 }
				currency={ currency }
				format="pre price <price/> Test format"
			/>
		);

		expect( container ).toMatchSnapshot();
	} );

	test( 'should announce a price range using the given currency, not the site currency', () => {
		// The site currency in tests is USD ($, `.` decimals, `,` thousands).
		const euro = {
			code: 'EUR',
			decimalSeparator: ',',
			minorUnit: 2,
			prefix: '',
			suffix: '&nbsp;€',
			symbol: '€',
			thousandSeparator: '.',
		};

		render(
			<ProductPrice
				currency={ euro }
				minPrice={ 100000 }
				maxPrice={ 250000 }
			/>
		);

		const screenReaderText = screen.getByText( /Price between/ );

		expect( screenReaderText ).toHaveTextContent(
			'Price between 1.000,00 € and 2.500,00 €'
		);
		expect( screenReaderText ).not.toHaveTextContent( '$' );
	} );
} );
