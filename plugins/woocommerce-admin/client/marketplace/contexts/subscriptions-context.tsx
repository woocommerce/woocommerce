/**
 * External dependencies
 */
import { useState, createContext, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SubscriptionsContextType } from './types';
import { Subscription } from '../components/my-subscriptions/types';
import { fetchSubscriptions } from '../utils/functions';

export const SubscriptionsContext = createContext< SubscriptionsContextType >( {
	subscriptions: [],
	setSubscriptions: () => {},
	loadSubscriptions: () => new Promise( () => {} ),
	isLoading: true,
	setIsLoading: () => {},
	isInstalling: () => false,
	addInstalling: () => {},
	removeInstalling: () => {},
} );

export function SubscriptionsContextProvider( props: {
	children: JSX.Element;
} ): JSX.Element {
	const [ subscriptions, setSubscriptions ] = useState<
		Array< Subscription >
	>( [] );
	const [ isLoading, setIsLoading ] = useState( true );

	const loadSubscriptions = ( toggleLoading?: boolean ) => {
		if ( toggleLoading === true ) {
			setIsLoading( true );
		}

		return fetchSubscriptions()
			.then( ( subscriptionResponse ) => {
				setSubscriptions( subscriptionResponse );
			} )
			.finally( () => {
				if ( toggleLoading ) {
					setIsLoading( false );
				}
			} );
	};

	const [ installing, setInstalling ] = useState< Array< string > >( [] );

	const isInstalling = ( productKey: string ) => {
		return installing.includes( productKey );
	};

	const addInstalling = ( productKey: string ) => {
		if ( isInstalling( productKey ) ) {
			return;
		}
		const newInstalling = [ ...installing, productKey ];
		setInstalling( newInstalling );
	};

	const removeInstalling = ( productKey: string ) => {
		if ( ! isInstalling( productKey ) ) {
			return;
		}
		const newInstalling = [ ...installing ];
		const index = newInstalling.indexOf( productKey );
		if ( index > -1 ) {
			newInstalling.splice( index, 1 );
		}
		setInstalling( newInstalling );
	};

	useEffect( () => {
		loadSubscriptions( true );
	}, [] );

	const contextValue = {
		subscriptions,
		setSubscriptions,
		loadSubscriptions: () => loadSubscriptions(),
		isLoading,
		setIsLoading,
		isInstalling,
		addInstalling,
		removeInstalling,
	};

	return (
		<SubscriptionsContext.Provider value={ contextValue }>
			{ props.children }
		</SubscriptionsContext.Provider>
	);
}
