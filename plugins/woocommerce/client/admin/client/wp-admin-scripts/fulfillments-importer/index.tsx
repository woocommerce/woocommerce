/**
 * External dependencies
 */
import React, { useCallback, useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { createPortal, createRoot } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import FulfillmentsImporterModal from './components/fulfillments-importer-modal';

// The importer's CSS is bundled into wp-admin-scripts/fulfillments/style.css
// (see fulfillments/style.scss) so it loads via the renderer's already-enqueued
// handle and no second wc-admin-style enqueue is needed.

const TRIGGER_CLASS = 'wc-fulfillment-import-trigger';
const TRIGGER_SLOT_ID = 'wc-fulfillments-importer-trigger-slot';

/**
 * Create (or return the existing) host element next to the "Add order"
 * page-title-action, into which the React trigger is portaled. Mirrors the
 * placement pattern WooCommerce core uses for the products list Import/Export
 * buttons (see `client/legacy/js/admin/woocommerce_admin.js`).
 */
function getOrCreateTriggerSlot(): HTMLElement | null {
	const existing = document.getElementById( TRIGGER_SLOT_ID );
	if ( existing ) {
		return existing;
	}

	const { body } = document;
	const isOrdersScreen =
		body.classList.contains( 'woocommerce_page_wc-orders' ) ||
		( body.classList.contains( 'edit-php' ) &&
			body.classList.contains( 'post-type-shop_order' ) );
	if ( ! isOrdersScreen ) {
		return null;
	}

	const titleAction = document.querySelector( '.wrap .page-title-action' );
	if ( ! titleAction || ! titleAction.parentNode ) {
		return null;
	}

	const slot = document.createElement( 'span' );
	slot.id = TRIGGER_SLOT_ID;
	titleAction.parentNode.insertBefore( slot, titleAction.nextSibling );
	return slot;
}

interface TriggerProps {
	onClick: () => void;
}

const ImportFulfillmentsTrigger: React.FC< TriggerProps > = ( { onClick } ) => (
	<button
		type="button"
		className={ `page-title-action ${ TRIGGER_CLASS }` }
		onClick={ onClick }
	>
		{ __( 'Import fulfillments', 'woocommerce' ) }
	</button>
);

function FulfillmentsImporterController() {
	const [ isOpen, setIsOpen ] = useState( false );

	const open = useCallback( () => {
		setIsOpen( true );
		recordEvent( 'fulfillments_import_modal_opened', {
			source: 'orders_list',
		} );
	}, [] );
	const close = useCallback( () => setIsOpen( false ), [] );

	const [ triggerSlot, setTriggerSlot ] = useState< HTMLElement | null >(
		null
	);

	// Create the slot after mount so DOM mutation never happens during render
	// (StrictMode invokes render-phase code twice in development).
	useEffect( () => {
		setTriggerSlot( getOrCreateTriggerSlot() );
	}, [] );

	return (
		<>
			{ triggerSlot
				? createPortal(
						<ImportFulfillmentsTrigger onClick={ open } />,
						triggerSlot
				  )
				: null }
			<FulfillmentsImporterModal isOpen={ isOpen } onClose={ close } />
		</>
	);
}

const container = document.querySelector(
	'#wc_fulfillments_importer_panel_container'
);

if ( container ) {
	createRoot( container ).render( <FulfillmentsImporterController /> );
}
