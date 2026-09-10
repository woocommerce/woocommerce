/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';

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

		test( 'should fall back to a period for an empty decimal separator', () => {
			render(
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
			expect( screen.getByText( '1563.45 €' ) ).toBeInTheDocument();
		} );

		test( 'should render when both separators are empty', () => {
			render(
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
			expect( screen.getByText( '1563.45 €' ) ).toBeInTheDocument();
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
