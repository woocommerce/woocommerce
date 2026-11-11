/**
 * `woocommerce/motion-safe`: `transition*` and `animation*` declarations (other than `none`) must sit inside
 * `@include motion-safe`, a `prefers-reduced-motion` media query, or `@keyframes`. Transitions that only fade or
 * change colour (including `motion-state()` and `motion-hold()`), and the shared fade animations, may run anywhere,
 * as they don't move anything. See docs/contributors/motion.md.
 */
const stylelint = require( 'stylelint' );

const {
	createPlugin,
	utils: { report, ruleMessages, validateOptions },
} = stylelint;

const ruleName = 'woocommerce/motion-safe';

const messages = ruleMessages( ruleName, {
	rejected: ( prop ) =>
		`Use a motion mixin, or wrap "${ prop }" in \`@include motion-safe { … }\`, so it respects reduced motion. Only transitions of opacity and colours may run outside it.`,
} );

const FADE_PROPERTIES = new Set( [
	'opacity',
	'color',
	'background-color',
	'border-color',
	'border-top-color',
	'border-right-color',
	'border-bottom-color',
	'border-left-color',
	'border-block-color',
	'border-block-start-color',
	'border-block-end-color',
	'border-inline-color',
	'border-inline-start-color',
	'border-inline-end-color',
	'outline-color',
	'box-shadow',
	'fill',
	'stroke',
	'text-decoration-color',
	'-webkit-text-fill-color',
] );

// Splits a comma-separated value, ignoring commas inside parentheses such as `cubic-bezier( … )`.
const splitList = ( value ) => {
	const items = [ '' ];
	let depth = 0;
	for ( const character of value ) {
		if ( character === '(' ) {
			depth++;
		} else if ( character === ')' ) {
			depth--;
		}
		if ( character === ',' && depth === 0 ) {
			items.push( '' );
		} else {
			items[ items.length - 1 ] += character;
		}
	}
	return items.map( ( item ) => item.trim() ).filter( Boolean );
};

const FADE_KEYFRAMES = new Set( [
	'wc-fade-in',
	'wc-fade-out',
	'wc-fade-text-out',
	'wc-hold-visible',
] );

// Helpers from `_motion.scss` that only return fade, colour, or hold transitions.
const FADE_FUNCTIONS = [ 'motion-state(', 'motion-hold(' ];

// A `transition` or `transition-property` that only fades or changes colour, or an `animation` that only fades.
const isFadeOnly = ( prop, value ) => {
	if ( prop === 'animation' || prop === 'animation-name' ) {
		return splitList( value ).every( ( item ) =>
			item.split( ' ' ).some( ( token ) => FADE_KEYFRAMES.has( token ) )
		);
	}
	if ( prop !== 'transition' && prop !== 'transition-property' ) {
		return false;
	}
	return splitList( value ).every(
		( item ) =>
			FADE_FUNCTIONS.some( ( name ) => item.startsWith( name ) ) ||
			FADE_PROPERTIES.has( item.split( ' ' )[ 0 ].toLowerCase() )
	);
};

const isGuard = ( { type, name, params } ) =>
	type === 'atrule' &&
	( name.endsWith( 'keyframes' ) ||
		( name === 'include' && params.startsWith( 'motion-safe' ) ) ||
		( name === 'media' && params.includes( 'prefers-reduced-motion' ) ) );

const ruleFunction = ( primary ) => ( root, result ) => {
	if (
		! validateOptions( result, ruleName, {
			actual: primary,
			possible: [ true ],
		} )
	) {
		return;
	}
	root.walkDecls( ( decl ) => {
		const prop = decl.prop.toLowerCase();
		if (
			( ! prop.startsWith( 'transition' ) &&
				! prop.startsWith( 'animation' ) ) ||
			decl.value.trim() === 'none' ||
			isFadeOnly( prop, decl.value )
		) {
			return;
		}
		for ( let node = decl.parent; node; node = node.parent ) {
			if ( isGuard( node ) ) {
				return;
			}
		}
		report( {
			message: messages.rejected( decl.prop ),
			node: decl,
			word: decl.prop,
			result,
			ruleName,
		} );
	} );
};

ruleFunction.ruleName = ruleName;
ruleFunction.messages = messages;

module.exports = createPlugin( ruleName, ruleFunction );
