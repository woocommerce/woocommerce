/** @format */
/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { APIProvider } from '@wordpress/components';
import { pick } from 'lodash';
import { render } from '@wordpress/element';
import { Provider as SlotFillProvider } from 'react-slot-fill';

/**
 * Internal dependencies
 */
import './stylesheets/_wpadmin-reset.scss';
import Layout from './layout';

render(
	<APIProvider
		{ ...wpApiSettings }
		{ ...pick( wp.api, [ 'postTypeRestBaseMapping', 'taxonomyRestBaseMapping' ] ) }
	>
		<SlotFillProvider>
			<Layout />
		</SlotFillProvider>
	</APIProvider>,
	document.getElementById( 'root' )
);

function editText( string ) {
	return `Filtered: ${ string }`;
}
addFilter( 'woodash.example', 'editText', editText );
