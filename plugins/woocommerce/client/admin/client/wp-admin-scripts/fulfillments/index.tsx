/**
 * External dependencies
 */
import React, { useCallback, useLayoutEffect, useState } from 'react';
import { createRoot } from '@wordpress/element';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './style.scss';
import FulfillmentDrawer from './components/user-interface/fulfillment-drawer/fulfillment-drawer';

function FulfillmentsController() {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ orderId, setOrderId ] = useState< number | null >( null );

	const deselectOrderRow = useCallback( () => {
		document.querySelectorAll( '.type-shop_order' ).forEach( ( row ) => {
			row.classList.remove( 'is-selected' );
		} );
	}, [] );

	const selectOrderRow = useCallback(
		( button: HTMLButtonElement ) => {
			const targetRow = button.closest( 'tr' );
			deselectOrderRow();
			targetRow?.classList.add( 'is-selected' );
		},
		[ deselectOrderRow ]
	);

	useLayoutEffect( () => {
		const handleClick = ( e: Event ) => {
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
		};

		document.body.addEventListener( 'click', handleClick );

		return () => {
			document.body.removeEventListener( 'click', handleClick );
		};
	}, [ selectOrderRow ] );

	const query = getQuery();
	const isOrderDetailsPage = query.hasOwnProperty( 'id' );

	return (
		<FulfillmentDrawer
			hasBackdrop={ isOrderDetailsPage }
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
