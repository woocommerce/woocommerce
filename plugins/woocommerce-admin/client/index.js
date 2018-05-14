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
	createElement(
		APIProvider,
		{
			...wpApiSettings,
			...pick( wp.api, [ 'postTypeRestBaseMapping', 'taxonomyRestBaseMapping' ] ),
		},
		createElement( Dashboard )
	),
	document.getElementById( 'root' )
);

function editText( string ) {
	return `Filtered: ${ string }`;
}
addFilter( 'woodash.example', 'editText', editText );
