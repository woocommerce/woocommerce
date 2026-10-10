/**
 * External dependencies
 */
import React, { useCallback, useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { createPortal, createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import FulfillmentsImporterModal from './components/fulfillments-importer-modal';
import './style.scss';

const TRIGGER_CLASS = 'wc-fulfillment-import-trigger';
const TRIGGER_SLOT_ID = 'wc-fulfillments-importer-trigger-slot';
const OPEN_QUERY_ARG = 'fulfillments_importer';

/**
 * Tools > Import redirects to the orders list with `fulfillments_importer=open`.
 * PHP localizes that as `autoOpen` because core strips the arg from the URL in
 * admin_head (it is a removable query arg); the URL check covers direct loads.
 */
function shouldAutoOpen(): boolean {
	const flag = window.wcFulfillmentsImporterSettings?.autoOpen;
	// wp_localize_script casts booleans to '1' or ''.
	if ( flag === true || flag === '1' ) {
		return true;
	}
	const params = new URLSearchParams( window.location.search );
	return params.get( OPEN_QUERY_ARG ) === 'open';
}

/**
 * Drop the auto-open arg so a refresh or a list-table link does not reopen the wizard.
 */
function stripAutoOpenArg(): void {
	const url = new URL( window.location.href );
	if ( ! url.searchParams.has( OPEN_QUERY_ARG ) ) {
		return;
	}
	url.searchParams.delete( OPEN_QUERY_ARG );
	window.history.replaceState( window.history.state, '', url.toString() );
}

/**
 * Create (or return the existing) host element next to the "Add order"
 * page-title-action, into which the React trigger is portaled.
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

	const open = useCallback( () => setIsOpen( true ), [] );
	const close = useCallback( () => setIsOpen( false ), [] );

	const [ triggerSlot, setTriggerSlot ] = useState< HTMLElement | null >(
		null
	);

	// Create the slot after mount so DOM mutation never happens during render
	// (StrictMode invokes render-phase code twice in development).
	useEffect( () => {
		setTriggerSlot( getOrCreateTriggerSlot() );
	}, [] );

	useEffect( () => {
		if ( shouldAutoOpen() ) {
			setIsOpen( true );
		}
		stripAutoOpenArg();
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
