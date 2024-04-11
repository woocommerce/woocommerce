/**
 * External dependencies
 */
import TestRenderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import FormattedMonetaryAmount from '../index';

describe( 'FormattedMonetaryAmount', () => {
	describe( 'separators', () => {
		test( 'should add the thousand separator', () => {
			const component = TestRenderer.create(
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
			expect( component.toJSON() ).toMatchSnapshot();
		} );

		test( 'should not add thousand separator', () => {
			const component = TestRenderer.create(
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
			expect( component.toJSON() ).toMatchSnapshot();
		} );

		test( 'should remove the thousand separator when identical to the decimal one', () => {
			const component = TestRenderer.create(
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
			expect( component.toJSON() ).toMatchSnapshot();
		} );
	} );
	describe( 'suffix/prefix', () => {
		test( 'should add the currency suffix', () => {
			const component = TestRenderer.create(
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
			expect( component.toJSON() ).toMatchSnapshot();
		} );

		test( 'should add the currency prefix', () => {
			const component = TestRenderer.create(
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
			expect( component.toJSON() ).toMatchSnapshot();
		} );
	} );

	describe( 'supports different value types', () => {
		test( 'should support numbers', () => {
			const component = TestRenderer.create(
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
			expect( component.toJSON() ).toMatchSnapshot();
		} );

		test( 'should support strings', () => {
			const component = TestRenderer.create(
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
			expect( component.toJSON() ).toMatchSnapshot();
		} );
	} );
} );
