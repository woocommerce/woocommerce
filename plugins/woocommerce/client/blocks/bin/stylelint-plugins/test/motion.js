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
			[ 'a fade', `$state: opacity;\n${ fade( '$state' ) }` ],
			[
				'a fade before a later movement assignment',
				`$state: opacity;\n${ fade( '$state' ) }\n$state: transform;`,
			],
			[
				'the last assignment before the use',
				`$state: transform;\n$state: opacity;\n${ fade( '$state' ) }`,
			],
			[
				'!default when unset',
				`$state: opacity !default;\n${ fade( '$state' ) }`,
			],
			[
				'!default after null',
				`$state: null;\n$state: opacity !default;\n.a { @include motion-transition($state: $state); }`,
			],
			[
				'!default that keeps an existing fade',
				`$state: opacity;\n$state: transform !default;\n${ fade( '$state' ) }`,
			],
			[
				'a local fade that shadows the root',
				`$state: transform;\n.a { $state: opacity; transition: motion-state($state); }`,
			],
			[
				'a mixin parameter',
				'@mixin m($state: ()) { transition: motion-state($state); }',
			],
			[
				'a mixin parameter with a later !default',
				'@mixin m($state: ()) { $state: transform !default; transition: motion-state($state); }',
			],
			[
				'a mixin that only reads fades',
				`$state: opacity;\n@mixin m { transition: motion-state($state); }\n@include m;`,
			],
			[
				'a chain of variables',
				`$a: opacity;\n$b: $a, color;\n${ fade( '$b' ) }`,
			],
			[
				'a fade assigned in @if',
				`$state: opacity;\n@if true {\n\t$state: color;\n}\n${ fade( '$state' ) }`,
			],
			[
				'!default in @if that keeps the value',
				`$state: opacity;\n@if true {\n\t$state: transform !default;\n}\n${ fade( '$state' ) }`,
			],
			[
				'an assignment in @if after the use',
				`$state: opacity;\n${ fade( '$state' ) }\n@if true {\n\t$state: transform;\n}`,
			],
			[
				'a use inside @if after its assignment',
				`$state: transform;\n@if true {\n\t$state: opacity;\n\t${ fade( '$state' ) }\n}`,
			],
			[
				'a local assignment in a rule inside @if',
				`$state: opacity;\n@if true {\n\t.other {\n\t\t$state: transform;\n\t}\n}\n${ fade( '$state' ) }`,
			],
			[
				'an @each binding of fades',
				`@each $state in opacity, color {\n\t${ fade( '$state' ) }\n}`,
			],
		] )( 'allows %s', ( _, code ) => {
			expect( lint( code ) ).toEqual( [] );
		} );

		it.each( [
			[ 'a movement', `$state: transform;\n${ fade( '$state' ) }` ],
			[
				'a movement before a later fade assignment',
				`$state: transform;\n${ fade( '$state' ) }\n$state: opacity;`,
			],
			[
				'!default that keeps an existing movement',
				`$state: transform;\n$state: opacity !default;\n.a { @include motion-transition($state: $state); }`,
			],
			[
				'a local !default that keeps a root movement',
				`$state: transform;\n.a { $state: opacity !default; transition: motion-state($state); }`,
			],
			[
				'a local assignment after the use',
				`$state: transform;\n.a { transition: motion-state($state); $state: opacity; }`,
			],
			[
				'a movement after a mixin parameter',
				'@mixin m($state: ()) { $state: transform; transition: motion-state($state); }',
			],
			[
				'a movement assigned after a mixin that reads it',
				`$state: opacity;\n@mixin m { transition: motion-state($state); }\n$state: transform;\n@include m;`,
			],
			[ 'an unknown variable', fade( '$nope' ) ],
			[
				'a variable that refers to itself',
				`$a: $a;\n${ fade( '$a' ) }`,
			],
			[
				'a movement assigned in @if',
				`$state: opacity;\n@if true {\n\t$state: transform;\n}\n${ fade( '$state' ) }`,
			],
			[
				'a movement assigned in @else',
				`$state: opacity;\n@if $x {\n\t$state: color;\n} @else {\n\t$state: transform;\n}\n${ fade( '$state' ) }`,
			],
			[
				'a movement assigned in @each',
				`$state: opacity;\n@each $p in a, b {\n\t$state: transform;\n}\n${ fade( '$state' ) }`,
			],
			[
				'!default after a local assignment in @if',
				`@if true {\n\t$state: opacity;\n}\n$state: transform !default;\n${ fade( '$state' ) }`,
			],
			[
				'an @each binding of a movement',
				`$state: opacity;\n@each $state in transform {\n\t.a { transition: motion-state($state); }\n}`,
			],
			[
				'an @each binding of a map',
				`@each $state, $x in $map {\n\t${ fade( '$state' ) }\n}`,
			],
			[
				'a @for binding',
				`@for $state from 1 through 2 {\n\t${ fade( '$state' ) }\n}`,
			],
		] )( 'rejects %s', ( _, code ) => {
			expect( lint( code ) ).toHaveLength( 1 );
		} );
	} );
} );
