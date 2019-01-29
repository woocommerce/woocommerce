/** @format */
/**
 * External dependencies
 */
import { render } from '@wordpress/element';
import { Provider as SlotFillProvider } from 'react-slot-fill';

/**
 * Internal dependencies
 */
import './stylesheets/_index.scss';
import { PageLayout } from './layout';
import 'wc-api/wp-data-store';

render(
	<SlotFillProvider>
		<PageLayout />
	</SlotFillProvider>,
	document.getElementById( 'root' )
);
