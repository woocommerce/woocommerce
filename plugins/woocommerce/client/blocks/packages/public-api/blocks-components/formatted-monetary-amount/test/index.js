/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import FormattedMonetaryAmount from '../index';

// The element isolating the currency symbol from the amount. Its text is
// compared with toBe, since toHaveTextContent trims the spacing whose
// placement these tests check.
const symbolSelector =
	'.wc-block-components-formatted-money-amount__currency-symbol';

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	SITE_CURRENCY: {
		code: 'EUR',
		symbol: 'TEST',
		thousandSeparator: '.',
		decimalSeparator: ',',
		minorUnit: 2,
		prefix: '',
		suffix: ' TEST',
	},
} ) );

describe( 'FormattedMonetaryAmount', () => {
	describe( 'separators', () => {
		test( 'should default to store currency configuration', () => {
			const { container } = render(
				<FormattedMonetaryAmount value="156345" />
			);

			expect( container ).toHaveTextContent( '1.563,45 TEST' );
		} );

		test( 'should add the thousand separator', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ {
						code: 'EUR',
						symbol: '€',
						thousandSeparator: '.',
						decimalSeparator: ',',
						minorUnit: 2,
						prefix: '',
						suffix: ' €',
					} }
				/>
			);

			expect( container ).toHaveTextContent( '1.563,45 €' );
		} );

		test( 'should not add thousand separator', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ {
						code: 'EUR',
						symbol: '€',
						decimalSeparator: ',',
						thousandSeparator: '',
						minorUnit: 2,
						prefix: '',
						suffix: ' €',
					} }
				/>
			);
			expect( container ).toHaveTextContent( '1563,45 €' );
		} );

		test( 'should remove the thousand separator when identical to the decimal one', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ {
						code: 'EUR',
						symbol: '€',
						thousandSeparator: ',',
						decimalSeparator: ',',
						minorUnit: 2,
						prefix: '',
						suffix: ' €',
					} }
				/>
			);
			expect( console ).toHaveWarned();
			expect( container ).toHaveTextContent( '1563,45 €' );
		} );

		test( 'should fall back to a period for an empty decimal separator', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ {
						code: 'EUR',
						symbol: '€',
						thousandSeparator: '.',
						decimalSeparator: '',
						minorUnit: 2,
						prefix: '',
						suffix: ' €',
					} }
				/>
			);
			expect( console ).toHaveWarned();
			expect( container ).toHaveTextContent( '1563.45 €' );
		} );

		test( 'should render when both separators are empty', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ {
						code: 'EUR',
						symbol: '€',
						thousandSeparator: '',
						decimalSeparator: '',
						minorUnit: 2,
						prefix: '',
						suffix: ' €',
					} }
				/>
			);
			expect( container ).toHaveTextContent( '1563.45 €' );
		} );
	} );
	describe( 'suffix/prefix', () => {
		test( 'should add the currency suffix', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value="15"
					currency={ {
						code: 'EUR',
						symbol: '€',
						thousandSeparator: '.',
						decimalSeparator: ',',
						minorUnit: 2,
						prefix: '',
						suffix: ' €',
					} }
				/>
			);
			expect( container ).toHaveTextContent( '0,15 €' );
		} );

		test( 'should add the currency prefix', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value="15"
					currency={ {
						code: 'EUR',
						symbol: '€',
						thousandSeparator: '.',
						decimalSeparator: ',',
						minorUnit: 2,
						prefix: '€ ',
						suffix: '',
					} }
				/>
			);
			expect( container ).toHaveTextContent( '€ 0,15' );
		} );
	} );

	describe( 'onValueChange', () => {
		/** @type {import('@woocommerce/types').Currency} */
		const eurCurrency = {
			code: 'EUR',
			symbol: '€',
			thousandSeparator: '.',
			decimalSeparator: ',',
			minorUnit: 2,
			prefix: '€ ',
			suffix: '',
		};

		test( 'fires for user input, converted to subunits', () => {
			const onValueChange = jest.fn();
			render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ eurCurrency }
					displayType="input"
					onValueChange={ onValueChange }
				/>
			);

			// Not on mount.
			expect( onValueChange ).not.toHaveBeenCalled();

			fireEvent.change( screen.getByRole( 'textbox' ), {
				target: { value: '€ 12,00' },
			} );
			expect( onValueChange ).toHaveBeenCalledTimes( 1 );
			expect( onValueChange ).toHaveBeenCalledWith( 1200 );
		} );

		test( 'also fires for value prop changes, matching v4', () => {
			const onValueChange = jest.fn();
			const { rerender } = render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ eurCurrency }
					displayType="input"
					onValueChange={ onValueChange }
				/>
			);

			// Kept deliberately so the major bump does not change the
			// callback's behaviour; consumers that push values back must
			// guard against the echo themselves.
			rerender(
				<FormattedMonetaryAmount
					value="179900"
					currency={ eurCurrency }
					displayType="input"
					onValueChange={ onValueChange }
				/>
			);
			expect( onValueChange ).toHaveBeenCalledWith( 179900 );
		} );
	} );

	describe( 'supports different value types', () => {
		test( 'should support numbers', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value={ 15.0 }
					currency={ {
						code: 'EUR',
						symbol: '€',
						thousandSeparator: '.',
						decimalSeparator: ',',
						minorUnit: 0,
						prefix: '',
						suffix: ' €',
					} }
				/>
			);
			expect( container ).toHaveTextContent( '15 €' );
		} );

		test( 'should support strings', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value="15.0"
					currency={ {
						code: 'EUR',
						symbol: '€',
						thousandSeparator: '.',
						decimalSeparator: ',',
						minorUnit: 0,
						prefix: '€ ',
						suffix: '',
					} }
				/>
			);
			expect( container ).toHaveTextContent( '€ 15' );
		} );
	} );

	describe( 'markup', () => {
		/** @type {import('@woocommerce/types').Currency} */
		const eurCurrency = {
			code: 'EUR',
			symbol: '€',
			thousandSeparator: '.',
			decimalSeparator: ',',
			minorUnit: 2,
			prefix: '€ ',
			suffix: '',
		};

		const lbpSymbol = 'ل.ل';
		/** @type {import('@woocommerce/types').Currency} */
		const lbpCurrency = {
			code: 'LBP',
			symbol: lbpSymbol,
			thousandSeparator: ',',
			decimalSeparator: '.',
			minorUnit: 2,
			prefix: `${ lbpSymbol } `,
			suffix: '',
		};

		const lrm = '‎';

		test( 'renders the price in a bdi with the symbol in its own isolating element, matching wc_price()', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ eurCurrency }
				/>
			);

			expect( container.querySelector( 'bdi' ) ).toHaveTextContent(
				'€ 1.563,45'
			);

			const symbol = container.querySelector( symbolSelector );
			// The space stays outside the isolate; inside it, it would be drawn
			// on the wrong side of the symbol.
			expect( symbol?.textContent ).toBe( '€' );
			expect( symbol ).toHaveAttribute( 'dir', 'auto' );
		} );

		test.each( [
			[ 'a prefix symbol', eurCurrency, '€', '€ 1.563,45' ],
			[
				'a suffix symbol',
				{ ...eurCurrency, prefix: '', suffix: ' €' },
				'€',
				'1.563,45 €',
			],
			[
				'a symbol followed by an entity-encoded non-breaking space',
				{ ...eurCurrency, prefix: '€&nbsp;' },
				'€',
				'€ 1.563,45',
			],
			[
				'an RTL-script prefix symbol',
				lbpCurrency,
				lbpSymbol,
				`${ lbpSymbol } 1,563.45`,
			],
			[
				'a filtered symbol carrying a directional mark',
				{ ...lbpCurrency, prefix: `${ lrm }${ lbpSymbol } ` },
				`${ lrm }${ lbpSymbol }`,
				`${ lrm }${ lbpSymbol } 1,563.45`,
			],
		] )(
			'isolates %s and keeps its spacing outside the isolate',
			( _case, currency, symbol, text ) => {
				const { container } = render(
					<FormattedMonetaryAmount
						value="156345"
						currency={ currency }
					/>
				);

				expect(
					container.querySelector( symbolSelector )?.textContent
				).toBe( symbol );
				expect( container.querySelector( 'bdi' ) ).toHaveTextContent(
					text
				);
			}
		);

		test( 'keeps the negative sign inside the bdi, ahead of the symbol', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value="-156345"
					currency={ eurCurrency }
				/>
			);

			expect( container.querySelector( 'bdi' ) ).toHaveTextContent(
				'-€ 1.563,45'
			);
		} );

		test( 'keeps the symbol in the value when rendering an input', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ eurCurrency }
					displayType="input"
				/>
			);

			expect( container.querySelector( 'bdi' ) ).toBeNull();
			expect( screen.getByRole( 'textbox' ) ).toHaveValue( '€ 1.563,45' );
		} );

		test( 'keeps the wrapper span classes, style and translate attribute', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ eurCurrency }
					className="custom-class"
					style={ { color: 'red' } }
				/>
			);

			// The span props come through the renderText callback now, so pin
			// down that nothing is lost on the way.
			const wrapper = container.firstChild;
			expect( wrapper ).toHaveClass(
				'wc-block-formatted-money-amount',
				'wc-block-components-formatted-money-amount',
				'custom-class'
			);
			expect( wrapper ).toHaveStyle( { color: 'rgb(255, 0, 0)' } );
			expect( wrapper ).toHaveAttribute( 'translate', 'no' );
		} );

		test( 'forwards getInputRef to the wrapper span in text mode', () => {
			const getInputRef = jest.fn();
			const { container } = render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ eurCurrency }
					getInputRef={ getInputRef }
				/>
			);

			// NumericFormat does not apply getInputRef when renderText is set,
			// so the component wires it to the span itself.
			expect( getInputRef ).toHaveBeenCalledWith( container.firstChild );
		} );

		test( 'leaves a consumer-supplied renderText in control of the output', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ eurCurrency }
					renderText={ ( formattedValue ) => (
						<strong>{ formattedValue }</strong>
					) }
				/>
			);

			expect( container.querySelector( 'bdi' ) ).toBeNull();
			expect( container.querySelector( 'strong' ) ).toHaveTextContent(
				'€ 1.563,45'
			);
		} );
	} );
} );
