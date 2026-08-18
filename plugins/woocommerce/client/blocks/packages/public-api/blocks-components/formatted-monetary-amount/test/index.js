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

		test( 'fires for user input only, converted to subunits', () => {
			const onValueChange = jest.fn();
			const { rerender } = render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ eurCurrency }
					displayType="input"
					onValueChange={ onValueChange }
				/>
			);

			// Not on mount.
			expect( onValueChange ).not.toHaveBeenCalled();

			// Not on a value prop change either: NumericFormat reports it, but
			// it echoes the rounded display value, which consumers would apply
			// as if the user had picked it.
			rerender(
				<FormattedMonetaryAmount
					value="179900"
					currency={ eurCurrency }
					displayType="input"
					onValueChange={ onValueChange }
				/>
			);
			expect( onValueChange ).not.toHaveBeenCalled();

			// User input does fire, converted to minor units.
			fireEvent.change( screen.getByRole( 'textbox' ), {
				target: { value: '€ 12,00' },
			} );
			expect( onValueChange ).toHaveBeenCalledTimes( 1 );
			expect( onValueChange ).toHaveBeenCalledWith( 1200 );
		} );

		test( 'does not echo the value rounded to decimalScale (price filter configuration)', () => {
			const onValueChange = jest.fn();
			const { rerender } = render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ eurCurrency }
					displayType="input"
					decimalScale={ 0 }
					onValueChange={ onValueChange }
				/>
			);

			// 1799 minor units display as "18" with decimalScale 0; before the
			// source guard, the prop-driven call reported that back as 1800.
			rerender(
				<FormattedMonetaryAmount
					value="1799"
					currency={ eurCurrency }
					displayType="input"
					decimalScale={ 0 }
					onValueChange={ onValueChange }
				/>
			);
			expect( onValueChange ).not.toHaveBeenCalledWith( 1800 );
			expect( onValueChange ).not.toHaveBeenCalled();
		} );

		test( 'still fires for blur corrections, which report as user events', () => {
			const onValueChange = jest.fn();
			render(
				<FormattedMonetaryAmount
					value="156345"
					currency={ eurCurrency }
					displayType="input"
					onValueChange={ onValueChange }
				/>
			);

			const input = screen.getByRole( 'textbox' );
			fireEvent.change( input, { target: { value: '€ 012,5' } } );
			expect( onValueChange ).toHaveBeenCalledTimes( 1 );

			// Blur strips the leading zero and reports the correction with
			// source "event", so the guard must let it through — while still
			// swallowing the prop-sourced resync that follows it.
			fireEvent.blur( input );
			expect( onValueChange ).toHaveBeenCalledTimes( 2 );
			expect( onValueChange ).toHaveBeenLastCalledWith( 1250 );
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
