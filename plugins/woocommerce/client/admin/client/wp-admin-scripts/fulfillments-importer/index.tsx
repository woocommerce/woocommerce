/**
 * External dependencies
 */
import React, { useCallback, useEffect, useState } from 'react';
import { createRoot } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './style.scss';
import FulfillmentsImporterModal from './components/fulfillments-importer-modal';

function FulfillmentsImporterController() {
	const [ isOpen, setIsOpen ] = useState( false );

	const open = useCallback( () => {
		setIsOpen( true );
		recordEvent( 'fulfillments_import_modal_opened', {
			source: 'orders_list',
		} );
	}, [] );
	const close = useCallback( () => setIsOpen( false ), [] );

	useEffect( () => {
		const handleClick = ( event: Event ) => {
			const target = event.target as HTMLElement | null;
			if ( ! target ) {
				return;
			}
			const trigger = target.closest( '.wc-fulfillment-import-trigger' );
			if ( trigger ) {
				event.preventDefault();
				event.stopPropagation();
				open();
			}
		};

		document.body.addEventListener( 'click', handleClick );
		return () => {
			document.body.removeEventListener( 'click', handleClick );
		};
	}, [ open ] );

	return <FulfillmentsImporterModal isOpen={ isOpen } onClose={ close } />;
}

const container = document.querySelector(
	'#wc_fulfillments_importer_panel_container'
);

if ( container ) {
	createRoot( container ).render( <FulfillmentsImporterController /> );
}
