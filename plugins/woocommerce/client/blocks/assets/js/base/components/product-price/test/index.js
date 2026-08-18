/**
 * External dependencies
 */
import { render } from '@testing-library/react';

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

	// The currency symbol sits in its own element, so each price is read whole.
	const getRegularPrice = ( container ) =>
		container.querySelector( '.wc-block-components-product-price__regular' )
			?.textContent;
	const getDiscountedPrice = ( container ) =>
		container.querySelector(
			'.wc-block-components-product-price__value.is-discounted'
		)?.textContent;

	test( 'should use default price if no format is provided', () => {
		const { container } = render(
			<ProductPrice
				price={ 50 }
				regularPrice={ 100 }
				currency={ currency }
			/>
		);

		expect( getRegularPrice( container ) ).toBe( '£1.00' );
		expect( getDiscountedPrice( container ) ).toBe( '£0.50' );
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

		expect( getRegularPrice( container ) ).toBe( '£1.00' );
		expect( getDiscountedPrice( container ) ).toBe( '£0.50' );
		// The custom format wraps the whole price, screen reader labels
		// included.
		expect( container.textContent ).toBe(
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

		const wrapper = container.firstChild;
		expect( wrapper ).toHaveClass(
			'price',
			'wc-block-components-product-price'
		);

		// Each price is preceded by its screen reader label.
		const [ previousLabel, discountedLabel ] = container.querySelectorAll(
			'span.screen-reader-text'
		);
		expect( previousLabel ).toHaveTextContent( 'Previous price:' );
		expect( discountedLabel ).toHaveTextContent( 'Discounted price:' );

		const del = container.querySelector( 'del' );
		expect( del ).toHaveClass(
			'wc-block-components-product-price__regular'
		);
		expect( del ).toHaveAttribute( 'translate', 'no' );
		expect( previousLabel.nextElementSibling ).toBe( del );

		const ins = container.querySelector( 'ins' );
		expect( ins ).toHaveClass(
			'wc-block-components-product-price__value',
			'is-discounted'
		);
		expect( ins ).toHaveAttribute( 'translate', 'no' );
		expect( discountedLabel.nextElementSibling ).toBe( ins );
	} );

	test( 'renders the regular price in a del and the sale price in an ins', () => {
		const { container } = render(
			<ProductPrice
				price={ 50 }
				regularPrice={ 100 }
				currency={ currency }
			/>
		);

		expect( container.querySelector( 'del' )?.textContent ).toBe( '£1.00' );
		expect( container.querySelector( 'ins' )?.textContent ).toBe( '£0.50' );
	} );

	test( 'isolates the currency symbol of both sale prices', () => {
		const { container } = render(
			<ProductPrice
				price={ 50 }
				regularPrice={ 100 }
				currency={ currency }
			/>
		);

		const symbols = container.querySelectorAll(
			'.wc-block-components-formatted-money-amount__currency-symbol'
		);
		expect( symbols ).toHaveLength( 2 );
		expect( symbols[ 0 ].textContent ).toBe( '£' );
		expect( symbols[ 1 ].textContent ).toBe( '£' );
	} );

	test( 'isolates the currency symbol of a single price', () => {
		const { container } = render(
			<ProductPrice price={ 50 } currency={ currency } />
		);

		expect(
			container.querySelector(
				'.wc-block-components-formatted-money-amount__currency-symbol'
			)?.textContent
		).toBe( '£' );
		expect( container.querySelector( 'bdi' )?.textContent ).toBe( '£0.50' );
	} );
} );
