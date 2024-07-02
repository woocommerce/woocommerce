/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import FormattedMonetaryAmount from '../index';

describe( 'FormattedMonetaryAmount', () => {
	describe( 'separators', () => {
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
					} }
				/>
			);
			expect( screen.getByText( '€ 0,15' ) ).toBeInTheDocument();
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
					} }
				/>
			);
			expect( screen.getByText( '€ 15' ) ).toBeInTheDocument();
		} );
	} );
} );
