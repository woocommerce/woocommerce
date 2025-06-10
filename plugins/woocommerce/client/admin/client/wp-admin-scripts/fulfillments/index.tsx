/**
 * External dependencies
 */
import React, { useLayoutEffect, useState } from 'react';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';
import FulfillmentDrawer from './components/user-interface/fulfillment-drawer/fulfillment-drawer';

function FulfillmentsController() {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ orderId, setOrderId ] = useState< number | null >( null );

	const deselectOrderRow = () => {
		document.querySelectorAll( '.type-shop_order' ).forEach( ( row ) => {
			row.classList.remove( 'is-selected' );
		} );
	};

	const selectOrderRow = ( button: HTMLButtonElement ) => {
		const targetRow = button.closest( 'tr' );
		deselectOrderRow();
		targetRow?.classList.add( 'is-selected' );
	};

	useLayoutEffect( () => {
		document.body.addEventListener( 'click', ( e ) => {
			const target = e.target as HTMLElement;
			if ( target.closest( '.fulfillments-trigger' ) ) {
				const button = target.closest(
					'.fulfillments-trigger'
				) as HTMLButtonElement;
				const id = parseInt( button.dataset.orderId || '', 10 );
				if ( id ) {
					e.preventDefault();
					e.stopPropagation();
					selectOrderRow( button );
					setOrderId( id );
					setIsOpen( true );
				}
			}
		} );
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	return (
		<FulfillmentDrawer
			isOpen={ isOpen }
			orderId={ orderId }
			onClose={ () => {
				deselectOrderRow();
				setIsOpen( false );
				setTimeout( () => {
					setOrderId( null );
				}, 300 );
			} }
		/>
	);
}

export default FulfillmentsController;

const container = document.querySelector(
	'#wc_order_fulfillments_panel_container'
) as HTMLElement;

createRoot( container ).render( <FulfillmentsController /> );
