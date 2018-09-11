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

render(
	<SlotFillProvider>
		<EmbedLayout />
	</SlotFillProvider>,
	document.getElementById( 'woocommerce-embedded-root' )
);
