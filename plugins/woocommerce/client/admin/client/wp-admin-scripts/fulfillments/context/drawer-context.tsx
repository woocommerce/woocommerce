/**
 * External dependencies
 */
import React, { createContext, useLayoutEffect, useState } from 'react';
import { useSelect } from '@wordpress/data';
import { isEqual } from 'lodash';

/**
 * Internal dependencies
 */
import { Fulfillment, Order, Refund } from '../data/types';
import { store as FulfillmentsStore } from '../data/store';
import { getItemsNotInAnyFulfillment } from '../utils/order-utils';

interface FulfillmentDrawerContextProps {
	fulfillments: Fulfillment[];
	setFulfillments: ( fulfillments: Fulfillment[] ) => void;
	order: Order | null;
	setOrder: ( order: Order | null ) => void;
	refunds: Refund[];
	setRefunds: ( refunds: Refund[] ) => void;
	openSection: string;
	setOpenSection: ( section: string ) => void;
	isEditing: boolean;
	setIsEditing: ( isEditing: boolean ) => void;
}

const defaultContextProps: FulfillmentDrawerContextProps = {
	fulfillments: [],
	setFulfillments: () => {},
	order: null,
	setOrder: () => {},
	refunds: [],
	setRefunds: () => {},
	openSection: '',
	setOpenSection: () => {},
	isEditing: false,
	setIsEditing: () => {},
};

const FulfillmentDrawerContextValue =
	createContext< FulfillmentDrawerContextProps >( defaultContextProps );

export const useFulfillmentDrawerContext = () => {
	const context = React.useContext( FulfillmentDrawerContextValue );
	if ( ! context ) {
		throw new Error(
			'useFulfillmentDrawerContext must be used within a FulfillmentDrawerProvider'
		);
	}
	return context;
};

export const FulfillmentDrawerProvider = ( {
	orderId,
	children,
}: {
	orderId: number | null;
	children: React.ReactNode;
} ) => {
	const [ openSection, setOpenSection ] = useState( 'order' );
	const [ isEditing, setIsEditing ] = useState( false );
	const [ fulfillments, setFulfillments ] = useState< Fulfillment[] >();
	const [ refunds, setRefunds ] = useState< Refund[] >();
	const [ order, setOrder ] = useState< Order | null >();

	useSelect(
		( select ) => {
			if ( ! orderId ) {
				return;
			}
			const store = select( FulfillmentsStore );
			const orderData = store.getOrder( orderId );
			const fulfillmentsData = store.readFulfillments( orderId );
			const refundsData = store.getRefunds( orderId );
			if ( ! isEqual( orderData, order ) ) {
				setOrder( orderData );
				setIsEditing( false );
			}
			if ( ! isEqual( refundsData, refunds ) ) {
				setRefunds( refundsData ?? [] );
				setIsEditing( false );
			}
			if ( ! isEqual( fulfillmentsData, fulfillments ) ) {
				setFulfillments( fulfillmentsData ?? [] );
				setIsEditing( false );
			}
		},
		[ orderId, fulfillments, order, refunds ]
	);

	useLayoutEffect( () => {
		const hasPendingItemsInOrder =
			order &&
			fulfillments &&
			getItemsNotInAnyFulfillment( fulfillments, order, refunds ).length >
				0;

		if ( hasPendingItemsInOrder ) {
			// If there are pending items in the order and multiple fulfillments,
			// open the order section to allow adding a new fulfillment.
			setOpenSection( 'order' );
		} else if ( fulfillments && fulfillments.length === 1 ) {
			// If all the items are in a single fulfillment,
			// open that fulfillment section directly.
			setOpenSection( 'fulfillment-' + fulfillments[ 0 ].id );
		} else {
			// If there are no pending items and multiple fulfillments,
			// collapse all.
			setOpenSection( '' );
		}
	}, [ orderId, fulfillments, order, refunds ] );

	if ( orderId === null ) {
		return null;
	}

	return (
		<FulfillmentDrawerContextValue.Provider
			value={ {
				fulfillments: fulfillments ?? [],
				setFulfillments,
				order: order ?? null,
				setOrder,
				refunds: refunds ?? [],
				setRefunds,
				openSection,
				setOpenSection,
				isEditing,
				setIsEditing,
			} }
		>
			{ children }
		</FulfillmentDrawerContextValue.Provider>
	);
};
