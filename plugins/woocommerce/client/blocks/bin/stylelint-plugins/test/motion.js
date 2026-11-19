/**
 * External dependencies
 */
const path = require( 'path' );

// Stylelint does not load in the jsdom environment of the tests, so the rule runs on its own, with the parts of the
// stylelint API that it uses.
const mockReports = [];
jest.mock( 'stylelint', () => ( {
	createPlugin: ( ruleName, rule ) => ( { ruleName, rule } ),
	utils: {
		report: ( problem ) => mockReports.push( problem ),
		ruleMessages: ( ruleName, messages ) => messages,
		validateOptions: () => true,
	},
} ) );

/**
 * Internal dependencies
 */
const { rule } = require( '../motion' );

// The SCSS syntax that the WordPress stylelint config uses.
const scss = require(
	require.resolve( 'postcss-scss', {
		paths: [
			path.dirname(
				require.resolve( '@wordpress/stylelint-config/package.json' )
			),
		],
	} )
);

const lint = ( code ) => {
	mockReports.length = 0;
	rule( true )( scss.parse( code ), {} );
	return mockReports.map( ( { message } ) => message );
};

const guarded = ( declaration ) =>
	`.a {\n\t@include motion-safe {\n\t\t${ declaration };\n\t}\n}`;

const fade = ( state ) => `.a { transition: motion-state(${ state }); }`;

describe( 'woocommerce/motion-safe', () => {
	describe( 'movement outside motion-safe', () => {
		it.each( [
			[ 'a fade', '.a { transition: opacity var(--d); }' ],
			[
				'a guarded transform',
				guarded( 'transition: transform var(--d)' ),
			],
			[
				'a single-line fade animation',
				'.a { animation: wc-fade-in var(--d); }',
			],
			[
				'a tab-separated fade animation',
				'.a { animation: wc-fade-in\tvar(--d); }',
			],
			[
				'a multiline fade animation',
				'.a {\n\tanimation:\n\t\twc-fade-in\n\t\tvar(--d);\n}',
			],
			[
				'a multiline fade transition',
				'.a {\n\ttransition:\n\t\topacity\n\t\tvar(--d);\n}',
			],
		] )( 'allows %s', ( _, code ) => {
			expect( lint( code ) ).toEqual( [] );
		} );

		it.each( [
			[
				'an unguarded transform',
				'.a { transition: transform var(--d); }',
			],
			[
				'an unguarded prefixed animation',
				'.a { -webkit-animation: wc-rise var(--d); }',
			],
			[
				'a multiline transform',
				'.a {\n\ttransition:\n\t\ttransform\n\t\tvar(--d);\n}',
			],
		] )( 'rejects %s', ( _, code ) => {
			expect( lint( code ) ).toHaveLength( 1 );
		} );
	} );

	describe( 'literal times', () => {
		it.each( [
			[ 'a token', guarded( 'transition-duration: var(--d)' ) ],
			[
				'a calc of tokens',
				guarded( 'transition-duration: calc(var(--d) * 2)' ),
			],
			[ 'zero times', guarded( 'transition-delay: 0s, 0ms' ) ],
			[
				'a loop',
				guarded( 'animation: wc-shimmer 1.5s infinite linear' ),
			],
			[
				'numbers in cubic-bezier',
				guarded(
					'transition: transform var(--d) cubic-bezier(0.4, 0, 0.2, 1)'
				),
			],
		] )( 'allows %s', ( _, code ) => {
			expect( lint( code ) ).toEqual( [] );
		} );

		it.each( [
			[ 'a bare time', guarded( 'transition-duration: 100ms' ) ],
			[
				'a time inside calc',
				guarded( 'transition-duration: calc(var(--d) + 100ms)' ),
			],
			[
				'a time inside calc without spaces',
				guarded( 'transition-duration: calc(var(--d)+100ms)' ),
			],
			[
				'a fallback inside var',
				guarded( 'transition-duration: var(--d, .2s)' ),
			],
			[ 'a negative delay', guarded( 'transition-delay: -100ms' ) ],
			[
				'a finite item next to a loop',
				guarded(
					'animation: wc-fade-in 123ms, wc-shimmer 1.5s infinite'
				),
			],
			[
				'a prefixed time',
				guarded( '-webkit-transition-duration: 100ms' ),
			],
		] )( 'rejects %s', ( _, code ) => {
			expect( lint( code ) ).toHaveLength( 1 );
		} );
	} );

	describe( 'Sass variables in motion-state() and motion-transition', () => {
		it.each( [
			[
				'a mixin parameter',
				'@mixin m($state: ()) { transition: motion-state($state); }',
			],
			[
				'a mixin parameter forwarded to motion-transition',
				'@mixin m($state: ()) { @include motion-transition($state: $state); }',
			],
			[
				'a mixin parameter in a nested rule',
				'@mixin m($state: ()) { .a { transition: motion-state($state...); } }',
			],
		] )( 'allows %s', ( _, code ) => {
			expect( lint( code ) ).toEqual( [] );
		} );

		it.each( [
			[ 'a root variable', `$state: opacity;\n${ fade( '$state' ) }` ],
			[
				'a root variable in motion-transition',
				'$state: opacity;\n.a { @include motion-transition($state: $state); }',
			],
			[ 'an unknown variable', fade( '$nope' ) ],
			[
				'a variable that is not a parameter of the mixin',
				'$state: opacity;\n@mixin m { transition: motion-state($state); }',
			],
			[
				'a mixin parameter that the mixin assigns',
				'@mixin m($state: ()) { $state: transform; transition: motion-state($state); }',
			],
			[
				'a mixin parameter that a loop rebinds',
				'@mixin m($state: ()) { @each $state in transform { .a { transition: motion-state($state); } } }',
			],
		] )( 'rejects %s', ( _, code ) => {
			expect( lint( code ) ).toHaveLength( 1 );
		} );
	} );
} );
