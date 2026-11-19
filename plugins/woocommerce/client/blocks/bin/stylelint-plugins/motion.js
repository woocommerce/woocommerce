/**
 * `woocommerce/motion-safe`: a `transition*` or `animation*` declaration (other than `none`) must be inside
 * `@include motion-safe` or `@keyframes`. Transitions that only fade or change color, including `motion-state()` of
 * those properties and `motion-hold()`, and the shared fade animations, can be anywhere, because they do not move
 * anything. The `$state` of `@include motion-transition` follows the same rule, because it runs with reduced motion.
 * Times in these declarations must use duration tokens, except in an item that loops (`infinite`).
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
	rejectedState: () =>
		'The `$state` of `motion-transition` runs with reduced motion, so it can list only opacity and colors. Pass movement as `$move`.',
	rejectedTime: ( time ) =>
		`Use a WPDS motion duration token instead of "${ time }". Only loops (\`infinite\`) can use literal times.`,
} );

const VENDOR_PREFIXES = [ '-webkit-', '-moz-', '-ms-', '-o-' ];

// `-webkit-animation` moves things just like `animation`, so check it the same way.
const unprefixed = ( prop ) => {
	const prefix = VENDOR_PREFIXES.find( ( p ) => prop.startsWith( p ) );
	return prefix ? prop.slice( prefix.length ) : prop;
};

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
// `motion-state()` animates, so each property, or the value of each Sass variable, must fade or change colour.
const isFadeFunction = ( item, decl ) => {
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
		( property ) => isFadeProperty( property, decl, new Set() )
	);
};

// The names of the parameters of a `@mixin`, for example `$state` and `$move` in `motion-transition($state: (), …)`.
const mixinParameters = ( { params } ) => {
	const open = params.indexOf( '(' );
	if ( open === -1 || ! params.endsWith( ')' ) ) {
		return [];
	}
	return splitList( params.slice( open + 1, -1 ) ).map( ( parameter ) =>
		parameter.split( ':' )[ 0 ].replace( '...', '' ).trim()
	);
};

// The items of a Sass list value, for example `opacity` and `color` in `(opacity, color) !default`.
const listTokens = ( value ) =>
	[ ...value.replace( '!default', ' ' ) ]
		.map( ( character ) =>
			'(),'.includes( character ) || character.trim() === ''
				? ' '
				: character
		)
		.join( '' )
		.split( ' ' )
		.filter( Boolean );

const MIXIN_PARAMETER = Symbol( 'mixin parameter' );
const UNKNOWN = Symbol( 'unknown' );

// Blocks that can update a variable of the scope around them. Any other block, such as a rule or `@media`, has its
// own scope, so an assignment inside it is local.
const FLOW_CONTROL = new Set( [ 'if', 'else', 'each', 'for', 'while' ] );

const isDefault = ( { value } ) => /!default\b/.test( value );

const isNull = ( value ) =>
	typeof value === 'object' &&
	value.value.replace( /!default\b/, '' ).trim() === 'null';

// The value that a loop binds to `name`, such as `opacity, color` in `@each $state in opacity, color`. It is null
// when the loop does not bind `name`, and `UNKNOWN` when the value cannot be read, such as for `@for` or a map.
const loopBinding = ( name, node ) => {
	if ( node.type !== 'atrule' ) {
		return null;
	}
	if ( node.name === 'for' ) {
		return node.params.trim().split( /\s/ )[ 0 ] === name ? UNKNOWN : null;
	}
	const match =
		node.name === 'each' &&
		/^([\s\S]+?)\s+in\s+([\s\S]+)$/.exec( node.params.trim() );
	const names = match
		? match[ 1 ].split( ',' ).map( ( item ) => item.trim() )
		: [];
	if ( ! names.includes( name ) ) {
		return null;
	}
	return names.length === 1 ? { value: match[ 2 ], context: node } : UNKNOWN;
};

// An assignment replaces the possible values. With `!default`, it only replaces a value that is missing or `null`.
const applyAssignment = ( decl, values ) => {
	const assigned = { value: decl.value, context: decl };
	if ( ! isDefault( decl ) ) {
		return [ assigned ];
	}
	const kept = values.filter( ( value ) => ! isNull( value ) );
	return values.length > 0 && kept.length === values.length
		? values
		: [ ...kept, assigned ];
};

// Applies the assignments in `nodes`, in order, to the possible values of the variable. An `@if` or loop may run or
// not, so its result adds to the values before it. It only updates a variable that already exists, because an
// assignment inside it would otherwise be local.
const applyAssignments = ( name, nodes, values ) =>
	nodes.reduce( ( current, item ) => {
		if ( item.type === 'decl' && item.prop === name ) {
			return applyAssignment( item, current );
		}
		if (
			item.type === 'atrule' &&
			FLOW_CONTROL.has( item.name ) &&
			current.length > 0 &&
			loopBinding( name, item ) === null
		) {
			const after = applyAssignments( name, item.nodes || [], current );
			return [
				...current,
				...after.filter( ( value ) => ! current.includes( value ) ),
			];
		}
		return current;
	}, values );

// A mixin reads the variables around it when it is included, which can be after any of their assignments, so each
// assignment in the scopes around the mixin is a possible value.
const includeTimeValues = ( name, mixin ) => {
	const values = [];
	const collect = ( nodes ) =>
		nodes.forEach( ( item ) => {
			if ( item.type === 'decl' && item.prop === name ) {
				values.push( { value: item.value, context: item } );
			} else if (
				item.type === 'atrule' &&
				FLOW_CONTROL.has( item.name ) &&
				loopBinding( name, item ) === null
			) {
				collect( item.nodes || [] );
			}
		} );
	for ( let scope = mixin.parent; scope; scope = scope.parent ) {
		collect( scope.nodes );
	}
	return values;
};

// The values that a Sass variable can have where `node` uses it, read as Sass does. Each value has the node to
// resolve its own variables from. The list holds `MIXIN_PARAMETER` for a parameter of the enclosing mixin, and
// `UNKNOWN` for a value that cannot be read. It is empty when the variable has no value.
const resolveVariable = ( name, node ) => {
	const scopes = [];
	let values = [];
	for ( let child = node; child.parent; child = child.parent ) {
		const scope = child.parent;
		scopes.unshift( scope.nodes.slice( 0, scope.nodes.indexOf( child ) ) );
		const binding = loopBinding( name, scope );
		if ( binding !== null ) {
			values = [ binding ];
			break;
		}
		if ( scope.type === 'atrule' && scope.name === 'mixin' ) {
			values = mixinParameters( scope ).includes( name )
				? [ MIXIN_PARAMETER ]
				: includeTimeValues( name, scope );
			break;
		}
	}
	return scopes.reduce(
		( current, nodes ) => applyAssignments( name, nodes, current ),
		values
	);
};

// A property, or a Sass variable whose possible values list only fade or color properties. The variable must be
// assigned in the same file, in the root or a block that encloses `node`. A parameter of the enclosing mixin passes,
// because the `motion-transition` mixin forwards its `$state` list. Other variables fail, because their value is
// unknown.
const isFadeProperty = ( property, node, seen ) => {
	if ( ! property.startsWith( '$' ) ) {
		return FADE_PROPERTIES.has( property.toLowerCase() );
	}
	const name = property.replace( '...', '' );
	if ( seen.has( name ) ) {
		return false;
	}
	seen.add( name );
	const values = resolveVariable( name, node );
	return (
		values.length > 0 &&
		values.every( ( value ) => {
			if ( value === MIXIN_PARAMETER || value === UNKNOWN ) {
				return value === MIXIN_PARAMETER;
			}
			const tokens = listTokens( value.value );
			const valueSeen = new Set( seen );
			return (
				tokens.length > 0 &&
				tokens.every( ( token ) =>
					isFadeProperty( token, value.context, valueSeen )
				)
			);
		} )
	);
};

// A `transition` or `transition-property` that only fades or changes color, or an `animation` that only fades.
const isFadeOnly = ( prop, decl ) => {
	const { value } = decl;
	if ( prop === 'animation' || prop === 'animation-name' ) {
		return splitList( value ).every( ( item ) =>
			item.split( /\s+/ ).some( ( token ) => FADE_KEYFRAMES.has( token ) )
		);
	}
	if ( prop !== 'transition' && prop !== 'transition-property' ) {
		return false;
	}
	return splitList( value ).every(
		( item ) =>
			isFadeFunction( item, decl ) ||
			FADE_PROPERTIES.has( item.split( /\s+/ )[ 0 ].toLowerCase() )
	);
};

// `@media … prefers-reduced-motion` is not a guard: it can also match `reduce`. Use `motion-safe`.
const isGuard = ( { type, name, params } ) =>
	type === 'atrule' &&
	( name.endsWith( 'keyframes' ) ||
		( name === 'include' && params.startsWith( 'motion-safe' ) ) );

const isGuarded = ( node ) => {
	for ( let parent = node.parent; parent; parent = parent.parent ) {
		if ( isGuard( parent ) ) {
			return true;
		}
	}
	return false;
};

// The `$state` argument of `@include motion-transition( … )`, named or first positional, or null when there is none.
const motionTransitionState = ( { params } ) => {
	const name = 'motion-transition';
	const rest = params.slice( name.length ).trim();
	if ( ! params.startsWith( name ) || ! rest.startsWith( '(' ) ) {
		return null;
	}
	const args = splitList( rest.slice( 1, -1 ) );
	for ( const arg of args ) {
		const colon = arg.indexOf( ':' );
		if ( colon !== -1 && arg.slice( 0, colon ).trim() === '$state' ) {
			return arg.slice( colon + 1 ).trim();
		}
	}
	const first = args[ 0 ];
	const colon = first ? first.indexOf( ':' ) : -1;
	const isNamed =
		colon !== -1 &&
		first.startsWith( '$' ) &&
		! first.slice( 0, colon ).trim().includes( ' ' );
	return first && ! isNamed ? first : null;
};

// A non-zero time such as `100ms`, `.2s` or `-1s`. Durations and delays must use tokens.
const LITERAL_TIME = /^[+-]?[\d.]*[1-9][\d.]*m?s$/i;

// The first literal time in `value`, at any depth, such as in `calc( var( … ) + 100ms )`. An item that loops
// (`infinite`) can use literal times.
const literalTime = ( value ) => {
	for ( const item of splitList( value ) ) {
		const tokens = item.split( /[\s(),*/]+/ );
		if ( ! tokens.includes( 'infinite' ) ) {
			const time = tokens.find( ( token ) => LITERAL_TIME.test( token ) );
			if ( time ) {
				return time;
			}
		}
	}
	return null;
};

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
		const prop = unprefixed( decl.prop.toLowerCase() );
		if (
			! prop.startsWith( 'transition' ) &&
			! prop.startsWith( 'animation' )
		) {
			return;
		}
		const time = literalTime( decl.value );
		if ( time ) {
			report( {
				message: messages.rejectedTime( time ),
				node: decl,
				word: time,
				result,
				ruleName,
			} );
		}
		if (
			decl.value.trim() === 'none' ||
			isFadeOnly( prop, decl ) ||
			isGuarded( decl )
		) {
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
	root.walkAtRules( 'include', ( include ) => {
		const state = motionTransitionState( include );
		if (
			state === null ||
			isGuarded( include ) ||
			listTokens( state ).every( ( token ) =>
				isFadeProperty( token, include, new Set() )
			)
		) {
			return;
		}
		report( {
			message: messages.rejectedState(),
			node: include,
			word: '$state',
			result,
			ruleName,
		} );
	} );
};

ruleFunction.ruleName = ruleName;
ruleFunction.messages = messages;

module.exports = createPlugin( ruleName, ruleFunction );
