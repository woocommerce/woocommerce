/**
 * Production builds drop React `propTypes`, like the
 * babel-plugin-transform-react-remove-prop-types step in webpack's Babel
 * setup, which also only runs on code outside node_modules.
 *
 * Removed: `Foo.propTypes = …;` statements and `static propTypes = …` class
 * fields. Code that reads `Foo.propTypes` at run time would get undefined,
 * which is also what the Babel plugin does for components.
 */
import path from 'node:path';
import MagicString from 'magic-string';
import { parseAst } from 'rolldown/parseAst';

const isPropTypes = ( node ) =>
	! node.computed &&
	( node.property?.name === 'propTypes' || node.key?.name === 'propTypes' );

function walk( node, visit ) {
	if ( ! node || typeof node !== 'object' ) {
		return;
	}
	if ( Array.isArray( node ) ) {
		node.forEach( ( child ) => walk( child, visit ) );
		return;
	}
	if ( typeof node.type === 'string' && visit( node ) === false ) {
		return;
	}
	for ( const key in node ) {
		const value = node[ key ];
		if ( value && typeof value === 'object' ) {
			walk( value, visit );
		}
	}
}

export function removePropTypes( code, id ) {
	const ext = path.extname( id );
	let ast;
	try {
		ast = parseAst( code, { lang: { '.ts': 'ts', '.tsx': 'tsx' }[ ext ] || 'jsx' } );
	} catch {
		return null;
	}
	const ms = new MagicString( code );
	walk( ast, ( node ) => {
		if (
			node.type === 'ExpressionStatement' &&
			node.expression.type === 'AssignmentExpression' &&
			node.expression.left.type === 'MemberExpression' &&
			isPropTypes( node.expression.left )
		) {
			ms.remove( node.start, node.end );
			return false;
		}
		if ( node.type === 'PropertyDefinition' && node.static && isPropTypes( node ) ) {
			ms.remove( node.start, node.end );
			return false;
		}
		return true;
	} );
	return ms.hasChanged() ? { code: ms.toString(), map: ms.generateMap( { hires: 'boundary', source: id } ) } : null;
}

export const propTypesPlugin = () => ( {
	name: 'wc-remove-prop-types',
	transform: {
		filter: {
			id: { include: /\.[jt]sx?$/, exclude: /[\\/]node_modules[\\/]/ },
			code: /propTypes/,
		},
		handler( code, id ) {
			return removePropTypes( code, id );
		},
	},
} );
