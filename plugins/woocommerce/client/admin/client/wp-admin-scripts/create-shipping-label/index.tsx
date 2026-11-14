/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';
import { withPluginsHydration } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import { CreateShippingLabelModal } from './create-shipping-label-modal';

const BUTTON_SELECTOR = '.wc-create-shipping-label__button';

const HydratedModal = withPluginsHydration( {
	...getAdminSetting( 'plugins' ),
	jetpackStatus: getAdminSetting( 'dataEndpoints', {} ).jetpackStatus,
} )( CreateShippingLabelModal );

let root: ReturnType< typeof createRoot > | null = null;

const openModal = ( button: HTMLElement ) => {
	if ( ! root ) {
		const container = document.createElement( 'div' );
		container.id = 'wc-create-shipping-label-modal-root';
		document.body.appendChild( container );
		root = createRoot( container );
	}

	const close = () => {
		root?.render( null );
		button.focus();
	};

	root.render(
		<HydratedModal
			storeCountry={ button.dataset.storeCountry ?? '' }
			onRequestClose={ close }
		/>
	);
};

document.addEventListener( 'click', ( event ) => {
	const button = ( event.target as Element | null )?.closest< HTMLElement >(
		BUTTON_SELECTOR
	);

	if ( ! button ) {
		return;
	}

	event.preventDefault();
	recordEvent( 'order_create_shipping_label_click', {
		country: button.dataset.storeCountry ?? '',
	} );
	openModal( button );
} );
