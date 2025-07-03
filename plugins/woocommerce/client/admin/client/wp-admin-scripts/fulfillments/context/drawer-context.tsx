/**
 * External dependencies
 */
import React, { createContext, useLayoutEffect, useState } from 'react';
import { useSelect } from '@wordpress/data';
import { isEqual } from 'lodash';

/**
 * Internal dependencies
 */
import { Fulfillment, Order } from '../data/types';
import { store as FulfillmentsStore } from '../data/store';
import { hasPendingItems } from '../utils/fulfillment-utils';

interface FulfillmentDrawerContextProps {
	fulfillments: Fulfillment[];
	setFulfillments: ( fulfillments: Fulfillment[] ) => void;
	order: Order | null;
	setOrder: ( order: Order | null ) => void;
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
	const [ order, setOrder ] = useState< Order | null >();

	useSelect(
		( select ) => {
			if ( ! orderId ) {
				return;
			}
			const store = select( FulfillmentsStore );
			const orderData = store.getOrder( orderId );
			const fulfillmentsData = store.readFulfillments( orderId );
			if ( ! isEqual( orderData, order ) ) {
				setOrder( orderData );
				setIsEditing( false );
			}
			if ( ! isEqual( fulfillmentsData, fulfillments ) ) {
				setFulfillments( fulfillmentsData ?? [] );
				setIsEditing( false );
			}
		},
		[ orderId, fulfillments, order ]
	);

	useLayoutEffect( () => {
		if ( ! fulfillments || fulfillments.length === 0 ) {
			setOpenSection( 'order' );
		} else if (
			order &&
			fulfillments &&
			! hasPendingItems( order, fulfillments ) &&
			fulfillments.length === 1
		) {
			setOpenSection( 'fulfillment-' + fulfillments[ 0 ].id );
		}
	}, [ orderId, fulfillments, order ] );

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
