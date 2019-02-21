/** @format */
/**
 * External dependencies
 */
import { render } from '@wordpress/element';
import { Provider as SlotFillProvider } from 'react-slot-fill';

/**
 * Internal dependencies
 */
import './stylesheets/_embedded.scss';
import { EmbedLayout } from './layout';
import 'store';
import 'wc-api/wp-data-store';

render(
	<SlotFillProvider>
		<EmbedLayout />
	</SlotFillProvider>,
	document.getElementById( 'woocommerce-embedded-root' )
);
