module.exports = {
	meta: {
		type: 'layout',
		schema: [],
	},
	create( context ) {
		const comments = context.getSourceCode().getAllComments();

		/**
		 * Locality classification of an import, "External" or "Internal".
		 *
		 * @typedef {string} WCPackageLocality
		 */

		/**
		 * Given a desired locality, generates the expected comment node value
		 * property.
		 *
		 * @param {WCPackageLocality} locality Desired package locality.
		 *
		 * @return {string} Expected comment node value.
		 */
		function getCommentValue( locality ) {
			return `*\n * ${ locality } dependencies\n `;
		}

		/**
		 * Given an import source string, returns the locality classification
		 * of the import sort.
		 *
		 * @param {string} source Import source string.
		 *
		 * @return {WCPackageLocality} Package locality.
		 */
		function getPackageLocality( source ) {
			if ( source.startsWith( '.' ) ) {
				return 'Internal';
			}

			return 'External';
		}

		/**
		 * Returns true if the given comment node satisfies a desired locality,
		 * or false otherwise.
		 *
		 * @param {espree.Node}       node     Comment node to check.
		 * @param {WCPackageLocality} locality Desired package locality.
		 *
		 * @return {boolean} Whether comment node satisfies locality.
		 */
		function isLocalityDependencyBlock( node, locality ) {
			const { type, value } = node;
			if ( type !== 'Block' ) {
				return false;
			}

			// Tolerances:
			// - Normalize `/**` and `/*`
			// - Case insensitive "Dependencies" vs. "dependencies"
			// - Ending period
			// - "Node" dependencies as an alias for External

			if ( locality === 'External' ) {
				locality = '(External|Node)';
			}

			const pattern = new RegExp(
				`^\\*?\\n \\* ${ locality } dependencies\\.?\\n $`,
				'i'
			);
			return pattern.test( value );
		}

		/**
		 * Returns true if the given node occurs prior in code to a reference,
		 * or false otherwise.
		 *
		 * @param {espree.Node} node      Node to test being before reference.
		 * @param {espree.Node} reference Node against which to compare.
		 *
		 * @return {boolean} Whether node occurs before reference.
		 */
		function isBefore( node, reference ) {
			return node.start < reference.start;
		}

		/**
		 * Tests source comments to determine whether a comment exists which
		 * satisfies the desired locality. If a match is found and requires no
		 * updates, the function returns false. Otherwise, it will return true.
		 *
		 * @param {espree.Node}       node     Node to test.
		 * @param {WCPackageLocality} locality Desired package locality.
		 *
		 * @return {boolean} Whether the node is in the correct locality.
		 */
		function isNodeInLocality( node, locality ) {
			const value = getCommentValue( locality );

			let comment;
			let nextComment;
			for ( let i = 0; i < comments.length; i++ ) {
				comment = comments[ i ];
				nextComment =
					i < comments.length - 1 ? comments[ i + 1 ] : null;

				if ( nextComment && isBefore( nextComment, node ) ) {
					// If it's not the immediately previous comment, continue.
					continue;
				}

				if ( ! isBefore( comment, node ) ) {
					// Exhausted options.
					break;
				}

				if ( ! isLocalityDependencyBlock( comment, locality ) ) {
					// Not usable (either not an block comment, or not one
					// matching a tolerable pattern).
					continue;
				}

				if ( comment.value === value ) {
					// No change needed. (OK)
					return true;
				}

				// Found a comment needing correction.
				return false;
			}

			return false;
		}

		return {
			Program( node ) {
				// Since we only care to enforce imports which occur at the
				// top-level scope, match on Program and test its children,
				// rather than matching the import nodes directly.
				node.body.forEach( ( child ) => {
					let source;
					switch ( child.type ) {
						case 'ImportDeclaration':
							source = child.source.value;
							break;

						case 'CallExpression':
							const { callee, arguments: args } = child;
							if (
								callee.name === 'require' &&
								args.length === 1 &&
								args[ 0 ].type === 'Literal' &&
								typeof args[ 0 ].value === 'string'
							) {
								source = args[ 0 ].value;
							}
							break;
					}

					if ( ! source ) {
						return;
					}

					const locality = getPackageLocality( source );

					if ( isNodeInLocality( child, locality ) ) {
						return;
					}

					context.report( {
						node: child,
						message: `Expected preceding "${ locality } dependencies" comment block`,
					} );
				} );
			},
		};
	},
};
