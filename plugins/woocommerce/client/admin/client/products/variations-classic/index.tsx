/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import VariationsTable from './variations-table';
import './style.scss';

const settings = window.wcVariationsClassicSettings;

if ( settings?.productId ) {
	const container = document.getElementById(
		'woocommerce-variations-classic-root'
	);
	if ( container ) {
		const root = createRoot( container );
		root.render( <VariationsTable productId={ settings.productId } /> );
	}
}
