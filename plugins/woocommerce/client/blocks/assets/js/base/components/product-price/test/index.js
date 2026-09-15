/**
 * External dependencies
 */
import { render, screen, within } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ProductPrice from '../index';
import { textContentMatcher } from '../../../../../../tests/utils/find-by-text';

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
		render(
			<ProductPrice
				price={ 50 }
				regularPrice={ 100 }
				currency={ currency }
			/>
		);

		expect( screen.getByRole( 'deletion' ) ).toHaveTextContent( '£1.00' );
		expect( screen.getByRole( 'insertion' ) ).toHaveTextContent( '£0.50' );
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

		expect( screen.getByRole( 'deletion' ) ).toHaveTextContent( '£1.00' );
		expect( screen.getByRole( 'insertion' ) ).toHaveTextContent( '£0.50' );
		// The custom format wraps the whole price, screen reader labels
		// included.
		expect( container ).toHaveTextContent(
			'pre price Previous price:£1.00Discounted price:£0.50 Test format'
		);
	} );

	test( 'keeps the sale price structure: labels and del/ins wrappers', () => {
		const { container } = render(
			<ProductPrice
				price={ 50 }
				regularPrice={ 100 }
				currency={ currency }
			/>
		);

		expect( container.firstChild ).toHaveClass(
			'price',
			'wc-block-components-product-price'
		);

		const previousLabel = screen.getByText( 'Previous price:' );
		const discountedLabel = screen.getByText( 'Discounted price:' );
		expect( previousLabel ).toHaveClass( 'screen-reader-text' );
		expect( discountedLabel ).toHaveClass( 'screen-reader-text' );

		// Each price follows its screen reader label.
		const del = screen.getByRole( 'deletion' );
		expect( del ).toHaveClass(
			'wc-block-components-product-price__regular'
		);
		expect( del ).toHaveAttribute( 'translate', 'no' );
		expect( previousLabel.nextElementSibling ).toBe( del );

		const ins = screen.getByRole( 'insertion' );
		expect( ins ).toHaveClass(
			'wc-block-components-product-price__value',
			'is-discounted'
		);
		expect( ins ).toHaveAttribute( 'translate', 'no' );
		expect( discountedLabel.nextElementSibling ).toBe( ins );
	} );

	test( 'renders the regular price in a del and the sale price in an ins', () => {
		render(
			<ProductPrice
				price={ 50 }
				regularPrice={ 100 }
				currency={ currency }
			/>
		);

		expect( screen.getByRole( 'deletion' ) ).toHaveTextContent( '£1.00' );
		expect( screen.getByRole( 'insertion' ) ).toHaveTextContent( '£0.50' );
	} );

	test( 'renders both sale prices as price elements inside their wrappers', () => {
		render(
			<ProductPrice
				price={ 50 }
				regularPrice={ 100 }
				currency={ currency }
			/>
		);

		// The wrappers hold a price element rather than a flat string, so the
		// currency symbol keeps the isolation FormattedMonetaryAmount gives it.
		expect(
			within( screen.getByRole( 'deletion' ) ).getByText(
				textContentMatcher( '£1.00' )
			)
		).toBeInTheDocument();
		expect(
			within( screen.getByRole( 'insertion' ) ).getByText(
				textContentMatcher( '£0.50' )
			)
		).toBeInTheDocument();
	} );

	test( 'renders a single price as a price element', () => {
		render( <ProductPrice price={ 50 } currency={ currency } /> );

		expect(
			screen.getByText( textContentMatcher( '£0.50' ) )
		).toBeInTheDocument();
	} );
} );
