/**
 * Traverse up through the chain of parent AST nodes returning the first parent
 * the predicate returns a truthy value for.
 *
 * @param {Object}   sourceNode The AST node to search from.
 * @param {Function} predicate  A predicate invoked for each parent.
 *
 * @return {?Object } The first encountered parent node where the predicate
 *                    returns a truthy value.
 */
function findParent( sourceNode, predicate ) {
	if ( ! sourceNode.parent ) {
		return;
	}

	if ( predicate( sourceNode.parent ) ) {
		return sourceNode.parent;
	}

	return findParent( sourceNode.parent, predicate );
}

/**
 * Tests whether the WOOCOMMERCE_BLOCKS_PHASE variable is accessed via
 * `process.env.WOOCOMMERCE_BLOCKS_PHASE`.
 *
 * @example
 * ```js
 * // good
 * if ( process.env.WOOCOMMERCE_BLOCKS_PHASE > 1 ) {
 *
 * // bad
 * if ( WOOCOMMERCE_BLOCKS_PHASE > 1 ) {
 * ```
 *
 * @param {Object} node    The WOOCOMMERCE_BLOCKS_PHASE identifier node.
 * @param {Object} context The eslint context object.
 * @todo update this rule to match the new flags.
 */
function testIsAccessedViaProcessEnv( node, context ) {
	let parent = node.parent;

	if (
		parent &&
		parent.type === 'MemberExpression' &&
		context.getSource( parent ) === 'process.env.WOOCOMMERCE_BLOCKS_PHASE'
	) {
		return;
	}
	if ( parent && parent.type === 'BinaryExpression' ) {
		if ( parent.left.type === 'Identifier' ) {
			parent = parent.left;
		} else {
			parent = parent.right;
		}
	}
	context.report( {
		node,
		messageId: 'accessedViaEnv',
		fix( fixer ) {
			return fixer.replaceText(
				parent,
				'process.env.WOOCOMMERCE_BLOCKS_PHASE'
			);
		},
	} );
}

/**
 * Tests whether the WOOCOMMERCE_BLOCKS_PHASE strict binary comparison
 * is strict equal only
 *
 * @example
 * ```js
 * // good
 * if ( process.env.WOOCOMMERCE_BLOCKS_PHASE === 'experimental' ) {
 * if ( process.env.WOOCOMMERCE_BLOCKS_PHASE === 'stable' ) {
 *
 * // bad
 * if ( process.env.WOOCOMMERCE_BLOCKS_PHASE !== 'experimental' ) {
 * if ( process.env.WOOCOMMERCE_BLOCKS_PHASE !== 'stable' ) {
 * ```
 *
 * @param {Object} node    The WOOCOMMERCE_BLOCKS_PHASE identifier node.
 * @param {Object} context The eslint context object.
 */
function testBinaryExpressionOperatorIsEqual( node, context ) {
	const sourceCode = context.getSourceCode();
	node = node.parent.parent;
	if ( node.type === 'BinaryExpression' ) {
		const operatorToken = sourceCode.getFirstTokenBetween(
			node.left,
			node.right,
			( token ) => token.value === node.operator
		);

		if ( operatorToken.value === '===' ) {
			return;
		}
		context.report( {
			node,
			loc: operatorToken.loc,
			messageId: 'equalOperator',
			fix( fixer ) {
				return fixer.replaceText( operatorToken, '===' );
			},
		} );
	}
}

/**
 * Tests whether the WOOCOMMERCE_BLOCKS_PHASE variable is used in a strict binary
 * equality expression in a comparison with a enum of string flags, triggering a
 * violation if not.
 *
 * @example
 * ```js
 * // good
 * if ( process.env.WOOCOMMERCE_BLOCKS_PHASE === 'experimental' ) {
 * if ( process.env.WOOCOMMERCE_BLOCKS_PHASE === 'stable' ) {
 *
 * // bad
 * if ( process.env.WOOCOMMERCE_BLOCKS_PHASE == 'experimental' ) {
 * if ( process.env.WOOCOMMERCE_BLOCKS_PHASE === 'core' ) {
 * ```
 *
 * @param {Object} node    The WOOCOMMERCE_BLOCKS_PHASE identifier node.
 * @param {Object} context The eslint context object.
 */
