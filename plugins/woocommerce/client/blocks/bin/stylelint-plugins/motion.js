/**
 * Stylelint rules for motion. See docs/contributors/motion.md.
 *
 * `woocommerce/motion-safe`: every `transition`, `transition-*`, `animation`, and `animation-*` declaration
 * must sit inside a reduced-motion guard, so motion is only added for shoppers who haven't asked for reduced
 * motion. Accepted guards (any ancestor):
 * - `@include motion-safe { … }` (the SCSS mixin from assets/css/abstracts/_mixins.scss)
 * - `@media not (prefers-reduced-motion)` or `@media (prefers-reduced-motion: no-preference)`
 * - `@media (prefers-reduced-motion: reduce)`, as declarations there only apply under reduced motion
 * Always allowed: a value of `none`, and anything inside `@keyframes`.
 *
 * `woocommerce/motion-tokens`: durations and delays come from the WPDS duration tokens rather than literal
 * times (`0s` and `0ms` are allowed), and transitions list their properties instead of using `all`. Looping
 * (`infinite`) animations are left out: loaders keep their own loop durations.
 */
const stylelint = require( 'stylelint' );

const {
	createPlugin,
	utils: { report, ruleMessages, validateOptions },
} = stylelint;

const ruleName = 'woocommerce/motion-safe';

const messages = ruleMessages( ruleName, {
	rejected: ( prop ) =>
		`Wrap "${ prop }" in \`@include motion-safe { … }\` so it respects reduced motion.`,
} );

const tokensRuleName = 'woocommerce/motion-tokens';

const tokensMessages = ruleMessages( tokensRuleName, {
	literalTime: ( time ) =>
		`Use a WPDS motion duration token, such as \`var(--wpds-motion-duration-sm)\`, instead of "${ time }".`,
	all: () => 'List the properties to transition instead of "all".',
} );

// Properties whose values may hold a duration or delay.
const TIMED_PROPS = [
	'transition',
	'transition-duration',
	'transition-delay',
	'animation',
	'animation-duration',
	'animation-delay',
];

const MOTION_PROPS = [ 'transition', 'animation' ];

const MEDIA_GUARDS = [
	'not(prefers-reduced-motion)',
	'(prefers-reduced-motion:no-preference)',
	'(prefers-reduced-motion:reduce)',
];

/**
 * Strips a vendor prefix such as `-webkit-`, leaving custom properties alone.
 *
 * @param {string} prop Lowercased property name.
 * @return {string} Property name without a vendor prefix.
 */
const unprefix = ( prop ) => {
	if ( ! prop.startsWith( '-' ) || prop.startsWith( '--' ) ) {
		return prop;
	}
	const end = prop.indexOf( '-', 1 );
	return end === -1 ? prop : prop.slice( end + 1 );
};

const isMotionProp = ( prop ) => {
	const name = unprefix( prop.toLowerCase() );
	return MOTION_PROPS.some(
		( motionProp ) =>
			name === motionProp || name.startsWith( `${ motionProp }-` )
	);
};

const isMotionSafeInclude = ( params ) => {
	const trimmed = params.trim();
	if ( ! trimmed.startsWith( 'motion-safe' ) ) {
		return false;
	}
	const next = trimmed.charAt( 'motion-safe'.length );
	return next === '' || next === '(' || next.trim() === '';
};

// Every query in a comma-separated list must carry the guard.
const isReducedMotionMedia = ( params ) =>
	params
		.toLowerCase()
		.split( ',' )
		.every( ( query ) => {
			const compact = query.split( /\s+/ ).join( '' );
			return MEDIA_GUARDS.some( ( guard ) => compact.includes( guard ) );
		} );

/**
 * Whether any ancestor is a reduced-motion guard or a keyframes block.
 *
 * @param {import('postcss').Declaration} decl Declaration to check.
 * @return {boolean} True when the declaration may hold motion.
 */
