/** @format */
/**
 * External dependencies
 */
import { APIProvider } from '@wordpress/components';
import { createElement, render } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { pick } from 'lodash';

/**
 * Internal dependencies
 */
import Dashboard from './dashboard';

render(
	createElement( APIProvider, {
		...wpApiSettings,
		...pick( wp.api, [
			'postTypeRestBaseMapping',
			'taxonomyRestBaseMapping',
		] ),
	}, createElement( Dashboard ) ),
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
