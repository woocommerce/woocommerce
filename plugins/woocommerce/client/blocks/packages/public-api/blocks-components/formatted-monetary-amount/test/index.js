/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import FormattedMonetaryAmount from '../index';

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
			render( <FormattedMonetaryAmount value="156345" /> );

			expect( screen.getByText( '1.563,45 TEST' ) ).toBeInTheDocument();
		} );

		test( 'should add the thousand separator', () => {
			render(
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

			expect( screen.getByText( '1.563,45 €' ) ).toBeInTheDocument();
		} );

		test( 'should not add thousand separator', () => {
			render(
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
			expect( screen.getByText( '1563,45 €' ) ).toBeInTheDocument();
		} );

		test( 'should remove the thousand separator when identical to the decimal one', () => {
			render(
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
			expect( screen.getByText( '1563,45 €' ) ).toBeInTheDocument();
		} );
	} );
	describe( 'suffix/prefix', () => {
		test( 'should add the currency suffix', () => {
			render(
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
			expect( screen.getByText( '0,15 €' ) ).toBeInTheDocument();
		} );

		test( 'should add the currency prefix', () => {
			render(
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
			expect( screen.getByText( '€ 0,15' ) ).toBeInTheDocument();
		} );
	} );

	describe( 'RTL-script currency symbols', () => {
		// Lebanese Pound (LBP) — an RTL-script symbol reordered by the bidi
		// algorithm when composed into a plain-text string with the amount.
		const FSI = '\u2068';
		const PDI = '\u2069';
		/** @type {import('@woocommerce/types').Currency} */
		const lbpCurrency = {
			code: 'LBP',
			symbol: 'ل.ل',
			thousandSeparator: ',',
			decimalSeparator: '.',
			minorUnit: 2,
			prefix: '',
			suffix: ' ل.ل',
		};

		test( 'wraps an RTL-script suffix in first-strong isolate characters', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ lbpCurrency }
				/>
			);

			expect( container.textContent ).toBe(
				`1,563.45 ${ FSI }ل.ل${ PDI }`
			);
		} );

		test( 'wraps an RTL-script prefix in first-strong isolate characters', () => {
			const { container } = render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ {
						...lbpCurrency,
						prefix: 'ل.ل ',
						suffix: '',
					} }
				/>
			);

			expect( container.textContent ).toBe(
				`${ FSI }ل.ل${ PDI } 1,563.45`
			);
		} );

		test( 'does not add isolate characters to LTR currency symbols', () => {
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

			expect( container.textContent ).toBe( '1.563,45 €' );
		} );

		test( 'does not add isolate characters to editable input values', () => {
			render(
				<FormattedMonetaryAmount
					displayType="input"
					value="156345"
					currency={ lbpCurrency }
					onValueChange={ () => void 0 }
				/>
			);

			expect( screen.getByRole( 'textbox' ) ).toHaveValue(
				'1,563.45 ل.ل'
			);
		} );
	} );

	describe( 'supports different value types', () => {
		test( 'should support numbers', () => {
			render(
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
			expect( screen.getByText( '15 €' ) ).toBeInTheDocument();
		} );

		test( 'should support strings', () => {
			render(
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
			expect( screen.getByText( '€ 15' ) ).toBeInTheDocument();
		} );
	} );
} );