const isGuarded = ( decl ) => {
	for ( let node = decl.parent; node; node = node.parent ) {
		if ( node.type !== 'atrule' ) {
			continue;
		}
		const name = node.name.toLowerCase();
		if ( name.endsWith( 'keyframes' ) ) {
			return true;
		}
		if ( name === 'include' && isMotionSafeInclude( node.params ) ) {
			return true;
		}
		if ( name === 'media' && isReducedMotionMedia( node.params ) ) {
			return true;
		}
	}
	return false;
};

/**
 * Splits a value into comma-separated items, each a list of words. Brackets count as separators, so the
 * words inside `var( … )` and `cubic-bezier( … )` are checked too.
 *
 * @param {string} value Declaration value.
 * @return {string[][]} Words in each comma-separated item.
 */
const valueItems = ( value ) =>
	value
		.toLowerCase()
		.split( ',' )
		.map( ( item ) =>
			item
				.split( '(' )
				.join( ' ' )
				.split( ')' )
				.join( ' ' )
				.split( /\s+/ )
				.filter( Boolean )
		);

/**
 * Whether a word is a literal time other than zero, such as `200ms` or `.3s`.
 *
 * @param {string} word Lowercased word from a value.
 * @return {boolean} True for a non-zero time literal.
 */
const isLiteralTime = ( word ) => {
	let number = '';
	if ( word.endsWith( 'ms' ) ) {
		number = word.slice( 0, -2 );
	} else if ( word.endsWith( 's' ) ) {
		number = word.slice( 0, -1 );
	}
	return (
		number !== '' &&
		Number.isFinite( Number( number ) ) &&
		Number( number ) !== 0
	);
};

const ruleFunction = ( primary ) => ( root, result ) => {
	const validOptions = validateOptions( result, ruleName, {
		actual: primary,
		possible: [ true ],
	} );
	if ( ! validOptions ) {
		return;
	}

	root.walkDecls( ( decl ) => {
		if ( ! isMotionProp( decl.prop ) ) {
			return;
		}
		if ( decl.value.trim().toLowerCase() === 'none' ) {
			return;
		}
		if ( isGuarded( decl ) ) {
			return;
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

const tokensRuleFunction = ( primary ) => ( root, result ) => {
	const validOptions = validateOptions( result, tokensRuleName, {
		actual: primary,
		possible: [ true ],
	} );
	if ( ! validOptions ) {
		return;
	}

	root.walkDecls( ( decl ) => {
		const prop = unprefix( decl.prop.toLowerCase() );
		if (
			! TIMED_PROPS.includes( prop ) &&
			prop !== 'transition-property'
		) {
			return;
		}
		const items = valueItems( decl.value );
		const literalTime = items
			.filter( ( words ) => ! words.includes( 'infinite' ) )
			.flat()
			.find( ( word ) => isLiteralTime( word ) );
		if ( TIMED_PROPS.includes( prop ) && literalTime ) {
			report( {
				message: tokensMessages.literalTime( literalTime ),
				node: decl,
				word: literalTime,
				result,
				ruleName: tokensRuleName,
			} );
		}
		// `all` names the property: the first word of a `transition` item, or any `transition-property` item.
		const usesAll =
			( prop === 'transition' &&
				items.some( ( words ) => words[ 0 ] === 'all' ) ) ||
			( prop === 'transition-property' &&
				items.some( ( words ) => words.includes( 'all' ) ) );
		if ( usesAll ) {
			report( {
				message: tokensMessages.all(),
				node: decl,
				word: 'all',
				result,
				ruleName: tokensRuleName,
			} );
		}
	} );
};

tokensRuleFunction.ruleName = tokensRuleName;
tokensRuleFunction.messages = tokensMessages;

ruleFunction.ruleName = ruleName;
ruleFunction.messages = messages;

module.exports = [
	createPlugin( ruleName, ruleFunction ),
	createPlugin( tokensRuleName, tokensRuleFunction ),
];
