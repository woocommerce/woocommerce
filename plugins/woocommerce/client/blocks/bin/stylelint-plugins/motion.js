/**
 * `woocommerce/motion-safe`: a `transition*` or `animation*` declaration (other than `none`) must be inside
 * `@include motion-safe` or `@keyframes`. Transitions that only fade or change color, including `motion-state()` of
 * those properties and `motion-hold()`, and the shared fade animations, can be anywhere, because they do not move
 * anything.
 * See docs/contributors/motion.md.
 */
const stylelint = require( 'stylelint' );

const {
	createPlugin,
	utils: { report, ruleMessages, validateOptions },
} = stylelint;

const ruleName = 'woocommerce/motion-safe';

const messages = ruleMessages( ruleName, {
	rejected: ( prop ) =>
		`Use a motion mixin, or put "${ prop }" in \`@include motion-safe { … }\`, so that it obeys reduced motion. Only transitions of opacity and colors can be outside it.`,
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

// Splits a comma-separated value. Commas inside parentheses, such as in `cubic-bezier( … )`, do not split it.
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

// Helpers from `_motion.scss`. `motion-hold()` jumps at the end (`step-end`), so it never animates any property.
// `motion-state()` animates, so each property must fade or change colour. Sass variables pass, because the
// `motion-transition` mixin forwards its `$state` list.
const isFadeFunction = ( item ) => {
	if ( ! item.endsWith( ')' ) ) {
		return false;
	}
	if ( item.startsWith( 'motion-hold(' ) ) {
		return true;
	}
	if ( ! item.startsWith( 'motion-state(' ) ) {
		return false;
	}
	return splitList( item.slice( 'motion-state('.length, -1 ) ).every(
		( property ) =>
			property.startsWith( '$' ) ||
			FADE_PROPERTIES.has( property.toLowerCase() )
	);
};

// A `transition` or `transition-property` that only fades or changes color, or an `animation` that only fades.
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
			isFadeFunction( item ) ||
			FADE_PROPERTIES.has( item.split( ' ' )[ 0 ].toLowerCase() )
	);
};

// `@media … prefers-reduced-motion` is not a guard: it can also match `reduce`. Use `motion-safe`.
const isGuard = ( { type, name, params } ) =>
	type === 'atrule' &&
	( name.endsWith( 'keyframes' ) ||
		( name === 'include' && params.startsWith( 'motion-safe' ) ) );

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
