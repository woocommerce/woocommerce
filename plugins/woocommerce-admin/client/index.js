/** @format */
/**
 * External dependencies
 */
import { APIProvider } from '@wordpress/components';
import { pick } from 'lodash';
import { render } from '@wordpress/element';
import { Provider as SlotFillProvider } from 'react-slot-fill';
import 'react-dates/initialize';

/**
 * Internal dependencies
 */
import './stylesheets/_index.scss';
import { PageLayout } from './layout';
import 'store';

render(
	<APIProvider
		{ ...wpApiSettings }
		{ ...pick( wp.api, [ 'postTypeRestBaseMapping', 'taxonomyRestBaseMapping' ] ) }
	>
		<SlotFillProvider>
			<PageLayout />
		</SlotFillProvider>
	</APIProvider>,
	document.getElementById( 'root' )
);
