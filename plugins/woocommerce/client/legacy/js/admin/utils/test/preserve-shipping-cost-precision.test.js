/**
 * Test for getPrecisionPreservingConfig method from utils/preserve-shipping-cost-precision.js
 */

const {
	getPrecisionPreservingConfig,
} = require( '../preserve-shipping-cost-precision' );

describe( 'Preserve Shipping Cost Precision - getPrecisionPreservingConfig', () => {
	const baseConfig = {
		precision: 2,
		decimalSeparator: '.',
		thousandSeparator: ',',
	};

	test( 'returns the config unchanged when value precision is at or below configured precision', () => {
		expect( getPrecisionPreservingConfig( '4.59', baseConfig ) ).toBe(
			baseConfig
		);
		expect( getPrecisionPreservingConfig( '4', baseConfig ) ).toBe(
			baseConfig
		);
		expect( getPrecisionPreservingConfig( '4.5', baseConfig ) ).toBe(
			baseConfig
		);
	} );

	test( 'bumps precision to match the stored value when it has more decimals than configured', () => {
		const result = getPrecisionPreservingConfig( '4.596', baseConfig );
		expect( result ).not.toBe( baseConfig );
		expect( result.precision ).toBe( 3 );
		expect( result.decimalSeparator ).toBe( '.' );
		expect( result.thousandSeparator ).toBe( ',' );
	} );

	test( 'preserves precision when decimal separator is a comma', () => {
		const commaConfig = {
			precision: 2,
			decimalSeparator: ',',
			thousandSeparator: '.',
		};
		const result = getPrecisionPreservingConfig( '4,596', commaConfig );
		expect( result.precision ).toBe( 3 );
	} );

	test( 'returns the original config when value is not a string', () => {
		expect( getPrecisionPreservingConfig( 4.596, baseConfig ) ).toBe(
			baseConfig
		);
		expect( getPrecisionPreservingConfig( undefined, baseConfig ) ).toBe(
			baseConfig
		);
		expect( getPrecisionPreservingConfig( null, baseConfig ) ).toBe(
			baseConfig
		);
	} );

	test( 'returns the original config when config is missing or invalid', () => {
		expect( getPrecisionPreservingConfig( '4.596', null ) ).toBe( null );
		expect( getPrecisionPreservingConfig( '4.596', undefined ) ).toBe(
			undefined
		);
		expect( getPrecisionPreservingConfig( '4.596', 'invalid' ) ).toBe(
			'invalid'
		);
	} );

	test( 'returns the original config when configured precision is not numeric', () => {
		const oddConfig = {
			precision: null,
			decimalSeparator: '.',
			thousandSeparator: ',',
		};
		expect( getPrecisionPreservingConfig( '4.596', oddConfig ) ).toBe(
			oddConfig
		);
	} );

	test( 'returns the original config when decimalSeparator is missing', () => {
		const noSep = { precision: 2, thousandSeparator: ',' };
		expect( getPrecisionPreservingConfig( '4.596', noSep ) ).toBe( noSep );
	} );

	test( 'leaves formula values that lack a decimal separator untouched', () => {
		expect( getPrecisionPreservingConfig( '10 * [qty]', baseConfig ) ).toBe(
			baseConfig
		);
	} );

	test( 'handles trailing decimal separator as zero precision delta', () => {
		// "4." has an empty decimal part, which should not bump precision above the configured value.
		expect( getPrecisionPreservingConfig( '4.', baseConfig ) ).toBe(
			baseConfig
		);
	} );
} );
