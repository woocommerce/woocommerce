/**
 * External dependencies
 */
import { h } from 'preact';

export const hydratedIslands = new WeakSet();

// Recursive function that transfoms a DOM tree into vDOM.
export function toVdom( node ) {
	const props = {};
	const { attributes, childNodes } = node;
	const wpDirectives = {};
	let hasWpDirectives = false;
	let ignore = false;
	let island = false;

	if ( node.nodeType === 3 ) return node.data;
	if ( node.nodeType === 4 ) {
		node.replaceWith( new Text( node.nodeValue ) );
		return node.nodeValue;
	}

	for ( let i = 0; i < attributes.length; i++ ) {
		const n = attributes[ i ].name;
		if ( n[ 0 ] === 'w' && n[ 1 ] === 'p' && n[ 2 ] === '-' && n[ 3 ] ) {
			if ( n === 'wp-ignore' ) {
				ignore = true;
			} else if ( n === 'wp-island' ) {
				island = true;
			} else {
				hasWpDirectives = true;
				let val = attributes[ i ].value;
				try {
					val = JSON.parse( val );
				} catch ( e ) {}
				const [ , prefix, suffix ] = /wp-([^:]+):?(.*)$/.exec( n );
				wpDirectives[ prefix ] = wpDirectives[ prefix ] || {};
				wpDirectives[ prefix ][ suffix || 'default' ] = val;
			}
		} else if ( n === 'ref' ) {
			continue;
		} else {
			props[ n ] = attributes[ i ].value;
		}
	}

	if ( ignore && ! island )
		return h( node.localName, {
			dangerouslySetInnerHTML: { __html: node.innerHTML },
		} );
	if ( island ) hydratedIslands.add( node );

	if ( hasWpDirectives ) props.wp = wpDirectives;

	const children = [];
	for ( let i = 0; i < childNodes.length; i++ ) {
		const child = childNodes[ i ];
		if ( child.nodeType === 8 || child.nodeType === 7 ) {
			child.remove();
			i--;
		} else {
			children.push( toVdom( child ) );
		}
	}

	return h( node.localName, props, children );
}
