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
	loadSubscriptions: () => {},
	isLoading: true,
	setIsLoading: () => {},
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

		fetchSubscriptions()
			.then( ( subscriptionResponse ) => {
				setSubscriptions( subscriptionResponse );
			} )
			.finally( () => {
				if ( toggleLoading ) {
					setIsLoading( false );
				}
			} );
	};

	useEffect( () => loadSubscriptions( true ), [] );

	const contextValue = {
		subscriptions,
		setSubscriptions,
		loadSubscriptions,
		isLoading,
		setIsLoading,
	};

	return (
		<SubscriptionsContext.Provider value={ contextValue }>
			{ props.children }
		</SubscriptionsContext.Provider>
	);
}
