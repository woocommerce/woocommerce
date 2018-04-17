/** @format */
/**
 * External dependencies
 */
import { createElement, render } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import Dashboard from './dashboard';

render(
	createElement( Dashboard ),
	document.getElementById( 'root' )
);

setTimeout( function() {
	function editText( string ) {
		return `Also filtered: ${ string }`;
	}
	addFilter( 'woodash.example', 'editText', editText );
}, 1500 );

function editText2( string ) {
	return `Filtered: ${ string }`;
}
addFilter( 'woodash.example2', 'editText2', editText2 );