function testIsUsedInStrictBinaryExpression( node, context ) {
	const parent = findParent(
		node,
		( candidate ) => candidate.type === 'BinaryExpression'
	);
	const flags = [ 'experimental', 'stable' ];
	let providedFlag;
	if ( parent ) {
		const comparisonNode =
			node.parent.type === 'MemberExpression' ? node.parent : node;
		const hasCorrectOperands =
			( parent.left === comparisonNode &&
				flags.includes( parent.right.value ) ) ||
			( parent.right === comparisonNode &&
				flags.includes( parent.left.value ) );
		if (
			parent.left === comparisonNode &&
			typeof parent.right.value === 'string'
		) {
			providedFlag = parent.right;
		} else {
			providedFlag = parent.left;
		}
		if ( hasCorrectOperands ) {
			return;
		}
	}

	if ( providedFlag && providedFlag.loc ) {
		context.report( {
			node,
			loc: providedFlag.loc,
			messageId: 'whiteListedFlag',
			data: {
				flags: flags.join( ', ' ),
			},
			fix( fixer ) {
				return fixer.replaceText( providedFlag, "'experimental'" );
			},
		} );
	}
}

/**
 * Tests whether the WOOCOMMERCE_BLOCKS_PHASE variable is used as the condition for an
 * if statement, triggering a violation if not.
 *
 * @example
 * ```js
 * // good
 * if ( process.env.WOOCOMMERCE_BLOCKS_PHASE === 'experimental' ) {
 *
 * // bad
 * const isFeatureActive = process.env.WOOCOMMERCE_BLOCKS_PHASE === 'experimental';
 * ```
 *
 * @param {Object} node    The WOOCOMMERCE_BLOCKS_PHASE identifier node.
 * @param {Object} context The eslint context object.
 */
function testIsUsedInIfOrTernary( node, context ) {
	const conditionalParent = findParent( node, ( candidate ) =>
		[ 'IfStatement', 'ConditionalExpression' ].includes( candidate.type )
	);
	const binaryParent = findParent(
		node,
		( candidate ) => candidate.type === 'BinaryExpression'
	);

	if (
		conditionalParent &&
		binaryParent &&
		conditionalParent.test &&
		conditionalParent.test.range[ 0 ] === binaryParent.range[ 0 ] &&
		conditionalParent.test.range[ 1 ] === binaryParent.range[ 1 ]
	) {
		return;
	}

	context.report( {
		node,
		messageId: 'noTernary',
	} );
}

module.exports = {
	meta: {
		type: 'problem',
		schema: [],
		fixable: 'code',
		messages: {
			accessedViaEnv:
				'The `WOOCOMMERCE_BLOCKS_PHASE` constant should be accessed using `process.env.WOOCOMMERCE_BLOCKS_PHASE`.',
			whiteListedFlag:
				'The `WOOCOMMERCE_BLOCKS_PHASE` constant should only be used in a strict equality comparison with a predefined flag of: {{ flags }}.',
			equalOperator:
				'The `WOOCOMMERCE_BLOCKS_PHASE` comparison should only be a strict equal `===`, if you need `!==` try switching the flag ',
			noTernary:
				'The `WOOCOMMERCE_BLOCKS_PHASE` constant should only be used as part of the condition in an if statement or ternary expression.',
		},
	},
	create( context ) {
		return {
			Identifier( node ) {
				// Bypass any identifiers with a node name different to `WOOCOMMERCE_BLOCKS_PHASE`.
				if ( node.name !== 'WOOCOMMERCE_BLOCKS_PHASE' ) {
					return;
				}

				testIsAccessedViaProcessEnv( node, context );
				testIsUsedInStrictBinaryExpression( node, context );
				testBinaryExpressionOperatorIsEqual( node, context );
				testIsUsedInIfOrTernary( node, context );
			},
			Literal( node ) {
				// Bypass any identifiers with a node value different to `WOOCOMMERCE_BLOCKS_PHASE`.
				if ( node.value !== 'WOOCOMMERCE_BLOCKS_PHASE' ) {
					return;
				}

				if ( node.parent && node.parent.type === 'MemberExpression' ) {
					testIsAccessedViaProcessEnv( node, context );
				}
			},
		};
	},
};
