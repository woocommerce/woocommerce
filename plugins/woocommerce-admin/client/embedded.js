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
import { EmbedLayout, PrimaryLayout as NoticeArea } from './layout';
import 'store';
import 'wc-api/wp-data-store';

const embeddedRoot = document.getElementById( 'woocommerce-embedded-root' );

// Render the header.
render(
	<SlotFillProvider>
		<EmbedLayout />
	</SlotFillProvider>,
	embeddedRoot
);

embeddedRoot.classList.remove( 'is-embed-loading' );

// Render notices just above the WP content div.
const wpBody = document.getElementById( 'wpbody-content' );
const wrap = wpBody.querySelector( '.wrap' );
const noticeContainer = document.createElement( 'div' );

render(
	<div className="woocommerce-layout">
		<NoticeArea />
	</div>,
	wpBody.insertBefore( noticeContainer, wrap )
);
