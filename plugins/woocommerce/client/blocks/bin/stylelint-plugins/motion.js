/**
 * `woocommerce/motion-safe`: `transition*` and `animation*` declarations (other than `none`) must sit inside
 * `@include motion-safe`, a `prefers-reduced-motion` media query, or `@keyframes`. See docs/contributors/motion.md.
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
			decl.value.trim() === 'none'
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
